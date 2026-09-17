# Pre-RC Contract & Surface Consolidation Blueprint

**Status:** Draft Phase Definition / Runtime Blocked Pending Contract Inventory and Owner Approval  
**Package:** `maatify/php-event-logging`  
**Parent Umbrella:** PR #6 — `draft/php-event-logging-standards-remediation`  
**Phase Draft:** `draft/pre-rc-contract-surface-consolidation`  
**Starting Parent HEAD Snapshot:** `411420083243d1b053b5f0046f4007a7590ce8e9`  
**Lifecycle:** Development / Pre-Stable  

> This document is the durable restart point for this phase. A new review session must reconstruct live GitHub state first, then continue from the ordered execution plan below. Branch names, PR numbers, and SHAs recorded here are snapshots and must never be treated as proof of current state without refetching GitHub.

---

## 1. Purpose

This phase exists to perform the final pre-RC contract and public-surface consolidation before any Phase 5 Reporting/Dashboard work or release preparation continues.

Earlier pagination work intentionally preserved compatibility while the package line was evolving. The Owner has now explicitly decided that the unpublished successor package must not carry transitional or compatibility-only pollution into its first RC merely because an unfinished pre-stable path once existed.

This phase therefore reviews the current public Runtime surface against the retained legacy baseline and the intended current architecture, then removes or replaces only artifacts proven to be transitional, duplicated, superseded, or architecturally unnecessary.

The phase is intentionally conservative: no symbol, method, DTO, repository, factory, provider, binding, test, or documented behavior may be removed merely because it looks old or redundant.

---

## 2. Governing Authority

Every task in this phase must use the repository authority order in `AGENTS.md`.

For this phase, the working order is:

1. Explicit Owner decisions recorded for this phase.
2. `EVENT_LOGGING_PACKAGE_REFERENCE.md`.
3. Exact current Runtime on the live Phase parent/base being reviewed.
4. Pinned standards under `docs/php-engineering-standards/`.
5. Approved architecture under `docs/architecture/`.
6. Roadmaps.
7. Historical audits and retained inventories as evidence only.

The primary retained historical baseline inside this repository is:

- `docs/audits/ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md`

The legacy repository `Maatify/event-logging` is **not** part of normal phase execution. It may be consulted only if a decision-relevant legacy fact is not represented by the retained in-repository snapshot or other preserved repository evidence.

---

## 3. Current State

At phase start, the intended system model is:

- Exactly six logging domains:
  - `AuthoritativeAudit`
  - `AuditTrail`
  - `SecuritySignals`
  - `BehaviorTrace` as the implementation of Operational Activity
  - `DiagnosticsTelemetry`
  - `DeliveryOperations`
- PSR-3 is a diagnostic/fallback channel, not a seventh domain.
- Domain isolation is mandatory; no generic logger, generic recorder, cross-domain writer, or string-routed logging API is allowed.
- `AuthoritativeAudit` is intentionally different from the other five domains:
  - fail-closed;
  - transactional outbox source of truth;
  - governed business change must fail if the authoritative outbox write fails;
  - no PSR-3 fallback may convert this into best-effort behavior.
- The five non-authoritative domains are fail-open at the Recorder boundary and may use an optional PSR-3 fallback logger.
- Infrastructure repositories/writers remain honest: storage failures throw and are not swallowed inside infrastructure.
- Primitive read/query APIs exist independently of Admin Query and support cursor-oriented sequential processing, export, migration, and similar non-grid use cases.
- Separate Admin Query APIs exist for all six domains for host-owned administrative screens using deterministic offset/page pagination.
- Superseded post-legacy-v1 pagination wrapper experiments already identified by prior rebuild work were removed.
- Phase 5 Reporting/Dashboard work has not started and remains blocked until this consolidation phase is closed.

---

## 4. Problem Statement / Gaps To Resolve

The package currently carries multiple generations of public and semi-public surfaces: published legacy primitives, compatibility-preserving additions, post-v1 experiments, completed Admin Query replacements, composition helpers, and current pre-stable APIs.

Before RC, the repository must prove which surfaces are intentionally canonical and which exist only because prior work avoided breaking contracts during development.

