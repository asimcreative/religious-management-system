# ADR-0009: LTR Layout with Urdu Language Support

## Status

Accepted

## Date

2024-01-01

## Context

RAMS serves religious organizations where a significant portion of the users are Urdu-speaking and may have limited English literacy.
Key user categories who may primarily use Urdu:

- Quran Teachers marking daily attendance
- Jamaat Leaders managing congregation records
- Madrasa administrators managing students

Urdu is written in the Nastaliq script (a variant of Arabic script) and reads right-to-left (RTL).
Arabic, also relevant to religious contexts, is also RTL.

The application must be usable in both English and Urdu.

The core architectural question: should the layout be LTR (left-to-right) with Urdu text as a language option, or should it be fully RTL when Urdu is active?

Considerations:

- Bootstrap 5 has RTL support (`bootstrap.rtl.css`) but it requires significant layout overhaul
- All existing admin templates, dashboards, and component libraries are LTR by default
- Charts, data tables, navigation menus, and form layouts change significantly in RTL mode
- Data values (numbers, dates, IDs, percentages) remain LTR even in RTL layouts (adding complexity)
- Version 1 must launch quickly; full RTL doubles the CSS maintenance burden
- Non-Urdu companies (e.g., Malaysia, UK) use English exclusively — RTL would be irrelevant for them

Urdu text display, however, does require:

- A proper Urdu/Nastaliq font (e.g., Noto Nastaliq Urdu, Jameel Noori Nastaliq)
- `dir="rtl"` on specific text containers holding Urdu content
- `font-family` override for Urdu text fields

## Decision

The application uses a **LTR layout** as the base direction.

Urdu language is supported as a **localization option** (translated labels, messages, and UI text) within the LTR layout.

Urdu text rendered within the LTR layout uses:

- `dir="rtl"` attribute on the specific element containing Urdu text
- Urdu Nastaliq font (Noto Nastaliq Urdu) loaded via Google Fonts or self-hosted
- `text-align: right` on Urdu text containers

Language switching:

- User selects language from Profile Settings
- Laravel's localization (`app()->setLocale('ur')`) switches all translation strings to Urdu
- The layout direction remains LTR; only the text content changes language
- Language preference is stored in `users.locale` column and restored on each login

Future consideration:

- Full RTL layout is a named future milestone. If the majority of users switch to Urdu and feedback confirms RTL is needed, a full RTL theme will be built as a separate CSS layer.

## Consequences

### Positive

- LTR layout is the Bootstrap 5 default — no extra CSS bundle needed
- All admin templates, charts, and data tables work correctly without modification
- Significantly faster to develop and test (only one layout direction)
- Urdu text still renders correctly with proper font and `dir="rtl"` on text nodes
- International companies (Malaysia, UK) have a clean LTR interface without any RTL artifacts

### Negative

- Urdu-speaking users accustomed to fully RTL applications (like WhatsApp, Facebook in Urdu) may find the LTR layout slightly unnatural initially
- Navigation, buttons, and menus are on the LTR side — Urdu speakers read the text right-to-left but interact with controls left-to-right
- Mixed-direction content (Urdu labels next to English values) requires careful CSS handling per component

### Neutral

- Every new UI component must be tested in both English and Urdu to verify text renders correctly
- Translation files (`lang/ur/`) must be maintained alongside `lang/en/`
- Font loading must be verified for offline/intranet deployments (some Masjids may have no internet access for Google Fonts)
- If any company requires full RTL, this ADR should be revisited and a separate RTL theme built as a future enhancement
