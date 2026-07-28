---
name: document-software
description: Creates or updates software documentation. Use for API docs, developer guides, operations guides, architecture notes, runbooks, behavior changes, onboarding material, or documentation-only requests.
---

# Document Software

## Required Inputs

- Audience, subject, source of truth, changed behavior, and desired documentation type.

## Workflow

1. Choose documentation type with `references/documentation-types.md`.
2. Check quality using `references/documentation-quality.md`.
3. Use an asset template only when it matches the requested artifact: `assets/api-documentation-template.md`, `assets/operations-guide-template.md`, or `assets/developer-guide-template.md`.
4. Verify documentation against code, commands, or configuration.
5. Stop if the requested documentation would invent unsupported behavior.

## Expected Result

Produce concise, accurate documentation with facts, assumptions, verification source, and maintenance owner when known.
