# Flexify Checkout — Pipeline de Internacionalização (i18n)

Este diretório contém um pipeline **autossuficiente em Node.js** (ESM) para gerar, traduzir
e compilar os arquivos de tradução do plugin Flexify Checkout. O *text domain* do plugin é
`flexify-checkout-for-woocommerce`, e todos os artefatos são gerados aqui dentro de
`languages/`.

O fluxo completo é:

```
código-fonte (.php)
        │  npm run pot
        ▼
   flexify-checkout-for-woocommerce.pot  ──────┐
        │  npm run translate(:ai)               │ (preenche os .po)
        ▼                                       │
flexify-checkout-for-woocommerce-<locale>.po    │
        │  npm run compile:mo                    │
        │  npm run compile:php                   │
        ▼                                        ▼
.mo                                          .l10n.php
```

---

## Idioma-fonte: Inglês

> **Atenção.** As strings de origem (`msgid`) do Flexify Checkout estão escritas em
> **inglês (en_US)** — por exemplo `__( 'License activated successfully. All features are now active!', ... )`.
> Por isso:
> - `en_US` **não** é um alvo de tradução (é o idioma de origem; não precisa de `.po`/`.mo`).
> - `pt_BR` agora é um **alvo de tradução** como qualquer outro idioma.
> - Prefira o motor de **IA** (`translate:ai`): ele detecta o idioma de origem por string.
>   O motor Google está configurado para **auto-detectar** a origem (sem `from` fixo), mas a
>   IA preserva placeholders/HTML/shortcodes com mais fidelidade.

Os idiomas-alvo ativos são definidos no mapa `LANGUAGES` em `translate-cli.js`:

| Locale  | Código | Idioma                  |
|---------|--------|-------------------------|
| `pt_BR` | `pt`   | Português (Brasil)      |
| `es_ES` | `es`   | Espanhol (Espanha)      |
| `fr_FR` | `fr`   | Francês (França)        |

Outros idiomas (de_DE, it_IT, nl_NL, pt_PT, zh_CN) estão listados comentados no mesmo mapa —
basta descomentá-los para incluí-los nas próximas execuções.

---

## Requisitos

- **Node.js 18+** (usa `fetch` global e ESM nativo).
- Para tradução automática, uma chave de API:
  - **OpenAI** (motor de IA, **recomendado** — detecta a origem por string), ou
  - **Google Cloud Translation** (motor padrão, com auto-detecção de origem).

## Instalação

```bash
cd languages
npm install
```

As dependências são `gettext-parser` (leitura/escrita de `.po`/`.mo`/`.pot`),
`@google-cloud/translate` e `dotenv`.

## Configuração das chaves de API

Copie o arquivo de exemplo e preencha as chaves. O `.env` é **ignorado pelo Git**.

```bash
cp .env.example .env
```

```ini
# Chave da API OpenAI (motor: openai, tradução com IA — recomendado)
OPENAI_API_KEY=sk-...
# Sobrescritas opcionais do motor OpenAI
OPENAI_MODEL=gpt-4o-mini
# OPENAI_BASE_URL=https://api.openai.com/v1

# Chave da API Google Cloud Translation (motor: google, padrão)
GOOGLE_TRANSLATE_API_KEY=AIza...

# Opcional: motor padrão quando --engine não é informado (google | openai)
# TRANSLATE_ENGINE=google
```

- Chave OpenAI: https://platform.openai.com/api-keys
- Chave Google: https://console.cloud.google.com/apis/credentials

---

## Comandos (scripts npm)

| Script                       | O que faz |
|------------------------------|-----------|
| `npm run pot`                | Gera/atualiza o `.pot` varrendo o código-fonte. |
| `npm run translate`          | Traduz os `.po` (todos os idiomas) com **Google** (padrão). |
| `npm run translate:lang -- <locale>` | Traduz apenas um idioma (ex.: `es_ES`). |
| `npm run translate:ai`       | Traduz com **OpenAI** (IA, recomendado). |
| `npm run translate:ai:lang -- <locale>` | Traduz um idioma com IA. |
| `npm run translate:ai:retry` | Re-traduz com IA as entradas cuja tradução ficou idêntica à origem. |
| `npm run compile:mo`         | Compila todos os `.po` → `.mo`. |
| `npm run compile:mo:lang -- <locale>` | Compila o `.mo` de um idioma. |
| `npm run compile:php`        | Compila todos os `.po` → `.l10n.php`. |
| `npm run compile:php:lang -- <locale>` | Compila o `.l10n.php` de um idioma. |

> Os scripts `pretranslate*` rodam `npm run pot` automaticamente antes de traduzir,
> garantindo que o `.pot` esteja sempre atualizado.

Os scripts CLI também aceitam flags diretas:

```bash
node translate-cli.js --engine=openai --lang=es_ES
node translate-cli.js --engine=openai --retranslate-identical
node compile-mo-cli.js --lang es_ES
node compile-php-cli.js es_ES
```

---

## Procedimento completo (passo a passo)

