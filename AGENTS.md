# Codex Agency Operating Rules

These rules apply repository-wide. The lead agent owns the user request, shared context, orchestration decisions, and consolidated completion report. Specialist agents and skills support the lead; they do not replace ownership.

## Concepts

- Skills are executable procedures stored in `.agents/skills/`. Activate them when their frontmatter description matches the task.
- Agent roles are Codex subagents stored in `.codex/agents/`. Use them for bounded work with explicit inputs and expected outputs.
- Orchestration is the lead agent's risk-based selection of skills, agents, verification, and approval gates.

## Discovery

- Inspect existing repository structure, commands, conventions, tests, deployment configuration, and relevant `AGENTS.md` files before changing code.
- Preserve user changes. Never revert, overwrite, or reformat unrelated files. If required work conflicts with existing changes, stop and report the conflict.
- Prefer repository-local tooling and conventions over new dependencies.

## Risk Classes

- Low: documentation, comments, small non-runtime configuration, or isolated tests with no behavior, data, security, deployment, or API impact.
- Medium: localized behavior changes, new features behind existing patterns, dependency updates with bounded blast radius, or user-visible UI/API changes.
- High: authentication, authorization, cryptography, payments, secrets, data model changes, migrations, concurrency, cross-service contracts, broad refactors, or changes affecting regulated or sensitive data.
- Production-critical: live incident response, deployment or rollback actions, irreversible migrations, production data changes, infrastructure changes, SLO-impacting work, or anything requiring credentials for live systems.

## Delegation Rules

Delegate only when at least one condition applies: independent review is required, context separation improves quality, specialized permissions are needed, work can be meaningfully parallelized, or risk justifies a specialist perspective. Do not delegate merely because a role exists.

- Low risk: lead agent may execute with relevant skills and self-review.
- Medium risk: implementation must be separated from independent review.
- High risk: use architecture, implementation, review, and relevant security or operations review.
- Production-critical: require operations review, rollback evidence, and an explicit human approval gate before production action.

## Quality Gates

- Identify required inputs, assumptions, open questions, and stopping conditions before implementation.
- Keep changes minimal and aligned with existing architecture.
- Add or update tests and documentation proportional to risk.
- Run the smallest meaningful verification set first, then broaden for shared or high-risk changes.
- Do not mark work complete with failing relevant checks unless the failure is unrelated and documented with evidence.

## Evidence Requirements

Completion reports must state changed files, verification commands and results, known unverified areas, residual risks, and any approvals requested or received. For defects, include reproduction evidence and root cause. For security and operations work, classify risks and cite concrete code, configuration, or command evidence.

## Review Requirements

Independent reviewers must work from the final diff and repository context, not from the implementer's reasoning alone. Findings must be evidence-based, severity-classified when applicable, and focused on correctness, regressions, tests, security, maintainability, and operational risk. Style-only comments are out of scope unless they affect behavior or maintainability.

## Security Triggers

Activate security review for authentication, authorization, session handling, secrets, encryption, input validation, file upload/download, deserialization, dependency trust, data exposure, multi-tenancy, audit logging, payments, or sensitive personal data.

## Operations Triggers

Activate operations review for deployments, infrastructure, migrations, rollback, observability, SLOs, capacity, cost, backup/restore, incident response, production defects, queues, scheduled jobs, or external service dependencies.

## Human Approval Gates

Human approval is required before deployments, production writes, irreversible migrations, credential or secret changes, destructive commands, external write operations, disabling safeguards, or any action with unclear production impact. Unauthorized deployments, irreversible migrations, and production changes are prohibited.

## Uncertainty

Ask concise questions when missing requirements materially affect safety, architecture, data, security, or user-visible behavior. If a reasonable low-risk assumption exists, state it and proceed. If instructions conflict, follow the most specific higher-priority instruction and report unresolved conflicts before changing affected files.

## Completion Reports

Keep reports concise. Cover what changed, why the chosen workflow was sufficient for the risk, verification performed, forward or independent reviews performed, residual risks, and practical next prompts or actions.