The phase must answer, with file-level and contract-level evidence:

- Which public symbols are inherited intentional contracts with independent use cases?
- Which symbols are compatibility-only baggage and no longer needed for the successor package?
- Which post-v1 APIs are architecturally correct and should become the first RC contract?
- Which post-v1 APIs duplicate another canonical path and should be removed or redesigned?
- Which apparent differences between domains are intentional semantic exceptions rather than inconsistency?
- Which convenience APIs are useful composition boundaries, and which create unnecessary duplicate ways to perform the same operation?
- Whether current tests and documentation protect intended behavior or accidentally freeze transitional behavior.

---

## 5. Fixed Decisions / Contracts That Must Not Be Accidentally Flattened

The following are fixed phase constraints unless the Owner explicitly changes them:

1. Preserve the six-domain classification and One-Domain Rule.
2. Preserve PSR-3 as a diagnostic/fallback channel only; it is not a logging domain.
3. Preserve domain isolation and prohibit generic routing such as `log(string $domain, ...)`.
4. Preserve `AuthoritativeAudit` fail-closed transactional outbox semantics.
5. Preserve fail-open Recorder-boundary behavior for the five non-authoritative domains.
6. Preserve infrastructure honesty: repositories/writers throw storage failures and do not decide fail-open/fail-closed policy.
7. Do not remove the primitive query path wholesale merely because Admin Query exists; its independent sequential/export/migration role must be evaluated and retained where intentional.
8. Do not preserve a post-v1 or pre-stable API solely because it previously existed during development.
9. Do not treat a historical audit or old PR body as current Runtime authority.
10. Do not start Phase 5 Reporting/Dashboard implementation until this phase is complete.
11. Do not create an RC, Stable release, tag, or GitHub Release in this phase.
12. Do not merge the Phase Draft or parent umbrella to `main` without explicit Owner authorization.

---

## 6. Classification Model

Every affected public or behaviorally relevant artifact must be explicitly classified before implementation as exactly one of:

### KEEP — Intentional Canonical Contract

Retain because it has an independent architectural purpose or is an explicitly protected/approved contract.

### REMOVE — Transitional / Compatibility-Only / Superseded Artifact

Delete only when evidence proves that the artifact has no required independent use case and is not a contract the approved target architecture intends to carry forward.

### REPLACE — Required Capability, Wrong Current Surface

The capability remains required, but the current public shape is duplicated, polluted, inconsistent, or otherwise not the intended first-RC contract. Replacement must be atomic and fully tested.

No implementation is authorized until the classification matrix is complete enough to support the affected Work Unit and the relevant Owner decisions are recorded.

---

## 7. Ordered Execution Plan

### Step 0 — Phase Setup and State Freeze

- Create this Phase Draft from the exact current HEAD of PR #6.
- Keep PR #6 as the parent integration boundary and block its merge to `main` while this phase is active.
- Record current `main`, parent HEAD, Phase Draft HEAD, PR states, and current checks at the start of each review task.
- No Runtime implementation in Step 0.

### Step 1 — Baseline Reconstruction and Contract Matrix

Perform a complete repository-local reconstruction using:

- `EVENT_LOGGING_PACKAGE_REFERENCE.md`;
- `docs/audits/ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md`;
- current `src/` Runtime;
- current tests;
- current schemas;
- current architecture and integration docs;
- relevant changelog history.

Inventory every relevant public/runtime surface for all six domains, including:

- Recorders;
- `record()` and `recordCommand()` entry points;
- Commands;
- write DTOs;
- writer/logger/outbox-writer contracts;
- policies and enum interfaces where they affect public behavior;
- primitive Query interfaces;
- primitive Query DTOs;
- legacy `read()` methods and Cursor DTOs where present;
- View/Event DTOs;
- Admin Query interfaces;
- Admin Query request/result DTOs;
- Admin Query repositories and descriptor builders;
- public exceptions;
- Factories;
- `EventLoggingProvider`;
- `EventLoggingProviderFactory`;
- `EventLoggingBindings`;
- examples and integration-facing usage paths.

For each artifact record:

