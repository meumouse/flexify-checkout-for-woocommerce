# AGENTS.md — Flexify Checkout for WooCommerce

This document describes the project layout, coding conventions, and development
workflow. It is written for both human developers and AI coding agents. **Read
this file before opening any change.**

> Proprietary software of MEUMOUSE.COM® — SOLUÇÕES DIGITAIS LTDA.
> See [license.md](license.md). Do not distribute or copy outside the license terms.

---

## 0. Language policy (read first)

- **All source code, code comments, docblocks, variable/function/class names,
  and commit messages MUST be written in English (`en_US`).** This is a hard
  rule for anything that lives in the code.
- **User-facing product docs** ([README.md](README.md)) and the
  [changelogs.md](changelogs.md) are written in **PT-BR** (the plugin's primary
  market). Do not translate these to English.
- **User-facing runtime strings** (labels, messages, notices) are authored in
  the source in English and localized through the i18n pipeline (see §5). Never
  hardcode a non-English string in the code.
- When in doubt: *code and comments → English; product/changelog docs → PT-BR.*

---

## 1. Overview

Flexify Checkout is a WordPress/WooCommerce plugin that replaces the default
checkout with a conversion-optimized, multi-step purchase flow. The project has
**two worlds** living in the same repository:

| Layer | Technology | Location | What it is |
|-------|-----------|----------|------------|
| **Backend** | PHP 7.4+ (PSR-4, namespaces) | `admin/src/` | All plugin logic: hooks, REST, integrations, checkout, webhooks |
| **Admin frontend** | Vue 3 + Pinia + Vue Router | `app/src/` | Settings-screen SPA inside wp-admin |
| **Checkout frontend (React)** | React 18 | `app/src/checkout-react/` | Optional React-rendered checkout ("Swift") |
| **Checkout frontend (legacy)** | JS/Vue + PHP templates | `app/src/checkout/` + `templates/` + `woocommerce/` | Classic multi-step checkout based on Woo templates |

Minimum runtime requirements: **WordPress 6.0+, WooCommerce 6.0+, PHP 7.4+**.

---

## 2. Directory structure

```
flexify-checkout-for-woocommerce/
├── flexify-checkout-for-woocommerce.php   # Entrypoint: defines constants and calls Core\Init::bootstrap()
├── uninstall.php                          # Cleanup on plugin uninstall
├── README.md                              # User documentation (PT-BR)
├── AGENTS.md                              # This file — engineering guidelines
├── CLAUDE.md                              # Pointer to AGENTS.md for Claude Code
├── changelogs.md                          # Version history (PT-BR) — ALWAYS update
├── license.md                             # Proprietary license
│
├── admin/                                 # ── PHP BACKEND ──
│   ├── composer.json                      # PSR-4: MeuMouse\Flexify_Checkout\ → src/
│   ├── vendor/                            # Composer autoloader (versioned; see §7)
│   └── src/
│       ├── Core/                          # Bootstrap, Assets, Scripts, Ajax, Helpers, Logger, Webhooks
│       ├── Admin/                         # Admin screens/options, Settings (Registry/Repository/Stores)
│       ├── API/                           # License, Updater, direct checkout, field REST helpers
│       ├── Rest/                          # REST endpoints (namespace flexify-checkout/v1) — one class per route
│       ├── Checkout/                      # Checkout logic: fields, conditions, steps, themes, React_Checkout
│       ├── Integrations/                  # ~50 integrations (themes, shipping, gateways, third-party plugins)
│       ├── Compatibility/                 # Backward-compat shims (legacy hooks/filters)
│       ├── Tracking/                      # Multi-platform tracking router (GA4, Google Ads, Meta)
│       ├── Cron/                          # Scheduled routines
│       ├── Validations/                   # Field validations
│       ├── Views/                         # Legacy screen rendering
│       └── Recovery_Carts/                # Cart recovery (Pro feature; formerly a separate addon)
│
├── app/                                   # ── FRONTEND (built with Vite) ──
│   ├── package.json                       # Runtime deps (Vue, React, Pinia, ApexCharts, CodeMirror)
│   ├── vite.config.js                     # Admin SPA build (Vue) → app/dist/settings/
│   ├── vite.checkout.config.js            # Legacy checkout build → app/dist/checkout/
│   ├── vite.checkout-react.config.js      # React checkout build (IIFE) → app/dist/checkout-react/
│   ├── tailwind.config.js / postcss.config.js
│   ├── dist/                              # Compiled bundles (NOT versioned — see app/.gitignore)
│   └── src/
│       ├── entries/                       # Build entrypoints (settings.js)
│       ├── App.vue, router/, stores/, services/, pages/, components/, styles/, utils/
│       ├── checkout/                      # Legacy checkout (JS)
│       └── checkout-react/                # React checkout (components, context, api, lib)
│
├── assets/                                # Statically served assets (img, theme css, vendor JS, JSON)
│   └── frontend/  admin/  recovery-carts/  vendor/
├── templates/                             # Plugin PHP templates (template-modern, template-dark, template-react)
├── woocommerce/                           # WooCommerce template overrides (common/modern/dark)
├── languages/                             # i18n: .pot/.po/.mo/.l10n.php + translation tooling (Node)
├── updater/                               # Plugin updater
├── scripts/build.mjs                      # Release packaging pipeline (.zip)
└── dist/                                  # (release output)
```

