# Safedrop

Safedrop is a Laravel MVP for a safe gaming discovery and creator platform focused on Minecraft and Roblox projects.

## Development

All development tools are declared in `flake.nix`.

```sh
nix develop
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
composer run dev
```

The local app runs at `http://127.0.0.1:8000`.

## Demo Data

`php artisan db:seed` loads demo data only for `local`, `dev`, `development`, `qa`, `quality-assurance`, and `testing` environments.

All demo users use the password `development-only`:

- `member@safedrop.test`
- `creator@safedrop.test`
- `adult-creator@safedrop.test`
- `moderator@safedrop.test`
- `admin@safedrop.test`

## Verification

```sh
nix flake metadata path:.
nix flake show path:.
nix develop path:. --command composer validate --strict
nix develop path:. --command composer install
nix develop path:. --command php artisan test
```

`path:.` evaluates the current working tree without requiring untracked files to be staged.
