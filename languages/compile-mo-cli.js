import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import gettextParser from "gettext-parser";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const TEXT_DOMAIN = "flexify-checkout-for-woocommerce";

function getPoFiles() {
  const args = process.argv.slice(2);
  const langFlagIndex = args.findIndex((arg) => arg === "--lang" || arg === "-l");
  const inlineLangArg = args.find((arg) => arg.startsWith("--lang="));

  let selectedLangCode = null;

  if (langFlagIndex !== -1) {
    selectedLangCode = args[langFlagIndex + 1] || null;
  } else if (inlineLangArg) {
    selectedLangCode = inlineLangArg.split("=")[1] || null;
  } else if (args[0] && !args[0].startsWith("-")) {
    selectedLangCode = args[0];
  }

  if (selectedLangCode) {
    return [path.join(__dirname, `${TEXT_DOMAIN}-${selectedLangCode}.po`)];
  }

  return fs
    .readdirSync(__dirname)
    .filter((file) => file.endsWith(".po") && file !== `${TEXT_DOMAIN}.pot`)
    .map((file) => path.join(__dirname, file));
}

function compileMoFile(poPath) {
  if (!fs.existsSync(poPath)) {
    console.error(`PO file not found: ${path.basename(poPath)}`);
    return false;
  }

  const moPath = poPath.replace(/\.po$/i, ".mo");
  const poData = gettextParser.po.parse(fs.readFileSync(poPath));
  const moData = gettextParser.mo.compile(poData);

  fs.writeFileSync(moPath, moData);
  console.log(`Written: ${path.basename(moPath)}`);
  return true;
}

function main() {
  const poFiles = getPoFiles();

  if (poFiles.length === 0) {
    console.log("No translation .po files found.");
    process.exit(0);
  }

  let hasError = false;

  for (const poFile of poFiles) {
    const ok = compileMoFile(poFile);
    if (!ok) {
      hasError = true;
    }
  }

  if (hasError) {
    process.exit(1);
  }
}

main();
