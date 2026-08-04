/**
 * Lightweight input masking for the Swift checkout, mirroring the jQuery-mask
 * patterns used by the classic checkout (see the stored input_mask values, e.g.
 * `000.000.000-00` for CPF, `00.000.000/0000-00` for CNPJ, `00000-000` for CEP,
 * `(00) 00000-0000` for phone). A `0` or `9` in the pattern is a digit slot;
 * every other character is a literal separator.
 */

/**
 * Format a raw value against a mask pattern, keeping only its digits.
 *
 * @param {string} pattern Mask pattern (e.g. `000.000.000-00`).
 * @param {string} raw     Raw typed value (may already contain separators).
 * @returns {string} The masked value, never longer than the pattern allows.
 */
export function applyMask(pattern, raw) {
  if (!pattern) {
    return raw == null ? '' : String(raw);
  }

  const digits = String(raw == null ? '' : raw).replace(/\D+/g, '');
  let out = '';
  let di = 0;

  for (let i = 0; i < pattern.length && di < digits.length; i += 1) {
    const slot = pattern[i];

    if (slot === '0' || slot === '9') {
      out += digits[di];
      di += 1;
    } else {
      out += slot;
    }
  }

  return out;
}

/**
 * Strip every non-digit from a value.
 *
 * @param {string} value Masked value.
 * @returns {string} Digits only.
 */
export function unmask(value) {
  return String(value == null ? '' : value).replace(/\D+/g, '');
}
