# Accessibility GitHub Project Plan: az_quickstart WCAG 2.2 AA

## Purpose

This document defines the phased delivery structure, issue taxonomy, and
GitHub project organization for the az_quickstart accessibility program. It
translates the findings documented in `ACCESSIBILITY-REVIEW-PLAN.md` into
actionable, sequenced work.

---

## Program overview

| Attribute | Value |
|-----------|-------|
| Standard targeted | WCAG 2.2 Level AA |
| Primary user benefit | Keyboard and screen reader access to core user journeys |
| Release risk approach | Phase by impact; guard against regression in CI |
| Source of truth | `ACCESSIBILITY-REVIEW-PLAN.md` (findings) + this file (delivery) |

---

## Phase structure

### Phase 0 — Accessibility guardrails and verification setup

**Goal:** Establish baseline measurement and CI guardrails before any
functional changes are made. Without a baseline it is impossible to verify
that later fixes work or to detect regressions in future releases.

**Deliverables:**

- [ ] Configure an automated axe scan step in CI (`ci.yml`) that runs against
  the demo site installed in the `install` job, targeting the verification
  matrix pages defined in `ACCESSIBILITY-REVIEW-PLAN.md`.
- [ ] Capture and commit baseline axe scan output so regressions become
  detectable.
- [ ] Document the manual testing procedure (keyboard walk-through steps and
  screen reader pairings) as a reusable checklist for PR reviews.
- [ ] Confirm the answers to the four open questions in
  `ACCESSIBILITY-REVIEW-PLAN.md` with the team before Phase 1 work begins.

**Exit criteria:**
Automated axe scan runs on every PR without blocking merges (informational
mode). Team has agreed answers to the four open questions.

---

### Phase 1 — Global user experience blockers

**Goal:** Fix the highest-impact, most broadly felt barriers first. Every page
in the site is affected by at least one Phase 1 item.

**Deliverables:**

#### 1.1 Fix broken skip-to-main-content link

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §1

**WCAG SC:** 2.4.1 Bypass Blocks (Level A)

**Files to change:**
- `themes/custom/az_barrio/templates/layout/page.html.twig`

**Change:** Add `id="content"` to the `<main>` element so the skip link
`href="#content"` in `html.html.twig` resolves correctly.

```twig
{# Before #}
<main{{ content_attributes }}>

{# After #}
<main id="content"{{ content_attributes }}>
```

**Acceptance test:** Activate the skip link (Tab to it, press Enter). Focus
must move to the `<main>` element and the page must scroll to main content.

---

#### 1.2 Fix pager: `aria-labelledby` and `aria-current`

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §5

**WCAG SC:** 1.3.1 Info and Relationships (Level A); 2.4.6 Headings and Labels
(Level AA)

**Files to change:**
- `themes/custom/az_barrio/templates/navigation/pager.html.twig`

**Changes:**
1. Replace `aria-label="{{ heading_id }}"` with
   `aria-labelledby="{{ heading_id }}"` on the `<nav>` element.
2. Add `aria-current="page"` to the current page `<span>`.

**Acceptance test:**
- Screen reader announces the nav landmark as "Pagination" (from the visually
  hidden heading), not as a raw ID string.
- Screen reader announces `aria-current="page"` on the current page item.

---

#### 1.3 Remove redundant `role="contentinfo"` from footer inner div

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §2

**WCAG SC:** 4.1.2 Name, Role, Value (Level AA)

**Files to change:**
- `themes/custom/az_barrio/templates/layout/page.html.twig`

**Change:** Remove `role="contentinfo"` from the `<div>` inside `<footer>`.
The `<footer>` element already carries the implicit `contentinfo` landmark.

**Acceptance test:**
Landmarks list in a screen reader shows exactly one `contentinfo` region per
page.

---

### Phase 2 — High-risk interactive components

**Goal:** Repair custom interaction patterns that have the highest probability
of blocking assistive technology users mid-task.

**Deliverables:**

#### 2.1 Fix accordion ARIA relationships

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §4

**WCAG SC:** 4.1.2 Name, Role, Value (Level AA)

**Files to change:**
- `modules/custom/az_accordion/templates/az-accordion.html.twig`

**Changes:**
1. Add a unique ID to the accordion button:
   `id="{{ accordion_item_id }}-button"`.
2. Change `aria-labelledby="{{ accordion_item_id }}"` on the panel to
   `aria-labelledby="{{ accordion_item_id }}-button"`.
3. Change `data-bs-parent="#{{ accordion_item_id }}"` to
   `data-bs-parent="#{{ accordion_container_id }}"` so Bootstrap correctly
   collapses siblings.

**Acceptance test:**
- Screen reader announces the accordion panel name using the heading button
  text, not the panel body text.
- Expanding one accordion item in a multi-item group collapses previously
  expanded siblings.

---

#### 2.2 Fix alphabetical listing: nav label, live region, and href bug

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §6

**WCAG SC:** 2.4.6 Headings and Labels (Level AA); 4.1.3 Status Messages
(Level AA)

