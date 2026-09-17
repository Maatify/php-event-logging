# Pre-RC Release Closure Sequence

**Status:** Active Release-Closure Program Definition  
**Package:** `maatify/php-event-logging`  
**Current lifecycle:** Development / Pre-Stable  
**Current active phase:** PR #23 — Pre-RC Contract & Surface Consolidation  

> This document defines the mandatory sequence after the current contract/documentation consolidation work. It exists so a future Lead session can continue the release-closure program without reconstructing the intended order from chat history.

---

## 1. Release-Closure Goal

Before the first RC/release action for `maatify/php-event-logging`, the package must reach a closed current-release scope:

- current Runtime/public surface intentionally consolidated;
- package-owned documentation reduced to current durable value;
- Engineering Standards adoption refreshed from the central standards repository using the canonical Adoption procedure;
- the resulting exact adopted standards set applied in a fresh full-library review;
- every confirmed standards finding remediated;
- every previously defined current-release roadmap commitment either completed or explicitly removed/reclassified by an Owner decision;
- no forgotten `Not Started`, `Pending`, partial, recommended-before-release, or silently deferred work remains in the current release scope;
- final verification is run against the exact final repository state.

The goal is not to preserve every historical suggestion. The goal is to prove that the package has **zero unresolved current-release commitments** before release authorization is considered.

---

## 2. Governing Principle

Git/PR history owns execution chronology. Active package documentation owns current truth, durable decisions, architecture, usage/integration, and explicitly approved future scope.

Each phase below must follow `AGENTS.md` and the pinned GitHub Phase Stack workflow:

`Blueprint → Draft Integration PR → Work Units → Verification → Documentation Sweep → Final Review → Ready → Owner-controlled merge`

No later phase may be treated as implicitly complete because an earlier CI run or historical review passed.

---

## 3. Ordered Program

### Phase A — Current Pre-RC Contract & Documentation Consolidation

Current phase: PR #23.

Complete the work defined by:

- `docs/blueprints/PRE_RC_CONTRACT_SURFACE_CONSOLIDATION_BLUEPRINT.md`

This phase includes:

- Runtime contract inventory and `KEEP / REMOVE / REPLACE` classification;
- recording/write-path review;
- primitive read review;
- Admin Query review;
- composition/invocation review;
- removal/replacement of approved compatibility/transitional pollution;
- repository-wide Markdown rationalization and consolidation;
- exact-head verification and final review.

This phase does **not** itself authorize release.

---

### Phase B — Engineering Standards Adoption Refresh

After the current consolidation phase is integrated and the repository state to be certified is stable, refresh the repository's adopted Engineering Standards from the central source:

`Maatify/php-engineering-standards`

#### Canonical procedure

Do **not** invent an adoption workflow or copy the standards repository wholesale.

At execution time, fetch the exact upstream commit intended for adoption and follow the canonical procedure in:

`standards/STANDARDS_ADOPTION_STANDARD_AR.md`

That file explicitly defines itself as the **single source of truth for the Adoption mechanism**.

The current local pinned copy is located at:

`docs/php-engineering-standards/standards/STANDARDS_ADOPTION_STANDARD_AR.md`

However, because this phase is specifically a refresh, the executor must first resolve the intended exact upstream commit and read the Adoption Standard from that exact upstream state before changing the local adoption set.

#### Mandatory adoption rules

The phase must follow the Adoption Standard rather than re-documenting or improvising its algorithm. In particular:

- use Selective Pinned Adoption;
- never use a full `standards/` repository snapshot as a fallback;
- pin copied upstream files to one exact upstream commit unless an explicit Owner exception exists;
- resolve active Profiles and inherited Profiles structurally;
- evaluate candidate Standards against their canonical applicability and the repository's actual artifact/scope facts;
- copy only the required Pinned Adoption Control Set and final Pinned Applicable Standards Set;
- regenerate/update the local `STANDARDS_MANIFEST.md` as the resolver record;
- remove stale locally pinned standards/profile files that no longer belong to the resolved adoption set;
- verify all copied files actually match the recorded upstream commit.

#### Deliverable

A reviewed Adoption Refresh Phase with a manifest that truthfully records:

- upstream repository;
- exact upstream adoption commit;
- active Profile activations/scopes;
- required inherited Profile manifests;
- Pinned Adoption Control Set;
- final Resolved Applicable Standards Set;
- Standard/Profile versions;
- any explicit Owner-approved exception.

No full-library compliance verdict is issued in this phase. This phase establishes the exact rules that the next phase must review against.

