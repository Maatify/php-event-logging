# ASCII_FLOW_LEGENDS.md

## Canonical ASCII Flow Language

**Project:** maatify/php-event-logging
**Status:** NON-BINDING (Reference Language)
**Scope:** All architecture, execution, storage, and logging diagrams
**Authority Alignment:**
This document MUST align with:

* `../../architecture/logging/unified-logging-system.ar.md`
* `../../architecture/logging/unified-logging-system.en.md`
* `../../architecture/logging/UNIFIED_LOGGING_DESIGN.md`

If a conflict exists, the **Unified Logging System documents win**.

**Audience:** Developers, reviewers, auditors, future maintainers
**Interpretation:** ZERO-AMBIGUITY

---

## 1. Purpose

This document defines the **only allowed ASCII language** for describing:

* Execution flow
* Error propagation
* Responsibility boundaries
* Safety boundaries

Any diagram in this repository (or extracted libraries) **MUST** comply with
these legends.

❌ No alternative symbols
❌ No informal arrows
❌ No implicit meaning
❌ No interpretation by “understanding the intent”

If a diagram violates these legends → **the diagram is INVALID**.

---

## 2. Core Flow Symbols

### Vertical Flow (Execution Order)

```
|
v
```

**Meaning:**

* Execution continues downward
* Each block represents the next execution step

---

### Horizontal Naming Convention

``` 
<ClassName>::method()
```

**Meaning:**

* Explicit call
* Caller → Callee
* No hidden side effects implied

---

## 3. Error & Exception Symbols

### Exception Raised

```
X (error)
```

**Meaning:**

* An exception is raised at this point
* Execution flow is interrupted

❗ This symbol **MUST NOT** appear without a defined outcome.

---

### Exception Propagation

```
throws <ExceptionName>
```

**Meaning:**

* Exception propagates upward
* Caller is now responsible

---

### Exception Catching (Explicit)

```
catches <ExceptionName>
```

**Meaning:**

* Exception is intercepted
* Control flow continues from this boundary

---

### Swallowing (Silencing)

```
swallow
```

**Meaning:**

* Exception is intentionally silenced
* Execution continues normally

⚠️ **CURRENT RUNTIME BOUNDARY**

* The five non-authoritative package recorders may catch and swallow recording
  failures at their recorder boundary because their current contracts are
  fail-open.
* `AuthoritativeAuditRecorder` is fail-closed and must not catch or swallow
  recorder-boundary failures.
* An explicitly supplied PSR-3 logger may receive a diagnostic for a
  non-authoritative failure. If none is supplied, the current Runtime has no
  mandatory fallback or reporting channel.
* Infrastructure and repositories MUST NOT swallow. They translate failures
  according to the applicable domain storage or query contract.

---

## 4. Canonical Failure Flow (Library Level)

```

Caller
  |
  v
<Subsystem>WriterInterface::write(WriteDTO)
  |
  v
Mysql<Subsystem>Writer (PDO)
  |
  v
DB
  |
  X (error)
  |
  v
throws <Subsystem>StorageException

```

### Interpretation (Current Boundary)

* Storage/PDO failures become the applicable domain storage exception.
* Admin Query validation, configuration, and execution failures become the
  applicable domain query exception.
* Infrastructure does not swallow, apply domain policy, or decide whether a
  recorder is fail-open or fail-closed.
* The recorder boundary owns the domain-specific failure semantics: the five
  non-authoritative recorders catch and swallow recording failures, while
  `AuthoritativeAuditRecorder` propagates them.

---

## 5. Canonical Recorder Safety Boundary (Package Level)

> Fail-open handling is part of the explicit non-authoritative package recorder
> contract. An external `SafeRecorder` is not a required current Runtime role.

```

Application / Host
  |
  v
<Subsystem>Recorder        (PACKAGE RECORDER)
  |
  v
<Subsystem>Writer          (PACKAGE CONTRACT)
  |
  X <Subsystem>StorageException
  |
  v
<Subsystem>Recorder catches (non-authoritative domains only)
  |
  v
swallow   (optional diagnostic to a supplied PSR-3 logger)
  |
  v
Main application flow continues

```

### Interpretation (Locked)

* The five non-authoritative package recorder contracts are fail-open.
* `AuthoritativeAuditRecorder` is the explicit fail-closed exception and does
  not catch or swallow recorder-boundary failures.
* A supplied PSR-3 logger MAY receive a diagnostic; reporting is not mandatory
  when no logger is supplied.
* The package remains framework-agnostic and does not require a host-side
  `SafeRecorder` wrapper.

---

## 6. Layer Responsibility Keywords

These keywords are **semantic markers** and MUST be respected.

```
Recorder
```

* Public package recording/coordinator boundary
* Delegates domain normalization/validation to the applicable Policy
* Builds commands/write DTOs as required by the domain contract
* Applies the domain's fail-open or fail-closed recorder semantics

```
Policy
```

* Independent domain-specific normalization/validation component
* Implements only the operations defined by that domain's current contract
* Does not decide storage failure handling

```
Infrastructure / Repository
```

* Executes storage or query operations
* Translates failures to applicable domain exceptions
* MUST NOT swallow, apply domain policy, or choose fail-open/fail-closed

```
Application
```

* Controllers / Middleware / CLI
* Must NEVER contain persistence logic
* Must not be presented as a package-owned recorder or storage boundary

---

## 7. DTO & Data Flow Rules (Diagram Level)

```
DTO
```

Means:

* Strongly typed object
* Serializable
* Has `toArray()` or equivalent
* Ad-hoc associative arrays MUST NOT replace a named command, DTO, or other
  defined contract
* Domain-defined metadata arrays may appear where the domain contract allows
  them

❌ This is FORBIDDEN as a contract substitute:

```
array
```

If data is passed, the diagram MUST name the applicable command, DTO, or
contract. It must not imply that every public recorder convenience method is
RecordDTO-only.

---

## 8. Forbidden Patterns (INVALID DIAGRAMS)

### ❌ Silent Library Failure

```

LibraryWriter
  |
  X (error)
  |
  swallow

```

---

### ❌ Implicit Catch

```

X (error)
  |
  v
continue

```

---

### ❌ Array-Based Data

```
write(array $data)
```

---

### ❌ Undefined Error Outcome

```
X error
```

(with no throws / catches / swallow defined)

---

## 9. Mandatory Validation Rules

Every ASCII diagram MUST satisfy:

1. Every `X (error)` has:

    * `throws` OR
    * `catches` OR
    * `swallow`

2. `swallow` appears only at the explicit non-authoritative Recorder boundary.

3. Infrastructure diagrams show the applicable domain storage or query
   exception and never show swallowing.

4. AuthoritativeAudit recorder diagrams show propagation rather than
   swallowing.

5. No diagram relies on reader interpretation

Violation of any rule = **Architectural Error**

---

## 10. Canonical Statement

This document defines a **language**, not guidance.

> If it is not represented here,
> it does not exist architecturally.

---

**END OF FILE**
