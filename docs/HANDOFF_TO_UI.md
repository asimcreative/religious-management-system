# Handoff: QA/Testing workstream → UI/UX workstream

**From:** QA, testing, security and code-quality workstream
**To:** the UI/UX redesign workstream
**Date:** 2026-08-06

Main is project ka QA/testing/security workstream handle karta hoon. Maine UI,
CSS, Blade markup ya visual JS ko **haath nahi lagaya** — wo aap ka scope hai.
Neeche sirf wo cheezein hain jo aap ke kaam ko affect karti hain.

Full technical detail: [`docs/UI_OBSERVATIONS.md`](UI_OBSERVATIONS.md)

---

## 1. Abhi karna hai — Pint fail ho raha hai

`app/Services/DashboardService.php` Laravel Pint check fail kar rahi hai
(`fully_qualified_strict_types`, `unary_operator_spaces`,
`not_operator_with_successor_space`, `ordered_imports`).

Commit se pehle chalayein:

```bash
php vendor/bin/pint
```

Maine ye file jaan bujh kar khud fix **nahi** ki — aap abhi ise edit kar rahe
hain, beech mein reformat karne se conflict ho jata.

---

## 2. Topbar ka logout button har page form ko shadow kar raha hai

`resources/views/partials/topbar.blade.php:133`

```html
<button type="submit" class="dropdown-item is-danger">
```

Ye collapsed dropdown ke andar hai — DOM mein maujood magar **hidden** — aur
page ke apne form se **pehle** aata hai.

Do masle:

- `button[type="submit"]` use karne wala koi bhi automation isi ko pakadta hai
  aur *"element is not visible"* par fail hota hai. Do Playwright tests isi
  wajah se fail hui theen.
- Zyada ahem: jo bhi cheez "page ka pehla submit dabao" karti hai, wo **logout**
  kar degi — intended action ke bajaye.

**Tajweez (faisla aap ka):** ise plain `<button>` + JS handler bana dein, ya koi
stable hook de dein jaise `data-action="logout"`, taake page ke apne submit
buttons pehla natural target rahen.

---

## 3. `layouts/app.blade.php:19` — inline `<script>`

Koi bug nahi, sirf heads-up: release audit mein *"tested CSP add karna"* pending
item hai. Inline scripts ke liye nonce/hash ya `unsafe-inline` chahiye hota hai,
jo CSP ko kamzor karta hai. Agar CSP ka plan hai to abhi bundled asset mein move
karna baad mein retrofit karne se sasta hai.

---

## 4. ⚠️ Salah attendance create page — ise mat todein

`resources/views/salah-attendance/create.blade.php`

Ye page **ek submission mein saari namazein** record karta hai — members ×
prayers ka grid, inputs ka naam:

```
attendance[employee_id][prayer_id]
```

Yahan `select[name="prayer_id"]` **jaan bujh kar nahi hai.**

Controller, `SalahAttendanceService::saveAllPrayersAttendance()`, aur kai tests
isi shape par depend karte hain. Single-prayer dropdown wapas laane se backend
tootega. Zaroorat ho to pehle baat kar lein.

---

## 5. Ye files QA ki hain — please overwrite na karein

**Sirf ye files** — baaqi tests/ mein aap ka apna kaam bilkul welcome hai:

- **`app/Http/Controllers/Web/SalahAttendanceController.php`**
  Ismein ek **high-severity security fix** hai — pehle sirf
  `salah.attendance.view` permission wala user attendance likh/overwrite kar
  sakta tha (koi authorization thi hi nahi). `store()` mein ab 12 lines ka
  `authorize` block hai. Ise revert/overwrite karne se hole wapas aa jayega.
- `tests/Feature/Security/*` (3 guard tests + SecurityTest)
- `tests/Feature/Salah/SalahAttendanceTest.php`,
  `tests/Feature/DomainTenantIntegrityTest.php`,
  `tests/Feature/CompanyTimezoneDateTest.php`
- `tests/Playwright/{attendance,auth,employee,notifications}.spec.ts`

**Correction (pehle maine kaha tha "tests/ ko na chhuein" — wo galat tha):**
aap ne `UiSmokeTest`, `LocaleSwitchTest`, `DashboardTrendTest` aur
`_shots.spec.ts` add kiye hain — ye bilkul sahi hai, apne feature ke tests
likhna aap ka haq hai. Suite ab 375 green hai, un sab ke saath. Sirf upar wali
files mat badlein.

---

## 5b. Aap ke `POST /locale` route ne mere guard test ka ek bug pakda — shukriya

Aap ne `Route::post('locale', LocaleController::class)` add kiya. Mera
`RouteAuthorizationCoverageTest` ise **pakad hi nahi raha tha**, kyunki mera
discovery logic invokable (single-action) controllers ko skip kar raha tha —
Laravel un ka action name `@method` suffix ke baghair deta hai. Wo mere test ka
bug tha, maine theek kar diya.

**Aap ka route bilkul sahi hai** — koi tabdeeli darkaar nahi:

- POST hai, GET par state mutate nahi kar raha ✔
- `locale` `SetLocale::SUPPORTED_LOCALES` se validate hota hai ✔
- guest ko sirf cookie milti hai ✔
- signed-in user ke liye `$request->user()` par likhta hai — identity session se
  aati hai, input se nahi, is liye koi IDOR surface nahi ✔

Maine ise guard ke allowlist mein justification ke saath add kar diya hai
(`locale.update`). **Aage se:** koi bhi naya POST/PUT/PATCH/DELETE route ya to
permission check kare, ya us allowlist mein wajah ke saath aaye — test aap ko
khud bata dega.

---

