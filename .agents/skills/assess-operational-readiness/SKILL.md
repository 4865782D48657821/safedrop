---
name: assess-operational-readiness
description: Assesses operational readiness. Use for deployments, migrations, incidents, SLO impact, observability, capacity, cost, backup, restore, recovery, or production support concerns.
---

# Assess Operational Readiness

## Required Inputs

- System context, change or incident scope, environments, SLOs, deployment path, rollback path, and operational constraints.

## Workflow

1. Review observability with `references/observability-checklist.md`.
2. Review service targets with `references/slo-guidelines.md`.
3. Review recovery with `references/backup-and-restore.md`.
4. Review resource impact with `references/capacity-and-cost.md`.
5. Use `assets/operational-readiness-template.md` for structured findings.
6. Stop before production changes unless human approval is explicit.

## Expected Result

Produce readiness status, operational gaps, rollback and recovery evidence, monitoring plan, and approval needs.
