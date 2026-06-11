#!/usr/bin/env node

/**
 * Flexify Checkout build pipeline.
 *
 * Orchestrates a production-ready plugin package:
 *   1. Install production PHP dependencies (vendor via Composer, --no-dev).
 *   2. Generate / compile translations (languages/ -> .pot, .po, .mo, .l10n.php).
 *   3. Stage only the runtime files into release/flexify-checkout-for-woocommerce/.
 *   4. Zip the staging dir into release/flexify-checkout-for-woocommerce-<version>.zip.
 *
 * Usage:
 *   node scripts/build.mjs [flags]
 *
 * Flags:
 *   --skip-composer       Don't run composer install (reuse existing vendor).
 *   --skip-translations   Don't touch translations (reuse existing artifacts).
 *   --translate           Re-translate .po files via AI before compiling (needs OPENAI_API_KEY).
 *   --engine=<name>       Translation engine for --translate (default: openai).
 *   --no-install          Skip dependency install steps (npm ci / composer install deps).
 *   --no-zip              Stage files but don't create the .zip.
 */

import { spawnSync } from 'node:child_process';
import { createWriteStream, existsSync } from 'node:fs';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import archiver from 'archiver';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const slug = 'flexify-checkout-for-woocommerce';

const releaseDir = path.join(root, 'release');
const stagingDir = path.join(releaseDir, slug);

/* ------------------------------------------------------------------ flags */

const argv = process.argv.slice(2);
const hasFlag = (name) => argv.includes(name);
const getOpt = (name, fallback) => {
	const prefix = `${name}=`;
	const match = argv.find((arg) => arg.startsWith(prefix));
	return match ? match.slice(prefix.length) : fallback;
};

const opts = {
	skipComposer: hasFlag('--skip-composer'),
	skipTranslations: hasFlag('--skip-translations'),
	translate: hasFlag('--translate'),
	engine: getOpt('--engine', 'openai'),
	install: !hasFlag('--no-install'),
	zip: !hasFlag('--no-zip'),
};

/* ---------------------------------------------------------------- helpers */

const log = (msg) => console.log(`\x1b[36m▶\x1b[0m ${msg}`);
const ok = (msg) => console.log(`\x1b[32m✓\x1b[0m ${msg}`);

function run(command, args, cwd) {
	const printable = `${command} ${args.join(' ')}`;
	log(`${printable}  (in ${path.relative(root, cwd) || '.'})`);

	const result = spawnSync(command, args, {
		cwd,
		stdio: 'inherit',
		// shell:true lets Windows resolve npm.cmd / composer.bat from PATH.
		shell: true,
	});

	if (result.status !== 0) {
		throw new Error(`Command failed (exit ${result.status}): ${printable}`);
	}
}

async function copyDir(relSource, relDest = relSource, filter) {
	const source = path.join(root, relSource);
	const dest = path.join(stagingDir, relDest);

	if (!existsSync(source)) {
		return;
	}

	await fs.cp(source, dest, { recursive: true, filter });
}

async function copyFile(relSource, relDest = relSource) {
	const source = path.join(root, relSource);

	if (!existsSync(source)) {
		return;
	}

	const dest = path.join(stagingDir, relDest);
	await fs.mkdir(path.dirname(dest), { recursive: true });
	await fs.copyFile(source, dest);
}

async function getPluginVersion() {
	const file = path.join(root, `${slug}.php`);
	const contents = await fs.readFile(file, 'utf8');
	const match = contents.match(/^\s*\*\s*Version:\s*(.+)$/m);
	return match ? match[1].trim() : '0.0.0';
}

function zipDirectory(sourceDir, outPath) {
	return new Promise((resolve, reject) => {
		const output = createWriteStream(outPath);
		const archive = archiver('zip', { zlib: { level: 9 } });

		output.on('close', () => resolve(archive.pointer()));
		archive.on('warning', (err) => (err.code === 'ENOENT' ? null : reject(err)));
		archive.on('error', reject);

		archive.pipe(output);
		// Nest everything under the plugin slug folder (WordPress expects this).
		archive.directory(sourceDir, slug);
		archive.finalize();
	});
}

/* ------------------------------------------------------------- copy rules */