---

### Phase C — Fresh Full-Library Standards Review & Remediation

Immediately after Phase B, perform a **new full review of the then-current library** against the freshly adopted exact standards set.

Do not reuse the historical standards audit or the previous PR #6 acceptance as current compliance evidence.

#### Review basis

1. latest exact `main` / phase base state;
2. current `AGENTS.md`;
3. freshly generated `STANDARDS_MANIFEST.md`;
4. only the Profiles and Standards resolved as applicable by the refreshed Adoption set;
5. current Runtime, schema, Composer metadata, CI, tests, public docs, integration docs, examples, and release-facing presentation.

#### Required output

Produce an evidence-based findings matrix. Every applicable requirement must end as one of:

- compliant / proven;
- finding requiring remediation;
- not applicable, with reason from the Standard's applicability;
- explicit Owner-approved exception, if the governing Standard/process allows it.

A report alone is insufficient. Every confirmed finding in the current-release scope must be fixed through stacked Work Units and reverified.

#### Exit condition

Phase C closes only when:

- the freshly adopted applicable Standards have been fully reviewed;
- all current-release findings are resolved;
- no known applicable standards violation remains;
- exact-head CI/verification evidence corresponds to the remediated state.

---

### Phase D — Roadmap & Deferred-Commitment Closure

After standards compliance is clean, perform a repository-wide commitment inventory before implementing remaining planned functionality.

The purpose is to prevent previously agreed work from being forgotten merely because its roadmap/document has become old.

#### Mandatory sources

Review at minimum:

- every file currently under `docs/roadmap/`;
- current `docs/architecture/DEFERRED_SCOPE.md`;
- active architecture/decision documents that contain `future`, `deferred`, `pending`, `not started`, partial, or prerequisite work;
- current Package Reference and CHANGELOG for promised/incomplete capabilities;
- active testing/examples plans containing pre-release requirements or recommendations;
- any current Blueprint/Owner decision that explicitly schedules later work.

At the time this sequence was created, the repository contains at least these roadmaps:

- `docs/roadmap/ADMIN_QUERY_API_ROADMAP.md`;
- `docs/roadmap/TESTING_AND_EXAMPLES_HARDENING_ROADMAP.md`.

This list is a snapshot only. A future session must enumerate the live repository again.

#### Known current roadmap state at this snapshot

`ADMIN_QUERY_API_ROADMAP.md` currently records:

- Phase 4 pagination: complete;
- Phase 5 Reporting and Dashboard Summary Contracts: **Not Started**;
- Phase 6 Host Integration Documentation and Validation: **Pending Phase 5**.

`TESTING_AND_EXAMPLES_HARDENING_ROADMAP.md` contains a complete pre-Stable hardening plan. Its items must be compared against current Runtime/tests/examples/CI. Do not assume an old roadmap item is incomplete merely because the document still lists it, and do not assume it is complete merely because similar work exists.

#### Commitment classification

Every live roadmap/deferred item must be classified with evidence as exactly one of:

- `COMPLETE — PROVEN IN CURRENT REPOSITORY`;
- `REQUIRED FOR CURRENT RELEASE — IMPLEMENT`;
- `OWNER-REMOVED FROM CURRENT RELEASE`;
- `OWNER-MOVED TO EXPLICIT FUTURE RELEASE`.

There is no fifth implicit `leave pending` state.

If an item is already implemented, prove it and retire/consolidate the completed roadmap history during documentation rationalization.

If an item remains required for the current release, execute it as its own properly stacked Phase/Work Units.

If an item is intentionally not part of the current release, that must be an explicit Owner decision and the active documentation must clearly place it in future scope rather than leaving ambiguous `Pending`/`Not Started` language.

#### Admin Query roadmap requirement

Unless an explicit Owner decision changes the current-release scope, the currently unimplemented roadmap sequence must be addressed rather than forgotten:

1. Phase 5 — Reporting and Dashboard Summary Contracts for all six domains;
2. Phase 6 — Host Integration Documentation and Validation after Phase 5.

No domain may be silently omitted from the roadmap closure.

---

### Phase E — Current-Release Scope Closure Audit

After all required roadmap implementation phases are complete, run a final closure inventory over the entire repository.

This is not a historical audit artifact that must live forever. Its durable outcome should be represented in current authoritative docs and Git/PR history.

The Lead must search for unresolved current-release indicators including, as applicable:

