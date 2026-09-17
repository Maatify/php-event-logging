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

This phase is **not the final release-closure program**. After it completes, the mandatory post-consolidation sequence is defined in:

- `docs/blueprints/PRE_RC_RELEASE_CLOSURE_SEQUENCE.md`

That sequence requires, in order, a fresh Engineering Standards Adoption refresh using the central Adoption procedure, a fresh full-library standards review/remediation, closure of all remaining current-release roadmap/deferred commitments, a zero-open-commitment audit, and final exact-state release-readiness certification before any release authorization is considered.

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
13. Completion of this phase does not close the release program; `PRE_RC_RELEASE_CLOSURE_SEQUENCE.md` remains mandatory before release readiness.

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

### Step 10 — Repository-wide Markdown Documentation Rationalization Review

Before the final Documentation Consolidation Sweep, review **every package-owned Markdown file by content**, not by filename or directory alone.

The purpose is to remove documentation inflation and leave a small, navigable set that answers durable questions:

- What is the package and its current public contract?
- What are the six logging domains and what is each one for?
- What architectural and Owner decisions still govern the package?
- How are recording, reading, Admin Query, failure semantics, storage, factories/providers, and integration supposed to work now?
- What future/deferred scope is still genuinely approved and unimplemented?

Git and PR history are the normal source for **how completed work happened**. The current package tree must not become an execution diary.

#### Markdown classification

Every package-owned Markdown file must be classified as one of:

- `KEEP — CURRENT AUTHORITY/USAGE` — needed to understand, integrate, operate, test, secure, or maintain the current package.
- `CONSOLIDATE` — contains durable information, but that information should move into a smaller surviving authoritative document before the source file is deleted.
- `DELETE — HISTORY ONLY` — contains only completed chronology, old audit/review state, implemented roadmap steps, superseded proposal detail, or information already represented authoritatively elsewhere.
- `KEEP — DEFERRED/FUTURE` — contains still-approved unimplemented scope or constraints that do not yet have another authoritative home.

#### Strong deletion/consolidation candidates

The following categories must be challenged rather than retained by default:

- completed roadmaps whose remaining durable rules already have an authoritative home;
- completed rebuild/POC blueprints;
- historical audits and review reports;
- migration/transition reports;
- phase-completion records;
- execution plans whose work is fully implemented;
- duplicate architecture explanations;
- duplicate language/overview documents that add no unique maintained value;
- documentation indexes that only point to obsolete files;
- examples plans after the useful examples themselves and current usage docs are complete.

A completed file is **not** automatically deleted. Before deletion perform a Content Preservation Audit over the whole file and prove that it contains no unique:

- active Owner decision;
- current architecture rule or invariant;
- current public/compatibility contract;
- unresolved decision evidence still needed by this phase;
- approved future/deferred work;
- consumer/integrator guidance;
- security/reliability requirement;
- operational constraint.

Any such durable content must first be moved, without semantic loss, into an appropriate surviving authority such as the Package Reference, architecture, domain-purpose, integration, testing/security, or explicit future-scope document.

#### Target documentation shape

The surviving package-owned docs should be intentionally small and role-based, favoring:

- root package/reference and release-facing files required by package standards;
- durable Owner decisions that cannot be represented cleanly elsewhere;
- concise current architecture;
- domain classification/purpose and meaningful differences between the six domains;
- recording/read/Admin Query interaction and failure semantics;
- storage/schema boundaries;
- integration/manual wiring/factory/DI usage where each adds distinct value;
- testing/security/operational guidance with current maintainership value;
- genuinely unimplemented future/deferred scope.

Do not retain a roadmap or audit solely so future reviewers can reconstruct history; Git/PR history already serves that role.

**Deliverable:** a complete Markdown retention matrix in `docs/audits/DOCUMENTATION_INVENTORY.md` or a replacement working inventory, with an evidence-based disposition for every package-owned Markdown file.

### Step 11 — Documentation Consolidation Sweep

Only after Runtime truth is stable and the Markdown rationalization review is complete:

- update `EVENT_LOGGING_PACKAGE_REFERENCE.md`;
- update surviving architecture documents;
- update surviving integration/usage guides;
- update examples and testing/security docs where current behavior requires it;
- update `CHANGELOG.md`;
- execute approved Markdown consolidations and deletions;
- update `docs/audits/DOCUMENTATION_INVENTORY.md` to represent the surviving documentation system;
- update genuine future/deferred scope;
- remove completed roadmap/phase/audit history whose durable content has been preserved elsewhere.

Documentation must describe the final intended contract and current interaction model, not the chronology of how transitional APIs were replaced.

### Step 12 — Verification Gate

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

### Step 13 — Final Review Against Latest Parent and `main`

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
- verify the surviving Markdown set contains current durable value rather than completed execution clutter;
- verify exact-head CI evidence.

