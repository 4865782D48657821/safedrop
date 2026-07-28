---
name: deliver-software-change
description: Orchestrates end-to-end software delivery. Use for feature work, defects, refactors, documentation changes, migrations, releases, or any request requiring risk classification, skill selection, delegation, verification, and a final delivery summary.
---

# Deliver Software Change

Use this as the central workflow for repository changes.

## Required Inputs

- User request and constraints.
- Repository context, existing rules, commands, tests, and conventions.
- Current workspace status and known user changes.
- Production, data, security, and deployment impact if relevant.

## Workflow

1. Inspect repository rules and conventions before editing.
2. Identify missing requirements and resolve only material ambiguity.
3. Classify risk using `references/risk-classification.md`.
4. Select the smallest sufficient workflow from `references/delivery-workflow.md`.
5. Activate task-specific skills only when their trigger matches the request.
6. Delegate only when justified by risk, independence, specialization, or parallelizable work.
7. Separate implementation from review for medium-risk and higher changes.
8. Apply human approval gates from `references/escalation-rules.md`.
9. Collect verification evidence. Use `../verify-change/scripts/collect-verification-evidence.sh` when command evidence must be bundled.
10. Produce a delivery summary using `assets/delivery-summary-template.md` when a structured report is useful.

## Decision Points

- Stop before implementation if approval is required, instructions conflict, or missing requirements materially affect safety.
- Use self-review only for low-risk changes.
- Use reviewer for medium-risk and above.
- Use architect for broad design, data model, cross-service, or quality-attribute trade-offs.
- Use security-reviewer for security triggers.
- Use operations for production, deployment, migration, incident, SLO, backup, capacity, or rollback triggers.

## Expected Result

Deliver a working change or a clearly blocked outcome with evidence, changed files, verification results, approvals, and residual risks.
