# System Design Patterns

- Modular monolith: prefer when deployment independence is not needed and local transactions matter.
- Service boundary: use when ownership, scaling, availability, or release cadence justifies operational cost.
- Event-driven flow: use for decoupling and asynchronous processing; require idempotency and replay strategy.
- Strangler migration: use to replace legacy behavior incrementally with rollback points.
- Adapter boundary: use to isolate external services and enable contract tests.
