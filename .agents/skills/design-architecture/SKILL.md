---
name: design-architecture
description: Designs or reviews software architecture. Use for new services, cross-module changes, data models, integrations, scalability, reliability, security trade-offs, or quality-attribute decisions before implementation.
---

# Design Architecture

## Required Inputs

- Requirements, existing architecture, constraints, quality attributes, integrations, and risk class.

## Workflow

1. Inspect current architecture and dependency boundaries.
2. Load `references/quality-attributes.md` for relevant attributes only.
3. Use `references/system-design-patterns.md` to compare viable patterns.
4. Review with `references/architecture-review-checklist.md`.
5. Document options and trade-offs using `assets/architecture-outline-template.md`.
6. Use `assets/context-diagram-template.md` when boundaries or actors are unclear.

## Expected Result

Recommend an architecture with trade-offs, risks, rejected alternatives, verification approach, and implementation implications.
