# 📘 النسخة العربية

## **Unified Logging System — الوثيقة المعمارية المعيارية للدومينات**

**الحالة:** Canonical / Approved
**الغرض:** المرجع المُلزم لدلالات الدومينات وقواعد السلامة، مع خضوعه لترتيب السلطة في المستودع؛
أما سلوك Runtime العام الحالي فيحكمه `EVENT_LOGGING_PACKAGE_REFERENCE.md`.

---

## 1. هدف النظام

بناء نظام Logging معماري صارم يمنع خلط الدلالات (Semantic Mixing)، ويضمن:

* سرعة التحقيقات الأمنية
* دقة المراجعات القانونية والتنظيمية
* وضوح تحليل السلوك
* استقرار تحسين الأداء

### المخرجات الأساسية

* كل حدث يُسجَّل في **دومين واحد فقط** (One-Domain Rule)
* كل دومين يملك تخزينًا معزولًا دلاليًا داخل MySQL، وقد يتكون من علاقة أو أكثر مملوكة للدومين
* منع تسجيل أي أسرار أو بيانات حساسة
* تصميم قابل للاستخراج لاحقًا كمكتبات مستقلة

---

## 2. القاعدة الذهبية: One-Domain Rule

أي Logged Event يجب أن ينتمي إلى **دومين واحد فقط** بناءً على **النية الأساسية** للحدث.

### عند وجود تداخل ظاهري

* ❌ ممنوع تكرار نفس الحدث في أكثر من دومين
* ✅ يُعتبر الحدث أكثر من **نية مستقلة**
* يتم تسجيل **أحداث متعددة منفصلة** ببيانات minimal

> الهدف: منع تلوث السجلات وتضارب التقارير.

---

## 3. الدومينات المعتمدة (نهائية)

لا يُسمح إلا بـ **6 دومينات فقط**:

1. Authoritative Audit
2. Audit Trail
3. Security Signals
4. Operational Activity
5. Diagnostics Telemetry
6. Delivery Operations

---

## 4. تعريف الدومينات

### 4.1 Authoritative Audit (Fail-Closed / Governance)

* سجل حاكم وموثوق لأي تغيير في:

    * security posture
    * الصلاحيات
    * السياسات الحاكمة
* **مصدر الحقيقة:** `maa_event_logging_authoritative_audit_outbox` (Transactional)
* `maa_event_logging_authoritative_audit_log` = نموذج قراءة materialized فقط

❌ ممنوع:

* login failures
* permission denied
* exceptions
* notifications

---

### 4.2 Audit Trail (Data Exposure)

* الإجابة على: *مين شاف إيه ومتى؟*
* خاص بعمليات العرض والتنزيل والتصدير

❌ ممنوع:

* create/update/delete

---

### 4.3 Security Signals (Best-effort)

* إشارات أمنية للمراقبة والتحقيق
* لا تؤثر على control-flow
* غير transactional

---

### 4.4 Operational Activity (Mutations Only)

* تتبع التغييرات التشغيلية اليومية
* create / update / delete فقط

❌ ممنوع:

* read / view / export

---

### 4.5 Diagnostics Telemetry (Technical)

* مراقبة الأداء والصحة التقنية
* بدون أسرار وبدون PII
* best-effort

---

### 4.6 Delivery Operations (Async Lifecycle)

* تتبع:

    * email / sms / webhook
    * jobs / retries / failures

---

## 5. الـ Pipeline الموحد

```
HTTP/UI
 → Recorder
   → Writer/Logger
     → MySQL Storage
```

يمثل `HTTP/UI` جهة الاستدعاء في التطبيق المضيف عند حدود التكامل، ولا توفره هذه الحزمة.

### توزيع المسؤوليات (ملزم)

* **Recorder**

    * يبني DTO
    * يجمع الـ Context
    * يطبق الـ Policy
* **Writer / Logger**

    * يكتب DTO فقط
    * لا يبني DTO
    * لا يقرر policy

❌ ممنوع:

* Controllers أو Services تكتب Logs مباشرة
* بناء DTO يدوي خارج Recorder

