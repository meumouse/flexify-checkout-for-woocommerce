# CLAUDE.md

All engineering guidelines for this project live in **[AGENTS.md](AGENTS.md)**.

Read [AGENTS.md](AGENTS.md) before making any change. It covers the repository
layout, PHP/backend conventions, the three Vite frontend bundles, i18n, build and
release packaging, the Composer autoloader/class-registry workflow, versioning,
the changelog rules, and the pre-change checklist.

Key rules to remember:

- **All source code, comments, docblocks, and commit messages are written in
  English (`en_US`).** Only the user-facing docs (`README.md`) and
  `changelogs.md` are in PT-BR. See §0 of [AGENTS.md](AGENTS.md).
- Backend logic goes in `admin/src/` (PHP, PSR-4); `app/src/` is UI only.
- The Composer `admin/vendor/` autoloader is versioned; `app/dist/` bundles are
  not. Run `composer dump-autoload -o` after adding/moving classes.
- Only commit or push when explicitly asked.
