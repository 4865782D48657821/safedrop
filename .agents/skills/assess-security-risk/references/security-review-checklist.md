# Security Review Checklist

- Authentication cannot be bypassed or confused across sessions.
- Authorization checks enforce the intended subject, object, and action.
- Inputs are validated at trust boundaries.
- Secrets are not logged, exposed, committed, or sent to clients.
- Sensitive data has appropriate access, retention, and audit behavior.
- Dependencies and external calls are trusted, pinned by repository policy, and failure-safe.