### 1. Gerar o template (`.pot`)

```bash
npm run pot
```

`generate-pot-cli.js` é um parser próprio que varre `.php` (e `.js`/`.ts`/`.vue` quando
existirem) em busca das funções de tradução do WordPress (`__`, `_e`, `_x`, `_n`,
`esc_html__`, `esc_attr__`, etc.), considerando **apenas** strings do text domain
`flexify-checkout-for-woocommerce`. Diretórios como `node_modules`, `vendor`,
`assets/vendor`, `dist`, `release` e `app` são ignorados.

> **Importante:** `release/` **deve** permanecer na lista `IGNORED_DIRECTORIES`.
> `release/` é uma cópia completa do plugin gerada no build — varrê-la duplica cada string e
> "ressuscita" strings já removidas do código.

> **Não envolva identificadores técnicos em `__()`** — nomes de diretivas do `php.ini`,
> nomes de classes e constantes devem ser literais puras, senão a IA os traduz token a token.

### 2. Traduzir (`.po`)

```bash
npm run translate:ai          # todos os idiomas, via IA (recomendado)
npm run translate:ai:lang -- es_ES   # somente es_ES
```

`translate-cli.js` lê o `.pot`, carrega o `.po` existente de cada idioma e é **incremental**:
apenas entradas com `msgstr` **vazio** são reenviadas para tradução. Traduções já existentes
(inclusive edições manuais) são preservadas, e msgids obsoletos são descartados (o `.po` é
reconstruído a partir do `.pot` a cada execução).

#### Re-traduzir passagens idênticas (`--retranslate-identical`)

Uma entrada cujo `msgstr` é igual ao `msgid` ficaria presa na origem para sempre. A flag
`--retranslate-identical` / `-r` (ou `RETRANSLATE_IDENTICAL=1`, ou `npm run translate:ai:retry`)
re-enfileira essas entradas **apenas para alvos não-inglês**. Strings legitimamente idênticas
(nomes de marca/país) são reenviadas mas devolvidas inalteradas — custo desprezível.

### 3. Compilar artefatos de runtime

```bash
npm run compile:mo     # .po -> .mo
npm run compile:php    # .po -> .l10n.php
```

Você pode editar um `.po` à mão e recompilar sem retraduzir.
O `translate-cli.js` já escreve `.po`, `.mo` e `.l10n.php` de uma só vez;
os comandos `compile:*` existem para regenerar a partir de `.po` editados manualmente.

---

## Artefatos gerados (por idioma)

| Arquivo | Consumido por | Descrição |
|---------|---------------|-----------|
| `flexify-checkout-for-woocommerce.pot` | tradutores | Template-mestre com todas as strings (sem traduções). |
| `flexify-checkout-for-woocommerce-<locale>.po` | tradutores / build | Catálogo editável com as traduções. |
| `flexify-checkout-for-woocommerce-<locale>.mo` | WordPress (PHP) | Binário gettext clássico. |
| `flexify-checkout-for-woocommerce-<locale>.l10n.php` | WordPress 6.5+ (PHP) | Formato PHP — o WP o **prefere** ao `.mo`. |

Os artefatos PHP são carregados via `load_plugin_textdomain` no carregamento do plugin.

> **Sem traduções de JS por enquanto.** Esta branch (5.5.x) não usa
> `wp_set_script_translations`, então o pipeline **não** gera arquivos `.json` por *handle* de
> script. Quando os apps Vue da migração 6.0.0 entrarem (admin schema-driven), reintroduza o
> array `SCRIPT_HANDLES` e a geração de `.json` no `translate-cli.js`, espelhando o pipeline do
> Joinotify.

---

## Arquivos do pipeline

| Arquivo | Papel |
|---------|-------|
| `generate-pot-cli.js` | Extrai strings do código → `.pot`. |
| `translate-cli.js`    | Orquestra tradução incremental + escreve `.po`/`.mo`/`.l10n.php`. |
| `openai-translate.js` | Motor de IA (OpenAI) via `fetch`; preserva `%s`, HTML, shortcodes, tokens e marcas. |
| `l10n-php.js`         | Conversor compartilhado PO → `.l10n.php` (formato WP 6.5+). |
| `compile-mo-cli.js`   | Recompila `.mo` a partir de `.po`. |
| `compile-php-cli.js`  | Recompila `.l10n.php` a partir de `.po`. |
| `.env` / `.env.example` | Chaves de API (o `.env` é git-ignored). |

---

## Integração com o build de release

Durante `npm run build` (na raiz do plugin, via `scripts/build.mjs`), a etapa de
traduções roda automaticamente: `npm run pot` → (opcional `--translate`) → `compile:mo`
→ `compile:php`. Somente os artefatos compilados (`.po`, `.mo`, `.pot`, `.l10n.php`)
são empacotados no ZIP de release — os scripts `*-cli.js` e o `node_modules` ficam de fora.

Para incluir a re-tradução por IA no build:

```bash
# na raiz do plugin
npm run build:translate     # = node scripts/build.mjs --translate (motor openai)
```