يوفر Runtime الحالي أيضًا عقود Admin Query مستقلة ومملوكة للحزمة للدومينات الستة. وهي تدعم
القراءة المتخصصة لكل دومين باستخدام pagination من نوع offset/page، ولا تضيف Controllers أو واجهة
مستخدم أو صلاحيات أو تقارير أو طبقة استعلام عامة عابرة للدومينات. وتبقى القراءة البدائية القائمة
على cursor مسارًا منفصلًا ومحميًا.

---

## 5.1 دلالات الفشل (Failure Semantics — Canonical)

### الدومينات غير الحاكمة (Best-effort / Fail-Open)

تشمل:
- Audit Trail
- Security Signals
- Operational Activity
- Diagnostics Telemetry
- Delivery Operations

#### عقد الـ Recorder (قاعدة صارمة)
- `Recorder::record()` **ممنوع أن يرمي أي Exception** تحت أي ظرف.
- لذلك **يجب** على الـ Recorder أن يقوم بـ `catch(Throwable)` عند أعلى Boundary داخل `record()`.
- بعد الإمساك بـ `Throwable`:
  - يُسمح بالـ swallow (عدم إعادة الرمي).
  - إذا تم توفير PSR-3 logger اختياري، فيجوز إرسال تشخيص تشغيلي منقّى إليه.
  - لا توجد في Runtime الحالية قناة بدائية إلزامية أخيرة.
- يمنع منعًا باتًا كسر الـ control-flow للتطبيق بسبب logging.

#### عقد الـ Infrastructure (قاعدة صارمة)
- أي Driver / Repository **ممنوع** يبلع Exceptions.
- يجب رمي Exceptions خاصة بالدومين (Domain-specific storage exceptions).
- الصدق التشغيلي (Honest failure) إلزامي في طبقة التخزين.

#### منع التكرار اللانهائي (Recursion Guard)
- ممنوع أن تؤدي محاولة الإبلاغ عن فشل logging إلى استدعاء Recorder أو Writer آخر.
- يجب ألا تؤدي معالجة الفشل إلى استدعاء Recorder أو Writer آخر، ولا يجوز الادعاء بوجود قناة
  primitive بديلة غير منفذة.

---

## 6. الحقول المشتركة (Normalized Context)

يتبع كل دومين عقد التخزين الحالي الخاص به. وعند وجود الحقل في العقد، يشمل السياق الموحّد:

* event_id (UUID)
* actor_type
* actor_id
* correlation_id
* request_id
* route_name
* ip_address
* user_agent
* occurred_at DATETIME(6)

لا يجوز اختراع حقول غير موجودة في مخطط الدومين؛ فعلى سبيل المثال لا يحتوي AuthoritativeAudit
على حقل `request_id`.

### سياسة الوقت

* **occurred_at MUST be UTC**
* التحويل للـ timezone يتم في طبقة العرض فقط

---

## 7. تعريف request_id و correlation_id

* **request_id**

    * معرف فريد لكل HTTP request واحد
* **correlation_id**

    * يربط عدة requests ضمن نفس الـ workflow أو العملية التجارية

---

## 8. قواعد الأمان (Hard Rules)

ممنوع تسجيل:

* passwords
* OTP
* access tokens
* session secrets
* encryption keys

### URLs

* تخزين path فقط
* بدون query strings

### referrer_path

* يجب:

    * إزالة query
    * **إخفاء أو mask أي token أو secret داخل path**
    * مثال:

        * ❌ `/reset-password/abc123`
        * ✅ `/reset-password/{masked}`

---

## 9. سياسة metadata JSON

* يجب أن تكون metadata منظمة ومحدودة.
* يتبع حجم metadata ومعالجة القيم المتجاوزة سياسة كل دومين وعقد Runtime الحالي.
* يجوز للدومينات ذات fail-open تنقية metadata المتجاوزة أو إسقاطها أو استبدالها ثم متابعة التسجيل
  وفق عقد الدومين.
* لا تضع هذه الوثيقة حدًا عالميًا أو قاعدة رفض عامة، ولا تخترع سلوكًا لحمولة AuthoritativeAudit
  غير معرف في Runtime الحالية.
