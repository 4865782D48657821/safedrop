# Escalation Rules

Require human approval before:

- Deployment or external write operations.
- Production data reads beyond approved diagnostics.
- Production data writes, migrations, rollbacks, or infrastructure changes.
- Destructive commands or irreversible transformations.
- Credential, secret, access-policy, or permission changes.
- Disabling security, test, audit, monitoring, backup, or recovery safeguards.

Stop and ask when approval status is missing or ambiguous.
