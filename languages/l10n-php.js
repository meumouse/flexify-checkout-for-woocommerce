import gettextParser from "gettext-parser";

/**
 * Shared helpers to build WordPress 6.5+ `.l10n.php` translation files
 * from gettext-parser PO data.
 *
 * WordPress core (WP_Translation_File_PHP) expects a PHP file returning an
 * associative array with the PO headers as top-level keys plus a `messages`
 * key. Inside `messages`:
 *   - context is encoded as  msgctxt . "\4" . msgid   (EOT, 0x04)
 *   - plurals are encoded as  msgid . "\0" . msgid_plural  (NUL, 0x00)
 *     and the value joins the msgstr array with "\0".
 */

const EOT = String.fromCharCode(4);
const NUL = String.fromCharCode(0);

/**
 * Escapes a string for use inside a single-quoted PHP literal.
 *
 * Single quotes avoid `$var` interpolation, which matters because some
 * source strings contain sequences like `$object->item`.
 */
function escapeSingleQuoted(value) {
  return value.replace(/\\/g, "\\\\").replace(/'/g, "\\'");
}

/**
 * Renders a JS string as a PHP string literal.
 *
 * Control bytes (NUL/EOT) used for context/plural keys cannot live inside a
 * single-quoted literal, so those segments are emitted as double-quoted
 * escapes and concatenated, keeping the human-readable parts single-quoted.
 */
function toPhpString(value) {
  if (!value.includes(NUL) && !value.includes(EOT)) {
    return `'${escapeSingleQuoted(value)}'`;
  }

  const parts = [];
  let buffer = "";

  for (const char of value) {
    if (char === NUL || char === EOT) {
      if (buffer) {
        parts.push(`'${escapeSingleQuoted(buffer)}'`);
        buffer = "";
      }
      parts.push(char === NUL ? '"\\0"' : '"\\4"');
      continue;
    }

    buffer += char;
  }

  if (buffer) {
    parts.push(`'${escapeSingleQuoted(buffer)}'`);
  }

  return parts.join(" . ");
}

/**
 * Builds the `messages` map (encoded keys -> encoded values) from PO data,
 * skipping the header entry and any untranslated strings.
 */
function buildMessages(poData) {
  const messages = [];

  for (const [context, entries] of Object.entries(poData.translations)) {
    for (const [msgid, entry] of Object.entries(entries)) {
      if (msgid === "" && context === "") {
        continue;
      }

      const msgstr = entry.msgstr || [];
      const hasTranslation = msgstr.some((value) => value && value.length > 0);
      if (!hasTranslation) {
        continue;
      }

      let key = msgid;
      if (entry.msgid_plural) {
        key += NUL + entry.msgid_plural;
      }
      if (context) {
        key = context + EOT + key;
      }

      const value = msgstr.length > 1 ? msgstr.join(NUL) : msgstr[0];
      messages.push([key, value]);
    }
  }

  return messages;
}

/**
 * Converts gettext-parser PO data into the contents of a `.l10n.php` file.
 */
export function poDataToPhp(poData) {
  const headers = poData.headers || {};
  const lines = ["<?php", "return ["];

  // WordPress reads header keys in lowercase (matching wp-cli make-php output).
  for (const [name, value] of Object.entries(headers)) {
    lines.push(`\t${toPhpString(name.toLowerCase())} => ${toPhpString(value)},`);
  }

  lines.push("\t'messages' => [");

  for (const [key, value] of buildMessages(poData)) {
    lines.push(`\t\t${toPhpString(key)} => ${toPhpString(value)},`);
  }

  lines.push("\t],");
  lines.push("];");
  lines.push("");

  return lines.join("\n");
}

/**
 * Parses a `.po` buffer and returns its `.l10n.php` representation.
 */
export function poBufferToPhp(buffer) {
  return poDataToPhp(gettextParser.po.parse(buffer));
}
