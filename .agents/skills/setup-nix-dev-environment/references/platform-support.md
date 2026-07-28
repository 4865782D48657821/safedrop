# Platform Support

Handle platform support explicitly.

## Systems

- Consider `x86_64-linux`, `aarch64-linux`, `x86_64-darwin`, and `aarch64-darwin`.
- Expose Linux-only packages only on Linux.
- Expose Darwin-only dependencies only on Darwin.
- Use platform conditionals for system libraries, SDKs, and tools with limited availability.

## Claims

- Do not equate Flake evaluation with runtime correctness.
- Do not claim support for systems that were not validated or whose limitations were not documented.
- State which systems were evaluated, which were smoke-tested, and which remain unverified.

## Blockers

- If a requested system cannot be supported safely, explain the blocker and present options such as narrowing supported systems, replacing a dependency, using a container, or documenting a limitation.
- Avoid fragile workarounds that hide platform incompatibility.