---

## 3. Backend architecture (PHP)

### 3.1. Bootstrap and lifecycle

The entrypoint
[`flexify-checkout-for-woocommerce.php`](flexify-checkout-for-woocommerce.php)
does the minimum: it defines `FLEXIFY_CHECKOUT_PLUGIN_VERSION`, loads the
Composer autoloader, and calls `Core\Init::bootstrap()`. All real initialization
lives in [`admin/src/Core/Init.php`](admin/src/Core/Init.php).

- **Constants** are defined in `Init::define_constants()` (prefix
  `FLEXIFY_CHECKOUT_*`): paths, URLs, and metadata. Prefer **helpers** over
  "hot" constants (e.g. use `flexify_checkout_is_debug()` instead of reading the
  debug constant directly).
- **Class instantiation**: `Init::instance_classes()` auto-instantiates the
  namespace's classes through a cached *class registry* (built from the Composer
  classmap). A class is instantiated when it is instantiable (not
  abstract/interface/trait) and its constructor **requires no arguments**. If the
  class exposes an `init()` method, it is called right after construction.
- Classes that must run before others, or that the registry cannot detect, are
  listed manually in the `instance_classes()` array (filter
  `Flexify_Checkout/Init/Instance_Classes`).
- **Admin vs. always context**: classes under `Views\Settings\` and
  `Admin\Settings\Views\` load only in the admin. Everything else loads always.
- When adding a new auto-instantiable class, make sure it appears in the Composer
  classmap (see §7) — otherwise register it manually.

### 3.2. PHP coding conventions

- **Root namespace**: `MeuMouse\Flexify_Checkout\` mapped to `admin/src/`
  (PSR-4). File name = class name. Namespace segments may contain `_`
  (e.g. `Recovery_Carts`).
- **`defined('ABSPATH') || exit;`** right after the `use` block in every file.
- **Docblock required** on every class and public method, with the tags:
  - `@since` (version it was introduced) and, when changed, `@version` (version
    of the last relevant change).
  - Typed `@param` / `@return`.
  - `@package MeuMouse\Flexify_Checkout\...` and `@author MeuMouse.com` on the class.
- **Comments and docblocks in English** — see §0.
- **Style**: match the neighboring file's indentation. Use spaces inside
  parentheses in the project style (`function( $arg )`, `array( ... )`). Use
  `array()` (the style already present in the code) — do not switch the existing
  style to `[]`.
- **Escaping and sanitization**: always escape on output (`esc_attr`,
  `esc_html`, `esc_url`, `wp_kses`) and sanitize on input. Sensitive REST
  endpoints and AJAX handlers **require nonce + capability check**
  (`manage_woocommerce`) — see the v5.5.5 security notes in
  [changelogs.md](changelogs.md).
- **i18n**: every user-facing string goes through `__()` / `_e()` /
  `esc_html__()` with the text domain `'flexify-checkout-for-woocommerce'`.

### 3.3. Hooks (actions and filters) — naming

Plugin-owned hooks use the slash-delimited prefix already established:

```
Flexify_Checkout/Before_Init
Flexify_Checkout/Init
Flexify_Checkout/Init/Instance_Classes
Flexify_Checkout/Checkout/Thankyou_Endpoint_Slugs
```

Keep this scheme (`Flexify_Checkout/Area/Hook_Name`) when creating new hooks.
Document every new hook with a docblock (`@since`, `@param`).

### 3.4. REST API

- REST namespace: **`flexify-checkout/v1`** (constant `REST_NAMESPACE` in
  [`admin/src/Rest/Abstract_Route.php`](admin/src/Rest/Abstract_Route.php)).
- **One class per route**, extending `Abstract_Route`. Declare `$route`,
  `$methods`, `$args`; registration happens automatically on `rest_api_init`
  when the class is booted by `Init`.
- New endpoints: provide a real `permission_callback` (never `__return_true` for
  write operations), and validate/sanitize `$args`. The base class defaults the
  permission to `current_user_can('manage_woocommerce')`.

### 3.5. Integrations

Each integration with a theme/gateway/third-party plugin is an isolated class in
[`admin/src/Integrations/`](admin/src/Integrations). Pattern: detect whether the
target is active and only then register hooks. When adding a new integration,
follow the shape of an existing one (e.g. `Astra.php`, `Flatsome.php`).

---

## 4. Frontend architecture (Vite)

There are **three independent bundles**, each with its own Vite config:

| Command (in `app/`) | Config | Output |
|---------------------|--------|--------|
| `npm run build:admin` | `vite.config.js` | `app/dist/settings/app.js` + `dist/styles/`, `dist/chunks/` |
| `npm run build:checkout` | `vite.checkout.config.js` | `app/dist/checkout/main.js` |
| `npm run build:checkout-react` | `vite.checkout-react.config.js` | `app/dist/checkout-react/main.{js,css}` (IIFE, React bundled in) |
| `npm run build` | all of the above | rebuilds everything (admin with `--emptyOutDir`) |
| `npm run dev` | `vite.config.js` | admin dev server |

Important notes:

- The **admin SPA** (Vue) is mounted by the entry
  [`app/src/entries/settings.js`](app/src/entries/settings.js). The initial route
  comes from the `view` exposed by WordPress in `flexifyCheckoutBootstrapConfig`,
  so each wp-admin submenu (Settings, License, Apps, Analytics, Carts, Queue)
  opens the app on the right route. Global state via **Pinia**
  (`app/src/stores/`), HTTP via `app/src/services/api.js`.
- The **React checkout** is built as an **IIFE** with React bundled in and
  enqueued via `wp_enqueue_script` only when React mode is active. Because it is
  browser code, `process.env.NODE_ENV` is replaced at build time (see the config).
- Bundles are loaded by PHP from `app/dist/...` in
  [`admin/src/Core/Assets.php`](admin/src/Core/Assets.php) and
  [`admin/src/Core/Scripts.php`](admin/src/Core/Scripts.php).
- **`app/dist/` is NOT versioned** (see `app/.gitignore` and the root
  `.gitignore`). Anyone cloning the repo must run the build. Release packaging
  includes the generated `dist/`.
- Static plugin assets (`assets/`) are served **unminified** — the `$min_file`
  property in `Core\Assets` is empty. Do not assume a `.min` suffix.

Frontend style: **Tailwind CSS** (config in `app/tailwind.config.js`),
PostCSS/autoprefixer. Vue components are SFCs (`.vue`) organized by function in
`app/src/components/` (buttons, fields, modals, settings, table, toasts, toggles,
icons). Toasts use `sonner` (React checkout) and `vue-sonner` (Vue admin).

### 4.1. Admin design system

The wp-admin SPA follows the **shared MeuMouse design system**, whose reference
implementation is the sibling plugin **Joinotify**. Flexify replicates its
tokens, type scale and spacing, but **keeps its own primary color `#008aff`**
(Joinotify's is `#0088ff`).

**Rule for agents:** when building or restyling admin UI, open the equivalent
Joinotify component first and copy the real values instead of inventing them. Do
not introduce a new type size, radius or spacing that has no counterpart there.

Reference files in the Joinotify repository (local checkout, sibling folder of
this plugin — `wp-content/plugins/joinotify/`):

| Concern | Joinotify reference |
|---------|---------------------|
| Page header | `app/src/components/layout/PageHeader.vue` |
| Settings tabs | `app/src/pages/settings/components/SectionTabs.vue` |
| Label/control row | `app/src/components/fields/FieldRow.vue` |
| Card + sticky action bar | `app/src/pages/settings/SettingsPage.vue`, `components/SettingsActionBar.vue` |
| Buttons / toggles / inputs | `app/src/components/buttons/BaseButton.vue`, `components/toggles/ToggleSwitch.vue`, `components/fields/TextField.vue` |
| Tokens and base layer | `app/tailwind.config.js`, `app/src/styles/main.css` |

#### Tokens

- **Palette**: `primary` (`#008aff`, scale 50–950), `shell` (50–900, the
  blue-gray used for canvas/secondary text), `ink` `#102033`, `success`,
  `danger`, `warning`, `info`, `dark`, `panel`, `muted`. All in
  [`app/tailwind.config.js`](app/tailwind.config.js).
- **Canvas**: plugin screens tint `#wpcontent` with `shell-50` (`#f4f7fb`) — see
  the `:has(.flexify-checkout-settings-page)` rule in
  [`app/src/styles/main.css`](app/src/styles/main.css).
- **Font**: Inter, applied on the app root.
- **Card**: `rounded-[8px] bg-white shadow-[0_1px_0_rgba(0,0,0,0.02)] ring-1
  ring-slate-100`, content padding `px-10 py-12`, sticky action bar
  `px-10 py-6` with `border-t border-black/10 bg-white/80 backdrop-blur-[5px]`.

#### Type and component scale

| Element | Spec |
|---------|------|
| Header eyebrow | `text-xs` / 600 / `uppercase` / `tracking-[0.22em]` / `text-shell-500` |
| Page title | `text-3xl` / `font-semibold` / `tracking-tight` / `text-ink` |
| Page description | `text-sm` / `leading-6` / `text-shell-500` / `max-w-3xl` |
| Settings tab | `min-w-[165px] px-6 py-5 text-[15px]` semibold uppercase; strip is `w-fit` with `bg-[#e7edf5] p-0.5`; active tab `bg-primary text-white` |
| Field label | `text-[15px]` / `font-semibold` / `text-slate-800` |
| Field description | `text-[13px]` / `leading-5` / `text-slate-500` |
| Field row | two columns **420px / 460px**, `py-6`, label and control vertically centered |
| Input | `px-4 py-3`, `text-[14px]`, radius 8px, border `#e2e8f0`, focus = `primary` border + 4px `primary-100` ring |
| Button | `sm px-3 py-2 text-[13px]` · `md px-5 py-3 text-[14px]` · `lg px-6 py-3.5 text-[15px]`, radius 8px |
| Toggle | `md` = track `h-6 w-11` / thumb `h-5 w-5`; `sm` = track `h-5 w-9` / thumb `h-4 w-4` |
| Vertical rhythm | header → tabs `mt-10`; header/tabs → card `mt-8` |

#### Flexify-specific implementation

- [`app/src/components/layout/PageHeader.vue`](app/src/components/layout/PageHeader.vue)
  is the **only** page header — every wp-admin subpage (Settings, License, Apps,
  Offers, Carts, Queue, Analytics) uses it. It renders the eyebrow, brand mark,
  title and description, and exposes the slots `icon`, `badge` (Pro pill),
  `description` and `actions`. It carries **no bottom margin**: the page adds
  `mt-8` to the block that follows.
- Settings fields are laid out as a real `<table>` (`<colgroup>` with a 444px
  first column + `table-fixed`), not a per-row grid, so the columns line up
  across every row of a tab. `<FieldRow>` renders a `<tr>` — it can only be used
  inside a `<tbody>`. Wide fields, viewports ≤1024px and modal tables collapse
  the cells to blocks (`.flexify-field-row--wide`,
  `.flexify-fields-table--stacked`).
- Cell padding lives in `<style scoped>`, not in Tailwind utilities: the config
  runs with `important: true`, so a `py-6` utility would beat the stacked-layout
  overrides.

---

## 5. Internationalization (i18n)

- Single text domain: **`flexify-checkout-for-woocommerce`**, folder `/languages`.
- The [`languages/`](languages) directory has its **own Node tooling**
  (`package.json` with scripts `pot`, `compile:mo`, `compile:php`, `translate`,
  `translate:ai`). Do not confuse it with the root or `app/` `package.json`.
- Flow: `pot` extracts the template from the code → one `.po` per language →
  `compile:mo` and `compile:php` produce the runtime artifacts WordPress loads
  (`.mo` and `*.l10n.php`).
- AI-assisted translation is optional (`--translate`, requires a key in
  `languages/.env`; default engine OpenAI). **Never** commit `.env`.
- Current languages: `en_US`, `es_ES`, `fr_FR` (+ PT-BR as the base).

---

## 6. Build and release packaging

From the plugin **root**:

```bash
npm run build         # full pipeline (scripts/build.mjs)
npm run build:fast    # no composer, no translations, no npm install (fast iteration)
npm run build:translate  # re-translate .po via AI before compiling
```

[`scripts/build.mjs`](scripts/build.mjs) does, in order:

1. `composer install --no-dev --optimize-autoloader` in `admin/` (production vendor).
2. Compile translations (`languages/` → `.pot/.po/.mo/.l10n.php`).
3. Stage only runtime files into `release/flexify-checkout-for-woocommerce/`.
4. Produce the ZIP `release/flexify-checkout-for-woocommerce-<version>.zip`
   (nested under the slug folder, as WordPress expects) + `manifest.json`.

Useful flags: `--skip-composer`, `--skip-translations`, `--no-install`,
`--no-zip`, `--translate`, `--engine=<name>`. The ZIP version is read from the
`Version:` header of the plugin's main file.

**What does NOT ship in the release** (denylist): `node_modules`, `.git`, `.env`,
`.DS_Store`, `Thumbs.db`, and the `languages/` Node tooling.

> ⚠️ To package, `app/dist/` must already be built (`cd app && npm run build`).
> `build.mjs` does not run Vite — it only copies `assets/` and PHP. Generate the
> `app/` bundles before packaging.

---

## 7. Composer and autoloader

- `composer.json` lives in **`admin/`**, not at the root. PSR-4:
  `MeuMouse\Flexify_Checkout\` → `admin/src/`.
- The `admin/vendor/` directory (autoloader) **is versioned** — that is why it
  shows up as "modified" in git after regeneration. When adding/renaming/moving
  classes, regenerate the classmap:

  ```bash
  cd admin && composer dump-autoload -o
  ```

  This is required because the `Init` *class registry* scans
  `vendor/composer/autoload_classmap.php`. Forget it and new classes will not be
  auto-instantiated. Typical commit for this:
  `Regenerate autoload classmap and translation catalogs`.

---

## 8. Versioning, changelog, and git

### 8.1. Versioning

- **SemVer** (`MAJOR.MINOR.PATCH`). The version lives in **three places** that
  must stay in sync at release time:
  - `Version:` header and `FLEXIFY_CHECKOUT_PLUGIN_VERSION` constant in
    [`flexify-checkout-for-woocommerce.php`](flexify-checkout-for-woocommerce.php).
  - `version` in `app/package.json`.
  - `version` in the root `package.json` (tooling).

### 8.2. Changelog

Every user-visible change **must** be recorded in
[`changelogs.md`](changelogs.md), in **PT-BR**, using the existing format:

```
Versão X.Y.Z (DD/MM/AAAA)
* Segurança
  - ...
* Correção de problemas
  - ...
* Recurso adicionado: ...
* Otimizações
  - ...
* Idioma adicionado: ...
```

Keep the newest entries at the top.

### 8.3. Commits

- Main branch: `main`. Work in a feature branch (e.g. `update-6.0.0`).
- Commit messages in the **imperative mood, in English**, short and descriptive
  — follow the existing history:
  - `Add a theme panel to the checkout builder and fix the live preview`
  - `Scope primary-color button styles to legacy checkout templates`
  - `Regenerate autoload classmap and translation catalogs`
- Split generated bundle/autoload changes into their own commits when it makes
  sense (e.g. `Rebuild admin and checkout bundles`).
- Only commit/push when explicitly asked.

---

## 9. Checklist before opening a change

- [ ] PHP follows PSR-4, has docblocks with `@since`/`@version`, escaping and
      sanitization, and the `defined('ABSPATH') || exit;` guard.
- [ ] Code and comments are written in English; user strings use the correct text
      domain and go through `__()`/`esc_*`.
- [ ] Sensitive REST/AJAX endpoints have nonce + capability check.
- [ ] New auto-instantiable class: constructor with no required args **and**
      `composer dump-autoload -o` run (or manual registration in `Init`).
- [ ] Changed the frontend? Ran the matching Vite build (`app/ npm run build`).
- [ ] New admin UI reuses `<PageHeader>` and the design-system scale from §4.1 —
      no new type sizes, radii or spacings invented.
- [ ] New hooks follow the `Flexify_Checkout/Area/Name` pattern and are documented.
- [ ] `changelogs.md` updated in PT-BR.
- [ ] Version synced across the relevant files (if this is a release).
- [ ] No secrets (`.env`, keys) or stray `node_modules`/`dist` committed.

---

## 10. Notes specifically for AI agents

- **Do not rewrite existing style** (spacing, `array()` vs `[]`, quotes). Match
  the neighboring file.
- **Backend ≠ frontend**: business logic belongs in `admin/src/` (PHP).
  `app/src/` is UI only. Do not duplicate business rules in JS.
- **Admin UI is not free-form**: it follows the shared design system (§4.1). Read
  the Joinotify reference component before styling a new screen, and keep the
  primary color `#008aff`.
- The **Composer vendor is versioned** and the **Vite dist is not** — mind what
  you commit.
- Before claiming something exists (constant, hook, helper, class), **verify it
  in the code** — this project evolved a lot between 5.x and 6.0 and there are
  backward-compat shims in `Compatibility/`.
- When adding a class that must boot, remember the *class registry* (§3.1) and
  `dump-autoload` (§7).
- User docs ([README.md](README.md)) and the changelog are in **PT-BR**; code,
  code comments, and commit messages are in **English** (see §0).

---

Product/support questions: <https://meumouse.com/plugins/flexify-checkout-para-woocommerce/>.
Documentation: <https://ajuda.meumouse.com/docs/flexify-checkout-for-woocommerce/overview>.
