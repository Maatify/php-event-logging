# LOGGING MODULE BLUEPRINT

**Status:** Canonical blueprint / subordinate to repository authority
**Scope:** Universal Logging Standard
**Audience:** Architects & Library Developers

---

## 1. Purpose & Responsibility Template

Every logging module MUST define a strict, single-purpose scope. It is a **Library**, not a **Service**.

### Defining Scope
A logging module exists to **capture and persist** a specific category of events. It MUST NOT cross into business logic, authorization, or user management.

### Mandatory Rules
- Non-authoritative modules **MUST** be "Fail-Open" (never block the application). The
  `AuthoritativeAudit` module is the explicit fail-closed exception and must preserve its
  transactional outbox guarantee.
- **MUST** be "Side-Effect Free" (persistence only).
- **MUST** be "Framework Agnostic" (no reliance on HTTP stacks or DI containers).
- **MUST NOT** contain business rules (e.g., "If user is admin, do X").
- **MUST NOT** enforce security (e.g., "Check permissions before logging").

### Prevention of Scope Creep
- If an event triggers a side effect (e.g., sending an email), that logic belongs in the **Application**, not the Logging Module.
- The module is a **Passive Recorder**.

---

## 2. Canonical Module Boundary

The module is a strict **Black Box**. The single canonical root Package Reference is
`EVENT_LOGGING_PACKAGE_REFERENCE.md`, and the current package namespace is
`Maatify\EventLogging\<Domain>`.

### Inside the Module (The Library)
- **Recorder:** The entry point for writing.
- **Policy:** The logic for validation and normalization.
- **DTOs:** The strict data structure contracts.
- **Contracts:** The interfaces for storage.
- **Infrastructure:** The default storage drivers (e.g., MySQL).
- **Admin Query contracts:** Domain-specific offset/page request and result contracts for the
  six current Admin Query APIs.
- **Admin Query implementation:** Domain-owned filter construction, trusted SQL, row mapping,
  and query exception boundaries.

### Outside the Module (The Host Application)
- **Configuration:** Injecting dependencies (PDO, Clock, and an optional PSR-3 logger).
- **UI & Presentation:** UI, presentation, and reporting screens.
- **HTTP/API:** Controllers, routes, middleware, and permissions.
- **Exports & Localization:** Host-owned exports and localization.
- **Analytics:** Host-specific and cross-system analytics.
- **Future package boundary:** Phase 5 MAY introduce package-owned, domain-scoped reporting or
  dashboard-summary contracts only after separate Owner approval; those contracts are not current
  Runtime.

### Forbidden Access Patterns
- **Contract Bypass:** Consumers MUST use the domain contracts or an approved factory/binding;
  they MUST NOT bypass those contracts by reading package tables directly.
- **Bypassing the Recorder:** Writers MUST NOT bypass the Recorder to write to storage.
- **Mutable State:** DTOs MUST be immutable.

---

## 3. Mandatory Directory Structure

A logging module MUST follow this structure to ensure predictability and separation of concerns.

```text
ModuleName/
├── Contract/          # Interfaces (Writer, Reader, Policy)
├── DTO/               # Immutable Data Transfer Objects
├── Database/          # Canonical SQL Schema
├── Enum/              # Domain enum types (Severity, ActorType where applicable)
├── Exception/         # Domain-specific Exceptions
├── Infrastructure/    # Storage Drivers (MySQL, etc.)
├── Recorder/          # The Public Entry Point & Logic
│   ├── {Name}Recorder.php
│   └── {Name}DefaultPolicy.php
├── README.md          # Usage Documentation
└── TESTING_STRATEGY.md # Testing Rules
```

The root Package Reference is not duplicated inside each domain module. The package uses the
current namespace `Maatify\EventLogging\<Domain>` for its domain-owned contracts and types.