- `Not Started`;
- `Pending`;
- `Partial`;
- `TODO` / `TBD`;
- `recommended before release` / `required before Stable`;
- deferred items without an explicit future-release Owner decision;
- active blueprints whose implementation is still incomplete;
- roadmaps with open current-release phases;
- current Package Reference gaps;
- examples/testing/security/CI requirements not yet proven.

Each hit must be dispositioned against the current release scope. Do not mechanically delete wording; resolve the underlying commitment first.

#### Zero-open-commitment gate

Before final release readiness can pass:

- current-release required roadmap items = `0` incomplete;
- applicable standards findings = `0` unresolved;
- active current-release architectural decisions awaiting implementation = `0`;
- undocumented intentional public contracts = `0` known;
- obsolete historical Markdown retained without durable value = `0` known after the documentation rationalization gate;
- release-required verification gates not run/passing = `0`;
- ambiguous `Pending`/`Not Started` items without Owner disposition = `0`.

Explicit future-release scope may remain only when it is intentionally classified as future by Owner decision and does not masquerade as incomplete current-release work.

---

### Phase F — Final Release Readiness Certification

Only after Phases A–E are complete, perform the final exact-state release-readiness review.

Required checks include all applicable package gates at that future exact head, including:

- Composer validation and dependency/platform checks;
- security/audit gate;
- strict optimized PSR autoload validation;
- PHPStan/static analysis;
- Unit tests;
- Regression tests;
- strict real MySQL Integration on supported PHP versions;
- Consumer Verification Harness;
- lowest-dependency verification;
- examples validation where part of the current contract;
- workflow lint;
- whitespace/diff check;
- CI Gate;
- current documentation/runtime alignment;
- release presentation/version/license consistency;
- final current-release commitment inventory with zero unresolved items.

All evidence must correspond to the exact release-candidate state. Historical green CI does not substitute for this gate.

Completion of Phase F means **release-ready**, not released. Tagging, RC publication, Stable publication, Packagist release handling, or GitHub Release creation still require explicit Owner authorization.

---

## 4. Git / Phase Sequencing

The release-closure program must preserve the repository's stacked-phase rule.

Do not put Standards Adoption Refresh, Standards Review/Remediation, remaining roadmap feature implementation, and final release certification into one giant implementation PR merely because they are consecutive gates.

Each meaningful phase gets its own Draft integration boundary and its own child Work Units where needed.

Recommended sequence from the live repository state at execution time:

1. complete and integrate the current Contract/Documentation Consolidation phase;
2. complete the appropriate final gate for that phase;
3. start a dedicated Standards Adoption Refresh phase;
4. after adoption is integrated, start a dedicated Fresh Standards Review/Remediation phase;
5. after compliance is clean, start the Roadmap/Deferred Commitment Closure program, split into real feature phases as required;
6. after all required roadmap work is complete, run the Current-Release Scope Closure Audit;
7. run Final Release Readiness Certification;
8. request Owner release authorization separately.

The exact parent/base for each future phase must be resolved from live GitHub state at that time. Do not reuse the SHAs recorded in earlier phases.

---

## 5. Restart Instructions

A new Lead/chat session continuing the release-closure program must:

1. read `AGENTS.md`;
2. fetch latest exact `main` and open PR state;
3. read `PRE_RC_CONTRACT_SURFACE_CONSOLIDATION_BLUEPRINT.md` if Phase A is still active;
4. read this `PRE_RC_RELEASE_CLOSURE_SEQUENCE.md` completely;
5. identify the last completed Phase from merged GitHub state, not chat memory;
6. if entering Standards Adoption Refresh, fetch the intended exact upstream `Maatify/php-engineering-standards` commit and read that commit's `standards/STANDARDS_ADOPTION_STANDARD_AR.md` before doing any adoption work;
7. if entering Standards Review, use only the freshly resolved local applicable Standards set;
8. if entering Roadmap Closure, enumerate the live `docs/roadmap/` directory and current deferred commitments again;
9. continue from the first incomplete mandatory gate.

---

## 6. Definition of Release-Closure Completion

The release-closure program is complete only when the repository can prove all of the following at one exact final state:

- current contract surface is intentional;
- current documentation is concise and authoritative;
- standards adoption is fresh and pinned;
- full-library compliance against that adoption is clean;
- all current-release roadmap commitments are complete or explicitly removed/moved by Owner decision;
- no previously planned current-release work is silently forgotten;
- all release-required tests/verification pass;
- no known current-release suggestion, recommendation, pending phase, or implementation gap remains open;
- package is ready for an Owner-controlled release decision.
