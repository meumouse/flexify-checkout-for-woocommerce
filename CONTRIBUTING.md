# Guia de contribuição — Flexify Checkout para WooCommerce

Este documento descreve a organização do projeto, as convenções de código e o
fluxo de desenvolvimento. Ele serve tanto para desenvolvedores humanos quanto
para agentes de IA que forem trabalhar no código. **Leia este arquivo antes de
abrir qualquer alteração.**

> Software proprietário da MEUMOUSE.COM® — SOLUÇÕES DIGITAIS LTDA.
> Consulte [license.md](license.md). Não distribua nem copie fora dos termos.

---

## 1. Visão geral

O Flexify Checkout é um plugin WordPress/WooCommerce que substitui o checkout
padrão por uma finalização de compra em múltiplas etapas, otimizada para
conversão. O projeto tem **dois mundos** que convivem no mesmo repositório:

| Camada | Tecnologia | Onde mora | O que é |
|--------|-----------|-----------|---------|
| **Backend** | PHP 7.4+ (PSR-4, namespaces) | `admin/src/` | Toda a lógica do plugin: hooks, REST, integrações, checkout, webhooks |
| **Frontend admin** | Vue 3 + Pinia + Vue Router | `app/src/` | SPA das telas de configuração no wp-admin |
| **Frontend checkout (React)** | React 18 | `app/src/checkout-react/` | Checkout opcional renderizado em React |
| **Frontend checkout (legado)** | JS/Vue + templates PHP | `app/src/checkout/` + `templates/` + `woocommerce/` | Checkout multi-etapas clássico baseado em templates do Woo |

Requisitos mínimos de runtime: **WordPress 6.0+, WooCommerce 6.0+, PHP 7.4+**.

---

## 2. Estrutura de diretórios

```
flexify-checkout-for-woocommerce/
├── flexify-checkout-for-woocommerce.php   # Entrypoint: define constantes e chama Core\Init::bootstrap()
├── README.md                              # Documentação do usuário (PT-BR)
├── CONTRIBUTING.md                        # Este arquivo
├── changelogs.md                          # Histórico de versões (PT-BR) — SEMPRE atualizar
├── license.md                             # Licença proprietária
│
├── admin/                                 # ── BACKEND PHP ──
│   ├── composer.json                      # PSR-4: MeuMouse\Flexify_Checkout\ → src/
│   ├── vendor/                            # Autoloader Composer (versionado; ver §7)
│   └── src/
│       ├── Core/                          # Bootstrap, Assets, Scripts, Ajax, Helpers, Logger, Webhooks
│       ├── Admin/                         # Telas e opções do admin, Settings (Registry/Repository/Stores)
│       ├── API/                           # Licença, Updater, checkout direto, REST de campos
│       ├── Rest/                          # Endpoints REST (namespace flexify-checkout/v1) — 1 classe por rota
│       ├── Checkout/                      # Lógica do checkout: campos, condições, steps, temas, React_Checkout
│       ├── Integrations/                  # ~50 integrações (temas, fretes, gateways, plugins de terceiros)
│       ├── Compatibility/                 # Shims de retrocompatibilidade (hooks/filtros legados)
│       ├── Tracking/                      # Roteador multi-plataforma (GA4, Google Ads, Meta)
│       ├── Cron/                          # Rotinas agendadas
│       ├── Validations/                   # Validações de campos
│       ├── Views/                         # Renderização de telas legadas
│       └── Recovery_Carts/                # Recuperação de carrinhos (feature Pro, antes era addon separado)
│
├── app/                                   # ── FRONTEND (build com Vite) ──
│   ├── package.json                       # Deps de runtime (Vue, React, Pinia, ApexCharts, CodeMirror)
│   ├── vite.config.js                     # Build do admin SPA (Vue) → app/dist/settings/
│   ├── vite.checkout.config.js            # Build do checkout legado → app/dist/checkout/
│   ├── vite.checkout-react.config.js      # Build do checkout React (IIFE) → app/dist/checkout-react/
│   ├── tailwind.config.js / postcss.config.js
│   ├── dist/                              # Bundles compilados (NÃO versionado — ver app/.gitignore)
│   └── src/
│       ├── entries/                       # Entradas de build (settings.js)
│       ├── App.vue, router/, stores/, services/, pages/, components/, styles/, utils/
│       ├── checkout/                      # Checkout legado (JS)
│       └── checkout-react/                # Checkout React (components, context, api, lib)
│
├── assets/                                # Estáticos servidos direto (img, css de temas, vendor JS, JSON)
│   ├── frontend/  admin/  recovery-carts/  vendor/
├── templates/                             # Templates PHP do plugin (template-modern, template-dark, template-react)
├── woocommerce/                           # Overrides de templates do WooCommerce (common/modern/dark)
├── languages/                             # i18n: .pot/.po/.mo/.l10n.php + tooling de tradução (Node)
├── updater/                               # Atualizador do plugin
├── scripts/build.mjs                      # Pipeline de empacotamento da release (.zip)
└── dist/                                  # (saída de release)
```

