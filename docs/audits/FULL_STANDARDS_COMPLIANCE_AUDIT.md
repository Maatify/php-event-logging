# Full Standards Compliance Audit

> **نوع الوثيقة:** تدقيق كامل للمعايير فقط. هذه الوثيقة لا تنفذ أي Remediation ولا تغيّر Runtime أو الاختبارات أو Schema أو Composer أو CI.

## 1. نطاق التدقيق والمرجع المثبت

| البيان | القيمة |
|---|---|
| Repository | `Maatify/php-event-logging` |
| Audited base branch | `draft/php-event-logging-standards-remediation` |
| Exact audited base SHA | `351769df5769f4ab6bd947667c847c79c3f06821` |
| Audit branch | `audit/full-standards-compliance` |
| Intended PR base | `draft/php-event-logging-standards-remediation` |
| Remote `main` SHA observed before audit | `04c6cf0de050300122c110959343aacb70b8ac10` |
| Parent Draft PR | [PR #6](https://github.com/Maatify/php-event-logging/pull/6), Draft, open at the time of audit |
| Package identity | `maatify/php-event-logging` |
| Namespace root | `Maatify\EventLogging\` |

تم فحص الملفات الفعلية على الـ exact base أعلاه. لم يبدأ التدقيق من `main`، ولم تُنفذ أي Work Unit من المقترحة أدناه.

## 2. Standards وProfiles المستخدمة

### 2.1 Upstream pin

مصدر المعايير هو المستودع `Maatify/php-engineering-standards` عند الـ commit المثبت:

```text
2fc57f9320f8a7f7147fb20abbcfa311fdf40c28
```

تمت قراءة `docs/php-engineering-standards/STANDARDS_MANIFEST.md` أولًا، ثم كامل الملفات المدرجة في مجموعة التطبيق المحلولة. وتمت مطابقة بصمات الملفات المحلية مع blobs الصادرة من نفس الـ commit؛ لا يوجد floating reference إلى `main`.

### 2.2 Active Profiles

| Profile | Version | Scope | Inherited Profiles |
|---|---:|---|---|
| `composer-package` | `1.0.0` | `/` | لا يوجد |
| `repository-governance` | `1.0.0` | `/` | لا يوجد |

لا توجد Profiles موروثة أو إضافية في الـ resolved set.

### 2.3 Resolved Applicable Standards

| Standard | Version | Local pinned path |
|---|---:|---|
| `std-package-building` | `1.3.0` | `docs/php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md` |
| `std-composer-package` | `1.2.0` | `docs/php-engineering-standards/standards/packages/COMPOSER_PACKAGE_STANDARD.md` |
| `std-ci-workflow` | `1.1.0` | `docs/php-engineering-standards/standards/packages/CI_WORKFLOW_STANDARD.md` |
| `std-library-presentation` | `1.0.1` | `docs/php-engineering-standards/standards/packages/LIBRARY_PRESENTATION_STANDARD.md` |
| `std-testing` | `1.1.0` | `docs/php-engineering-standards/standards/testing/TESTING_STANDARD.md` |
| `std-ai-collaboration-workflow` | `6.0.0` | `docs/php-engineering-standards/standards/ai/AI_COLLABORATION_WORKFLOW_AR.md` |
| `std-github-phase-stack-workflow` | `2.2.0` | `docs/php-engineering-standards/standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md` |

هذه هي المجموعة النهائية الناتجة عن الـ adoption resolver، وليست snapshot احتياطية من مستودع المعايير.

## 3. Executive verdict

**النتيجة: NOT COMPLIANT — توجد 10 Confirmed Findings.**

المخالَفات موزعة بين Composer/PHP، CI، اختبار المستهلك، توثيق التحقق المحلي، metadata الخاصة بالـ schema، وDTO واحد. توجد أيضًا مناطق واسعة متوافقة أو غير منطبقة، موضحة صراحة في القسمين 5 و6. لم تُصلح أي مخالفة في هذا التدقيق.

## 4. Confirmed Findings

كل Finding أدناه يجمع نصًا معياريًا محددًا مع دليل قابل لإعادة الفحص من الـ repository. اختلاف الأسلوب وحده لم يُسجل مخالفة.

### F-001 — Composer lockfile متتبَّع في مكتبة Maatify قابلة لإعادة الاستخدام

- **Severity:** High
- **Standard / rule:** `std-composer-package` §25، وقاعدة المكتبات القابلة لإعادة الاستخدام التي تمنع committed `composer.lock`؛ وتدعمها مراجعة package composition في §4.
- **Repository evidence:** الملف `composer.lock` موجود في جذر المستودع ويظهر ضمن `git ls-files composer.lock`. وفي المقابل يذكر `CONTRIBUTING.md:5` أن `composer.lock` يجب ألا يُلتزم به، كما أن `.gitignore` يحتوي قاعدة تجاهله.
- **سبب المخالفة:** حالة Git الفعلية تخالف سياسة المكتبة المعلنة وسياسة Composer للمكتبات القابلة لإعادة الاستخدام؛ وجود قاعدة ignore لا يلغي ملفًا متتبَّعًا بالفعل.
- **Required remediation boundary:** قرار وسياسة lockfile داخل Composer/package workflow، مع إزالة الملف من التتبع عند تنفيذ Work Unit مصرح بها. لا يتضمن هذا التدقيق الحذف.

### F-002 — PHP compatibility contract أدنى من baseline الحالي

- **Severity:** High
- **Standard / rule:** `std-package-building` §1 يفرض PHP `>=8.4` للمكتبات الجديدة؛ و`std-composer-package` §15.1 يقرر نفس baseline للمكتبة الجديدة أو غير المنشورة Stable، مع استثناء المكتبة المنشورة سابقًا عند نفس الهوية والعقد.
- **Repository evidence:** `composer.json:48` يعلن `"php": "^8.2"`، و`composer.json:68-70` يثبت Composer platform على `8.2.0`. وتكرر ذلك `README.md:44,182`، و`EVENT_LOGGING_PACKAGE_REFERENCE.md:23`، و`docs/integration/INSTALLATION.md` في متطلبات PHP. `README.md:20` و`EVENT_LOGGING_PACKAGE_REFERENCE.md:5` يثبتان أن هوية `maatify/php-event-logging` ما زالت Development بلا Stable release؛ الإصدار التاريخي ذو الهوية المختلفة `maatify/event-logging` لا يفعّل استثناء الهوية الحالية.
- **سبب المخالفة:** الحزمة الحالية غير المنشورة Stable تستخدم baseline `8.2` بدل baseline الحالي `8.4`.
- **Required remediation boundary:** Owner-approved PHP support decision ثم مواءمة Composer والوثائق ومصفوفة CI. لا يُسمح بتغيير العقد في هذا PR.

### F-003 — CI يستخدم `composer update` رغم وجود lockfile متتبَّع

- **Severity:** High
- **Standard / rule:** `std-ci-workflow` §5: عند تتبع `composer.lock` يجب استخدام `composer install` وألا يعاد توليد lockfile داخل CI؛ أما `composer update` فهو مسار المكتبات التي لا تتتبع lockfile.
- **Repository evidence:** `composer.lock` متتبَّع. ويستخدم `.github/workflows/ci.yml:133` `composer update` في Quality، و`:190` في Latest Dependency Tests، و`:241` في Integration، و`:296` في Lowest Dependencies.
- **سبب المخالفة:** workflow resolution mode لا يطابق حالة lockfile الفعلية؛ jobs قد تعيد حل الاعتمادات بدل اختبار الحالة المثبتة.
- **Required remediation boundary:** مواءمة سياسة lockfile وdependency-resolution jobs مع قرار F-001، مع الحفاظ على مصفوفة latest/lowest المقصودة فقط حيث يسمح بها الـ Standard.

### F-004 — Change Detection لا يعتبر `composer.lock` مسارًا مؤثرًا

- **Severity:** Medium
- **Standard / rule:** `std-ci-workflow` §4: إذا كان `composer.lock` متتبعًا، فيجب أن يدخل في relevance detection حتى تؤدي تغييرات الاعتمادات إلى الـ heavy gates المناسبة.
- **Repository evidence:** `.github/workflows/ci.yml:60-70` تفحص `composer.json` و`phpstan.neon` و`phpunit.xml.dist` ومسارات المصدر والاختبارات والـ workflows، لكنها لا تتضمن `composer.lock`.
- **سبب المخالفة:** تغيير lockfile المتتبَّع يمكن أن يصنف كتغيير غير مؤثر، فيُتجاوز عنه مسار الاختبارات الثقيلة.
- **Required remediation boundary:** تعديل change detector ضمن CI policy بعد حسم سياسة lockfile؛ لا تعديل في هذا PR.

### F-005 — لا يوجد whitespace / patch-hygiene gate في CI

- **Severity:** Medium
- **Standard / rule:** `std-ci-workflow` §8.4 وchecklist §19: يجب أن يكون whitespace/patch hygiene check صريحًا ضمن CI، مثل `git diff --check` أو ما يعادله.
- **Repository evidence:** مراجعة كامل `.github/workflows/ci.yml` لا تظهر `git diff --check` أو whitespace validator أو job مكافئ. الأمر المحلي `git diff --check` مرّ على الحالة الحالية، لكنه ليس بديلًا عن gate موجود في CI.
- **سبب المخالفة:** سلامة diff ليست gate آليًا مطلوبًا في workflow الحالي.
- **Required remediation boundary:** إضافة gate صغير ومحدد إلى CI دون إعادة تصميم workflow أو تغيير gates غير المتعلقة به.

### F-006 — Consumer Verification Harness غير موجود

- **Severity:** High
- **Standard / rule:** `std-testing` §3.4 و§5: كل standalone reusable Package يجب أن يملك Consumer Verification Harness خارجيًا قابلًا لإعادة الإنتاج، بجذر Composer منفصل، production autoload/public API، workflow واقعي، وتشغيلين نظيفين متكررين. ويؤكد `std-package-building` §23 اكتمال package work only with the harness عند انطباقه.
- **Repository evidence:** فحص شجرة الملفات الفعلية ومسارات package/test/example لم يجد Consumer root أو Harness؛ لا يوجد مسار أو fixture أو Composer root خارجي مخصص لهذا الغرض. الاختبارات الحالية تحت `tests/`، وليست Harness خارجيًا منفصلًا.
- **سبب المخالفة:** وجود Unit/Regression/Integration tests لا يحقق طبقة Consumer Verification الإضافية المطلوبة للمكتبة standalone.
- **Required remediation boundary:** إنشاء Harness خارجي مستقل يستخدم الحزمة عبر production autoload/public API، مع workflow واقعي وتشغيلين نظيفين؛ لا تُنقل fixtures الداخلية إليه ولا يُنفذ هنا.

### F-007 — CI لا يشغّل Consumer Verification Harness ولا يربطه بالـ aggregate gate

- **Severity:** High
- **Standard / rule:** `std-ci-workflow` §2.2 و§16: Consumer Verification Harness، عند انطباقه، gate إلزامي fail-closed ويجب أن يدخل في aggregate gate.
- **Repository evidence:** jobs المدرجة في `.github/workflows/ci.yml:19-345` هي `changes`, `workflow-lint`, `quality`, `latest-dependency-tests`, `integration-tests`, `lowest-dependencies`, و`gate`. قائمة `needs` في `:304-310` لا تحتوي Harness job، وlogic الـ gate في `:329-338` لا يفحص نتيجة Harness.
- **سبب المخالفة:** حتى لو أُضيف Harness لاحقًا، لا توجد حاليًا آلية CI تشغله أو تمنع نجاح الـ aggregate gate عند فشله.
- **Required remediation boundary:** إضافة Harness execution وfail-closed aggregation ضمن CI، بعد توفير artifact الخاص بـ F-006.

### F-008 — توثيق التحقق المحلي لا يطابق مجموعة الـ applicable gates الحالية

- **Severity:** Medium
- **Standard / rule:** `std-ci-workflow` §2.1 يطلب local parity لكل gate منطبق؛ و`std-library-presentation` §16 يطلب أن يوضح `CONTRIBUTING.md` أو ما يعادله متطلبات التحقق المحلي، الاختبارات، Integration، وPR expectations.
- **Repository evidence:** `CONTRIBUTING.md:32-64` يوثق `composer install`, `composer validate`, `composer analyse` وPHPUnit، لكنه لا يوثق `composer audit`, platform verification, `git diff --check`, workflow lint، Consumer Harness، أو تحقق schema المطلوب. `TESTING_STRATEGY.md:3-10` يقول إن static analysis سيُضاف بعد تثبيت dependencies وCI، رغم أن PHPStan وCI موجودان فعليًا؛ كما لا يقدم مجموعة local gates النهائية.
- **سبب المخالفة:** تعليمات المساهمين والاستراتيجية لا تعكس مجموعة التحقق الحالية التي يفرضها الـ resolved CI/testing standards، وفيها عبارة زمنية stale عن static analysis.
- **Required remediation boundary:** توحيد أوامر وإرشادات التحقق المحلية في الوثائق فقط، بعد تثبيت قرارات WU-1 وWU-3؛ لا تغيير سلوك أدوات التحقق في هذا PR.

### F-009 — Schema columns بلا meaningful SQL `COMMENT`

- **Severity:** Medium
- **Standard / rule:** `std-package-building` §6 يطلب meaningful `COMMENT` على الأعمدة عند امتلاك package لجداول persistence، إضافة إلى table-level policy comments.
- **Repository evidence:** ملفات schema السبعة تحت `src/*/Database/` (`AuditTrail`, `AuthoritativeAudit`, `BehaviorTrace`, `DeliveryOperations`, `DiagnosticsTelemetry`, `SecuritySignals`) تحتوي table-level `COMMENT` فقط، كما يظهر مثلًا في `src/BehaviorTrace/Database/schema.maa_event_logging_behavior_trace.sql:44` و`src/DeliveryOperations/Database/schema.maa_event_logging_delivery_operations.sql:64`. لا توجد column definitions منتهية بـ SQL `COMMENT`; تعليقات `--` النصية لا تنشئ column metadata.
- **سبب المخالفة:** الجداول package-owned وتحقق naming/index/policy metadata جزئيًا، لكن metadata المطلوبة على مستوى الأعمدة غير موجودة في المجموعة الفعلية.
- **Required remediation boundary:** Schema migration/DDL metadata وschema verification المرافق، مع مراجعة أي documentation تعتمد على DDL. ممنوع تنفيذ schema change في هذا التدقيق.

### F-010 — `DeliveryOperationsAdminQueryRequestDTO` ليس class-level `readonly`

- **Severity:** Medium
- **Standard / rule:** `std-package-building` §9 يفرض أن تكون DTOs `final readonly` snapshots/results/requests، بالإضافة إلى `JsonSerializable` عند انطباق serialization contract.
- **Repository evidence:** `src/DeliveryOperations/DTO/DeliveryOperationsAdminQueryRequestDTO.php:9` يعلن `final class ... implements \JsonSerializable` وليس `final readonly class`. الحقول في `:11-41` معلنة `public readonly`، لكن class declaration نفسها ليست `readonly`.
- **سبب المخالفة:** readonly properties لا تحقق قاعدة الـ Standard التي تطلب readonly DTO class.
- **Required remediation boundary:** تعديل declaration والاختبارات المتأثرة فقط ضمن Runtime/DTO Work Unit منفصل؛ لم يُعدّل الملف هنا.

## 5. مناطق متوافقة وفق الأدلة الحالية

هذه ليست claims مبنية على غياب الأخطاء فقط؛ تم فحص الملفات والـ configurations ذات الصلة:

- adoption manifest يثبت `Resolution Status: VALID`، والـ active Profiles والـ resolved Standards والـ upstream commit متسقة.
- Composer identity، PSR-4 namespace، direct runtime dependencies، scripts الأساسية، و`type: library` موجودة ومتسقة مع standalone package composition، مع استثناءات F-001/F-002/F-003 الخاصة بالـ lock/PHP resolution.
- `phpstan.neon` يضبط Level Max ويفحص `src` و`tests` دون baseline أو `ignoreErrors`، وPHPStan المحلي مرّ.
- namespace root هو `Maatify\EventLogging\`، وأسماء interfaces/enums/exceptions تتبع suffix rules في الملفات المفحوصة. المخالفة الوحيدة المسجلة في DTO declarations هي F-010.
- مسارات Admin Query تستخدم shared `Maatify\Persistence\Pdo\Pagination` وتستخدم selected columns صريحة؛ لم تُسجل مخالفة duplicated pagination engine.
- مصادر الوقت تستخدم shared `ClockInterface`، ولم يظهر `date_default_timezone_set` في package source.
- exception boundaries وfail-open/fail-closed semantics الحالية موثقة ومغطاة ضمن الاختبارات الحالية؛ لا يثبت هذا التدقيق أي runtime violation إضافية.
- schema table prefixes، primary keys، indexes، table-level policy comments، وعدم وجود host foreign keys/JOINs تطابق القواعد المفحوصة؛ F-009 محدود إلى column comments.
- CI يحوي action pinning، permissions read، MySQL 8.0 health check، timeouts، concurrency، `fail-fast: false`، explicit audit، workflow lint مع checksum، ومصفوفات PHP؛ findings CI أعلاه محددة فقط فيما ثبت نقصه.
- README وSECURITY وCODE_OF_CONDUCT وCHANGELOG موجودة؛ حالة Development وغياب Stable/Packagist badges الحية قبل الإصدار موثقان بصورة متسقة، وREADME footer هو العنصر النهائي.
- Unit وRegression وIntegration suites معرفة في `phpunit.xml.dist`، وPHPUnit architecture الحالية تفصل suites؛ نتيجة Integration المحلية غير متاحة بسبب البيئة كما هو موضح في §7.

## 6. Not Applicable وUncertainty

### 6.1 Not Applicable

- code-style formatter gate لم يُسجل كمخالفة لأن repository لا يملك code-style configuration أو gate مفروضًا يمكن نسبته إلى هذا الـ Standard في الحالة المفحوصة.
- controllers، routes، UI، host framework wiring، browser E2E، وconsumer presentation ليست package-owned capabilities لهذا standalone library.
- release/tag/published Stable work وPhase 5 خارج نطاق هذا PR، لذلك لم تُحوّل متطلبات release preparation إلى Findings حالية.

### 6.2 Uncertainty requiring follow-up, not a confirmed violation

- الاختبارات الحالية تشمل direct public repository/recorder behavior وreal-MySQL Integration suite، لكن لا يوجد Harness خارجي. غياب Harness مؤكد في F-006؛ أما الحكم التفصيلي على ما إذا كان كل workflow observable يحتاج طبقة E2E إضافية فوق Harness فيُحسم أثناء تصميم F-006، ولا يُسجل Finding مستقلًا الآن.
- تغيير PHP baseline قد يؤثر في مستخدمين فعليين قبل Stable release. الحالة المعيارية الحالية تدعم `>=8.4` لهذه الهوية غير المنشورة، لكن آلية rollout/announcement تحتاج Owner Decision مستقلًا كما في §8.

## 7. Verification evidence

تم تنفيذ الأوامر التالية على `351769df5769f4ab6bd947667c847c79c3f06821` قبل إضافة وثيقة التدقيق:

| Command | Result | Evidence |
|---|---|---|
| `composer validate --strict` | PASS | `./composer.json is valid` |
| `composer dump-autoload --optimize --strict-psr` | PASS | optimized autoload generated; 2034 classes |
| `composer audit` | PASS | no vulnerability advisories reported |
| `composer check-platform-reqs` | PASS | PHP 8.5.9 and required extensions satisfied |
| `vendor/bin/phpstan analyse` | PASS | Level Max; 278/278; no errors |
| `vendor/bin/phpunit --testsuite Unit` | PASS with warnings | 531 tests, 2129 assertions, exit 0; PHPUnit reported 25 deprecations |
| `vendor/bin/phpunit --testsuite Regression` | PASS with warning | 96 tests, 10374 assertions, exit 0; PHPUnit reported 1 deprecation |
| `vendor/bin/phpunit --testsuite Integration` | UNAVAILABLE | exit 2; 131 tests, 122 configuration errors and 9 skips because `EVENT_LOGGING_TEST_MYSQL_DSN` and related MySQL configuration were not provided. This is not PASS. |
| PHP syntax over `src`, `tests`, `examples` | PASS | 293 PHP files linted with `php -l` |
| `git diff --check` | PASS | clean base before audit document |

### 7.1 Pinned-file verification

The local blobs for the adoption standard, both profiles, and all seven resolved standards were compared to the upstream GitHub content at `2fc57f9320f8a7f7147fb20abbcfa311fdf40c28`; every compared blob matched. No local pinned file in the resolved set came from another commit.

### 7.2 Current GitHub CI context

PR #6 was observed as open and Draft with base `main@04c6cf0de050300122c110959343aacb70b8ac10`, head `draft/php-event-logging-standards-remediation@351769df5769f4ab6bd947667c847c79c3f06821`, and `MERGEABLE`. Its current checks showed successful Change Detection, Workflow Lint، and aggregate CI Gate، while Quality, Latest Dependency Tests، Integration، and Lowest Dependencies were skipped for that PR state. This result لا يبرئ workflow من findings المعيارية أعلاه؛ تم فحص workflow نفسه.

## 8. Owner Decisions المطلوبة

هذه قرارات مستقلة عن Confirmed Findings ولا تغيّر عددها:

1. **PHP compatibility rollout:** اعتماد رفع contract الحالي لهذه الهوية إلى `>=8.4`، وتحديد صيغة تحديث README/Package Reference/installation guidance وCI matrix. إن رأى Owner ضرورة إبقاء دعم `8.2` قبل Stable، فيلزم قرار موثق واستراتيجية compatibility صريحة، لا إبقاء الحالة غير محسومة.
2. **Lockfile policy:** تأكيد مسار reusable-library بلا committed lockfile، ثم تحديد ترتيب تعديل Composer وCI والوثائق في Work Unit واحد.
3. **Schema metadata rollout:** تحديد ما إذا كانت column comments ستُضاف مباشرة إلى schema package-owned أو عبر migration/rebuild procedure، مع تحديد gate الذي يثبت عدم تغيير persistence behavior.
4. **Consumer Harness release boundary:** اعتماد external Composer root وواقعية workflow ومرات التشغيل المطلوبة، وتحديد ما إذا كان Harness سيستخدم source repository مؤقتًا قبل أول Stable publication أو مسارًا منشورًا لاحقًا.

## 9. Proposed Remediation Work Units

هذه خطة تقسيم فقط. لم يبدأ أي Work Unit في هذا الفرع.

### WU-1 — Composer/PHP contract and lock-aware CI policy

- **Closes:** F-001, F-002, F-003, F-004.
- **Scope:** حسم lockfile policy وPHP baseline، ثم مواءمة Composer metadata والوثائق ذات الصلة وchange detection وdependency resolution.
- **Expected areas:** `composer.json`, `composer.lock`, `.gitignore`, `.github/workflows/ci.yml`, `README.md`, `EVENT_LOGGING_PACKAGE_REFERENCE.md`, `docs/integration/INSTALLATION.md`, `CONTRIBUTING.md`.
- **Dependencies:** Owner Decisions 1 و2 أولًا؛ لا يعتمد على WU-2، لكن WU-3 يحتاج resolution policy مستقرة.
- **Type:** Composer + CI + Docs، مع أثر compatibility يحتاج Owner approval.
- **Required gate:** `composer validate --strict`, dependency resolution وفق السياسة المعتمدة، `composer check-platform-reqs`, `composer audit --no-interaction --abandoned=fail`، مصفوفة PHP المعتمدة، ثم workflow lint.

### WU-2 — Package DTO and schema metadata conformance

- **Closes:** F-009, F-010.
- **Scope:** جعل DTO declaration مطابقة للـ readonly rule وإضافة/تثبيت column metadata المطلوبة دون تغيير SQL behavior أو public business contract.
- **Expected areas:** `src/DeliveryOperations/DTO/DeliveryOperationsAdminQueryRequestDTO.php`، وملفات schema تحت `src/*/Database/`، مع الاختبارات أو schema verification الضرورية.
- **Dependencies:** مستقل تنفيذيًا عن WU-1، لكن أي تغيير schema يحتاج Owner Decision 3.
- **Type:** Code + Schema + Tests عند الضرورة.
- **Required gate:** PHPStan Level Max، Unit/Regression، schema verification، strict real-MySQL Integration، و`git diff --check`.

### WU-3 — Consumer Verification Harness and fail-closed CI gate

- **Closes:** F-005, F-006, F-007.
- **Scope:** إنشاء external Consumer Verification Harness قابل لإعادة الإنتاج، ثم تشغيله في CI وربطه بالـ aggregate gate، وإضافة whitespace/patch-hygiene gate.
- **Expected areas:** Consumer Composer root/fixtures/scripts، `.github/workflows/ci.yml`، وأي توثيق تشغيل لازم للـ Harness.
- **Dependencies:** يعتمد على WU-1؛ ويفضل تنفيذه بعد استقرار WU-2 حتى يتحقق من package state النهائية.
- **Type:** Tests + CI + Docs خليط ضروري.
- **Required gate:** تشغيل Harness بتثبيت نظيف مرتين متتاليتين، استخدام production autoload/public API، real persistence boundary عند انطباقه، workflow lint، aggregate gate fail-closed، و`git diff --check`.

### WU-4 — Local verification documentation parity

- **Closes:** F-008.
- **Scope:** تحديث تعليمات المساهمين واستراتيجية الاختبار لتطابق الأوامر والـ gates النهائية، مع فصل unavailable MySQL بوضوح وعدم وصفه PASS.
- **Expected areas:** `CONTRIBUTING.md`, `TESTING_STRATEGY.md`، وربما `docs/integration/INSTALLATION.md` إذا تأثرت أوامر package setup بقرارات WU-1.
- **Dependencies:** بعد WU-1 وWU-3، حتى توثق الوثائق المسارات النهائية لا مسارات انتقالية.
- **Type:** Docs فقط.
- **Required gate:** مراجعة links والأوامر مقابل workflow الفعلي، ثم `composer validate --strict`, PHPStan، جميع suites المتاحة، Harness، workflow lint، و`git diff --check`.

### Final integration gate (ليس Work Unit)

بعد إغلاق Work Units بالترتيب المناسب، يلزم Gate منفصل لا PR إضافي لمجرد إعادة التحقق: full Unit/Regression، strict real-MySQL Integration، latest/lowest dependency paths وفق السياسة، Consumer Harness بتشغيلين نظيفين، Composer/audit/platform checks، workflow lint، ومراجعة نهائية للـ resolved standards على head النهائي. لا يُنفذ هذا الـ Gate ضمن PR التدقيق الحالي.

## 10. Audit boundary

هذا المستند يسجل الحالة والأدلة والتقسيم المقترح فقط. لا يحتوي هذا الفرع على:

- Runtime fixes أو architecture refactoring.
- Persistence/PDO behavior أو schema changes.
- Composer dependency upgrade أو CI redesign.
- Test repair بسبب compliance gaps.
- Phase 5 أو features أو release/tag work.
