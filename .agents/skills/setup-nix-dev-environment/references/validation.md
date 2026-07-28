# Validation

Validate with native Nix commands when available and safe.

## Core Commands

Attempt as applicable:

```sh
nix flake metadata path:.
nix flake show path:.
nix flake check path:.
nix develop path:. --command bash -c '<safe smoke-test commands>'
```

Use `path:.` for newly created untracked Flake files and explain why.

## Smoke Tests

- Verify expected executables are on `PATH`.
- Query relevant versions.
- Verify compilers or native libraries are discoverable where applicable.
- Verify package-manager commands can be located.
- Run safe project lint, test, type-check, or build commands when prerequisites are available and cost is reasonable.
- Evaluate the Flake for supported systems where feasible.
- Verify formatting if a formatter output is provided.

## Reporting

- Report command, exit status, concise outcome, and limitation or skip reason.
- Do not claim validation that was not performed.
- Do not run unexpectedly expensive builds or large downloads without warning the user.
- If validation requires network access, explain what must be fetched.

## Failures

1. Diagnose the actual cause.
2. Make the smallest defensible correction.
3. Rerun the failed check.
4. Do not suppress the problem with permissive flags or impurity unless the user explicitly accepts that trade-off.

For review mode, report findings without making corrections unless the user asks for them.
