# Running the tests locally

The plugin's PHPUnit tests (`tests/test-*.php`) need the WordPress test
suite, which is not part of a normal WordPress download. The scripts in this
folder fetch everything and run the tests against the WordPress install the
plugin already sits in.

## One command

```sh
./tests/local/run.sh
```

The first run downloads the WordPress test suite, PHPUnit and the PHPUnit
polyfills (about a minute); later runs reuse them. Arguments go straight to
PHPUnit:

```sh
./tests/local/run.sh --filter test_renderer_accepts_post
./tests/local/run.sh --debug
```

Requirements: `svn` and `curl` for the downloads, and a running MySQL/MariaDB
server - XAMPP's is fine.

## What it does

* `setup.sh` reads the real version from `wp-includes/version.php` (the folder
  is called `wordpress-6-3`, but the install gets updated in place) and exports
  the matching test suite from `develop.svn.wordpress.org`.
* It downloads PHPUnit 9.6 and the Yoast PHPUnit polyfills the suite needs.
* It writes a `wp-tests-config.php` pointing at the surrounding WordPress
  install and creates the empty test database.
* `run.sh` then runs PHPUnit with `phpunit.xml` and `bootstrap.php` from this
  folder, on the tests in `tests/`.

Downloads land in `~/.cache/category-archives-block-tests`, outside the plugin
folder, so nothing extra ends up in a release package. Delete that folder to
start over.

## Settings

All overridable as environment variables:

| Variable | Default | Meaning |
| --- | --- | --- |
| `CAB_PHP` | XAMPP's PHP, else `php` | PHP binary to use |
| `CAB_TESTS_CACHE` | `~/.cache/category-archives-block-tests` | where the downloads go |
| `CAB_WP_VERSION` | version of the install | test suite tag to fetch |
| `CAB_PHPUNIT_VERSION` | `9.6` | PHPUnit version |
| `CAB_DB_NAME` | `wordpress_test` | test database |
| `CAB_DB_USER` / `CAB_DB_PASSWORD` / `CAB_DB_HOST` | `root` / empty / `127.0.0.1` | database login |

**The test database is wiped:** the suite drops and recreates all `wptests_`
tables in it on every run. Never point it at a database you care about.



