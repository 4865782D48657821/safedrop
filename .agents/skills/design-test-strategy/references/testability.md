# Testability

- Prefer deterministic tests with controlled time, network, randomness, and storage.
- Use existing factories and fixtures before creating new ones.
- Avoid tests that require production credentials or live external mutation.
- Verify observability by asserting emitted metrics, logs, traces, or events where supported.