### Step 14 — Integrate Phase Draft Into PR #6

Only after the Phase Draft is accepted:

- squash-merge the Phase Draft into `draft/php-event-logging-standards-remediation` under the applicable standing authority;
- verify the child PR merged state and exact merge SHA;
- fetch the new exact PR #6 HEAD;
- rerun/re-evaluate the complete final integrated gate on the new PR #6 HEAD;
- update stale PR #6 metadata and final status.

### Step 15 — Owner-Controlled Current-Phase Merge

PR #6 remains the outer umbrella for the current remediation/consolidation phase. Its merge to `main` is **not** authorized by completion of this child phase.

Only after a fresh final PR #6 review and explicit Owner instruction may the outer umbrella be squash-merged to `main`.

Even after that merge, the package is **not release-ready solely because PR #6 is complete**. The mandatory release-closure program in `docs/blueprints/PRE_RC_RELEASE_CLOSURE_SEQUENCE.md` still follows:

1. Engineering Standards Adoption Refresh using the central Adoption procedure;
2. fresh full-library standards review and remediation;
3. roadmap/deferred-commitment closure, including all remaining current-release work;
4. zero-open-current-release-commitment audit;
5. final exact-state release-readiness certification.

Tagging, RC publication, Stable publication, or GitHub Release creation require separate explicit Owner authorization after that program completes.

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
- Repository-wide package-owned Markdown rationalization and consolidation.

### Out of Scope

- Phase 5 Reporting/Dashboard feature implementation in this current phase.
- Standards Adoption Refresh itself; it is a mandatory later phase defined by `PRE_RC_RELEASE_CLOSURE_SEQUENCE.md`.
- Fresh post-adoption full-library standards remediation; it is a mandatory later phase.
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
| Documentation rationalization | Complete Markdown retention matrix + no deleted unique current/future/Owner/usage content |
| Repository quality | Composer validation/audit, PHPStan, workflow lint, whitespace, CI Gate |

---

## 10. Documentation Impact

Expected documentation review surface is **all package-owned Markdown**, with particular attention to:

- `EVENT_LOGGING_PACKAGE_REFERENCE.md`
- `CHANGELOG.md`
- `README.md`
- `docs/architecture/**`
- `docs/integration/**`
- `docs/reference/**`
- `docs/examples/**`
- `docs/testing/**`
- `docs/roadmap/**`
- `docs/audits/**`
- domain `src/*/README.md`
- package-level `schema/README.md`
- active phase/release-closure blueprints under `docs/blueprints/`

Pinned Engineering Standards under `docs/php-engineering-standards/` are governed by the adoption mechanism and are **not** deleted merely as documentation clutter; their exact local set is addressed later by the mandatory Standards Adoption Refresh phase.

The final documentation diff must preserve current authoritative value while removing completed-process noise and duplication.

---

## 11. Definition of Done

This phase is complete only when all of the following are true:

- the complete relevant public/runtime surface has been inventoried;
- every affected Runtime artifact is classified `KEEP`, `REMOVE`, or `REPLACE` with evidence;
- Owner decisions required for breaking/architectural changes are recorded;
- no active architectural contradiction remains;
- all approved Runtime consolidation is implemented atomically;
- intended semantic exceptions remain intact;
- transitional/compatibility-only artifacts approved for removal have no surviving Runtime, test, example, or active-doc references;
- all retained contracts have Regression protection appropriate to their role;
- strict real MySQL Integration passes where persistence is involved;
- Consumer Verification Harness passes on the exact integrated Phase Draft HEAD;
- every package-owned Markdown file has a reviewed retention disposition;
- durable decisions/architecture/usage/future constraints from deleted docs have a surviving authoritative home;
- completed roadmap/audit/phase history without current durable value is not retained merely as documentation history;
- documentation matches final Runtime truth;
- Phase 5 remains unstarted in this phase unless separately authorized;
- `PRE_RC_RELEASE_CLOSURE_SEQUENCE.md` is present as the mandatory continuation before release readiness;
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
6. Read `docs/blueprints/PRE_RC_RELEASE_CLOSURE_SEQUENCE.md` so the post-phase mandatory sequence is not lost.
7. Read `EVENT_LOGGING_PACKAGE_REFERENCE.md`.
8. Read `docs/audits/ADMIN_QUERY_PHASE_1_RUNTIME_COMPATIBILITY_INVENTORY.md` as retained historical baseline evidence while the contract classification still depends on it.
9. Read the relevant active architecture documents for the step currently in progress.
10. Determine the last completed numbered Step and Work Unit from merged PR state and repository files, not from chat history.
11. Continue only from the next incomplete gate.

If GitHub state contradicts this document, GitHub/live repository evidence and the authority order in `AGENTS.md` control; update stale lifecycle metadata before proceeding.