## 6. Commit se pehle ye chala lein

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64;$env:PATH"
php artisan test          # abhi: 365 passed, 0 failed
php vendor/bin/pint       # style fix
php vendor/bin/phpstan analyse --memory-limit=2G
```

Abhi ki state: **365 PHPUnit + 47 Playwright green, PHPStan level 5 clean.**
Aap ke dashboard / `SetLocale` changes ke against bhi sab pass ho raha hai.

**Agar aap ki UI change se koi test fail ho:** please test ki assertion na
badlein — mostly wo koi asli contract assert kar rahi hoti hai. Batayein, main
dekh loonga.

Teen tests **architectural guards** hain — agar in mein se koi fail ho to wo
asli security regression hai, flaky test nahi:

| Guard | Kya enforce karta hai |
|---|---|
| `Security\RouteAuthorizationCoverageTest` | har state-changing route authorize karta ho |
| `Security\TenantScopeCoverageTest` | har `company_id` model par tenant scope ho |
| `Security\SearchTenantLeakTest` | search doosri company ka data leak na kare |

---

## 7. Commit — maine kuch commit NAHI kiya

Working tree mein hum dono ka kaam mila-jula pada hai. Maine jaan bujh kar
commit nahi kiya taake aap ke in-flight kaam ke beech conflict na ho. Aap apne
hisaab se commit kar lein — neeche sab kuch hai jo chahiye.

### QA workstream ki files (ye commit mein zaroor jani chahiyein)

**Modified:**
```
app/Http/Controllers/Web/SalahAttendanceController.php   <- security fix
tests/Feature/CompanyTimezoneDateTest.php
tests/Feature/DomainTenantIntegrityTest.php
tests/Feature/Salah/SalahAttendanceTest.php
tests/Feature/Security/SecurityTest.php
tests/Playwright/attendance.spec.ts
tests/Playwright/auth.spec.ts
tests/Playwright/employee.spec.ts
tests/Playwright/notifications.spec.ts
.gitignore                                               <- Playwright artifacts ignore
```

**New:**
```
tests/Feature/Security/RouteAuthorizationCoverageTest.php
tests/Feature/Security/TenantScopeCoverageTest.php
tests/Feature/Security/SearchTenantLeakTest.php
docs/AI_MEMORY.md
docs/BUG_TRACKER.md
docs/FINAL_QA_REPORT.md
docs/KNOWN_LIMITATIONS.md
docs/TESTING_STATUS.md
docs/UI_OBSERVATIONS.md
docs/HANDOFF_TO_UI.md
```

`FINAL_RELEASE_AUDIT.md` (root) meri file **nahi** hai — session shuru hone se
pehle se untracked padi thi. Aap decide kar lein.

### Tajweez: sab kuch ek commit mein na daalein

Ye meri raay hai, faisla aap ka — lekin ek **high-severity security fix** ko
100+ file ke UI redesign ke saath ek hi commit mein daalna theek nahi hoga:

- security fix ko alag se cherry-pick / revert / audit karna namumkin ho jayega
- reviewer ko 12 lines ka asli fix 5000 lines ke diff mein dhoondna paray ga
- agar redesign roll back karna pare, security hole bhi wapas aa jayega

**Behtar tareeqa — 3 commits:**

```bash
# 1) Security fix + uski regression tests (sab se ahem, alag rahe)
git add app/Http/Controllers/Web/SalahAttendanceController.php \
        tests/Feature/Security/ \
        tests/Feature/Salah/SalahAttendanceTest.php \
        tests/Feature/DomainTenantIntegrityTest.php \
        tests/Feature/CompanyTimezoneDateTest.php
git commit -m "fix(security): require create/update permission for salah attendance"

# 2) Test-suite + docs
git add tests/Playwright/ docs/ .gitignore
git commit -m "test: fix stale contracts, add authorization and tenancy guards"

# 3) Aap ka UI redesign
git add resources/ lang/ app/ routes/ tests/Feature/UiSmokeTest.php ...
git commit -m "feat(ui): redesign application shell and module screens"
```

### Commit se pehle

```powershell
php vendor/bin/pint          # DashboardService.php abhi fail hai (point 1)
php artisan test             # 375 passed hona chahiye
php vendor/bin/phpstan analyse --memory-limit=2G
```

### CLAUDE.md ka rule — GitHub issue

Project ka standard hai: **har bug fix / feature ka pehle GitHub issue banta
hai**, aur commit/PR usay reference karta hai. Security fix ke liye issue
banana behtar hoga, misal:

> `security: salah attendance store endpoint missing authorization`
>
> **What's happening** — `POST /salah-attendance` par koi authorization check
> nahi tha. `salah.attendance.view` permission wala user apni company ki kisi
> bhi Jamaat ki attendance likh/overwrite kar sakta tha.
> **Expected** — create ke liye `salah.attendance.create`, existing din replace
> karne ke liye `salah.attendance.update` darkaar ho.
> **Evidence** — view-only user se valid payload post karne par row DB mein
> ban gayi (proof test se confirm kiya). Detail: `docs/BUG_TRACKER.md` RAMS-001.
> **Impact** — har company, har tenant. Broken access control (OWASP A01).

Agar aap issue bana dein to commit mein `Fixes #<number>` add kar dein.

---

## 8. Koi action nahi chahiye — sirf FYI

Empty-state markup mein nested elements (`.empty-state`, `__art`, `__title`,
`__text`) sab `[class*="empty"]` se match hote hain, isliye Playwright strict
mode trigger hota tha. Test selectors `.first()` se scope kar diye gaye hain.
Aap ki taraf se kuch nahi karna — markup bilkul theek hai.
