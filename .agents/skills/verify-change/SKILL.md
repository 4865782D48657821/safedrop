---
name: verify-change
description: Verifies software changes and gathers evidence. Use after implementation, before completion reports, for review handoff, or when commands, changed files, and residual risks must be recorded without modifying files.
---

# Verify Change

## Required Inputs

- Changed files, relevant commands, risk class, and expected behavior.

## Workflow

1. Choose checks from `references/verification-ladder.md`.
2. Apply evidence standards from `references/evidence-requirements.md`.
3. Run repository commands directly or use `scripts/collect-verification-evidence.sh` to bundle status, changed files, and command results.
4. Preserve exit codes and report failures honestly.
5. Stop if verification would require production mutation or missing approval.

## Expected Result

Produce command results, changed-file evidence, failed checks, unverified areas, and residual risk.
