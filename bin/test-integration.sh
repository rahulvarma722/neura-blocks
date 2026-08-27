#!/usr/bin/env bash
#
# Runs the integration suite against a real WordPress.
#
#   ./bin/test-integration.sh              # uses `wp` from PATH
#   WP_CLI=/path/to/wp ./bin/test-integration.sh
#
# The unit suite (composer test) covers pure logic with no WordPress at all and
# runs in milliseconds. This one needs a booted WordPress with the plugin
# active — the block registry, the parser, do_blocks(), and the REAL esc_url() /
# wp_kses() / get_block_wrapper_attributes(). Stubbing those would mean
# asserting against our own assumptions instead of against WordPress.
#
# It runs against ANY WordPress via wp-cli rather than requiring wp-env and
# Docker, so it works on a Local site and on CI unchanged.

set -euo pipefail

cd "$( dirname "${BASH_SOURCE[0]}" )/.."

WP="${WP_CLI:-wp}"

if ! command -v "$WP" >/dev/null 2>&1; then
	printf '\033[31merror:\033[0m wp-cli not found.\n' >&2
	printf '       Set WP_CLI=/path/to/wp, or install it: brew install wp-cli\n' >&2
	exit 1
fi

SUITE="tests/integration/test-render.php"
[[ -f "$SUITE" ]] || { printf '\033[31merror:\033[0m %s not found.\n' "$SUITE" >&2; exit 1; }

# Output is captured rather than streamed so the summary can be parsed. Note
# the assignment is on its own line: with `local OUTPUT=$( … )` or an inline
# pipe, the exit status would be the assignment's, not the command's — the same
# class of mistake that made this suite silently pass while reporting failures.
set +e
OUTPUT="$( "$WP" eval-file "$SUITE" 2>&1 )"
STATUS=$?
set -e

printf '%s\n' "$OUTPUT"

# Two independent signals, because either alone has failed before:
#   - the exit code, which a pipe in a wrapper script can swallow
#   - the printed summary, which cannot
SUMMARY="$( printf '%s\n' "$OUTPUT" | grep -E '^[0-9]+ passed, [0-9]+ failed' | tail -1 || true )"

if [[ -z "$SUMMARY" ]]; then
	printf '\033[31merror:\033[0m the suite produced no summary line — it did not run to completion.\n' >&2
	exit 1
fi

FAILED="$( printf '%s\n' "$SUMMARY" | sed -E 's/.*, ([0-9]+) failed.*/\1/' )"

if [[ "$FAILED" != "0" ]] || [[ "$STATUS" -ne 0 ]]; then
	printf '\033[31mFAILED\033[0m  %s (exit %d)\n' "$SUMMARY" "$STATUS" >&2
	exit 1
fi

printf '\033[32mOK\033[0m  %s\n' "$SUMMARY"
