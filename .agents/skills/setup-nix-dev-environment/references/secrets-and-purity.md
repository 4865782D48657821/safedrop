# Secrets and Purity

Protect secrets and preserve reproducibility.

## Secrets

- Never place secrets in `flake.nix`, `flake.lock`, derivations, Nix store paths, committed `.env` files, shell hooks, or validation output.
- Document only secret variable names, not values.
- Prefer an existing secret-management system.
- Otherwise propose an untracked local environment file or external secret provider.
- Warn when a proposed approach would copy sensitive material into the Nix store.

## Purity

- Distinguish genuine requirements for impurity from accidental reliance on the host environment.
- Avoid `--impure` unless the user explicitly accepts the reproducibility trade-off.
- Avoid shell hooks that read host-specific secret files or mutate project state without clear user action.

## Binary Caches

- Treat private caches, trusted substituters, and public keys as security-sensitive.
- Require explicit approval before changing cache trust configuration or organization-wide cache settings.
- Do not request private keys, tokens, or secret cache credentials.