---

## 3. Arquitetura do backend (PHP)

### 3.1. Bootstrap e ciclo de vida

O entrypoint [`flexify-checkout-for-woocommerce.php`](flexify-checkout-for-woocommerce.php)
faz o mínimo: define `FLEXIFY_CHECKOUT_PLUGIN_VERSION`, carrega o autoloader do
Composer e chama `Core\Init::bootstrap()`. Toda a inicialização real está em
[`admin/src/Core/Init.php`](admin/src/Core/Init.php).

- **Constantes** são definidas em `Init::define_constants()` (prefixo
  `FLEXIFY_CHECKOUT_*`). Caminhos, URLs e metadados. Prefira **helpers** a
  constantes “quentes” (ex.: use `flexify_checkout_is_debug()` em vez de ler a
  constante de debug).
- **Instanciação de classes**: `Init::instance_classes()` instancia
  automaticamente as classes do namespace via um *class registry* cacheado
  (construído a partir do classmap do Composer). Uma classe é instanciada se:
  for instanciável (não abstrata/interface/trait) e o construtor **não exigir
  argumentos**. Se a classe tiver um método `init()`, ele é chamado após a
  construção.
- Classes que precisam rodar antes das demais, ou que o registry não detecta,
  são listadas manualmente no array de `instance_classes()` (filtro
  `Flexify_Checkout/Init/Instance_Classes`).
