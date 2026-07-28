#!/usr/bin/env bash
set -u

if [ ! -d . ]; then
  echo "error: current directory is unavailable" >&2
  exit 2
fi

repo_root=$(pwd)
if command -v git >/dev/null 2>&1 && git rev-parse --show-toplevel >/dev/null 2>&1; then
  repo_root=$(git rev-parse --show-toplevel)
  cd "$repo_root" || exit 2
fi

echo "verification_evidence"
echo "repository_root: $repo_root"
echo "timestamp_utc: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"

if command -v git >/dev/null 2>&1 && git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo
  echo "git_status:"
  git status --short
  echo
  echo "changed_files:"
  git diff --name-only
  git diff --cached --name-only
else
  echo
  echo "git_status: git unavailable or not a repository"
fi

overall=0
if [ "$#" -gt 0 ]; then
  echo
  echo "commands:"
fi

for cmd in "$@"; do
  echo
  echo "command: $cmd"
  bash -lc "$cmd"
  code=$?
  echo "exit_code: $code"
  if [ "$code" -ne 0 ] && [ "$overall" -eq 0 ]; then
    overall=$code
  fi
done

exit "$overall"
