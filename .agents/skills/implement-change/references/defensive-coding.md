# Defensive Coding

- Validate untrusted inputs at boundaries.
- Handle null, empty, malformed, duplicate, stale, and concurrent states.
- Preserve idempotency for retries and background jobs.
- Avoid logging secrets, tokens, credentials, or sensitive personal data.
- Include tests for failure paths when they are part of the risk.
