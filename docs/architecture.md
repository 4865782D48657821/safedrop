# Safedrop MVP Architecture

## Decision

Start as a Laravel modular monolith with SQLite for local development and a relational database boundary that can move to PostgreSQL or MySQL later.

## Rationale

The MVP needs strong local consistency across projects, releases, external targets, moderation status, roles, and monetization eligibility. Deployment independence is not yet valuable enough to justify service boundaries.

## Initial Boundaries

- Discovery: public browsing, project pages, game and project type filters.
- Publishing: project metadata, releases, changelogs, external targets.
- Safety: URL review status, redirect preview, reports, moderation status.
- Accounts: roles and age groups, with monetization gated server-side.
- Monetization: modelled later; junior creator ad and payout restrictions are a hard invariant.

## Non-Goals

- File hosting in the MVP.
- Automated ad buying.
- Production authentication and identity verification in the first scaffold.
- External URL crawler implementation in the first scaffold.

## Verification Strategy

- Feature tests for public discovery and safe redirect preview.
- Migration tests once database-backed project flows replace seed config.
- Policy tests before adding creator publishing, moderation, or monetization workflows.
