#!/usr/bin/env bash
# Runs the plugin's PHPUnit tests. Sets the environment up on first use.
# Arguments are passed on to PHPUnit, e.g.:
#   ./tests/local/run.sh --filter testtitleHTML
set -euo pipefail

source "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/env.sh"

"$LOCAL_DIR/setup.sh"

exec "$PHP" "$CACHE/phpunit-$PHPUNIT_VERSION.phar" -c "$LOCAL_DIR/phpunit.xml" "$@"
