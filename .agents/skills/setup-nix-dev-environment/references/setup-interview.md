# Setup Interview

Ask only about unresolved material choices. Ask one small group of closely related questions at a time. Explain why the decision matters and offer a recommended default when repository evidence supports one.

## Supported Systems

- Consider `x86_64-linux`, `aarch64-linux`, `x86_64-darwin`, and `aarch64-darwin`.
- Recommend only systems that are genuinely required by users, CI, or repository evidence.
- Do not recommend the common four-system set by default when there is no evidence that all four systems are needed.
- When targets are unknown, recommend the current developer platform plus any CI platform discovered from the repository, and ask whether additional systems are required.
- Do not claim cross-platform support merely because the Flake evaluates.
- Ask for target systems when repository evidence does not establish them and platform support affects package choices.

## Nix Installation

- Determine when relevant whether Nix is installed, the installed Nix version, whether `flakes` and `nix-command` are enabled, whether installation type matters, and whether the user can modify Nix configuration.
- Do not install or reconfigure Nix automatically.
- If Nix is unavailable or features are disabled, consult current official documentation from `https://nix.dev/` or `https://nixos.org/manual/nix/stable/`, provide minimal guidance, and wait for the user to complete or authorize setup.

## Development Workflow

- Clarify unresolved primary development command, build command, tests, linting, formatting, type checking, background services, Docker or external runtimes, native libraries, and application dependency ownership.
- Prefer this boundary unless evidence justifies another design: Nix supplies language runtimes, compilers, native libraries, CLIs, and system tools; project package managers supply application-level dependencies; existing application lockfiles remain authoritative.

## Optional Integrations

- Ask only when relevant about `direnv`, `nix-direnv`, CI validation, `nix fmt`, package outputs, app outputs, pre-commit tooling, binary caches such as Cachix, local service orchestration, and multiple named development shells.
- Do not add optional integrations without confirmation.

## Version Policy

- Resolve whether Nixpkgs should use a stable release, `nixos-unstable`, an organization-provided input, or another explicit policy.
- Treat `flake.lock` as the source of pinned revisions.
- Do not hard-code stable or unstable as universally correct. Base the recommendation on project compatibility, freshness needs, CI policy, and existing organization conventions.
