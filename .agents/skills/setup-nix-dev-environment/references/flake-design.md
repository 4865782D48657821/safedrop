# Flake Design

Prepare a design proposal before implementation.

## Required Proposal Fields

1. Detected stack.
2. Operating mode.
3. Supported systems.
4. Nixpkgs policy.
5. Packages included in the shell and why.
6. Responsibilities retained by application package managers.
7. Proposed Flake outputs.
8. Optional integrations.
9. Files to create or modify.
10. Validation commands.
11. Limitations and unresolved risks.

## Structure

- Prefer a minimal structure: `flake.nix`, `flake.lock`, and `.envrc` when direnv is approved.
- Introduce modules such as `nix/devshell.nix`, `nix/packages.nix`, or `nix/checks.nix` only when complexity provides a concrete reason.
- Prefer a small local systems mapping such as `builtins.genAttrs` when it avoids an unnecessary dependency.
- Do not introduce `flake-utils`, `flake-parts`, devenv, or another framework merely for convenience. Use one only when existing repository conventions or demonstrated complexity justify it and the user confirms.

## Shells and Outputs

- Use `pkgs.mkShell` when a compiler toolchain or build environment is required.
- Use `pkgs.mkShellNoCC` when no compiler toolchain is required.
- Include only tools with repository-specific reasons.
- Keep `shellHook` short and transparent.
- Omit `shellHook` when it would only print instructions, banners, or reminders that belong in documentation or the completion report.
- Do not hide installation, migration, network access, secret loading, or service startup inside `shellHook`.
- Add `formatter`, `checks`, `packages`, or `apps` outputs only when repository evidence or user goals justify them.
