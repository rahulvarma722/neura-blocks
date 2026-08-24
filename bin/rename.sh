#!/usr/bin/env bash
#
# Renames the plugin.
#
# Rewrites every identifier derived from the plugin name, in all three
# casings, then renames the plugin folder.
#
#   ./bin/rename.sh <new-slug> [New Display Name]      # dry run
#   ./bin/rename.sh <new-slug> [New Display Name] --go  # apply
#
# Example:
#   ./bin/rename.sh brickyard Brickyard --go
#
# What this CANNOT fix: post content already saved on any site. Block
# names live in the database as `<!-- wp:blockkit/button -->` comments,
# and static blocks' `wp-block-blockkit-*` classes are saved into markup.
# Renaming after release requires block deprecations or a migration.
# Before release, this script is all you need.

set -euo pipefail

OLD_SLUG="blockkit"
OLD_CONST="BLOCKKIT"
OLD_CLASS="BlockKit"

NEW_SLUG="${1:-}"
NEW_DISPLAY="${2:-}"
APPLY="${3:-}"

if [[ -z "$NEW_SLUG" ]]; then
	echo "usage: $0 <new-slug> [New Display Name] [--go]" >&2
	exit 1
fi

if [[ ! "$NEW_SLUG" =~ ^[a-z][a-z0-9-]*$ ]]; then
	echo "error: slug must be lowercase letters, digits and hyphens, starting with a letter." >&2
	exit 1
fi

# Derive the other two casings from the slug when no display name is given.
if [[ -z "$NEW_DISPLAY" || "$NEW_DISPLAY" == "--go" ]]; then
	[[ "$NEW_DISPLAY" == "--go" ]] && APPLY="--go"
	NEW_DISPLAY="$(echo "$NEW_SLUG" | sed -E 's/(^|-)([a-z])/\U\2/g')"
fi

NEW_CONST="$(echo "$NEW_SLUG" | tr '[:lower:]-' '[:upper:]_')"
NEW_CLASS="$NEW_DISPLAY"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "  slug      $OLD_SLUG  ->  $NEW_SLUG"
echo "  constants $OLD_CONST  ->  $NEW_CONST"
echo "  classes   $OLD_CLASS  ->  $NEW_CLASS"
echo "  folder    $(basename "$ROOT")  ->  $NEW_SLUG"
echo

# Everything except build artefacts, dependencies and this script itself.
# A while-read loop rather than mapfile, which is bash 4+ and macOS ships 3.2.
FILES=()
while IFS= read -r f; do
	FILES+=("$f")
done < <(
	find . -type f \
		-not -path "./node_modules/*" \
		-not -path "./vendor/*" \
		-not -path "./build/*" \
		-not -path "./.git/*" \
		-not -name "rename.sh"
)

# BSD sed (macOS) requires an argument to -i; GNU sed must not have one.
if sed --version >/dev/null 2>&1; then
	sed_inplace() { sed -i "$@"; }
else
	sed_inplace() { sed -i '' "$@"; }
fi

if [[ "$APPLY" != "--go" ]]; then
	echo "DRY RUN — files that would change:"
	for f in "${FILES[@]}"; do
		if grep -qE "$OLD_SLUG|$OLD_CONST|$OLD_CLASS" "$f" 2>/dev/null; then
			printf '  %-46s %s\n' "$f" "$(grep -cE "$OLD_SLUG|$OLD_CONST|$OLD_CLASS" "$f") match(es)"
		fi
	done
	echo
	echo "Re-run with --go to apply."
	exit 0
fi

for f in "${FILES[@]}"; do
	# Longest/most specific first so BLOCKKIT is not eaten by blockkit.
	sed_inplace \
		-e "s/${OLD_CONST}/${NEW_CONST}/g" \
		-e "s/${OLD_CLASS}/${NEW_CLASS}/g" \
		-e "s/${OLD_SLUG}/${NEW_SLUG}/g" \
		"$f"
done

# Update this script's own baseline so it can be run again later. It is
# excluded from the rewrite loop above (otherwise the sed expressions
# would rewrite themselves mid-run), so it has to be patched separately.
sed_inplace \
	-e "s/^OLD_SLUG=\".*\"/OLD_SLUG=\"${NEW_SLUG}\"/" \
	-e "s/^OLD_CONST=\".*\"/OLD_CONST=\"${NEW_CONST}\"/" \
	-e "s/^OLD_CLASS=\".*\"/OLD_CLASS=\"${NEW_CLASS}\"/" \
	"bin/rename.sh"

# Rename any file whose NAME carries the slug — the main plugin file and
# every includes/class-<slug>-*.php. Deepest paths first so renaming a
# directory never invalidates a path still queued behind it.
find . -depth -name "*${OLD_SLUG}*" \
	-not -path "./node_modules/*" \
	-not -path "./build/*" \
	-not -path "./.git/*" | while IFS= read -r path; do
	dir="$(dirname "$path")"
	base="$(basename "$path")"
	mv "$path" "$dir/${base//$OLD_SLUG/$NEW_SLUG}"
done

echo "Files rewritten. Renaming folder..."
cd ..
mv "$(basename "$ROOT")" "$NEW_SLUG"

echo
echo "Done. Next:"
echo "  1. rm -rf build && npm run build"
echo "  2. Deactivate and reactivate the plugin (the folder path changed)."
echo "  3. grep -ri '$OLD_SLUG' . --exclude-dir={node_modules,build,.git}   # expect no hits"
