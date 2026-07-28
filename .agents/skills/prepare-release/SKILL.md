---
name: prepare-release
description: Prepares release plans and release notes. Use for deployments, release readiness, versioned delivery, rollout planning, rollback planning, stakeholder communication, or production change preparation.
---

# Prepare Release

## Required Inputs

- Change set, target environment, rollout method, verification evidence, rollback path, and approval requirements.

## Workflow

1. Check readiness with `references/release-readiness.md`.
2. Select rollout approach using `references/deployment-strategies.md`.
3. Verify rollback needs with `references/rollback-requirements.md`.
4. Use `assets/release-plan-template.md` and `assets/release-notes-template.md` when a written handoff is required.
5. Stop before deployment or external writes without explicit approval.

## Expected Result

Produce release readiness status, rollout plan, rollback plan, approvals, monitoring, and release notes.
