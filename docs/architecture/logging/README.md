# Logging Architecture — Index

This directory contains the **canonical architecture and specifications**
for the Unified Logging System.

Not all documents have the same authority level.
This index defines **what is binding**, **what is supporting**, and
**what is reference-only**.

---

## 🔴 Logging Semantics (Authoritative & Binding)

These documents are the authoritative source for logging-domain semantics and safety rules.
They remain subordinate to `AGENTS.md` and the root `EVENT_LOGGING_PACKAGE_REFERENCE.md` for
repository governance and the current public Runtime contract.

- 📘 **Unified Logging System — Arabic (Canonical)**
    - [`unified-logging-system.ar.md`](./unified-logging-system.ar.md)

- 📘 **Unified Logging System — English (Canonical)**
    - [`unified-logging-system.en.md`](./unified-logging-system.en.md)

These documents define:
- The 6 canonical logging domains
- The One-Domain Rule (strict)
- Security and data-safety rules
- Storage and archiving guarantees
- Operational policies (retries, limits, sanitization)

---

## 🟠 Canonical Supporting Specifications (Binding, Subordinate)

These documents **must comply** with the Unified Logging System.
They provide detailed rules and implementation guidance but **must not redefine semantics or the
current public Runtime contract**.

- **Unified Logging Design**
    - [`UNIFIED_LOGGING_DESIGN.md`](./UNIFIED_LOGGING_DESIGN.md)

- **Global Logging Rules**
    - [`GLOBAL_LOGGING_RULES.md`](./GLOBAL_LOGGING_RULES.md)

- **Canonical Logger Design Standard**
    - [`CANONICAL_LOGGER_DESIGN_STANDARD.md`](./CANONICAL_LOGGER_DESIGN_STANDARD.md)

- **Log Domains Overview**
    - [`LOG_DOMAINS_OVERVIEW.md`](./LOG_DOMAINS_OVERVIEW.md)

- **Log Storage and Archiving**
    - [`LOG_STORAGE_AND_ARCHIVING.md`](./LOG_STORAGE_AND_ARCHIVING.md)

If any inconsistency exists, apply the repository authority order: Owner decisions, the root
Package Reference, current Runtime evidence, and then these subordinate logging specifications.

---

## 🟢 Reference & Visualization Documents (Non-Binding)

These documents are provided for understanding, visualization,
and future library extraction.
They are **not authoritative** and have been moved to `docs/reference/logging/`.

- **Logging ASCII Overview**
    - [`../../reference/logging/LOGGING_ASCII_OVERVIEW.md`](../../reference/logging/LOGGING_ASCII_OVERVIEW.md)

- **ASCII Flow Legends**
    - [`../../reference/logging/ASCII_FLOW_LEGENDS.md`](../../reference/logging/ASCII_FLOW_LEGENDS.md)

- **Logging Library Structure (Canonical Reference)**
    - [`../../reference/logging/LOGGING_LIBRARY_STRUCTURE_CANONICAL.md`](../../reference/logging/LOGGING_LIBRARY_STRUCTURE_CANONICAL.md)

---

## Canonical Blueprints

The following documents define **authoritative architectural blueprints**
that all logging modules MUST follow:

- **LOGGING_MODULE_BLUEPRINT.md**
  - Defines the universal, library-grade standard for building logging modules.
  - Covers recorder ownership, policy isolation, DTO contracts, failure semantics, primitive
    readers, current domain-specific Admin Query APIs, and UI separation rules.
  - This blueprint is mandatory for all new logging modules.

### Reference Implementation

Reference implementations of this blueprint exist within the core domains of this package.


---

## 🚨 Change Policy (Critical)

Any change to:
- Logging domains
- One-Domain Rule
- Authoritative vs non-authoritative semantics
- Logged data categories
- Security or sanitization rules
- Storage or archiving guarantees

- Fail-open guarantees (Recorder exception boundary) apply only to:
  - `AuditTrail`
  - `SecuritySignals`
  - `BehaviorTrace` / `Operational Activity`
  - `DiagnosticsTelemetry`
  - `DeliveryOperations`
  - Each listed domain's `Recorder::record()` MUST NOT throw to the caller due to a recording failure.
  - For these domains, `Throwable` MUST be caught ONLY at the Recorder boundary (top-level).
  - An optional supplied PSR-3 logger MAY receive a sanitized diagnostic; no reporting or fallback
    channel is mandatory.
  - Swallowing is forbidden in Infrastructure / Repository / DTO layers (they MUST throw domain custom exceptions).
  - The only separate read-side swallow permitted is metadata decode corruption during read-mapping (metadata => null).
- `AuthoritativeAudit` is the explicit fail-closed exception:
  - Its Recorder MUST NOT catch or swallow recorder-boundary failures.
  - Integrity and storage failures MUST propagate; the transactional outbox guarantee is not replaced by best-effort handling.


is considered an **Architectural Change**
and requires a formal review and approval.

No silent or ad-hoc changes are allowed.

---

## ✅ Status

- **Architecture:** Approved
- **Stability:** Approved logging architecture; subordinate to repository authority
