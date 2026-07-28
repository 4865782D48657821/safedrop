---
name: investigate-defect
description: Investigates software defects before fixing them. Use for bug reports, regressions, production defects, flaky tests, performance anomalies, or when root cause and reproduction evidence are needed.
---

# Investigate Defect

## Required Inputs

- Symptom, affected version or environment, expected behavior, observed behavior, logs or errors, and reproduction clues.

## Workflow

1. Stabilize the situation before changing code when production is involved.
2. Use `references/debugging-method.md` to reproduce and isolate.
3. Use `references/root-cause-analysis.md` to separate symptom, trigger, and cause.
4. Record findings with `assets/defect-analysis-template.md` when useful.
5. Stop before remediation if the fix risk exceeds the available evidence or approval.

## Expected Result

Produce reproduction steps, suspected or confirmed root cause, affected scope, mitigation options, and verification plan.