// Skip dev clutter that may sneak into otherwise-shipped directories.
const denyList = new Set(['node_modules', '.git', '.env', '.DS_Store', 'Thumbs.db']);

const baseFilter = (src) => !denyList.has(path.basename(src));

// languages/: ship only compiled artifacts, never the Node tooling.
const languageExtensions = new Set(['.po', '.mo', '.pot', '.json', '.php']);
const languageFilter = (src) => {
	const name = path.basename(src);

	if (denyList.has(name) || name.startsWith('package') || name === '.env.example') {
		return false;
	}

	// Always allow directory entries so their children get evaluated.
	if (!path.extname(name)) {
		return true;
	}

	// Keep .l10n.php and friends; drop the *-cli.js pipeline scripts.
	return languageExtensions.has(path.extname(name));
};

/* ----------------------------------------------------------------- stages */

function installPhpDependencies() {
	if (opts.skipComposer) {
		log('Skipping Composer install (--skip-composer).');
		return;
	}

	run(
		'composer',
		['install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress'],
		root,
	);
	ok('Production PHP dependencies installed (vendor).');
}

function buildTranslations() {
	if (opts.skipTranslations) {
		log('Skipping translations (--skip-translations).');
		return;
	}

	const langDir = path.join(root, 'languages');

	if (opts.install) {
		run('npm', ['ci'], langDir);
	}

	// Refresh the template from current source.
	run('npm', ['run', 'pot'], langDir);

	if (opts.translate) {
		// Re-fill every .po via the chosen engine (needs API keys in languages/.env).
		// translate:ai => OpenAI; translate => Google.
		const script = opts.engine === 'openai' ? 'translate:ai' : 'translate';
		run('npm', ['run', script], langDir);
	}

	// Compile .po -> .mo and .l10n.php so WordPress can load them at runtime.
	run('npm', ['run', 'compile:mo'], langDir);
	run('npm', ['run', 'compile:php'], langDir);
	ok('Translations compiled (languages/*.mo, *.l10n.php).');
}

async function stageFiles() {
	log('Staging runtime files...');

	await fs.rm(releaseDir, { recursive: true, force: true });
	await fs.mkdir(stagingDir, { recursive: true });

	// Top-level files. composer.json stays for reference / re-installs.
	for (const file of [`${slug}.php`, 'README.md', 'license.md', 'changelogs.md', 'composer.json']) {
		await copyFile(file);
	}

	// PHP runtime: source, templates, integrations, production autoloader.
	for (const dir of ['inc', 'templates', 'woocommerce', 'updater', 'vendor']) {
		await copyDir(dir, dir, baseFilter);
	}

	// Static assets (served unminified — see Core\Assets::$min_file).
	await copyDir('assets', 'assets', baseFilter);

	// Compiled translation artifacts only.
	await copyDir('languages', 'languages', languageFilter);

	ok(`Staged at ${path.relative(root, stagingDir)}.`);
}

async function packageZip(version) {
	const manifest = {
		name: slug,
		version,
		generatedAt: new Date().toISOString(),
		zipFile: `${slug}-${version}.zip`,
	};

	await fs.writeFile(
		path.join(releaseDir, 'manifest.json'),
		`${JSON.stringify(manifest, null, 2)}\n`,
	);

	if (!opts.zip) {
		log('Skipping zip (--no-zip).');
		return;
	}

	const zipPath = path.join(releaseDir, manifest.zipFile);
	const bytes = await zipDirectory(stagingDir, zipPath);
	ok(`ZIP created: ${path.relative(root, zipPath)} (${(bytes / 1024 / 1024).toFixed(2)} MB)`);
}

/* -------------------------------------------------------------------- main */

async function main() {
	const version = await getPluginVersion();
	console.log(`\n\x1b[1mBuilding ${slug} v${version}\x1b[0m\n`);

	installPhpDependencies();
	buildTranslations();
	await stageFiles();
	await packageZip(version);

	console.log(`\n\x1b[32m\x1b[1mBuild complete.\x1b[0m\n`);
}

main().catch((err) => {
	console.error(`\n\x1b[31m✗ Build failed:\x1b[0m ${err.message}\n`);
	process.exit(1);
});
