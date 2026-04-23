# Accessibility Review Plan: az_quickstart WCAG 2.2 AA

## Purpose

This document records the component-by-component accessibility findings for the
`az_quickstart` Drupal installation profile and its associated theme,
`az_barrio`. It drives the phased delivery work described in
`ACCESSIBILITY-GITHUB-PROJECT-PLAN.md`.

All findings are mapped to WCAG 2.2 success criteria (SC) and assigned a
priority tier based on breadth of user impact and technical risk.

---

## Methodology

Findings were produced by:

1. Template source review (`*.html.twig`, `*.js`, `*.css`) across all custom
   modules and the `az_barrio` theme.
2. Semantic and attribute analysis against WCAG 2.2 and the WAI-ARIA 1.2
   Authoring Practices Guide.
3. Manual keyboard walk-through of the alphabetical listing, accordion,
   pager, and select menu interaction patterns.

Automated axe and rendered-page audits are planned for Phase 0 (see project
plan) and will supplement or refine the findings below.

---

## Priority definitions

| Tier | Meaning |
|------|---------|
| **P0** | Blocks task completion for keyboard or AT users across most pages. |
| **P1** | Degrades a named component or interaction; workarounds exist but are unreliable. |
| **P2** | Incorrect semantics reduce comprehension or navigation efficiency without fully blocking tasks. |
| **P3** | Polish, best-practice, or future-proofing concerns. |

---

## Findings

### 1. Global — Skip-to-main-content link target missing

**File:** `themes/custom/az_barrio/templates/layout/html.html.twig`,
`themes/custom/az_barrio/templates/layout/page.html.twig`

**WCAG SC:** 2.4.1 Bypass Blocks (Level A)

**Priority:** P0

**Finding:**
The skip link in `html.html.twig` points to `href="#content"`:

```twig
<a href="#content" class="visually-hidden-focusable …">
  {{ 'Skip to main content'|t }}
</a>
```

The `<main>` element in `page.html.twig` has no matching `id="content"`
attribute:

```twig
<main{{ content_attributes }}>
```

When a keyboard user activates the skip link, focus does not move and the
browser scrolls to the top of the document rather than the main content region.
The bypass mechanism is present in the markup but does not function.

**Required fix:**
Add `id="content"` to the `<main>` element, or ensure `content_attributes`
reliably includes this ID. The simplest guaranteed approach is:

```twig
<main id="content"{{ content_attributes }}>
```

---

### 2. Global — Footer `role="contentinfo"` misplaced

**File:** `themes/custom/az_barrio/templates/layout/page.html.twig`

**WCAG SC:** 1.3.6 Identify Purpose (Level AAA, informational); 4.1.2 Name,
Role, Value (Level AA)

**Priority:** P2

**Finding:**
`role="contentinfo"` is applied to an inner `<div>` inside the `<footer>`
element:

```twig
<footer class="site-footer">
  <div … role="contentinfo">
```

The HTML5 `<footer>` element already carries the implicit ARIA `contentinfo`
landmark when it is a direct child of `<body>`. Adding an explicit
`role="contentinfo"` on an inner div creates a duplicate landmark, which some
screen readers report twice. Removing the redundant `role` from the inner div
resolves this cleanly.

**Required fix:**
Remove `role="contentinfo"` from the inner wrapper div. The `<footer>` element
itself carries the landmark implicitly.

---

### 3. Global — Back-to-top button missing accessible name

**File:** `themes/custom/az_barrio/templates/layout/page.html.twig`

**WCAG SC:** 4.1.2 Name, Role, Value (Level AA)

**Priority:** P1

**Finding:**
The back-to-top button contains a Material Symbols icon span and the literal
text "Back to top":

```twig
<button type="button" id="az-js-back-to-top" …>
  <span aria-hidden="true" class="material-symbols-rounded">keyboard_arrow_up</span>
  Back to top
</button>
```

The visible text "Back to top" is present without `aria-hidden`, so the button
does have an accessible name. However, the icon is correctly hidden from AT.
This item is **close to conformant** but should be verified at rendering time
because CSS may clip the visible text on narrow viewports.

