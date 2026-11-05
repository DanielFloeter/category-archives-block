#!/usr/bin/env bash
# Shared environment for the local PHPUnit runner. Sourced by setup.sh and run.sh.
#
# Everything can be overridden from the outside, e.g.:
#   CAB_DB_USER=me CAB_DB_PASSWORD=secret ./tests/local/run.sh

LOCAL_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLUGIN_DIR="$( cd "$LOCAL_DIR/../.." && pwd )"
# plugin -> plugins -> wp-content -> WordPress root.
WP_ROOT="$( cd "$PLUGIN_DIR/../../.." && pwd )"

# Downloads (WP test suite, PHPUnit, polyfills) live outside the plugin folder
# so that the plugin directory stays clean for packaging.
CACHE="${CAB_TESTS_CACHE:-$HOME/.cache/category-archives-block-tests}"

PHPUNIT_VERSION="${CAB_PHPUNIT_VERSION:-9.6}"
POLYFILLS_VERSION="1.1.0"

DB_NAME="${CAB_DB_NAME:-wordpress_test}"
DB_USER="${CAB_DB_USER:-root}"
DB_PASSWORD="${CAB_DB_PASSWORD:-}"
DB_HOST="${CAB_DB_HOST:-127.0.0.1}"

# Pick a PHP binary that actually runs and has mysqli. On the XAMPP boxes the
# PHP in $PATH is often a broken Homebrew build, so XAMPP's own PHP wins.
find_php() {
	local candidate
	for candidate in "${CAB_PHP:-}" /Applications/XAMPP/xamppfiles/bin/php "$( command -v php || true )"; do
		[ -n "$candidate" ] || continue
		[ -x "$candidate" ] || continue
		"$candidate" -r 'exit( extension_loaded( "mysqli" ) ? 0 : 1 );' >/dev/null 2>&1 || continue
		echo "$candidate"
		return 0
	done
	return 1
}

PHP="$( find_php )" || {
	echo "No usable PHP binary found (needs to run and provide mysqli)." >&2
	echo "Set CAB_PHP to the PHP you want to use." >&2
	exit 1
}

export CAB_TESTS_CACHE="$CACHE"
export CAB_POLYFILLS_PATH="$CACHE/PHPUnit-Polyfills-$POLYFILLS_VERSION"
