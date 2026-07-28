---
name: review-code-change
description: Reviews code changes independently. Use for medium-risk and higher changes, pull-request style review, regression analysis, missing tests, security-sensitive diffs, or maintainability review.
---

# Review Code Change

## Required Inputs

- Final diff or changed files, request context, risk class, and verification evidence.

## Workflow

1. Review the diff independently from implementation notes.
2. Apply `references/review-checklist.md`.
3. Classify findings with `references/severity-classification.md`.
4. Require evidence from `references/review-evidence.md` before reporting a finding.
5. Use `assets/review-report-template.md` for structured reports.
6. Stop if the diff or request context is unavailable.

## Expected Result

Report evidence-based findings first, ordered by severity, with concise residual risk and test gaps.