- exact path/symbol;
- origin/baseline evidence;
- current use case;
- current consumers inside the package;
- current tests protecting it;
- documentation references;
- whether it overlaps another path;
- proposed `KEEP / REMOVE / REPLACE` classification;
- rationale;
- expected migration impact.

**Deliverable:** complete Contract Classification Matrix inside this Blueprint or a directly linked Blueprint companion document.

### Step 2 — Recording / Write Path Review

Review the write-side API independently from pagination work.

Determine whether each of the following is intentional and necessary:

- primitive `record(...)` convenience entry point;
- `recordCommand(...)` command-based entry point;
- domain Command types;
- writer vs logger vs outbox-writer contract differences;
- Recorder-to-writer responsibilities;
- Policy responsibilities;
- fail-open/fail-closed exception boundaries;
- direct repository/writer construction surfaces;
- metadata and path-safety boundaries.

Mandatory safeguard: do **not** normalize `AuthoritativeAudit` into the same write/failure path as the other five domains.

### Step 3 — Primitive Read Contract Review

Review the primitive cursor/read surface as an independent capability.

At minimum, verify:

- canonical `find(*QueryDTO)` behavior across all six domains;
- cursor fields and ordering semantics;
- limit behavior;
- hydration/fallback semantics;
- exception boundaries;
- the independent sequential/export/migration use case;
- the extra legacy `read(?*CursorDTO, int $limit)` path in domains where it exists.

Known review candidates include the additional legacy `read()+CursorDTO` paths in `BehaviorTrace` and `DiagnosticsTelemetry`; they are candidates only, not pre-approved deletions.

### Step 4 — Admin Query Contract Review

Review the complete post-v1 Admin Query surface without assuming that pre-stable compatibility must be preserved.

For each of the six domains evaluate:

- `*AdminQueryInterface`;
- `*AdminQueryRequestDTO`;
- `*AdminPageResultDTO`;
- `*AdminQueryMysqlRepository`;
- descriptor/filter builders;
- exception types;
- filter semantics;
- null-state filters;
- sorting contract;
- pagination metadata;
- result item DTO reuse;
- duplication with primitive query behavior;
- delegation boundaries to `maatify/persistence`.

The goal is one clean, intentional Admin Query architecture for the first RC, not preservation of every intermediate shape that existed during development.

### Step 5 — Composition / Invocation Surface Review

Review package-level construction and invocation paths:

- six domain Factories;
- `EventLoggingProvider`;
- `EventLoggingProviderFactory`;
- optional `EventLoggingBindings`;
- manual wiring;
- direct contract injection.

Confirm that convenience helpers do not create a second semantic API or hide domain boundaries.

Explicitly prohibit introduction of a generic event-logging manager/router.

### Step 6 — Cross-Surface Consistency Review

After Steps 2–5, review the whole package as one consumer surface and detect:

- multiple public ways to perform the same operation with no independent purpose;
- mismatched naming that is accidental rather than semantic;
- duplicate pagination contracts;
- DTOs that exist only to bridge discarded experiments;
- exceptions that no longer correspond to a distinct boundary;
- documentation/examples that preserve obsolete paths;
- tests that freeze transitional behavior instead of intended contracts.

### Step 7 — Owner Decision Gate / Blueprint Freeze

Before Runtime implementation:

- present the completed `KEEP / REMOVE / REPLACE` matrix to the Owner;
- isolate every breaking or architectural decision that requires Owner approval;
- record approved decisions in this Blueprint and any required Owner-decision document;
- align contradictory active architecture documents before implementation;
- freeze the implementation scope and Work Units.

**No Runtime implementation before this gate is passed.**

### Step 8 — Runtime Work Units

Create only the Work Units justified by the approved matrix. Do not create empty or ceremonial Work Units.

Likely separation, subject to the frozen Blueprint:

- WU — Recording/Composition consolidation, if findings exist.
- WU — Primitive Read consolidation, if findings exist.
- WU — Admin Query consolidation, if findings exist.
- WU — Cross-surface cleanup/migration, if a distinct atomic change is required.

Each Work Unit:

1. starts from the current Phase Draft HEAD;
2. has its own branch and PR targeting the Phase Draft;
3. changes only approved scope;
4. includes required Unit/Regression/Integration coverage in the same atomic change;
5. is directly reviewed by the Lead;
6. is squash-merged into the Phase Draft only after the approved gate passes.

