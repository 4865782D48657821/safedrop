# Implementation Rules

## File Changes

- Preserve unrelated user changes.
- Respect existing `AGENTS.md` and repository conventions.
- Integrate existing Nix configuration incrementally.
- Retain alternative development workflows unless removal is requested.
- Use `apply_patch` for manual changes.
- Avoid commits, pushes, staging, and external writes unless requested and approved.

## Nix Safety

- Avoid global Nix configuration changes.
- Avoid garbage collection and destructive store operations.
- Avoid changing trusted substituters, public keys, or cache trust without explicit approval.
- Generate `flake.lock` using Nix rather than editing it manually.
- Update only necessary Flake inputs.
- Avoid `--impure` unless impurity is an explicit, justified requirement.
- Avoid staging files merely to make Git-backed Flake evaluation include them.
- When newly created files are untracked, use `path:.` for initial validation and explain that this evaluates the working tree without requiring Git staging.

## Direnv

- If direnv is approved, use the minimal `.envrc` content:

```sh
use flake
```

- Do not execute `direnv allow`; tell the user it is a trust action they must run.

## Language Dependency Ownership

- Do not migrate application dependencies into Nix automatically.
- Preserve JavaScript package-manager lockfiles as authoritative.
- Preserve Python dependency lockfiles as authoritative.
- Preserve `Cargo.lock` as authoritative for Rust crates.
- Preserve `go.mod` and `go.sum` as authoritative for Go modules.
- Detect and respect other package ecosystems.
- Use Nix primarily for runtimes, compilers, native libraries, CLIs, and shared tooling unless full Nix packaging is explicitly requested.

## Repair Mode

- Diagnose the concrete failure before editing.
- Make the smallest defensible correction.
- Do not replace an entire Flake when a targeted platform condition, package name, input, or output correction solves the issue.