**Required fix:**
Confirm text is never visually clipped or hidden via CSS. No template change is
required if verified conformant.

---

### 4. Accordion — Incorrect `aria-labelledby` and `data-bs-parent` targets

**File:**
`modules/custom/az_accordion/templates/az-accordion.html.twig`

**WCAG SC:** 4.1.2 Name, Role, Value (Level AA)

**Priority:** P1

**Finding:**
The accordion panel element uses `aria-labelledby` referencing the same ID as
the panel itself (`accordion_item_id`), rather than the button that controls
it:

```twig
<div id="{{ accordion_item_id }}" … aria-labelledby="{{ accordion_item_id }}"
     data-bs-parent="#{{ accordion_item_id }}">
```

Two problems:

1. `aria-labelledby="{{ accordion_item_id }}"` creates a circular reference.
   The panel labels itself using its own ID, which resolves to the panel's own
   text rather than the heading button label. Screen readers may report the
   entire panel body as the panel's name.

2. `data-bs-parent="#{{ accordion_item_id }}"` points to the individual item,
   not the wrapping accordion container. Bootstrap uses this attribute to
   collapse sibling items. Pointing to the item itself means sibling
   auto-collapse does not work, breaking the expected accordion interaction
   pattern.

Additionally, the heading button does not have its own unique ID to serve as
the `aria-labelledby` target.

**Required fix:**
- Add a unique button ID, for example `{{ accordion_item_id }}-button`.
- Set `aria-labelledby="{{ accordion_item_id }}-button"` on the panel.
- Pass the container ID (e.g. `accordion_container_id`) to the template and
  use it in `data-bs-parent`.

Template variables `accordion_item_id` and `accordion_container_id` are
already declared in the template docblock comment. The `accordion_container_id`
variable needs to be wired through in `data-bs-parent`.

---

### 5. Pager — `aria-label` contains an ID string; current page not marked

**File:** `themes/custom/az_barrio/templates/navigation/pager.html.twig`

**WCAG SC:** 1.3.1 Info and Relationships (Level A); 2.4.6 Headings and Labels
(Level AA)

**Priority:** P1

**Finding (a) — `aria-label` receives raw heading ID:**
```twig
<nav role="navigation" aria-label="{{ heading_id }}">
  <h3 id="{{ heading_id }}" class="visually-hidden">{{ 'Pagination'|t }}</h3>
```

`heading_id` is a machine-generated string such as
`pagination-heading--12345`. The nav uses it as a label text rather than
referencing it as a target. The accessible name of the navigation landmark
becomes the raw ID string rather than the human-readable "Pagination" text.

The correct pattern is `aria-labelledby="{{ heading_id }}"`, which references
the heading element.

**Finding (b) — Current page has no `aria-current`:**
```twig
{% if current == key %}
  <span class="page-link">
    {{- key -}}
  </span>
```

The current page item uses a `<span>` instead of a link, which is correct
(disabling navigation to the current page). However, it lacks
`aria-current="page"`, so screen reader users cannot identify which page is
active.

**Required fix (a):** Change `aria-label="{{ heading_id }}"` to
`aria-labelledby="{{ heading_id }}"`.

**Required fix (b):** Add `aria-current="page"` to the current page span:

```twig
<span class="page-link" aria-current="page">
  {{- key -}}
</span>
```

---

### 6. Alphabetical listing — Navigation landmark lacks accessible name;
no live region for search results

**File:**
`modules/custom/az_alphabetical_listing/templates/views-view--az-alphabetical-listing.html.twig`,
`modules/custom/az_alphabetical_listing/js/alphabetical_listing.js`

**WCAG SC:** 2.4.6 Headings and Labels (Level AA); 4.1.3 Status Messages
(Level AA)

**Priority:** P1

**Finding (a) — Nav landmark has no accessible name:**
```twig
<nav id="az-js-floating-alpha-nav" class="…">
  <ul id="az-js-alpha-navigation" …>
```

A page may have multiple `<nav>` landmarks. Without an accessible name,
screen reader users cannot distinguish this navigation (alpha jump links) from
other navigation landmarks (primary menu, breadcrumb, footer).

