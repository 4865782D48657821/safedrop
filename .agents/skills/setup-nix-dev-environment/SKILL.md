---
name: setup-nix-dev-environment
description: Inspect a software repository and interactively design, create, extend, repair, or review a reproducible Nix Flake development environment. Use when the user asks to add or configure flake.nix, flake.lock, nix develop, devShells, direnv with Nix, cross-platform Nix development tooling, Nix-based CI validation, or when diagnosing an existing repository development Flake. Do not use for NixOS host configuration, Home Manager configuration, package publishing alone, or unrelated Nix language questions.
---

# Setup Nix Dev Environment

Guide Codex through interactive Nix Flake development-environment work. Keep the workflow lightweight: inspect first, ask only material unresolved questions, propose a design or diagnosis, obtain required confirmation, then implement or review incrementally.

## Required Inputs

- Current user request.
- Repository files and instructions.
- Existing Nix, direnv, CI, package-manager, build, test, and setup evidence.
- User confirmation before creating a new design or materially restructuring an existing Flake.

## Mode Selection

- Select Create when no Flake exists and the request asks for a Nix development environment.
- Select Extend when a Flake exists and the request adds tools, systems, checks, outputs, CI, direnv, shells, or integrations.
- Select Repair when evaluation, `nix develop`, CI, platform support, lockfile, or shell behavior is broken.
- Select Review when the user asks for assessment or advice without changes. Stay read-only unless the user later asks for edits.
- State the selected mode and evidence. If multiple modes apply, choose the smallest mode that satisfies the request.

## Workflow

1. Inspect the repository using `references/repository-discovery.md`. Do not run installs, builds, migrations, or service starts merely to learn structure.
2. Select the operating mode.
3. Ask a focused interview only for unresolved material choices using `references/setup-interview.md`. Ask one small group of related questions at a time, explain why each choice matters, and offer a recommended default when evidence supports it.
4. Propose the environment design or diagnosis using `references/flake-design.md`.
5. Request confirmation before implementing a new Flake design, introducing optional integrations, changing cache trust, adding CI, adding service orchestration, or materially restructuring existing Nix files.
6. Implement or review incrementally using `references/implementation-rules.md`. For review mode, do not modify files.
7. Apply platform rules from `references/platform-support.md` whenever supported systems or platform-specific dependencies matter.
8. Apply `references/secrets-and-purity.md` whenever secrets, caches, environment variables, impurity, credentials, or host state are involved.
9. Validate with `references/validation.md`.
10. Hand over using `assets/completion-report-template.md` selectively. Omit empty or irrelevant sections.

## Decision Points

- Proceed directly to a concise proposal when repository evidence and the user request resolve essential choices.
- Stop before making a materially consequential unsupported assumption about supported systems, Nixpkgs policy, package ownership, secrets, binary caches, CI, services, migrations, or restructuring.
- Prefer a minimal Flake and application-package-manager ownership unless evidence justifies deeper Nix packaging.
- Use current official Nix sources from `https://nix.dev/` or `https://nixos.org/manual/nix/stable/` when installation steps, command behavior, syntax, or experimental-feature status matters.

## Safety Boundaries

- Require explicit approval before installing or reconfiguring Nix, changing global Nix configuration, changing trusted substituters or public keys, enabling organization-wide binary caches, staging or committing files, performing uncertain large downloads, introducing a Flake framework, restructuring complex Nix setup, adding CI workflows, adding service orchestration, running migrations, or handling real secret values.
- Prohibit destructive Nix store operations, garbage collection as part of setup, secret exposure, unauthorized deployments, unrelated file changes, and claims of validation that were not performed.
- Do not execute `direnv allow`; present it as a user trust action.
- Do not use subagents for ordinary setups. Delegate only for concrete value such as large monorepo discovery, independent review of a complex Flake, materially different Linux and Darwin concerns, or security-sensitive cache, secret, or CI configuration.

## Completion Criteria

- Report facts, inferences, assumptions, and open questions separately.
- Report files created or modified, systems supported and validated, Nixpkgs policy, shell tools, project package-manager responsibilities, commands run, skipped checks, manual steps, limitations, and residual risks.
- Do not claim reproducibility, portability, or cross-platform support beyond the evidence collected.