**Files to change:**
- `modules/custom/az_alphabetical_listing/templates/views-view--az-alphabetical-listing.html.twig`
- `modules/custom/az_alphabetical_listing/js/alphabetical_listing.js`

**Changes:**

*Template:*
1. Add `aria-label="{{ 'Alphabetical navigation'|t }}"` to the `<nav>`
   element.
2. Add a visually hidden live region adjacent to the search input:

```twig
<div id="az-js-alphabetical-listing-status"
     role="status"
     aria-live="polite"
     aria-atomic="true"
     class="visually-hidden"></div>
```

*JavaScript:*
3. In the `azAlphabeticalListingCheckNoResults` function, update the live
   region text when results change:

```js
const statusRegion = document.getElementById(
  'az-js-alphabetical-listing-status',
);
if (statusRegion) {
  const count = document.querySelectorAll(
    '.az-js-alphabetical-listing-search-result:not(.hide-result)',
  ).length;
  statusRegion.textContent = count === 0
    ? Drupal.t('No results found.')
    : Drupal.t('@count results found.', { '@count': count });
}
```

4. Fix the `href` assignment bug: prepend `#` when setting the anchor href:

```js
// Before
navTarget.attr('href', $(element).attr('id'));

// After
navTarget.attr('href', '#' + $(element).attr('id'));
```

**Acceptance test:**
- Landmarks list shows the alpha nav with the name "Alphabetical navigation".
- After typing in the search field, a screen reader announces the result count.
- Keyboard activation of an alpha letter link scrolls to and focuses the
  corresponding section anchor on the same page without navigating to a
  new URL.

---

#### 2.3 Fix select menu: accessible form name and `aria-disabled` on select

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §7

**WCAG SC:** 1.3.1 Info and Relationships (Level A); 4.1.2 Name, Role, Value
(Level AA)

**Files to change:**
- `modules/custom/az_select_menu/templates/az-select-menu.html.twig`
- `modules/custom/az_select_menu/js/az-select-menu.js`

**Changes:**

*Template:*
1. Add `aria-label` to the `<form>` element sourced from
   `preform_text_sr_only` (falling back to `preform_text`). Note:
   `preform_text_sr_only` is the intentional variable name used throughout
   the `az_select_menu` module (config schema, template, and PHP class all
   use this spelling):

```twig
<form {{ form_attributes }}
  aria-label="{{ menu_block_configuration.az_select_menu.preform_text_sr_only
    ?: menu_block_configuration.az_select_menu.preform_text }}">
```

*JavaScript:*
2. Remove the `selectElement.setAttribute('aria-disabled', 'true')` line. Use
   the native `disabled` attribute when the empty option is selected, or leave
   the select element enabled (it is not the disabled element; the button is).

**Acceptance test:**
- Form landmark has an accessible name derived from the configured label.
- Screen reader does not report the select element as disabled when only the
  navigation button is disabled.

---

#### 2.4 Carousel — audit and remediate Slick integration

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §8

**WCAG SC:** 2.1.1 Keyboard (Level A); 2.2.2 Pause, Stop, Hide (Level A)

**Prerequisite:** Rendered-page audit of `/pages/photo-galleries` using a
keyboard and screen reader to confirm current control names and auto-play
behavior.

**Files to change:** TBD pending rendered-page audit.

**Minimum required outcomes:**
- All carousel controls (previous, next, pause, slide indicators) are
  focusable and have accessible names.
- If auto-play is enabled, a visible pause control exists and is keyboard
  accessible.
- Carousel respects `prefers-reduced-motion: reduce`.

---

#### 2.5 Date picker — audit and remediate vanilla-calendar-pro integration

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §11

**WCAG SC:** 2.1.1 Keyboard (Level A); 2.1.2 No Keyboard Trap (Level A)

**Prerequisite:** Manual keyboard and screen reader walkthrough of the event
calendar at `/calendar`.

**Files to change:** TBD pending rendered-page audit.

**Minimum required outcomes:**
- Arrow keys navigate calendar grid cells.
- Escape closes the date picker and returns focus to the triggering control.
- Tab does not become trapped inside the date picker widget.

---

### Phase 3 — Content and template semantics

**Goal:** Improve semantic markup in templates where meaning is currently
reduced or lost, without changing visible UI behavior.

**Deliverables:**

#### 3.1 Add `<caption>` to publication type listing table

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §9

**WCAG SC:** 1.3.1 Info and Relationships (Level A)

**Files to change:**
- `modules/custom/az_publication/templates/az-publication-type-listing-table.html.twig`

**Change:** Add a `<caption>` inside the `<table>` element:

```twig
<table{{ attributes.addClass('responsive-enabled') }}>
  <caption>{{ 'Publication types'|t }}</caption>
  <thead>
```

---

#### 3.2 Marketing Cloud export — scope decision and remediation

**Source finding:** ACCESSIBILITY-REVIEW-PLAN.md §10

**Action depends on scope decision (Phase 0 exit criterion):**
- If browser-facing: add landmark structure, `lang` attribute, and `<title>`
  to the export HTML template.
- If pipeline-only: document the decision in the template comment and close
  the finding.

---

### Phase 4 — Verification and release readiness