* لا تعيد الأرشفة المستقبلية تعريف سياسة metadata الخاصة بالـRecorder.

### استثناء تلف JSON أثناء القراءة (Read-Mapping)

- يُسمح للـ Readers فقط بابتلاع أخطاء JSON decode الخاصة بحقل `metadata` أثناء القراءة.
- في حالة التلف:
  - يجب أن تصبح `metadata = null`
  - ويجب إرجاع الحدث نفسه بدون إسقاطه.
- أي swallow آخر داخل Readers أو Mappers **ممنوع**.

---

## 10. actor_type — قواعد التطبيع والتحقق

تخضع عملية تطبيع والتحقق من `actor_type` لسياسة وعقد كل دومين في Runtime الحالية. لا تفرض هذه
الوثيقة قائمة عالمية مغلقة للقيم؛ ويتبع كل دومين القيم والسلوك المحددين في عقده الحالي.

---

## 11. التخزين (Baseline)

* التخزين في Runtime الحالية هو MySQL فقط (5.7+).
* التخزين معزول دلاليًا لكل دومين، وليس مطلوبًا أن يستخدم جدولًا واحدًا بالضبط لكل دومين.
* يملك AuthoritativeAudit جدول `maa_event_logging_authoritative_audit_outbox` بوصفه مصدر الحقيقة
  authoritative، وجدول `maa_event_logging_authoritative_audit_log` بوصفه نموذج القراءة المادي.
* MongoDB وأي backend آخر غير MySQL غير مدعوم.
* paging ثابت: `(occurred_at, id)` حيث ينطبق ذلك على عقد الدومين الحالي.

### قاعدة تحويل الأرقام (PDO / MySQL)

- بعض أعمدة MySQL الرقمية (مثل BIGINT) قد تُعاد من PDO كسلاسل نصية.
- يجب على Query Mappers اعتبار القيم الرقمية النصية صالحة.
- التحويل يجب أن يكون آمنًا (مثل `is_numeric` ثم cast)،
  وليس الاعتماد على `is_int` فقط.

---

## 12. الأرشفة (مؤجلة؛ MySQL → MySQL Mode B فقط)

* MySQL → MySQL
* جداول `*_archive`
* نفس الأعمدة والفهارس
* بدون Foreign Keys
* ملف SQL منفصل

هذه قيود مستقبلية فقط. لا ينفذ Runtime الحالي للحزمة الأرشفة أو عمال الاحتفاظ أو القراءة من
الجداول الساخنة والمؤرشفة. ولا يوجد Mode للأرشفة أو backend غير MySQL؛ راجع `DEFERRED_SCOPE.md`.

### قاعدة صارمة

> لا حذف من hot table إلا بعد نجاح النقل للأرشيف.

---

## 13. سياسات تشغيل مؤجلة (Operational Policies)

القواعد التالية قيود تشغيلية مستقبلية للمستهلكين أو الأرشفة أو عمال التسليم عند اعتمادها بشكل
منفصل. لا ينفذها Runtime الحالي للحزمة، ويحدد `DEFERRED_SCOPE.md` حدودها الحالية.

### 13.1 Outbox Processing

* retry: exponential backoff
* max attempts: configurable (افتراضي 10)
* بعد max → dead letter / manual intervention
* alert لو lag > threshold

### 13.2 Delivery Operations Retry

* max attempts: 5
* backoff تدريجي
* status نهائي: `failed_permanent`

### 13.3 Archiving Trigger

* افتراضي:

    * records أقدم من 90 يوم
    * batch size: 10K
    * تشغيل يومي
* قابل للتغيير

---

## 14. أمثلة تطبيقية

### login_failed

→ Security Signals

### create_admin

حدثان منفصلان:

1. Authoritative Audit
2. Operational Activity

---

## 15. حالة الوثيقة

✅ **دلالات الدومينات معتمدة**
أي تغيير مستقبلي يُعد تغييرًا معماريًا ويتطلب مراجعة رسمية، مع الحفاظ على التوافق مع مرجع الحزمة
الجذري وترتيب السلطة في المستودع.

---