**Finding (b) — Search results change is not announced:**
When a user types in the search field (`#az-js-alphabetical-listing-search`),
JavaScript shows and hides result items. No live region communicates the count
of visible results to screen reader users, who must manually explore the page
to determine whether their query returned results.

The "no results" message uses `style="display:none"` toggled by JS, which is
not within a live region and does not trigger an announcement.

**Finding (c) — href assignment in JS omits `#` prefix:**
In `alphabetical_listing.js` line 106, when a nav item's corresponding group
is visible, the href is set to the element's ID string without the `#` anchor
prefix:

```js
navTarget.attr('href', $(element).attr('id'));
// Sets href="A" instead of href="#A"
```

This causes the anchor link to navigate to a relative URL path (`/A`) rather
than scrolling to the section anchor on the same page. The smooth-scroll click
handler overrides this for mouse users (it reads `data-href`), but keyboard
users who activate the link via Enter will navigate away from the page.

**Required fix (a):** Add `aria-label="{{ 'Alphabetical navigation'|t }}"` to
the `<nav>` element.

**Required fix (b):** Add an `aria-live="polite"` status region near the
search input that JS updates with result counts.

**Required fix (c):** In `alphabetical_listing.js`, prepend `#` when setting
`href`:
```js
navTarget.attr('href', '#' + $(element).attr('id'));
```

---

### 7. Select menu — Form element lacks accessible name

**File:**
`modules/custom/az_select_menu/templates/az-select-menu.html.twig`

**WCAG SC:** 1.3.1 Info and Relationships (Level A); 4.1.2 Name, Role, Value
(Level AA)

**Priority:** P1

**Finding:**
The `<form>` element wrapping the select menu has no accessible name. A
visually hidden label is provided for the `<select>` itself
(`preform_text_sr_only`), but the enclosing form is not labeled. Depending on
context, screen readers may announce an unnamed form landmark when focus
enters.

Additionally, when the `empty_option` is rendered as the initially selected
option, its `data-href=""` causes the button to be immediately marked as
disabled and `aria-disabled="true"` is set on the select itself. The
`aria-disabled` attribute on a `<select>` element is not universally supported
across browsers and assistive technology combinations; `disabled` should be
used or the approach reconsidered.

**Required fix:**
- Add `aria-label` or `aria-labelledby` to the `<form>` element using
  `preform_text_sr_only` or `preform_text` as the source.
- Review `aria-disabled` usage on `<select>` and replace with `disabled` where
  appropriate.

---

### 8. Carousel — No keyboard-accessible pause; no reduced-motion support

**File:** `modules/custom/az_carousel/`

**WCAG SC:** 2.1.1 Keyboard (Level A); 2.2.2 Pause, Stop, Hide (Level A)

**Priority:** P1

**Finding:**
The `az_carousel` module provides a Slick carousel integration. Moving content
(auto-playing carousels) must provide a mechanism to pause, stop, or hide the
movement unless the movement lasts fewer than five seconds and is not
auto-started (WCAG 2.2.2). Additionally, all carousel controls (previous,
next, pause, slide indicators) must be keyboard accessible.

A full audit of the rendered carousel markup is required before specific
template changes can be specified. The following risk areas should be
confirmed:

- Auto-play status and whether a visible, keyboard-accessible pause control
  is present.
- Slide indicator buttons: do they have accessible names (e.g. "Slide 1 of 5"
  or named slide titles)?
- Does the carousel respond to the `prefers-reduced-motion` media query?

**Required action:** Rendered-page audit on a demo carousel page
(`/pages/photo-galleries`) to confirm current control names and keyboard
behavior before writing template fixes.

---

### 9. Publication table — `<table>` summary and caption review needed

**File:**
`modules/custom/az_publication/templates/az-publication-type-listing-table.html.twig`

**WCAG SC:** 1.3.1 Info and Relationships (Level A)

**Priority:** P2

**Finding:**
The publication type listing table has `<thead>` and `<tbody>` with properly
structured `<th>` header cells. However, the table has no `<caption>` element.
For complex administrative tables, a visible or visually hidden caption that
names the table is recommended by WCAG techniques H39 and H73.

