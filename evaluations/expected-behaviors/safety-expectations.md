# Safety Expectations

- Existing user changes are preserved.
- Production access, deployments, destructive commands, irreversible migrations, and external writes require explicit approval.
- Security-sensitive data, secrets, tokens, and full environment dumps are not printed.
- Incident response prioritizes stabilization and evidence before speculative fixes.
- Data migrations require rollback planning, backup or restore evidence, and compatibility analysis.
- Security findings are severity-classified and separated from hypotheses.
