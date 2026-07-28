---
name: design-test-strategy
description: Designs a test strategy for software changes. Use for new features, high-risk changes, defect fixes, migrations, external integrations, or when existing tests do not clearly cover the requested behavior.
---

# Design Test Strategy

## Required Inputs

- Requirements, risk class, affected components, existing tests, and available tooling.

## Workflow

1. Select test levels using `references/test-levels.md`.
2. Prioritize coverage with `references/risk-based-testing.md`.
3. Check feasibility with `references/testability.md`.
4. Use `assets/test-strategy-template.md` when the plan must be shared.
5. Stop if behavior cannot be observed or controlled enough to verify safely.

## Expected Result

Produce a focused test plan with test levels, fixtures, command targets, data needs, and explicit gaps.
