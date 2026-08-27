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
# What this CANNOT fix: post content already saved on any site. Block names
# live in the database as `<!-- wp:<slug>/button -->` comments, and static
# blocks' `wp-block-<slug>-*` classes are saved into published markup.
# Renaming after release requires block deprecations or a content migration.
# Before release, this script is all you need.
#
# The three OLD_* values below are the CURRENT names, and the script rewrites
# them in itself on every successful run, so it stays usable after a rename.
# Comments here use <slug> rather than the literal name for the same reason.

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

# Title-case a hyphenated slug: brick-yard -> BrickYard.
#
# Written out rather than `sed -E 's/(^|-)([a-z])/\U\2/g'`, because \U in a
# replacement is a GNU extension. BSD sed — which is what macOS ships, and what
# this script is most likely to be run on — emits a literal "U" instead, so the
# old form derived "Ubrickyard" and would have rewritten every BlockKit class to
# that. Silent, and only visible after the fact.
title_case() {
	local remainder="$1" out="" part
	while [[ -n "$remainder" ]]; do
		part="${remainder%%-*}"
		if [[ -n "$part" ]]; then
			out+="$( printf '%s' "${part:0:1}" | tr '[:lower:]' '[:upper:]' )${part:1}"
		fi
		[[ "$remainder" == *-* ]] || break
		remainder="${remainder#*-}"
	done
	printf '%s' "$out"
}

# Derive the other two casings from the slug when no display name is given.
if [[ -z "$NEW_DISPLAY" || "$NEW_DISPLAY" == "--go" ]]; then
	[[ "$NEW_DISPLAY" == "--go" ]] && APPLY="--go"
	NEW_DISPLAY="$( title_case "$NEW_SLUG" )"
fi

NEW_CONST="$(echo "$NEW_SLUG" | tr '[:lower:]-' '[:upper:]_')"
NEW_CLASS="$NEW_DISPLAY"

# The display name doubles as the PHP CLASS PREFIX — `BlockKit` is both the
# `Plugin Name:` header and the `BlockKit_Blocks` in class names, and sed cannot
# tell those two roles apart from the same literal. So it has to be a valid PHP
# identifier. Without this check, `Brick & Yard` produced `class Brick & Yard {`
# and a plugin that no longer parses, while the rename reported success.
if [[ ! "$NEW_CLASS" =~ ^[A-Za-z][A-Za-z0-9_]*$ ]]; then
	echo "error: display name \"$NEW_CLASS\" is also used as the PHP class prefix," >&2
	echo "       so it must be letters, digits and underscores, starting with a letter." >&2
	echo >&2
	echo "       Use an identifier-safe name here, then change the human-facing name" >&2
	echo "       afterwards — it is only two places, neither an identifier:" >&2
	echo "         - the 'Plugin Name:' header in the main plugin file" >&2
	echo "         - the block category title in includes/class-<slug>-blocks.php" >&2
	exit 1
fi

# A display name is free text, and sed treats `&` in a replacement as "the whole
# match" and `/` as the delimiter. Without escaping, `Brick & Yard` expands to
# `Brick BlockKit Yard`. Escaped once here rather than at each call site.
sed_escape() {
	printf '%s' "$1" | sed -e 's/[\/&\\]/\\&/g'
}

NEW_CONST_ESC="$( sed_escape "$NEW_CONST" )"
NEW_CLASS_ESC="$( sed_escape "$NEW_CLASS" )"
NEW_SLUG_ESC="$( sed_escape "$NEW_SLUG" )"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# The last step renames the plugin folder. Check now rather than after every
# file has already been rewritten and the tree is half-migrated.
if [[ "$(basename "$ROOT")" != "$NEW_SLUG" && -e "$(dirname "$ROOT")/$NEW_SLUG" ]]; then
	echo "error: $(dirname "$ROOT")/$NEW_SLUG already exists; move it aside first." >&2
	exit 1
fi

