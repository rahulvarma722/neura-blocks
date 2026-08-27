#!/usr/bin/env bash
#
# Runs Plugin Check against the RELEASE ARTIFACT instead of the repo.
#
#   ./bin/check-release.sh
#
# WHY THIS EXISTS.
#
# Running Plugin Check on the working tree is misleading, and it is the easy
# mistake to make because the plugin is sitting right there, installed. The repo
# legitimately contains files that must never ship — bin/, dist/, .github/,
# .eslintrc.js, phpcs.xml.dist, .gitignore, RENAMING.md — so a scan of it reports
# a fistful of errors that say nothing about what a reviewer will receive. Every
# one of them is excluded by bin/build-zip.sh.
#
# What matters is the ZIP. This builds it, installs the extracted copy
# alongside, scans THAT, and cleans up.
#
# Requires wp-cli and the plugin-check plugin.

set -euo pipefail

cd "$( dirname "${BASH_SOURCE[0]}" )/.."

SLUG="blockkit"
PLUGINS_DIR="$( cd .. && pwd )"
CHECK_SLUG="${SLUG}-relcheck"
CHECK_DIR="${PLUGINS_DIR}/${CHECK_SLUG}"

WP="${WP_CLI:-wp}"
command -v "$WP" >/dev/null 2>&1 || {
	echo "error: wp-cli not found. Set WP_CLI=/path/to/wp if it is not on PATH." >&2
	exit 1
}

cleanup() { rm -rf "$CHECK_DIR" "${TMP:-}"; }
trap cleanup EXIT

echo "Building..."
./bin/build-zip.sh >/dev/null

VERSION=$( grep -m1 '^\s*\*\s*Version:' "${SLUG}.php" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]' )
ZIP="dist/${SLUG}-${VERSION}.zip"
[[ -f "$ZIP" ]] || { echo "error: $ZIP not found." >&2; exit 1; }

TMP="$( mktemp -d )"
unzip -q "$ZIP" -d "$TMP"
rm -rf "$CHECK_DIR"
cp -R "${TMP}/${SLUG}" "$CHECK_DIR"

echo "Checking ${ZIP} (as ${CHECK_SLUG})..."
echo

# Plugin Check derives the EXPECTED TEXT DOMAIN from the directory name, so a
# copy checked under any name but "blockkit" reports a mismatch for every
# translated string. That is an artefact of this harness, not of the plugin, so
# those two codes are filtered — and the filter is narrow and named, rather than
# a blanket "ignore errors".
OUTPUT="$( "$WP" plugin check "$CHECK_SLUG" \
	--categories=general,plugin_repo,security,performance,accessibility \
	--format=csv --fields=file,line,type,code,message 2>&1 \
	| grep -vE 'TextDomainMismatch|textdomain_mismatch' || true )"

FINDINGS="$( printf '%s\n' "$OUTPUT" | grep -cE ',(ERROR|WARNING),' || true )"

printf '%s\n' "$OUTPUT"
echo

if [[ "${FINDINGS:-0}" -eq 0 ]]; then
	printf '\033[32mCLEAN\033[0m  no findings in the release artifact\n'
	echo
	echo "Note: text-domain mismatches were filtered, because the check directory"
	echo "      is named ${CHECK_SLUG} rather than ${SLUG}. Nothing else was ignored."
else
	printf '\033[31m%s finding(s)\033[0m in the release artifact — listed above.\n' "$FINDINGS"
	exit 1
fi
