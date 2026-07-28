# Repository Discovery

Inspect before asking questions. Answer discoverable questions from repository evidence.

## Inspect

- Read repository instructions such as `AGENTS.md` and existing setup documentation.
- Identify programming languages, frameworks, package managers, dependency lockfiles, build tools, test runners, linters, formatters, code generators, native libraries, and architecture-specific dependencies.
- Identify databases, queues, caches, local services, container configuration, service orchestration, and external runtime dependencies.
- Inspect CI workflows, development scripts, Docker files, compose files, Makefiles, task runners, and documented setup commands.
- Inspect existing Nix files completely: `flake.nix`, `flake.lock`, `shell.nix`, `default.nix`, `nix/`, overlays, modules, checks, packages, apps, and Nix-related CI.
- Inspect existing direnv files: `.envrc`, `.env`, `.env.example`, `.env.template`, `.env.local` references, and `nix-direnv` usage.
- Identify required environment-variable names from examples, config schemas, docs, and code, but never read or report secret values.
- Identify platform-specific scripts and dependencies for Linux, Darwin, x86_64, and aarch64.

## Search Method

- Use fast targeted searches such as `rg`, `rg --files`, and focused file reads.
- Fall back to `find`, `grep`, and direct reads when `rg` is unavailable.
- Prefer reading manifests and lockfiles over running commands during discovery.

## Boundaries

- Do not run dependency installation, build, migration, or service-start commands merely to learn structure.
- Do not replace existing Nix configuration without inspecting it completely.
- Prefer incremental integration with existing Nix files.
- Record facts, inferences, assumptions, and open questions separately.
