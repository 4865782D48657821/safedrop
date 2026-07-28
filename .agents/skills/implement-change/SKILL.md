---
name: implement-change
description: Implements scoped software changes. Use when requirements are clear enough to edit code, tests, configuration, or documentation while preserving existing user work and repository conventions.
---

# Implement Change

## Required Inputs

- Clear scope, affected behavior, risk class, repository conventions, and verification commands.

## Workflow

1. Read relevant code, tests, and rules before editing.
2. Apply `references/change-scope-rules.md` to avoid unrelated churn.
3. Use `references/implementation-principles.md` for edit strategy.
4. Use `references/defensive-coding.md` when handling inputs, errors, persistence, or external systems.
5. Add or update focused tests and documentation proportional to risk.
6. Run relevant verification and record results.
7. Stop before production actions, destructive commands, or unclear high-risk assumptions.

## Expected Result

Produce a minimal, verified change with clear evidence and no unrelated file modifications.
