/**
 * AI translation engine backed by the OpenAI Chat Completions API.
 *
 * Unlike a plain machine-translation service, the model is instructed to
 * preserve sprintf placeholders, HTML markup, shortcodes and brand names, and
 * to detect the source language per string. This matters for Flexify Checkout
 * because the source strings are written in Portuguese (pt_BR) — a fixed
 * `from:"en"` machine translation would corrupt them.
 *
 * Uses the global `fetch` (Node 18+), so no extra dependency is required.
 */

const DEFAULT_MODEL = "gpt-4o-mini";
const DEFAULT_BASE_URL = "https://api.openai.com/v1";
const BATCH_SIZE = 20;
const DELAY_BETWEEN_BATCHES = 500;
const MAX_RETRIES = 4;

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function buildSystemPrompt(targetLanguage) {
  return [
    "You are a professional software localizer for Flexify Checkout, a WordPress/WooCommerce plugin that builds a multi-step, optimized checkout experience.",
    `Translate each UI string into ${targetLanguage} (natural, concise, as used in software interfaces).`,
    "",
    "Strict rules:",
    "- The source strings are usually written in Portuguese (Brazil), but some may already be in English. Detect the language and translate into the target language.",
    `- If a string is already written in ${targetLanguage}, return it unchanged.`,
    "- Preserve EXACTLY, without translating or reordering: sprintf placeholders (%s, %d, %1$s, %2$s, %%), HTML tags and their attributes, HTML entities, URLs, email addresses, WordPress shortcodes (e.g. [shortcode id=\"100\"]) and template tokens such as ${count} or {{ ... }}.",
    "- Keep leading and trailing whitespace, line breaks and surrounding punctuation identical to the source.",
    "- Do NOT translate brand/product names: Flexify Checkout, MeuMouse.com, WooCommerce, WordPress, Joinotify, WhatsApp, Elementor, PayPal, Pix, Mercado Pago, Pagar.me, Correios, Melhor Envio, SuperFrete, Stripe.",
    "- Translate UI text only. Never add explanations, quotes or extra characters.",
    "",
    "You will receive a JSON object whose values are strings keyed by numeric ids.",
    "Respond with a JSON object using the SAME ids as keys and the translated strings as values. Do not add or omit keys.",
  ].join("\n");
}

async function requestBatch(batch, targetLanguage, config) {
  const payload = {};
  batch.forEach((item, index) => {
    payload[String(index)] = item.msgid;
  });

  const body = {
    model: config.model,
    temperature: 0,
    response_format: { type: "json_object" },
    messages: [
      { role: "system", content: buildSystemPrompt(targetLanguage) },
      { role: "user", content: JSON.stringify(payload) },
    ],
  };

  let lastError;

  for (let attempt = 0; attempt <= MAX_RETRIES; attempt++) {
    let response;

    try {
      response = await fetch(`${config.baseUrl}/chat/completions`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${config.apiKey}`,
        },
        body: JSON.stringify(body),
      });
    } catch (error) {
      lastError = error;
      const delay = Math.pow(2, attempt) * 1000;
      console.log(`    Network error. Retrying in ${delay / 1000}s (${attempt + 1}/${MAX_RETRIES})...`);
      await sleep(delay);
      continue;
    }

    if (response.status === 429 || response.status >= 500) {
      lastError = new Error(`HTTP ${response.status}`);
      const delay = Math.pow(2, attempt + 1) * 1000;
      console.log(`    Rate limited / server error (${response.status}). Retrying in ${delay / 1000}s (${attempt + 1}/${MAX_RETRIES})...`);
      await sleep(delay);
      continue;
    }

    if (!response.ok) {
      const detail = await response.text();
      throw new Error(`OpenAI request failed (${response.status}): ${detail}`);
    }

    const data = await response.json();
    const content = data?.choices?.[0]?.message?.content;

    if (!content) {
      throw new Error("OpenAI response did not contain any content.");
    }

    let parsed;
    try {
      parsed = JSON.parse(content);
    } catch (error) {
      throw new Error(`Could not parse OpenAI JSON response: ${error.message}`);
    }

    const translations = {};
    batch.forEach((item, index) => {
      const value = parsed[String(index)];
      if (typeof value === "string") {
        translations[item.msgid] = value;
      }
    });

    return translations;
  }

  throw lastError || new Error("OpenAI request failed after retries.");
}

/**
 * Translates an array of { msgid } entries into the target language.
 * Returns a map of msgid -> translated string.
 */
export async function translateStringsOpenAI(strings, langInfo) {
  if (strings.length === 0) {
    return {};
  }

  const apiKey = process.env.OPENAI_API_KEY;
  if (!apiKey) {
    throw new Error(
      "OPENAI_API_KEY environment variable is not set. Add it to languages/.env or your environment."
    );
  }

  const config = {
    apiKey,
    model: process.env.OPENAI_MODEL || DEFAULT_MODEL,
    baseUrl: (process.env.OPENAI_BASE_URL || DEFAULT_BASE_URL).replace(/\/+$/, ""),
  };

  console.log(`   Using OpenAI model "${config.model}"`);

  const translations = {};
  const totalBatches = Math.ceil(strings.length / BATCH_SIZE);

  for (let i = 0; i < strings.length; i += BATCH_SIZE) {
    const batch = strings.slice(i, i + BATCH_SIZE);
    const batchNum = Math.floor(i / BATCH_SIZE) + 1;

    console.log(`    Translating batch ${batchNum}/${totalBatches} (${batch.length} strings)...`);

    try {
      const batchTranslations = await requestBatch(batch, langInfo.name, config);
      Object.assign(translations, batchTranslations);
    } catch (error) {
      console.error(`    Error translating batch ${batchNum}: ${error.message}`);
    }

    if (i + BATCH_SIZE < strings.length) {
      await sleep(DELAY_BETWEEN_BATCHES);
    }
  }

  return translations;
}