### Step 9 — Test and Consumer Contract Consolidation

After Runtime Work Units are integrated:

- remove tests that exclusively protect deliberately removed transitional contracts;
- add/retain Regression coverage for all `KEEP` contracts;
- add complete coverage for every `REPLACE` contract;
- prove real MySQL behavior for persistence changes;
- prove direct repository exception behavior where relevant;
- prove fail-open/fail-closed Recorder boundaries;
- prove AuthoritativeAudit transaction/outbox semantics remain intact;
- run the Consumer Verification Harness against the integrated Phase Draft.

If maintained host usage is in scope for a removed public surface, perform evidence-based host usage search before finalizing deletion/migration impact.

### Step 10 — Documentation Sweep

Only after Runtime truth is stable:

- update `EVENT_LOGGING_PACKAGE_REFERENCE.md`;
- update relevant architecture documents;
- update integration guides;
- update examples;
- update `CHANGELOG.md`;
- update `docs/audits/DOCUMENTATION_INVENTORY.md`;
- update the roadmap and phase status;
- retire or reclassify obsolete historical/transition documents only after Content Preservation Audit where required.

Documentation must describe the final intended contract, not the chronology of how transitional APIs were replaced.

### Step 11 — Verification Gate

The integrated Phase Draft must run, as applicable:

- `composer validate --strict`;
- optimized strict PSR autoload validation;
- Composer audit;
- PHPStan/static analysis;
- Unit tests;
- Regression tests;
- strict real MySQL Integration on supported PHP versions;
- Consumer Verification Harness;
- lowest-dependency verification;
- workflow lint;
- `git diff --check` / whitespace gate;
- repository CI Gate.

Every result must be tied to the exact Phase Draft HEAD. Skipped, unavailable, not-run, or failed gates must be reported truthfully and never represented as passing.

### Step 12 — Final Review Against Latest Parent and `main`

The Lead performs a fresh review after all WUs and documentation are integrated:

- fetch latest remote `main` exact SHA;
- fetch exact PR #6 parent HEAD;
- fetch exact Phase Draft HEAD;
- recalculate diff and mergeability;
- review final changed-file list and contract impact;
- verify no scope leakage;
- verify documentation matches Runtime;
- verify no transitional surface remains unintentionally active;
- verify no intended semantic exception was flattened;
- verify exact-head CI evidence.

### Step 13 — Integrate Phase Draft Into PR #6

Only after the Phase Draft is accepted:

- squash-merge the Phase Draft into `draft/php-event-logging-standards-remediation` under the applicable standing authority;
- verify the child PR merged state and exact merge SHA;
- fetch the new exact PR #6 HEAD;
- rerun/re-evaluate the complete final integrated gate on the new PR #6 HEAD;
- update stale PR #6 metadata and final status.

### Step 14 — Owner-Controlled Final Merge

PR #6 remains the outer umbrella. Its merge to `main` is **not** authorized by completion of this child phase.

Only after a fresh final PR #6 review and explicit Owner instruction may the outer umbrella be squash-merged to `main`.

Tagging, RC publication, Stable publication, or GitHub Release creation require separate explicit Owner authorization.

---

## 8. Scope

### In Scope

- Public and behaviorally relevant Runtime contracts across all six domains.
- Recording/write invocation surfaces.
- Primitive read/query surfaces.
- Admin Query surfaces.
- Factory/provider/bootstrap composition surfaces.
- Related exceptions, DTOs, repositories, mappers/builders, tests, examples, and documentation.
- Removal of proven transitional or compatibility-only artifacts before first RC.
- Replacement of duplicated or architecturally incorrect pre-stable contracts where explicitly approved.

### Out of Scope

- Phase 5 Reporting/Dashboard feature implementation.
- New logging domains.
- Generic cross-domain querying or logging.
- Host controllers/routes/UI/permissions/localization.
- New archive/retention implementation.
- Outbox consumer/materialization implementation unless separately authorized.
- Unrelated schema redesign.
- Unrelated dependency/Composer/CI redesign.
- RC/Stable/tag/release publication.

---

