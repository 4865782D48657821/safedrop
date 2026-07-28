---
name: plan-implementation
description: Plans implementation work. Use for multi-file changes, sequenced refactors, dependency-heavy work, risky edits, or when implementation needs explicit steps, verification, ownership, and rollback notes.
---

# Plan Implementation

## Required Inputs

- Requirements, risk class, affected areas, constraints, and available verification commands.

## Workflow

1. Inspect relevant code and tests before planning.
2. Use `references/dependency-analysis.md` to find ordering and coupling.
3. Apply `references/planning-guidelines.md` to keep steps small and verifiable.
4. Capture plan with `assets/implementation-plan-template.md` when useful.
5. Stop if dependencies or ownership boundaries are unclear enough to risk rework.

## Expected Result

Produce a sequenced plan with affected files, dependencies, verification, rollback notes, and open questions.
