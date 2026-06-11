import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import gettextParser from "gettext-parser";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const TEXT_DOMAIN = "flexify-checkout-for-woocommerce";
const PLUGIN_ROOT = path.resolve(__dirname, "..");
const OUTPUT_FILE = path.join(__dirname, `${TEXT_DOMAIN}.pot`);
const INCLUDED_EXTENSIONS = new Set([".php", ".js", ".jsx", ".ts", ".tsx", ".vue"]);
const IGNORED_DIRECTORIES = new Set([
  ".git",
  ".vscode",
  ".claude",
  "dist",
  "build",
  "node_modules",
  // Catches both the Composer vendor/ at the plugin root and the bundled
  // third-party libraries under assets/vendor/ (matched by directory name).
  "vendor",
  "languages",
  // Packaged build copy (full plugin duplicate + zip) — scanning it double-
  // extracts every string and resurrects ones already removed from source.
  "release",
  // Vue/Vite workspace (only present from 6.0.0 onward). When its source lands,
  // remove this entry and add JS script handles back to translate-cli.js.
  "app",
]);

const SUPPORTED_FUNCTIONS = new Set([
  "__",
  "_e",
  "_x",
  "_ex",
  "_n",
  "_nx",
  "esc_attr__",
  "esc_attr_e",
  "esc_attr_x",
  "esc_html__",
  "esc_html_e",
  "esc_html_x",
]);

function walkFiles(directory) {
  const files = [];

  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const fullPath = path.join(directory, entry.name);

    if (entry.isDirectory()) {
      if (!IGNORED_DIRECTORIES.has(entry.name)) {
        files.push(...walkFiles(fullPath));
      }
      continue;
    }

    if (INCLUDED_EXTENSIONS.has(path.extname(entry.name))) {
      files.push(fullPath);
    }
  }

  return files;
}

function isIdentifierBoundary(char) {
  return !char || !/[A-Za-z0-9_$]/.test(char);
}

function findClosingParen(source, openIndex) {
  let depth = 1;
  let inString = null;
  let escaped = false;
  let inLineComment = false;
  let inBlockComment = false;

  for (let index = openIndex + 1; index < source.length; index++) {
    const char = source[index];
    const nextChar = source[index + 1];

    if (inLineComment) {
      if (char === "\n") {
        inLineComment = false;
      }
      continue;
    }

    if (inBlockComment) {
      if (char === "*" && nextChar === "/") {
        inBlockComment = false;
        index++;
      }
      continue;
    }

    if (inString) {
      if (escaped) {
        escaped = false;
        continue;
      }

      if (char === "\\") {
        escaped = true;
        continue;
      }

      if (char === inString) {
        inString = null;
      }
      continue;
    }

    if (char === "/" && nextChar === "/") {
      inLineComment = true;
      index++;
      continue;
    }

    if (char === "/" && nextChar === "*") {
      inBlockComment = true;
      index++;
      continue;
    }

    if (char === "'" || char === '"' || char === "`") {
      inString = char;
      continue;
    }

    if (char === "(") {
      depth++;
      continue;
    }

    if (char === ")") {
      depth--;
      if (depth === 0) {
        return index;
      }
    }
  }

  return -1;
}

function splitTopLevelArgs(argumentSource) {
  const args = [];
  let current = "";
  let depth = 0;
  let inString = null;
  let escaped = false;

  for (let index = 0; index < argumentSource.length; index++) {
    const char = argumentSource[index];

    if (inString) {
      current += char;

      if (escaped) {
        escaped = false;
        continue;
      }

      if (char === "\\") {
        escaped = true;
        continue;
      }

      if (char === inString) {
        inString = null;
      }
      continue;
    }

    if (char === "'" || char === '"' || char === "`") {
      inString = char;
      current += char;
      continue;
    }

    if (char === "(" || char === "[" || char === "{") {
      depth++;
      current += char;
      continue;
    }

    if (char === ")" || char === "]" || char === "}") {
      depth--;
      current += char;
      continue;
    }

    if (char === "," && depth === 0) {
      args.push(current.trim());
      current = "";
      continue;
    }

    current += char;
  }

  if (current.trim()) {
    args.push(current.trim());
  }

  return args;
}