### Rationale
- **Recorder/**: Isolates the "Application-Facing" logic (validation, safety) from the "Storage-Facing" logic.
- **Infrastructure/**: Keeps external dependencies (PDO, Redis) isolated from the Domain logic.
- **DTO/**: Enforces structural contracts across boundaries.

---

## 4. Write-Side Blueprint (Recorder Pattern)

The **Recorder** is the heart of the module. It is the **only** permitted entry point for writing logs.

### Responsibilities
1.  **Accept Primitive/Enum Inputs:** Do not force the caller to build DTOs.
2.  **Validate & Normalize:** Delegate to the **Policy**.
3.  **Construct DTOs:** Convert valid inputs into immutable DTOs.
4.  **Persist:** Pass the applicable domain write contract to storage.
5.  **Guarantee Safety:** Catch and suppress **ALL** storage exceptions for non-authoritative
    recorders; `AuthoritativeAudit` is the explicit fail-closed exception.

### Fail-Open Guarantee
For non-authoritative domains, `record()` MUST return `void` and MUST NOT throw exceptions to the
caller. Failures are swallowed at the recorder boundary. If an optional PSR-3 logger was supplied,
it MAY receive a sanitized diagnostic; the current Runtime does not require a primitive last-resort
channel. `AuthoritativeAudit` remains fail-closed and propagates failures.

### Validation vs Sanitization
- **Validation:** Reject impossible states (e.g., "Event Key is null").
- **Sanitization:** Fix recoverable states (e.g., "Truncate strings to DB limit", "Coerce negative duration to 0").

### Requirements
- **Clock Abstraction:** MUST inject a `ClockInterface` (never call `new DateTime()` directly).
- **UUID Strategy:** MUST generate IDs (UUIDv4 or ULID) within the Recorder.

### Method Signature Template (Pseudocode)

```php
class ModuleRecorder {
    public function record(
        string $event,
        Enum|string $severity,
        Enum|string $actorType,
        ?int $actorId,
        ?array $metadata
    ): void {
        try {
            // 1. Policy Normalization
            // 2. DTO Construction
            // 3. Applicable domain write contract->write(DTO)
        } catch (\Throwable $e) {
            // 4. Suppress for non-authoritative domains; optionally report via supplied PSR-3 logger
            // MUST NOT rethrow
        }
    }
}
```

---

## 5. Policy Pattern Blueprint

The **Policy** encapsulates the rules for "What is allowed to be logged".

### Purpose
To keep the Recorder clean and allow the Host Application to customize rules without modifying the library.

### Logic Responsibilities
- **Actor Type Normalization:** e.g., "Convert 'super-admin' to 'ADMIN'".
- **Severity Normalization:** e.g., "Truncate custom levels".
- **Metadata Handling:** Follow the current domain policy for size, sanitization, dropping, or
  replacement of oversized metadata.

### Forbidden Logic
- **Database Access:** Policies MUST be pure functions.
- **Side Effects:** Policies MUST NOT modify external state.

### Extensibility
The module MUST provide a `DefaultPolicy`. The Host Application MAY implement a custom Policy and inject it.

---

## 6. DTO Strategy

DTOs are the currency of the module.

### Rules
1.  **Immutable:** Properties MUST be `readonly`.
2.  **Strictly Typed:** No `mixed` types (except within verified metadata arrays).
3.  **Canonical Alignment:** DTO properties MUST map 1:1 to the canonical database schema.
4.  **No Behavior:** DTOs are data carriers only.

### Arrays
- **Structured Data:** MUST use DTOs.
- **Unstructured Data:** `metadata` arrays are allowed but MUST be validated by the Policy (size limits, depth).

### Cursor Representation
Cursor representation follows each domain's current primitive read contract. Some domains carry
cursor fields in their `QueryDTO`; `BehaviorTrace` and `DiagnosticsTelemetry` retain their legacy
`CursorDTO` read paths. This blueprint does not impose a standalone `CursorDTO` on every domain.

---

## 7. Read-Side Blueprint (CORE)

The module MUST provide a **Primitive Reader** for system access and any separately approved
archiving work.

### Primitive Reader Characteristics
- **Cursor-Based:** Pagination follows the domain's current primitive cursor contract; it may use
  cursor fields in a `QueryDTO` or a domain-specific legacy `CursorDTO`.
- **Sequential:** Ordered by time descending.
- **Stateless:** No "Page 5" logic; only "After Cursor X".

### Guarantees
- **Fail-Safe Hydration:** If the DB contains invalid data (e.g., manual edits), the Reader MUST NOT crash. It should sanitize on read.

**Read-Mapping Corruption Tolerance (Explicit Exception):**
- Readers MAY swallow JSON decode errors for `metadata` ONLY during read-mapping.
- In case of corruption, `metadata` MUST be set to null and the event returned.
- No other swallowing is permitted on the read-side.

### Why Required?
The primitive reader is independently verifiable and remains separate from the current Admin
Query path described below.

---

## 8. Current Admin Query Path and Host Presentation Boundary

The package provides a separate, domain-specific Admin Query path for all six logging domains.
It supports the approved filters, trusted sort mapping, count/data alignment, row mapping, and
offset/page result contract for each domain. It does not replace the primitive cursor reader.

### Package Responsibilities
- Validate each domain's request DTO.
- Build domain-owned filters, trusted SQL, and matching parameters.
- Delegate generic pagination mechanics to `maatify/persistence`.
- Return package-owned page and view DTOs with domain-specific exceptions.

### Host Responsibilities
1. The host maps its request and permissions to the domain Admin Query request DTO.
2. The host invokes the package-owned domain contract and maps the result to its response.
3. The host owns controllers, routes, authorization, UI presentation, exports, localization,
   and actor/entity name resolution.

The host MUST NOT query the package's storage tables directly as a substitute for the current
Admin Query contracts. The host owns reporting screens and host-specific/cross-system analytics.
Future Phase 5 may add package-owned domain-scoped reporting or dashboard-summary contracts only
after separate Owner approval; the current Runtime does not include them. Deferred scope remains
governed by `docs/architecture/DEFERRED_SCOPE.md`.

---

## 9. Failure Semantics (MANDATORY)

### The Golden Rule
**Non-authoritative logging must never break the application.** `AuthoritativeAudit` is the
explicit fail-closed exception and propagates integrity failures.

### Rules
- **Non-authoritative Recorder:** MUST catch `Throwable` and swallow it at the recorder boundary.
- **AuthoritativeAudit Recorder:** MUST preserve its fail-closed boundary and propagate integrity
  failures.
- **Infrastructure:** MAY throw `StorageException` (honest failure).
- **Policy:** follows the domain's current policy contract; this blueprint does not impose one
  universal exception rule.
- **Reader:** MAY throw (reads are not critical to user flow).

### Handling Failures
- **Non-authoritative recorders:** Swallow connection timeouts, SQL errors, and serialization
  errors after catching them at the recorder boundary.
- **AuthoritativeAudit:** Does not swallow outbox or storage failures.
- **Report (optional):** If a PSR-3 logger was supplied, it MAY receive sanitized exception details.
  No mandatory primitive fallback channel exists in the current Runtime.

### Recursion Guard (Hard Rule)

- Failure handling MUST NOT trigger any logging Recorder or Writer again.
- Failure reporting MUST NOT claim or require an unimplemented primitive fallback channel.
- Recursive logging attempts are forbidden.

---

## 10. Testing Blueprint

### Unit Tests
- **Target:** Recorder, Policy, DTOs.
- **Strategy:** Mock the Storage Interface.
- **Assert:** Correct DTO construction, correct Policy application, correct exception suppression.

### Integration Tests
- **Target:** Infrastructure (Repository).
- **Strategy:** Real Database (MySQL).
- **Assert:** Data persists, Round-trip (Write -> Read) works, Constraints (foreign keys, types) are honored.

### Constraints
- **MUST NOT** assert controller, permission, or UI behavior in package tests.
- **MUST** cover the primitive cursor reader and the current domain-specific Admin Query
  filtering, sorting, count/data alignment, mapping, and exception contracts.

---

## 11. Library-Readiness Checklist (Reusable)

Use this checklist to certify a module as "Blueprint Compliant".

- [ ] **Directory Structure**: strict separation of `Recorder`, `DTO`, `Contract`.
- [ ] **Dependency Safety**: No dependence on framework helpers (`request()`, `auth()`).
- [ ] **DTO Boundaries**: Writer, storage, and query contracts use DTOs where their current
   domain contract requires them; documented recorder convenience methods remain allowed.
- [ ] **Failure Boundary**: Non-authoritative Recorders catch all exceptions; AuthoritativeAudit
   preserves fail-closed behavior.
- [ ] **Policy Isolated**: Validation logic is in a separate class.
- [ ] **Primitive Reader**: A cursor-based reader is present.
- [ ] **Documentation**: `EVENT_LOGGING_PACKAGE_REFERENCE.md` is the single canonical root Package
   Reference.

---

## 12. Anti-Patterns to Explicitly Avoid

### ❌ The Split-Brain Recorder
**Anti-Pattern:** The Module contains only Storage Drivers, and the Application (Domain) implements the Recorder.
**Fix:** The Recorder MUST live inside the Module.

### ❌ Magic Arrays
**Anti-Pattern:** Passing associative arrays (`['user_id' => 1]`) deep into the system.
**Fix:** Convert to the applicable domain contract (often a DTO) at the recorder boundary; retain
documented recorder convenience methods where they are part of the current public surface.

### ❌ UI Coupling
**Anti-Pattern:** Adding UI routes, permissions, or dashboard behavior to a module.
**Fix:** Keep package queries domain-specific and framework-agnostic. Build presentation in the
Host on top of the current Admin Query contracts; keep the primitive reader separate.

### ❌ Hardcoded Dependencies
**Anti-Pattern:** `new MySQLRepository()`.
**Fix:** Depend on the domain contract or another approved explicit dependency. A PSR-3 logger is
only an optional diagnostic dependency when supplied; it is not the domain write contract.

### ❌ Throwing on Write
**Anti-Pattern:** Allowing non-authoritative logging DB errors to bubble up to the Controller.
**Fix:** Non-authoritative Recorders MUST catch and report failures; AuthoritativeAudit MUST
preserve its explicit fail-closed boundary.


## Namespace & Library Isolation (MANDATORY)

Any logging module MUST be treated as a standalone library from day one.

Rules:
- Modules MUST NOT live under the App\ namespace.
- Domain types MUST use the package namespace `Maatify\EventLogging\<Domain>`.
- Composer PSR-4 autoloading MUST reflect this isolation.
- The host application MUST act only as a consumer.

Rationale:
This guarantees zero-cost extraction of the module as a standalone library
without refactoring namespaces or internal references.
