# Risk Classification

Classify by the highest applicable risk.

| Risk | Indicators | Minimum handling |
| --- | --- | --- |
| Low | Documentation, comments, isolated tests, non-runtime metadata | Lead agent, relevant skill, self-review |
| Medium | Local behavior change, user-visible feature, bounded dependency update, API/UI adjustment | Implementer plus independent reviewer |
| High | Auth, authorization, secrets, sensitive data, migrations, payments, cryptography, concurrency, broad refactor, cross-service contract | Architect, implementer, reviewer, relevant specialist |
| Production-critical | Live incident, production data write, deployment, rollback, irreversible migration, infrastructure or SLO-impacting action | High-risk workflow plus operations review and human approval |

Escalate when blast radius, reversibility, or evidence is unclear.
