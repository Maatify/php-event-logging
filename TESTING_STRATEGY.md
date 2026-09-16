# Testing Strategy

This document describes the current local verification path for
`maatify/php-event-logging`. The commands below mirror the verification
contracts used by [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

The repository is a reusable Composer library and deliberately does not track
`composer.lock`. Dependency resolution therefore uses `composer update`; any
generated lock file is temporary and must not be committed.

## Prerequisites

- PHP 8.4 or 8.5 with `ext-json`, `ext-pdo`, and `pdo_mysql` for the real-MySQL
  Integration gate.
- Composer and the package development dependencies.
- `actionlint` for local workflow verification. CI downloads and verifies
  actionlint `1.7.12` before running it.
- A disposable MySQL 8.0 database for Integration and Consumer Verification
  Harness runs.

Install or resolve dependencies from the repository root:

```bash
composer update --no-interaction --prefer-dist --no-progress
```

## Local quality gates

Run the following commands from the repository root.

### Composer validation, resolution, and platform checks

```bash
composer validate --strict
composer update --no-interaction --prefer-dist --no-progress
composer check-platform-reqs
```

The CI quality and dependency jobs use these commands. The required baseline
does not use `--ignore-platform-reqs` or `--ignore-platform-req`.

### PHP syntax validation

The current CI syntax gate checks package-owned PHP files under `src/`,
`tests/`, and `examples/`:

```bash
find src tests examples -type f -name '*.php' -print0 | xargs -0 -r -n 1 php -l
```

Example files are syntax-checked but are intentionally not part of PHPStan's
configured analysis paths.

### PHPStan

PHPStan is already an active gate; it is not pending a future CI or dependency
addition. The maintained Composer script runs Level Max over `src` and `tests`:

```bash
composer analyse
```

### Unit and Regression

```bash
composer test:unit
composer test:regression
```

The Regression suite includes the package-owned schema verification in
`tests/Regression/SchemaMetadataTest.php`. To run that schema check directly:

```bash
vendor/bin/phpunit tests/Regression/SchemaMetadataTest.php
```

The schema check reads all six domain SQL files and verifies that every column
has a meaningful SQL `COMMENT`; it is not a claim that a schema migration was
run.

### Real-MySQL Integration

The Integration suite verifies the actual MySQL repository boundary. It
requires all three variables below:

```bash
EVENT_LOGGING_TEST_MYSQL_DSN="mysql:host=127.0.0.1;port=3306;dbname=event_logging_test;charset=utf8mb4" \
EVENT_LOGGING_TEST_MYSQL_USER="event_logging" \
EVENT_LOGGING_TEST_MYSQL_PASSWORD="event_logging" \
composer test:integration
```

When the MySQL configuration is missing, the real-MySQL Integration gate is
`UNAVAILABLE`. Shared fixtures may report skips, and strict repository tests
may fail fast with a configuration error; neither outcome may be reported as
`PASS`. CI provisions MySQL 8.0 and supplies these variables for its PHP 8.4
and 8.5 Integration jobs.

### Consumer Verification Harness

The Harness creates a separate clean Composer consumer, installs the package
artifact, exercises the production autoloader and public API, and repeats the
workflow twice:

```bash
EVENT_LOGGING_HARNESS_MYSQL_DSN="mysql:host=127.0.0.1;port=3306;dbname=event_logging_test;charset=utf8mb4" \
EVENT_LOGGING_HARNESS_MYSQL_USER="event_logging" \
EVENT_LOGGING_HARNESS_MYSQL_PASSWORD="event_logging" \
bash tests/ConsumerVerificationHarness/run.sh
```

The Harness gate is fail-closed. Missing required Harness configuration or
service access makes the command fail; it must not be converted into a
passing result or a silent skip.

### Whitespace check

For the same comparison used by CI, run `git diff --check` with the resolved
base and head SHAs:

```bash
git diff --check <base-sha> <head-sha>
```

For uncommitted local work, also check both working-tree and staged changes:

```bash
git diff --check
git diff --cached --check
```

### Workflow lint

With `actionlint` available on `PATH`:

```bash
actionlint .github/workflows/ci.yml
```

The CI Workflow Lint job downloads the pinned actionlint `1.7.12`, verifies
its checksum, and lints the workflow file.

### Composer audit

Run the enforced non-interactive audit command:

```bash
composer audit --no-interaction --abandoned=fail
```

## Dependency-matrix commands

The CI jobs also verify both ends of the declared dependency ranges. For the
lowest-supported dependency path, run:

```bash
composer update --prefer-lowest --prefer-stable --no-interaction --prefer-dist --no-progress
composer check-platform-reqs
composer analyse -- --memory-limit=512M
composer test
```

The normal latest-compatible dependency path runs `composer update`, platform
checks, Unit, and Regression. CI executes the applicable paths on PHP 8.4 and
8.5; a local run proves the current PHP environment only.