**Goal:** Confirm all Phase 1–3 fixes work correctly under assistive
technology, update CI to block regressions, and document the program outcome.

**Deliverables:**

- [ ] Run manual verification checklist (keyboard + screen reader) against all
  Phase 1–3 changes on the agreed test matrix pages.
- [ ] Promote axe CI scan from informational to blocking on the violation
  categories addressed by Phases 1–3.
- [ ] Update `RELEASES.md` or release notes to document accessibility
  improvements per release.
- [ ] Close or migrate any related GitHub issues linked to this program.
- [ ] Tag findings that remain open (Phase 3+ scope, deferred items) as
  `accessibility` with a clear status label.

---

## GitHub issue taxonomy

### Labels required

| Label | Color suggestion | Purpose |
|-------|-----------------|---------|
| `accessibility` | `#0075ca` | All accessibility issues |
| `wcag-a` | `#e4e669` | WCAG Level A findings |
| `wcag-aa` | `#f9c513` | WCAG Level AA findings |
| `a11y-phase-0` | `#bfd4f2` | Phase 0 work |
| `a11y-phase-1` | `#bfd4f2` | Phase 1 work |
| `a11y-phase-2` | `#bfd4f2` | Phase 2 work |
| `a11y-phase-3` | `#bfd4f2` | Phase 3 work |
| `a11y-phase-4` | `#bfd4f2` | Phase 4 work |

### Milestone mapping

| Milestone | Phase | Expected release type |
|-----------|-------|-----------------------|
| A11Y Phase 0 — Guardrails | Phase 0 | Patch or minor |
| A11Y Phase 1 — Global blockers | Phase 1 | Patch |
| A11Y Phase 2 — Interactive components | Phase 2 | Minor or patch |
| A11Y Phase 3 — Semantics | Phase 3 | Minor |
| A11Y Phase 4 — Verification | Phase 4 | Minor |

---

## Delivery issue templates

Use the following structure when creating delivery issues under each phase.

### Delivery issue title format

```
[A11Y] <Phase N> – <Component>: <WCAG SC short name>
```

Example:
```
[A11Y] Phase 1 – Pager: aria-labelledby and aria-current (WCAG 1.3.1, 2.4.6)
```

### Delivery issue body template

```markdown
## Accessibility finding

**Phase:** N
**WCAG SC:** x.x.x <Name> (Level A/AA)
**Priority:** Px
**Source:** ACCESSIBILITY-REVIEW-PLAN.md §N

## Problem

<Copy the finding summary from the review plan.>

## Proposed fix

<Copy the required fix from the review plan.>

## Files to change

- `path/to/file.html.twig`
- `path/to/file.js`

## Acceptance criteria

- [ ] <Specific, testable criterion 1>
- [ ] <Specific, testable criterion 2>
- [ ] Verified with keyboard-only navigation.
- [ ] Verified with <screen reader> on <browser>.
- [ ] No regressions on <test page path>.
```

---

## Related issues to link into this program

When creating the GitHub project dashboard, link the following types of
existing issues:

- Issues labeled `accessibility` or `a11y` in the repository.
- Issues referencing specific components covered in this plan (accordion,
  carousel, alphabetical listing, select menu, pager).
- Any open issues referencing WCAG, screen reader, keyboard navigation, or
  ARIA.

---

## GitHub project dashboard

If repository permissions allow, create a GitHub Project (beta) board with:

- **Board name:** AZ Quickstart Accessibility Program
- **Views:** By phase (grouped by milestone), by component (grouped by label),
  by status (To Do / In Progress / In Review / Done)
- **Linked repository:** `az-digital/az_quickstart`
- **Automation:** Auto-add issues labeled `accessibility` to the board

---

## Success measures (program-level)

| Measure | How verified |
|---------|-------------|
| Skip link moves focus to main content | Manual keyboard test on front page |
| Pager landmark name is "Pagination" | Screen reader landmarks list |
| Accordion panel names match heading text | Screen reader browse mode |
| Alphabetical listing search announces result count | Screen reader with search input active |
| Alpha nav letter links stay on page | Keyboard activation test |
| Select menu form has accessible name | Screen reader form mode |
| Carousel controls are keyboard accessible | Manual keyboard test on photo gallery page |
| Date picker does not trap focus | Manual keyboard test on calendar page |
| Axe scan runs in CI on every PR | CI workflow log |
| No new axe violations introduced in PR | CI workflow check |

---

## Conditions of satisfaction for this program issue

- [ ] `ACCESSIBILITY-REVIEW-PLAN.md` committed to repository.
- [ ] `ACCESSIBILITY-GITHUB-PROJECT-PLAN.md` committed to repository.
- [ ] Parent phase issues created and linked to the program umbrella issue.
- [ ] Delivery issues created under each phase and linked to their parent phase
  issue.
- [ ] Existing accessibility-related issues linked into the program.
- [ ] GitHub project dashboard created (if permissions allow).
- [ ] Team has reviewed scope, sequencing, and release expectations.
- [ ] Open questions in `ACCESSIBILITY-REVIEW-PLAN.md` answered before Phase 1
  implementation begins.