- **Contexto admin vs. always**: classes em `Views\Settings\` e
  `Admin\Settings\Views\` só carregam no admin. As demais carregam sempre.
- Ao adicionar uma classe nova auto-instanciável, garanta que ela apareça no
  classmap do Composer (ver §7) — caso contrário, registre-a manualmente.

### 3.2. Convenções de código PHP

- **Namespace raiz**: `MeuMouse\Flexify_Checkout\` mapeado para `admin/src/`
  (PSR-4). O nome do arquivo = nome da classe. Pastas com `_` no nome de
  namespace (ex.: `Recovery_Carts`).
- **`defined('ABSPATH') || exit;`** logo após o bloco de `use` em todo arquivo.
- **Docblock obrigatório** em toda classe e método público, com as tags:
  - `@since` (versão em que foi introduzido) e, quando alterado, `@version`
    (versão da última mudança relevante).
  - `@param` / `@return` tipados.
  - `@package MeuMouse\Flexify_Checkout\...` e `@author MeuMouse.com` na classe.
- **Estilo**: indentação consistente com o arquivo vizinho, espaços dentro de
  parênteses no estilo do projeto (`function( $arg )`, `array( ... )`). Use
  `array()` (estilo já presente no código) — não troque o estilo existente.
- **Escape e sanitização**: sempre escape na saída (`esc_attr`, `esc_html`,
  `esc_url`, `wp_kses`) e sanitize na entrada. Endpoints REST e handlers AJAX
  sensíveis **exigem nonce + checagem de capability** (gerenciamento do
  WooCommerce) — ver as notas de segurança da v5.5.5 no [changelogs.md](changelogs.md).
- **i18n**: todas as strings voltadas ao usuário passam por `__()` / `_e()` /
  `esc_html__()` com o text domain `'flexify-checkout-for-woocommerce'`.

### 3.3. Hooks (actions e filters) — nomenclatura

Hooks próprios do plugin usam o prefixo com barras, no padrão já existente:

```
Flexify_Checkout/Before_Init
Flexify_Checkout/Init
Flexify_Checkout/Init/Instance_Classes
Flexify_Checkout/Checkout/Thankyou_Endpoint_Slugs
```

Mantenha esse esquema (`Flexify_Checkout/Area/Nome_Do_Hook`) ao criar novos
hooks. Documente cada novo hook com docblock (`@since`, `@param`).

### 3.4. REST API

- Namespace REST: **`flexify-checkout/v1`** (constante `REST_NAMESPACE` em
  [`admin/src/Rest/Abstract_Route.php`](admin/src/Rest/Abstract_Route.php)).
- **Uma classe por rota**, estendendo `Abstract_Route`. Declara `$route`,
  `$methods`, `$args`; o registro acontece automaticamente em `rest_api_init`
  quando a classe é instanciada pelo `Init`.
- Endpoints novos: adicione `permission_callback` real (nunca `__return_true`
  para operações de escrita), valide/sanitize `$args`.

### 3.5. Integrações

Cada integração com tema/gateway/plugin de terceiro é uma classe isolada em
[`admin/src/Integrations/`](admin/src/Integrations). Padrão: detectar se o
alvo está ativo e só então registrar hooks. Ao adicionar uma nova integração,
siga o formato de uma existente (ex.: `Astra.php`, `Flatsome.php`).

---

## 4. Arquitetura do frontend (Vite)

Há **três bundles independentes**, cada um com seu config Vite:

| Comando (`app/`) | Config | Saída |
|------------------|--------|-------|
| `npm run build:admin` | `vite.config.js` | `app/dist/settings/app.js` + `dist/styles/`, `dist/chunks/` |
| `npm run build:checkout` | `vite.checkout.config.js` | `app/dist/checkout/main.js` |
| `npm run build:checkout-react` | `vite.checkout-react.config.js` | `app/dist/checkout-react/main.{js,css}` (IIFE, React embutido) |
| `npm run build` | todos acima | reconstrói tudo (admin com `--emptyOutDir`) |
| `npm run dev` | `vite.config.js` | servidor de dev do admin |

Notas importantes:

- O **admin SPA** (Vue) é montado pela entrada
  [`app/src/entries/settings.js`](app/src/entries/settings.js). A rota inicial
  vem do `view` exposto pelo WordPress em `flexifyCheckoutBootstrapConfig`, de
  modo que cada submenu do wp-admin (Configurações, Licença, Apps, Analytics,
  Carts, Queue) abre o app na rota certa. Estado global via **Pinia**
  (`app/src/stores/`), HTTP via `app/src/services/api.js`.
- O **checkout React** é compilado como **IIFE** com React embutido e
  enfileirado via `wp_enqueue_script` só quando o modo React está ativo. Como
  é browser, `process.env.NODE_ENV` é substituído em build time (ver o config).
- Os bundles são carregados pelo PHP a partir de `app/dist/...` em
  [`admin/src/Core/Assets.php`](admin/src/Core/Assets.php) e
  [`admin/src/Core/Scripts.php`](admin/src/Core/Scripts.php).
- **`app/dist/` NÃO é versionado** (ver [`app/.gitignore`](app/.gitignore) e o
  `.gitignore` raiz). Quem clona o repo precisa rodar o build. O empacotamento
  de release inclui o `dist/` gerado.
- Assets estáticos do plugin (`assets/`) são servidos **sem minificação** — a
  propriedade `$min_file` em `Core\Assets` fica vazia. Não assuma `.min`.

Estilo do frontend: **Tailwind CSS** (config em `app/tailwind.config.js`),
PostCSS/autoprefixer. Componentes Vue em SFC (`.vue`) organizados por função em
`app/src/components/` (buttons, fields, modals, settings, table, toasts,
toggles, icons).

---

## 5. Internacionalização (i18n)

- Text domain único: **`flexify-checkout-for-woocommerce`**, pasta `/languages`.
- O diretório [`languages/`](languages) tem seu **próprio tooling Node**
  (`package.json` com scripts `pot`, `compile:mo`, `compile:php`, `translate`,
  `translate:ai`). Não confunda com o `package.json` da raiz ou do `app/`.
- Fluxo: `pot` extrai o template a partir do código → `.po` por idioma →
  `compile:mo` e `compile:php` geram os artefatos que o WordPress carrega em
  runtime (`.mo` e `*.l10n.php`).
- Tradução assistida por IA é opcional (`--translate`, precisa de chave em
  `languages/.env`; engine padrão OpenAI). **Nunca** comite `.env`.
- Idiomas atuais: `en_US`, `es_ES`, `fr_FR` (+ PT-BR como base).

---

## 6. Build e empacotamento da release

Da **raiz** do plugin:

```bash
npm run build         # pipeline completo (scripts/build.mjs)
npm run build:fast    # sem composer, sem traduções, sem npm install (iteração rápida)
npm run build:translate  # re-traduz .po via IA antes de compilar
```

[`scripts/build.mjs`](scripts/build.mjs) faz, em ordem:

1. `composer install --no-dev --optimize-autoloader` em `admin/` (vendor de produção).
2. Compila traduções (`languages/` → `.pot/.po/.mo/.l10n.php`).
3. Faz *staging* só dos arquivos de runtime em `release/flexify-checkout-for-woocommerce/`.
4. Gera o ZIP `release/flexify-checkout-for-woocommerce-<versão>.zip` (aninhado sob a pasta do slug, como o WordPress espera) + `manifest.json`.

Flags úteis: `--skip-composer`, `--skip-translations`, `--no-install`,
`--no-zip`, `--translate`, `--engine=<nome>`. A versão do ZIP é lida do header
`Version:` do arquivo principal do plugin.

**O que NÃO entra na release** (denylist): `node_modules`, `.git`, `.env`,
`.DS_Store`, `Thumbs.db`, e o tooling Node de `languages/`.

> ⚠️ Para empacotar, o `app/dist/` precisa estar buildado (`cd app && npm run
> build`). O `build.mjs` não roda o Vite — ele só copia `assets/` e o PHP. Gere
> os bundles do `app/` antes de empacotar.

---

## 7. Composer e autoloader

- O `composer.json` fica em **`admin/`**, não na raiz. PSR-4:
  `MeuMouse\Flexify_Checkout\` → `admin/src/`.
- O diretório `admin/vendor/` (autoloader) **é versionado** — por isso aparece
  como “modificado” no git após regenerar. Ao adicionar/renomear/mover classes,
  regenere o classmap:

  ```bash
  cd admin && composer dump-autoload -o
  ```

  Isso é necessário porque o *class registry* do `Init` escaneia
  `vendor/composer/autoload_classmap.php`. Esqueça disso e classes novas não
  são instanciadas automaticamente. O commit típico para isso:
  `Regenerate autoload classmap and translation catalogs`.

---

## 8. Versionamento, changelog e git

### 8.1. Versionamento

- **SemVer** (`MAJOR.MINOR.PATCH`). A versão vive em **três lugares** que devem
  ficar sincronizados ao lançar:
  - Header `Version:` e constante `FLEXIFY_CHECKOUT_PLUGIN_VERSION` em
    [`flexify-checkout-for-woocommerce.php`](flexify-checkout-for-woocommerce.php).
  - `version` em `app/package.json`.
  - (o `package.json` da raiz tem sua própria versão de tooling — verifique ao lançar.)

### 8.2. Changelog

Toda mudança visível ao usuário **deve** ser registrada em
[`changelogs.md`](changelogs.md), em **PT-BR**, no formato existente:

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

Mantenha as entradas mais recentes no topo.

### 8.3. Commits

- Branch principal: `main`. Trabalhe em branch de feature (ex.: `update-6.0.0`).
- Mensagens de commit no **imperativo, em inglês**, curtas e descritivas — siga
  o histórico existente:
  - `Add a theme panel to the checkout builder and fix the live preview`
  - `Scope primary-color button styles to legacy checkout templates`
  - `Regenerate autoload classmap and translation catalogs`
- Separe mudanças de bundle/autoload geradas em commits próprios quando fizer
  sentido (ex.: `Rebuild admin and checkout bundles`).
- Só faça commit/push quando solicitado.

---

## 9. Checklist antes de abrir uma alteração

- [ ] Código PHP segue PSR-4, tem docblock com `@since`/`@version`, escape e
      sanitização, e o guard `defined('ABSPATH') || exit;`.
- [ ] Strings ao usuário usam o text domain correto e passam por `__()`/`esc_*`.
- [ ] Endpoints REST/AJAX sensíveis têm nonce + checagem de capability.
- [ ] Classe nova auto-instanciável: construtor sem args obrigatórios **e**
      `composer dump-autoload -o` rodado (ou registro manual no `Init`).
- [ ] Mudou frontend? Rodou o build Vite correspondente (`app/ npm run build`).
- [ ] Hooks novos seguem o padrão `Flexify_Checkout/Area/Nome` e estão documentados.
- [ ] `changelogs.md` atualizado em PT-BR.
- [ ] Versão sincronizada nos arquivos relevantes (se for release).
- [ ] Nenhum segredo (`.env`, chaves) ou `node_modules`/`dist` indevido commitado.

---

## 10. Notas específicas para agentes de IA

- **Não reescreva o estilo existente** (espaçamento, `array()` vs `[]`, aspas).
  Combine com o arquivo vizinho.
- **Backend ≠ frontend**: lógica de negócio vai em `admin/src/` (PHP). O
  `app/src/` é só UI. Não duplique regra de negócio no JS.
- O **vendor do Composer é versionado** e o **dist do Vite não é** — atenção ao
  que você comita.
- Antes de afirmar que algo existe (constante, hook, helper, classe),
  **verifique no código** — este projeto evoluiu bastante entre as versões 5.x e
  6.0 e há shims de retrocompatibilidade em `Compatibility/`.
- Ao adicionar uma classe que precisa ser instanciada no boot, lembre do
  *class registry* (§3.1) e do `dump-autoload` (§7).
- A documentação ao usuário ([README.md](README.md)) e o changelog são em
  **PT-BR**; comentários de código e mensagens de commit, em **inglês**.

---

Dúvidas de produto/suporte: <https://meumouse.com/plugins/flexify-checkout-para-woocommerce/>.
Documentação: <https://ajuda.meumouse.com/docs/flexify-checkout-for-woocommerce/overview>.
