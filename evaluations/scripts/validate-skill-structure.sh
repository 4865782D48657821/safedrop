#!/usr/bin/env bash
set -u

root="${1:-.agents/skills}"
errors=0

fail() {
  echo "error: $*" >&2
  errors=$((errors + 1))
}

if [ ! -d "$root" ]; then
  fail "skill root not found: $root"
  exit 1
fi

check_file_not_empty() {
  file="$1"
  if [ ! -s "$file" ]; then
    fail "empty file: $file"
  fi
}

while IFS= read -r file; do
  check_file_not_empty "$file"
  base=$(basename "$file")
  case "$base" in
    README.md|CHANGELOG.md)
      case "$file" in
        "$root"/*/*) fail "prohibited skill-local auxiliary file: $file" ;;
      esac
      ;;
  esac
done <<EOF
$(find "$root" -type f -print)
EOF

skill_names=""

while IFS= read -r skill_dir; do
  skill_name=$(basename "$skill_dir")
  skill_file="$skill_dir/SKILL.md"

  if ! printf '%s\n' "$skill_name" | grep -Eq '^[a-z0-9]+(-[a-z0-9]+)*$'; then
    fail "invalid skill directory name: $skill_name"
  fi

  if [ ! -f "$skill_file" ]; then
    fail "missing SKILL.md: $skill_dir"
    continue
  fi

  skill_names="${skill_names}${skill_name}
"

  first_line=$(sed -n '1p' "$skill_file")
  third_or_later=$(sed -n '2,20p' "$skill_file" | grep -n '^---$' | head -n 1 | cut -d: -f1)
  if [ "$first_line" != "---" ] || [ -z "$third_or_later" ]; then
    fail "frontmatter block missing or unterminated: $skill_file"
    continue
  fi

  fm_end=$((third_or_later + 1))
  fm=$(sed -n "2,$((fm_end - 1))p" "$skill_file")
  name_count=$(printf '%s\n' "$fm" | grep -c '^name: ')
  desc_count=$(printf '%s\n' "$fm" | grep -c '^description: ')
  other_count=$(printf '%s\n' "$fm" | grep -v -E '^(name|description): ' | grep -c '.')

  [ "$name_count" -eq 1 ] || fail "frontmatter must contain one name: $skill_file"
  [ "$desc_count" -eq 1 ] || fail "frontmatter must contain one description: $skill_file"
  [ "$other_count" -eq 0 ] || fail "frontmatter contains unsupported fields: $skill_file"

  declared_name=$(printf '%s\n' "$fm" | sed -n 's/^name: //p')
  declared_desc=$(printf '%s\n' "$fm" | sed -n 's/^description: //p')
  [ "$declared_name" = "$skill_name" ] || fail "skill name does not match directory: $skill_file"
  [ -n "$declared_desc" ] || fail "empty description: $skill_file"
  if ! printf '%s\n' "$declared_name" | grep -Eq '^[a-z0-9]+(-[a-z0-9]+)*$'; then
    fail "invalid frontmatter name: $skill_file"
  fi

  refs=$(grep -Eo '`(\.\./[A-Za-z0-9._/-]+|(assets|references|scripts)/[A-Za-z0-9._/-]+)`' "$skill_file" | tr -d '`')
  for ref in $refs; do
    if [ ! -e "$skill_dir/$ref" ]; then
      fail "missing referenced file from $skill_file: $ref"
    fi
  done
done <<EOF
$(find "$root" -mindepth 1 -maxdepth 1 -type d -print)
EOF

dupes=$(printf '%s' "$skill_names" | sort | uniq -d)
if [ -n "$dupes" ]; then
  fail "duplicate skill names: $dupes"
fi

if command -v skills-ref >/dev/null 2>&1; then
  while IFS= read -r skill_dir; do
    skills-ref validate "$skill_dir" || fail "skills-ref validation failed: $skill_dir"
  done <<EOF
$(find "$root" -mindepth 1 -maxdepth 1 -type d -print)
EOF
fi

if [ "$errors" -ne 0 ]; then
  echo "skill structure validation failed with $errors error(s)" >&2
  exit 1
fi

echo "skill structure validation passed"