echo "  slug      $OLD_SLUG  ->  $NEW_SLUG"
echo "  constants $OLD_CONST  ->  $NEW_CONST"
echo "  classes   $OLD_CLASS  ->  $NEW_CLASS"
echo "  folder    $(basename "$ROOT")  ->  $NEW_SLUG"
echo

# Everything except build artefacts, dependencies and this script itself.
#
# dist/ matters as much as build/: it holds the released ZIP, and running sed
# over a ZIP does not rename anything, it corrupts the archive. `grep -I` then
# skips anything else binary, so a future asset cannot be quietly mangled the
# same way.
#
# A while-read loop rather than mapfile, which is bash 4+ and macOS ships 3.2.
FILES=()
while IFS= read -r f; do
	# -I: treat binary as non-matching, so binaries never enter the list.
	grep -Iq . "$f" 2>/dev/null || continue
	FILES+=("$f")
done < <(
	find . -type f \
		-not -path "./node_modules/*" \
		-not -path "./vendor/*" \
		-not -path "./build/*" \
		-not -path "./dist/*" \
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
	# Longest/most specific first, so the CONST form is not eaten by the slug
	# form when one is a substring of the other.
	sed_inplace \
		-e "s/${OLD_CONST}/${NEW_CONST_ESC}/g" \
		-e "s/${OLD_CLASS}/${NEW_CLASS_ESC}/g" \
		-e "s/${OLD_SLUG}/${NEW_SLUG_ESC}/g" \
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
	-not -path "./vendor/*" \
	-not -path "./build/*" \
	-not -path "./dist/*" \
	-not -path "./.git/*" | while IFS= read -r path; do
	dir="$(dirname "$path")"
	base="$(basename "$path")"
	mv "$path" "$dir/${base//$OLD_SLUG/$NEW_SLUG}"
done

# VERIFY, rather than telling the reader to verify.
#
# The whole promise of this script is that no occurrence survives. That is a
# checkable claim, so it is checked here — a rename that half-worked is worse
# than one that refused, because the plugin still loads and fails later in ways
# that look unrelated.
echo "Verifying no occurrences survive..."
LEAKS=0
for f in "${FILES[@]}"; do
	if grep -InE "${OLD_SLUG}|${OLD_CONST}|${OLD_CLASS}" "$f" 2>/dev/null; then
		LEAKS=1
	fi
done

if [[ "$LEAKS" -ne 0 ]]; then
	echo >&2
	echo "error: occurrences of ${OLD_SLUG}/${OLD_CONST}/${OLD_CLASS} survive, listed above." >&2
	echo "The tree is half-renamed. Fix those, then rename the folder by hand." >&2
	exit 1
fi
echo "  ok  no occurrences remain in tracked text files"

# And that the result is still valid PHP. A rewrite can be complete and still
# leave a file that does not parse — which is exactly what an unguarded display
# name used to do to every class declaration.
if command -v php >/dev/null 2>&1; then
	PARSE_FAIL=0
	while IFS= read -r php_file; do
		php -l "$php_file" >/dev/null 2>&1 || { php -l "$php_file" >&2; PARSE_FAIL=1; }
	done < <( find . -name '*.php' -not -path "./node_modules/*" -not -path "./vendor/*" -not -path "./dist/*" )

	if [[ "$PARSE_FAIL" -ne 0 ]]; then
		echo >&2
		echo "error: the rename produced invalid PHP, listed above. Nothing was moved." >&2
		exit 1
	fi
	echo "  ok  every PHP file still parses"
else
	echo "  --  php not on PATH; skipped the parse check"
fi

echo "Renaming folder..."
cd ..
mv "$(basename "$ROOT")" "$NEW_SLUG"

echo
echo "Done. Next:"
echo "  1. rm -rf build && npm run build"
echo "  2. rm -rf vendor && composer install     # composer.json name changed"
echo "  3. Deactivate and reactivate the plugin (the folder path changed)."
echo "  4. ./bin/build-zip.sh                    # re-run the release gate"