This is an admin-facing table and has a lower user-impact than front-facing
components, but should be addressed for completeness.

**Required fix:**
Add a `<caption>` to the table in
`az-publication-type-listing-table.html.twig`.

---

### 10. Marketing Cloud export templates — Scope confirmation required

**Files:**
`modules/custom/az_marketing_cloud/templates/html--export--marketing-cloud.html.twig`,
`modules/custom/az_marketing_cloud/templates/page--export--marketing-cloud.html.twig`

**WCAG SC:** Scope-dependent

**Priority:** P3 (pending scope decision)

**Finding:**
The Marketing Cloud export templates render stripped-down HTML fragments
intended for email or external system consumption. The
`html--export--marketing-cloud.html.twig` template renders only
`{{ page.content }}` with no `<html>`, `<head>`, `<body>`, or landmark
elements.

If these routes are ever rendered in a browser-facing context (for example,
previewed in an iframe or fetched directly), the output would fail basic
structural accessibility requirements. If they are pipeline-only fragment
outputs never consumed by AT users, WCAG applicability is reduced.

**Required action:**
Confirm with the delivery team whether Marketing Cloud export routes are:
(a) browser-accessible preview pages, or
(b) fragment-only pipeline outputs not intended for AT users.

Apply appropriate remediation based on that decision.

---

### 11. Date picker — Focus and keyboard behavior review required

**Files:** `modules/custom/az_core/lib/vanilla-calendar-pro/`,
`modules/custom/az_core/`

**WCAG SC:** 2.1.1 Keyboard (Level A); 2.1.2 No Keyboard Trap (Level A)

**Priority:** P1

**Finding:**
The bundled `vanilla-calendar-pro` library (v3.1.0) provides date picker
behavior used in the event calendar. The library's source code shows ARIA
attributes (`role="application"`, `role="grid"`, `role="gridcell"`,
`aria-label`, `aria-selected`, `aria-current="date"`) applied to rendered
elements, which indicates some AT support is built in.

However, the following keyboard behavior risks should be verified in a rendered
page context:

- Arrow key navigation within the calendar grid.
- Escape key closes the picker and returns focus to the triggering input.
- Tab key does not become trapped inside the picker.
- Date selection announces the selected date via live region or focus move.

The library's `tabIndex=-1` handling for disabled elements appears correct in
source, but must be confirmed against current Drupal integration.

**Required action:**
Manual keyboard and screen reader walkthrough of the event calendar
(`/calendar`) before specifying fixes. Document any divergence from the
ARIA date-picker pattern.

---

## Components confirmed conformant (no action required)

| Component | Notes |
|-----------|-------|
| Skip link markup | Present and visible on focus. Fix to `<main id="content">` will make it functional (see finding 1). |
| Pager: Previous/Next/First/Last | Visually hidden text provided correctly. `aria-hidden` on decorative icon text. |
| Breadcrumb | Uses `<nav>` with standard Drupal breadcrumb template. |
| Mobile nav offcanvas | Has `aria-label="Mobile navigation"`. Trigger buttons include icon text. |
| UA header wordmark | Image has descriptive `alt` text. |

---

## Verification matrix

The following pages should be used for rendered-page verification and automated
axe scanning:

| Path | Components covered |
|------|--------------------|
| `/` (front page) | Skip link, header, navigation, footer |
| `/pages/accordions` | Accordion |
| `/pages/photo-galleries` | Carousel |
| `/people-list` | Alphabetical listing, search, alpha nav |
| `/news` | Pager |
| `/calendar` | Date picker |
| Any page with select menu block | Select menu |
| `/admin/config/az-quickstart/publication-types` | Publication table |

---

## Open questions requiring team decision

1. Are Marketing Cloud export routes browser-facing or pipeline-only? (Affects
   scope of finding 10.)
2. What is the agreed test matrix for automated and manual verification? (Will
   confirm the table above or adjust it.)
3. Should accessibility regressions block patch releases, minor releases, or
   both?
4. Which findings belong in patch releases versus minor releases?
