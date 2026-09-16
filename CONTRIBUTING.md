# Contributing to maatify/php-event-logging

Thank you for your interest in contributing to **maatify/php-event-logging**! We welcome contributions that help improve this package.

**maatify/php-event-logging** is a standalone, framework-agnostic Composer library. It is designed to be completely independent of any host application architecture or framework. As a reusable library, `composer.lock` must not be committed to the repository.

## Ways to Contribute

You can contribute to this project in several ways:

* **Reporting Bugs:** If you find a bug, please report it via GitHub Issues.
* **Improving Documentation/Examples:** Clarifying instructions, fixing typos, or adding new examples in the `docs/` and `examples/` directories.
* **Fixing Package-Local Behavior:** Resolving bugs or edge cases within the boundaries of the package itself.
* **Improving Tests/Static Analysis:** Adding test cases, improving coverage, or fixing PHPStan issues.
* **Improving MySQL/PDO Package-Owned Repositories:** Optimizing queries or fixing issues related strictly to our defined database schema interactions.
* **Improving Domain-Specific Logging Behavior:** Enhancing the logic for our six specific logging domains (AuthoritativeAudit, AuditTrail, SecuritySignals, BehaviorTrace, DiagnosticsTelemetry, DeliveryOperations).

## Project Structure

When navigating the project, you will encounter the following directories:

* `src/` — Contains all the production source code.
* `tests/` — Contains Unit, Integration, and Regression tests.
* `docs/` — Contains detailed architectural and integration documentation.
* `examples/` — Contains illustrative, standalone example scripts.
* `schema/` — Contains the package-level schema index. The actual SQL schema files are domain-local under `src/*/Database/`.

## Local Verification Before a Pull Request

The complete local verification path is documented in
[`TESTING_STRATEGY.md`](TESTING_STRATEGY.md). The repository does not track a
`composer.lock`, so resolve dependencies with the same command used by CI:

```bash
composer validate --strict
composer update --no-interaction --prefer-dist --no-progress
composer check-platform-reqs
find src tests examples -type f -name '*.php' -print0 | xargs -0 -r -n 1 php -l
composer analyse
composer audit --no-interaction --abandoned=fail
composer test:unit
composer test:regression
vendor/bin/phpunit tests/Regression/SchemaMetadataTest.php
```

The schema verification is part of the Regression suite and checks the six
domain SQL files through `tests/Regression/SchemaMetadataTest.php`.

The real-MySQL Integration gate requires a valid MySQL 8.0 service and all
three variables below:

```bash
EVENT_LOGGING_TEST_MYSQL_DSN="mysql:host=127.0.0.1;port=3306;dbname=event_logging_test;charset=utf8mb4" \
EVENT_LOGGING_TEST_MYSQL_USER="event_logging" \
EVENT_LOGGING_TEST_MYSQL_PASSWORD="event_logging" \
composer test:integration
```

If the MySQL configuration is missing, Integration is `UNAVAILABLE`, not
`PASS`. Shared fixtures may skip and strict repository tests may fail fast
with a configuration error; neither result is a passing Integration result.

Run the external-consumer gate with its separate Harness variables:

```bash
EVENT_LOGGING_HARNESS_MYSQL_DSN="mysql:host=127.0.0.1;port=3306;dbname=event_logging_test;charset=utf8mb4" \
EVENT_LOGGING_HARNESS_MYSQL_USER="event_logging" \
EVENT_LOGGING_HARNESS_MYSQL_PASSWORD="event_logging" \
bash tests/ConsumerVerificationHarness/run.sh
```

The Harness must complete two clean consumer runs. Missing Harness setup must
fail closed and must not be silently skipped.

Before opening the PR, also run the repository and workflow checks:

```bash
actionlint .github/workflows/ci.yml
git diff --check <base-sha> <head-sha>
```

The lowest-dependency CI path additionally uses:

```bash
composer update --prefer-lowest --prefer-stable --no-interaction --prefer-dist --no-progress
composer check-platform-reqs
composer analyse -- --memory-limit=512M
composer test
```

PHPStan and CI are current gates, not planned future additions. See the
testing strategy for the mapping between these local commands and the CI jobs.

## Package Distribution Status

This package is in **Development / Pre-Stable**. `maatify/php-event-logging` is registered on [Packagist](https://packagist.org/packages/maatify/php-event-logging), but no Stable or RC release, or other published exact SemVer version, is claimed under this package identity. Stable consumer installation remains tied to a future Owner-approved release.

## Architectural Rules

To maintain the integrity and standalone nature of this package, all contributions **must** adhere to the following architectural constraints:

* **No framework bindings:** The package must not rely on Laravel, Symfony, Slim, or any other framework.
* **No host app namespaces:** Code must not reference `App`, `Athar`, `EP4N`, or any project-specific namespaces.
* **No generic logger API:** The package does not expose a catch-all logging API, generic recorder, or domain-string-routed logger. PSR-3 is only accepted as an optional fallback logger for fail-open domains.
* **No generic recorder/repository:** Each domain has its own dedicated recorder and repository.
* **No shared generic `logs` or `event_logs` table:** Storage is
  domain-isolated; a domain may own more than one package table when its
  approved architecture requires it. `AuthoritativeAudit` owns its
  authoritative outbox and materialized read log. There is no universal
  one-table-per-domain rule.
* **MySQL/PDO only:** We strictly support MySQL-backed repositories.
* **Host provides PDO:** The consuming application is responsible for passing an active PDO connection.
* **No controllers/routes/UI/permissions:** This package does not provide admin screens, API routes, or UI components.
* **AuthoritativeAudit remains fail-closed:** Storage failures in the AuthoritativeAudit domain must throw exceptions and must not be swallowed.
* **Fail-open behavior stays at recorder boundary:** For non-authoritative domains, failure swallowing (fail-open) occurs only at the recorder layer, with optional fallback logging.
* **PSR-3 is an optional fallback diagnostic sink:** In the five
  non-authoritative domains, an injected `LoggerInterface` MAY receive
  diagnostics for recording failures, including domain-specific metadata-size
  or encoding handling where the current recording path uses it. If no logger
  is supplied, there is no mandatory fallback or reporting channel and the
  fail-open recorder contract remains in effect. `AuthoritativeAudit` does not
  use fallback logging and remains fail-closed.

## Pull Request Guidelines

* **Keep PRs focused:** Try to solve one specific issue per Pull Request.
* **Update tests and docs:** If you change behavior, you must update the relevant tests and documentation.
* **Mention BC impact:** If your change breaks backward compatibility (BC), clearly state this in the PR description.
* **Do not change public API casually:** Architectural or public API changes require prior discussion and owner approval before implementation.
* **Update CHANGELOG:** For notable changes, please update `CHANGELOG.md` adhering to the "Keep a Changelog" format.

## Security

If you discover a security vulnerability, please **do not report it in a public issue**.

Security vulnerabilities must follow [SECURITY.md](SECURITY.md). Instead of public issues, please review the security policy and report it privately via email to [support@maatify.com](mailto:support@maatify.com).
