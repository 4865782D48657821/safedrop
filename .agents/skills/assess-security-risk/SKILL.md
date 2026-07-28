---
name: assess-security-risk
description: Assesses security risk. Use for authentication, authorization, inputs, secrets, trust boundaries, sensitive data, cryptography, dependency trust, file handling, logging, or security review findings.
---

# Assess Security Risk

## Required Inputs

- Change description, affected assets, trust boundaries, data sensitivity, users or roles, and relevant diff or design.

## Workflow

1. Identify assets, actors, data, and trust boundaries with `references/trust-boundaries.md`.
2. Apply `references/security-review-checklist.md`.
3. Threat-model relevant flows using `references/threat-modeling.md`.
4. Classify confirmed findings with `references/severity-classification.md`.
5. Use `assets/threat-model-template.md` or `assets/security-report-template.md` for handoff.
6. Stop before changing credentials, policies, production data, or security controls without approval.

## Expected Result

Produce confirmed findings, hypotheses, severity, evidence, impact, and concrete mitigation options.