function decodeStringLiteral(rawValue) {
  if (!rawValue || rawValue.length < 2) {
    return null;
  }

  const quote = rawValue[0];
  if (!["'", '"', "`"].includes(quote) || rawValue[rawValue.length - 1] !== quote) {
    return null;
  }

  const inner = rawValue.slice(1, -1);
  return inner
    .replace(/\\\\/g, "\\")
    .replace(/\\'/g, "'")
    .replace(/\\"/g, '"')
    .replace(/\\`/g, "`")
    .replace(/\\r/g, "\r")
    .replace(/\\n/g, "\n")
    .replace(/\\t/g, "\t");
}

function getFunctionSpec(functionName, args) {
  switch (functionName) {
    case "__":
    case "_e":
    case "esc_attr__":
    case "esc_attr_e":
    case "esc_html__":
    case "esc_html_e":
      return { msgid: args[0], domain: args[1] };
    case "_x":
    case "_ex":
    case "esc_attr_x":
    case "esc_html_x":
      return { msgid: args[0], context: args[1], domain: args[2] };
    case "_n":
      return { msgid: args[0], plural: args[1], domain: args[3] };
    case "_nx":
      return { msgid: args[0], plural: args[1], context: args[3], domain: args[4] };
    default:
      return null;
  }
}

function getLineNumber(source, index) {
  return source.slice(0, index).split("\n").length;
}

function addReference(existingReference, nextReference) {
  if (!existingReference) {
    return nextReference;
  }

  const referenceSet = new Set(existingReference.split("\n"));
  referenceSet.add(nextReference);
  return Array.from(referenceSet).join("\n");
}

function extractStringsFromFile(filePath) {
  const source = fs.readFileSync(filePath, "utf8");
  const results = [];

  for (let index = 0; index < source.length; index++) {
    for (const functionName of SUPPORTED_FUNCTIONS) {
      if (!source.startsWith(functionName, index)) {
        continue;
      }

      const before = source[index - 1];
      const after = source[index + functionName.length];
      if (!isIdentifierBoundary(before) || !isIdentifierBoundary(after)) {
        continue;
      }

      let cursor = index + functionName.length;
      while (cursor < source.length && /\s/.test(source[cursor])) {
        cursor++;
      }

      if (source[cursor] !== "(") {
        continue;
      }

      const closingParen = findClosingParen(source, cursor);
      if (closingParen === -1) {
        continue;
      }

      const args = splitTopLevelArgs(source.slice(cursor + 1, closingParen));
      const spec = getFunctionSpec(functionName, args);

      if (!spec) {
        index = closingParen;
        break;
      }

      const msgid = decodeStringLiteral(spec.msgid);
      const domain = decodeStringLiteral(spec.domain);
      const context = decodeStringLiteral(spec.context);
      const plural = decodeStringLiteral(spec.plural);

      if (!msgid) {
        index = closingParen;
        break;
      }

      if (domain && domain !== TEXT_DOMAIN) {
        index = closingParen;
        break;
      }

      results.push({
        msgid,
        plural,
        context,
        reference: `${path.relative(PLUGIN_ROOT, filePath).replace(/\\/g, "/")}:${getLineNumber(source, index)}`,
      });

      index = closingParen;
      break;
    }
  }

  return results;
}

function buildPotData(entries) {
  const translations = { "": {} };

  translations[""][""] = {
    msgid: "",
    msgstr: [
      [
        `Project-Id-Version: ${TEXT_DOMAIN}\n`,
        "Report-Msgid-Bugs-To: \n",
        `POT-Creation-Date: ${new Date().toISOString()}\n`,
        "PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n",
        "Last-Translator: \n",
        "Language-Team: \n",
        "Language: \n",
        "MIME-Version: 1.0\n",
        "Content-Type: text/plain; charset=UTF-8\n",
        "Content-Transfer-Encoding: 8bit\n",
        "Plural-Forms: nplurals=2; plural=(n != 1);\n",
        `X-Domain: ${TEXT_DOMAIN}\n`,
      ].join(""),
    ],
  };

  for (const entry of entries) {
    const contextKey = entry.context || "";
    if (!translations[contextKey]) {
      translations[contextKey] = {};
    }

    const existing = translations[contextKey][entry.msgid];
    translations[contextKey][entry.msgid] = {
      msgid: entry.msgid,
      msgctxt: entry.context,
      msgid_plural: entry.plural,
      msgstr: entry.plural ? ["", ""] : [""],
      comments: {
        reference: addReference(existing?.comments?.reference, entry.reference),
      },
    };
  }

  return {
    charset: "utf-8",
    headers: {
      "project-id-version": TEXT_DOMAIN,
      "content-type": "text/plain; charset=UTF-8",
      "content-transfer-encoding": "8bit",
      "plural-forms": "nplurals=2; plural=(n != 1);",
      "x-domain": TEXT_DOMAIN,
    },
    translations,
  };
}

function main() {
  const files = walkFiles(PLUGIN_ROOT);
  const extractedEntries = files.flatMap(extractStringsFromFile);
  const dedupedEntries = extractedEntries.sort((left, right) => {
    if (left.msgid !== right.msgid) {
      return left.msgid.localeCompare(right.msgid);
    }

    return (left.context || "").localeCompare(right.context || "");
  });

  const potData = buildPotData(dedupedEntries);
  fs.writeFileSync(OUTPUT_FILE, gettextParser.po.compile(potData));

  console.log(`Generated ${path.basename(OUTPUT_FILE)} with ${dedupedEntries.length} extracted entries.`);
}

main();