## 9. Test Matrix

| Area | Minimum Required Evidence |
|---|---|
| Recording contracts retained | Unit + Regression |
| Fail-open five-domain boundary | Unit/Regression + focused Integration where persistence behavior matters |
| AuthoritativeAudit fail-closed/outbox behavior | Regression + real MySQL Integration |
| Primitive query retained behavior | Regression + real MySQL Integration |
| Removed primitive compatibility path | Proof of classification + removal of obsolete tests/docs + no surviving package references |
| Admin Query retained/replaced behavior | Unit + Regression + real MySQL Integration |
| Pagination delegation | Tests proving `maatify/persistence` boundary and deterministic results |
| Factory/Provider/Bindings | Unit/Regression + Consumer Harness |
| Schema-impacting change, if separately approved | Schema Regression + real MySQL Integration |
| Final integrated consumer contract | Consumer Verification Harness with clean runs |
| Repository quality | Composer validation/audit, PHPStan, workflow lint, whitespace, CI Gate |

---

## 10. Documentation Impact

Expected documentation review surface includes, at minimum:

- `EVENT_LOGGING_PACKAGE_REFERENCE.md`
- `CHANGELOG.md`
- `README.md` when consumer-facing usage changes
- `docs/architecture/PRIMITIVE_READ_QUERY_SUPPORT_DESIGN.md`
- `docs/architecture/ADMIN_QUERY_API_ARCHITECTURE.md`
- relevant domain blueprints/decision documents
- `docs/architecture/FACTORY_AND_PROVIDER_DESIGN.md`
- `docs/architecture/INTEGRATION_SURFACE_DESIGN.md`
- logging architecture documents where failure/invocation semantics are described
- `docs/integration/ADMIN_READ_USAGE.md`
- `docs/integration/FACTORY_USAGE.md`
- `docs/integration/MANUAL_WIRING.md`
- `docs/integration/DI_BINDINGS.md`
- `docs/examples/EXAMPLES_COVERAGE_PLAN.md`
- `docs/roadmap/ADMIN_QUERY_API_ROADMAP.md`
- `docs/audits/DOCUMENTATION_INVENTORY.md`

The actual documentation diff must be driven by approved Runtime changes; this list is a review surface, not permission to edit unrelated documents.

---

## 11. Definition of Done

This phase is complete only when all of the following are true:

- the complete relevant public/runtime surface has been inventoried;
- every affected artifact is classified `KEEP`, `REMOVE`, or `REPLACE` with evidence;
- Owner decisions required for breaking/architectural changes are recorded;
- no active architectural contradiction remains;
- all approved Runtime consolidation is implemented atomically;
- intended semantic exceptions remain intact;
- transitional/compatibility-only artifacts approved for removal have no surviving Runtime, test, example, or active-doc references;
- all retained contracts have Regression protection appropriate to their role;
- strict real MySQL Integration passes where persistence is involved;
- Consumer Verification Harness passes on the exact integrated Phase Draft HEAD;
- documentation matches final Runtime truth;
- Phase 5 remains unstarted unless separately authorized;
- final review against latest parent and `main` finds no unresolved blocker;
- the Phase Draft is accepted for squash integration into PR #6.

---

## 12. Restart Instructions For A New Chat / New Lead Session

A fresh session must **not** assume this document's recorded SHAs or statuses are still current.

Start exactly as follows:

1. Read `AGENTS.md` completely.
2. Fetch latest remote `main` exact SHA.
3. Fetch PR #6 exact live state, base/head SHAs, Draft/Ready status, diff, files, mergeability, and checks.
4. Fetch the Phase Draft PR exact live state and head SHA.
5. Read this Blueprint completely.
6. Read `EVENT_LOGGING_PACKAGE_REFERENCE.md`.
7. Read `docs/audits/ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md` as retained historical baseline evidence.
8. Read the relevant active architecture documents for the step currently in progress.
9. Determine the last completed numbered Step and Work Unit from merged PR state and repository files, not from chat history.
10. Continue only from the next incomplete gate.

If GitHub state contradicts this document, GitHub/live repository evidence and the authority order in `AGENTS.md` control; update stale lifecycle metadata before proceeding.
