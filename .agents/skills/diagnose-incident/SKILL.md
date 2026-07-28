---
name: diagnose-incident
description: Diagnoses incidents safely. Use for production defects, outages, degraded service, data integrity incidents, alert response, stabilization, incident logs, communication, or postmortem preparation.
---

# Diagnose Incident

## Required Inputs

- Symptom, impact, time window, affected systems, available telemetry, current mitigations, and approval boundaries.

## Workflow

1. Classify severity using `references/incident-severity.md`.
2. Stabilize first using `references/incident-workflow.md`.
3. Communicate with `references/communication-guidelines.md`.
4. Use `assets/incident-log-template.md` during response.
5. Use `assets/postmortem-template.md` after stabilization.
6. Stop before production mutation unless approval is explicit.

## Expected Result

Produce severity, impact, timeline, evidence, mitigations, approval needs, and follow-up prevention.
