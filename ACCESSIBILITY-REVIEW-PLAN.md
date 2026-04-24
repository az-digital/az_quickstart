# Accessibility Review and WCAG 2.2 AA Remediation Plan for az_quickstart

## Audit Information

This document captures a source-level accessibility review of az_quickstart and a prioritized remediation plan for reaching and maintaining WCAG 2.2 AA conformance.

- Audit date: April 23, 2026
- Auditor: GitHub Copilot
- Repository scope: custom theme, custom modules, project templates, CI configuration, and accessibility-related frontend assets across az_quickstart
- Review method: targeted source inspection, project-wide pattern searches, and a secondary specialist pass focused on Drupal frontend accessibility risks

This was a code review, not a rendered-site certification. No browser automation, no screen-reader session, and no manual keyboard walkthrough were executed against a running site during this pass. That means this document identifies confirmed code-level risks and the work required to verify runtime behavior, but it does not prove that no accessibility issues exist.

For a GitHub-ready execution structure, phased issue set, and product-oriented issue framing, see [ACCESSIBILITY-GITHUB-PROJECT-PLAN.md](ACCESSIBILITY-GITHUB-PROJECT-PLAN.md).

The live GitHub program artifacts are now available at:

- [Accessibility Program - az_quickstart project board](https://github.com/orgs/az-digital/projects/285)
- [#5533 - A11Y Program: WCAG 2.2 AA accessibility remediation for az_quickstart](https://github.com/az-digital/az_quickstart/issues/5533)

## Executive Summary

az_quickstart is not currently ready for a WCAG 2.2 AA conformance claim.

The most important problems are systemic rather than isolated. The current codebase has issues in page bypass navigation, accessible names for core controls, dynamic announcements, focus management, carousel behavior, custom widget semantics, and regression prevention. Those problems affect global navigation, search, filtering, carousels, accordion behavior, custom select navigation, the date picker, admin tables, and Marketing Cloud export templates.

The project also lacks an accessibility regression gate in CI. That is not itself a WCAG failure, but it is the main reason accessibility fixes will not remain fixed unless the team changes how the project is verified.

The highest-value sequence is:

1. Fix the global blockers that affect most pages and most users.
2. Repair the custom widgets that currently drift out of sync with assistive technology.
3. Clean up template semantics in tables, exports, and image-driven layouts.
4. Add automated and manual accessibility verification so the project can credibly maintain compliance.

## Confirmed Findings in Impact Order

1. The skip link target is not reliably present on standard pages. Severity: serious. Confidence: medium. WCAG: 2.4.1 Bypass Blocks. Evidence: [themes/custom/az_barrio/templates/layout/html.html.twig](themes/custom/az_barrio/templates/layout/html.html.twig#L44), [themes/custom/az_barrio/templates/layout/page.html.twig](themes/custom/az_barrio/templates/layout/page.html.twig#L194). The site-wide skip link points to `#content`, but the standard page template does not define a stable matching main-content target in the reviewed source. Keyboard users can be forced through repeated header and navigation content before they reach primary content. Fix by creating one canonical target on every page, preferably `id="main-content"` on the main landmark with `tabindex="-1"`, and update the skip link to point to that exact element.

2. The site search submit button is rendered as an icon-only control without an accessible name. Severity: serious. Confidence: high. WCAG: 1.1.1 Non-text Content and 4.1.2 Name, Role, Value. Evidence: [themes/custom/az_barrio/templates/forms/input--search-block-form--submit.html.twig](themes/custom/az_barrio/templates/forms/input--search-block-form--submit.html.twig#L13), [themes/custom/az_barrio/templates/forms/input--search.html.twig](themes/custom/az_barrio/templates/forms/input--search.html.twig#L13). The search input has an accessible name, but the submit control becomes a button that only contains a search icon. Screen-reader and speech-input users can encounter an unnamed action at a core site entry point. Fix by adding visible text or an explicit `aria-label` on the submit button.

3. The custom select-menu widget exposes invalid and contradictory accessibility state. Severity: serious. Confidence: high. WCAG: 3.3.1 Error Identification and 4.1.2 Name, Role, Value. Evidence: [modules/custom/az_select_menu/src/Plugin/Block/AzSelectMenu.php](modules/custom/az_select_menu/src/Plugin/Block/AzSelectMenu.php#L136), [modules/custom/az_select_menu/src/Plugin/Block/AzSelectMenu.php](modules/custom/az_select_menu/src/Plugin/Block/AzSelectMenu.php#L150), [modules/custom/az_select_menu/js/az-select-menu.js](modules/custom/az_select_menu/js/az-select-menu.js#L68), [modules/custom/az_select_menu/js/az-select-menu.js](modules/custom/az_select_menu/js/az-select-menu.js#L94). The widget uses a popover for error feedback, keeps `aria-invalid="false"`, and sets `aria-disabled="true"` on the select when the empty option is selected without clearly restoring the real state when a valid option is chosen. Users can be told the control is disabled or receive a visual-only error without a programmatic relationship to the field. Fix by rendering persistent inline error text, toggling `aria-invalid`, linking the message with `aria-describedby` or `aria-errormessage`, and removing incorrect disabled state from the select.

4. The accordion template has broken ARIA relationships. Severity: serious. Confidence: high. WCAG: 1.3.1 Info and Relationships and 4.1.2 Name, Role, Value. Evidence: [modules/custom/az_accordion/templates/az-accordion.html.twig](modules/custom/az_accordion/templates/az-accordion.html.twig#L19), [modules/custom/az_accordion/templates/az-accordion.html.twig](modules/custom/az_accordion/templates/az-accordion.html.twig#L25). Each panel points `aria-labelledby` to its own panel ID instead of the trigger button, and `data-bs-parent` points back to the item rather than the accordion container. That breaks the naming relationship between trigger and region and makes assistive technology output less reliable. Fix by assigning a unique ID to the button, referencing that ID from the panel, and setting `data-bs-parent` to the accordion container.

5. Hidden Slick slides can permanently lose keyboard access after the a11y patch runs. Severity: serious. Confidence: high. WCAG: 2.1.1 Keyboard and 2.4.3 Focus Order. Evidence: [themes/custom/az_barrio/js/slick-a11y-patch.js](themes/custom/az_barrio/js/slick-a11y-patch.js#L30), [themes/custom/az_barrio/js/slick-a11y-patch.js](themes/custom/az_barrio/js/slick-a11y-patch.js#L34). The patch removes descendants of hidden slides from the tab order, but it does not restore prior focusability when those slides become visible later. Keyboard users can lose access to links and buttons inside carousel content. Fix by storing original tabindex values and recalculating focusability on every slide change.

6. The photo-gallery carousel does not expose enough structure or state to assistive technology. Severity: serious. Confidence: high. WCAG: 1.3.1 Info and Relationships, 2.4.6 Headings and Labels, and 4.1.2 Name, Role, Value. Evidence: [modules/custom/az_paragraphs/az_paragraphs_photo_gallery/templates/photo-gallery-carousel.html.twig](modules/custom/az_paragraphs/az_paragraphs_photo_gallery/templates/photo-gallery-carousel.html.twig), [modules/custom/az_paragraphs/az_paragraphs_photo_gallery/templates/slider-gallery.html.twig](modules/custom/az_paragraphs/az_paragraphs_photo_gallery/templates/slider-gallery.html.twig). Previous and next controls have basic hidden text, but the carousel itself has no accessible name, slides are not identified as slides, and there is no active-slide announcement. Screen-reader users can move through the widget without reliable orientation. Fix by naming the carousel, giving slides explicit position labels such as "Slide X of Y," and exposing active-slide changes through synchronized state or a polite live region.

7. The vendored date picker uses `role="application"` and assertive live regions for routine navigation. Severity: serious. Confidence: high. WCAG: 4.1.2 Name, Role, Value and 4.1.3 Status Messages. Evidence: [modules/custom/az_core/lib/vanilla-calendar-pro/index.js](modules/custom/az_core/lib/vanilla-calendar-pro/index.js#L2), [modules/custom/az_core/lib/vanilla-calendar-pro/index.mjs](modules/custom/az_core/lib/vanilla-calendar-pro/index.mjs#L2). The calendar root uses application mode, and the dates, months, and years views announce normal navigation with `aria-live="assertive"`. That can suppress familiar browse-mode behavior and create excessive interruption. Fix by removing `role="application"`, using standard grid or dialog semantics, and moving non-urgent updates to polite announcements.

8. The alphabetical listing filter updates are silent to assistive technology. Severity: serious. Confidence: high. WCAG: 4.1.3 Status Messages. Evidence: [modules/custom/az_alphabetical_listing/templates/views-view--az-alphabetical-listing.html.twig](modules/custom/az_alphabetical_listing/templates/views-view--az-alphabetical-listing.html.twig#L101), [modules/custom/az_alphabetical_listing/js/alphabetical_listing.js](modules/custom/az_alphabetical_listing/js/alphabetical_listing.js#L114). Search results are hidden and shown dynamically, and the no-results panel toggles visually, but there is no persistent live region communicating the current result state. Screen-reader users may not know that filtering occurred or whether results remain. Fix by adding an always-mounted polite status region that announces result counts and the no-results condition.

9. The alphabetical listing A to Z jump behavior scrolls visually but does not move focus. Severity: serious. Confidence: high. WCAG: 2.4.3 Focus Order and 2.4.7 Focus Visible. Evidence: [modules/custom/az_alphabetical_listing/js/alphabetical_listing.js](modules/custom/az_alphabetical_listing/js/alphabetical_listing.js#L151). The script intercepts native anchor behavior, animates the scroll, and updates the hash, but leaves focus on the alphabet control. Keyboard and screen-reader users do not land in the destination context after activation. Fix by either allowing native anchor behavior or moving focus to the destination heading after scroll completion.

10. Routine status messages are announced too aggressively. Severity: serious. Confidence: high. WCAG: 4.1.3 Status Messages. Evidence: [themes/custom/az_barrio/js/toasts.js](themes/custom/az_barrio/js/toasts.js#L41), [themes/custom/az_barrio/js/toasts.js](themes/custom/az_barrio/js/toasts.js#L95). The alert path always uses `role="alert"`, and the toast path always uses `aria-live="assertive"`, even for ordinary status and info messages. This interrupts screen-reader users unnecessarily and makes urgent messages harder to distinguish. Fix by using polite announcements for routine feedback and reserving alerts for urgent failures and warnings.

11. Pager landmarks are named with an internal ID token instead of a human-readable label. Severity: moderate. Confidence: high. WCAG: 1.3.1 Info and Relationships and 2.4.6 Headings and Labels. Evidence: [themes/custom/az_barrio/templates/navigation/pager.html.twig](themes/custom/az_barrio/templates/navigation/pager.html.twig#L36), [themes/custom/az_barrio/templates/views/views-mini-pager.html.twig](themes/custom/az_barrio/templates/views/views-mini-pager.html.twig#L16). The navigation elements use `aria-label="{{ heading_id }}"`, which names the landmark with an ID token instead of meaningful text. That reduces landmark usability for screen-reader navigation. Fix by changing to `aria-labelledby="{{ heading_id }}"` or a literal readable label such as `Pagination`.

12. The select-menu CSS removes default focus indication without a robust replacement. Severity: moderate. Confidence: high. WCAG: 1.4.11 Non-text Contrast and 2.4.7 Focus Visible. Evidence: [modules/custom/az_select_menu/css/az-select-menu.css](modules/custom/az_select_menu/css/az-select-menu.css#L45). The control removes the outline and relies on a bottom-border color change that is weaker than a dedicated focus ring. Keyboard users can struggle to locate the focused control. Fix by restoring a visible `:focus-visible` ring with sufficient contrast and preserving a clear control boundary.

13. The publication type table relies on implicit semantics and has no caption. Severity: serious. Confidence: high. WCAG: 1.3.1 Info and Relationships. Evidence: [modules/custom/az_publication/templates/az-publication-type-listing-table.html.twig](modules/custom/az_publication/templates/az-publication-type-listing-table.html.twig#L19). The table has column headers, but no caption and no row header for the primary label cell. That weakens context for screen-reader users moving across rows and operations. Fix by adding a caption, explicit scopes for column headers where needed, and a row header cell for the publication type label.

14. Marketing Cloud image-driven layouts hard-code empty alt text on content images. Severity: serious. Confidence: high. WCAG: 1.1.1 Non-text Content. Evidence: [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--hero-layout.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--hero-layout.html.twig#L81), [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--30-70-layout.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--30-70-layout.html.twig#L86), [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout.html.twig#L86), [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout-image-right.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout-image-right.html.twig#L86), [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--70-30-layout-image-right.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--70-30-layout-image-right.html.twig#L86). If those images carry real content or context, they are currently hidden from assistive technology. Fix by threading through author-provided media alt text and only using `alt=""` for explicitly decorative assets.

15. The Marketing Cloud export HTML shell strips document-level semantics. Severity: moderate. Confidence: medium. WCAG: 2.4.2 Page Titled, 2.4.1 Bypass Blocks, and 3.1.1 Language of Page. Evidence: [modules/custom/az_marketing_cloud/templates/html--export--marketing-cloud.html.twig](modules/custom/az_marketing_cloud/templates/html--export--marketing-cloud.html.twig#L1). The HTML template outputs only `page.content`, which means browser-facing export routes can lose the normal document shell and page-level landmarks. If these routes are opened directly, assistive technology users get a degraded navigation model. Fix by either rendering a minimal document shell for browser-facing routes or ensuring these templates are only used as fragments outside normal browser navigation.

16. The project has no explicit accessibility regression gate. Severity: program risk. Confidence: high. Evidence: [package.json](package.json), [.github/workflows/ci.yml](.github/workflows/ci.yml). The current toolchain covers linting, PHP static analysis, security, and PHPUnit, but no accessibility scanning or keyboard or assistive-technology verification. This is the main reason WCAG defects can re-enter the codebase after remediation. Fix by adding automated page-level accessibility checks and a required manual verification matrix for high-risk interactions.

## Additional Audit Areas That Still Need Runtime Verification

The following areas were not proven broken from source alone, but they are high-priority verification targets because they map to WCAG 2.2 AA risk areas or depend on content authors and runtime behavior.

1. Drag-and-drop and reorder workflows. The project includes draggable ordering flows such as carousel reordering through DraggableViews. Those flows must be checked for WCAG 2.5.7 Dragging Movements and keyboard alternatives.

2. Authentication and password-management compatibility. CAS and login-related flows should be tested for WCAG 3.3.8 Accessible Authentication - Minimum, including support for paste, password managers, passkeys if available, and recovery flows.

3. Audio and video content governance. The codebase supports local audio, local video, remote video, and consent-gated embeds. Captions, transcripts, autoplay behavior, and player control accessibility must be verified with real content.

4. Background-image usage. [modules/custom/az_media/templates/az-responsive-background-image.html.twig](modules/custom/az_media/templates/az-responsive-background-image.html.twig) is safe only when images are decorative. Any meaningful content conveyed through CSS backgrounds will fail non-text alternatives.

5. Stretched-link patterns in card and ranking components. [modules/custom/az_card/templates/az-card.html.twig](modules/custom/az_card/templates/az-card.html.twig#L46) and similar templates should be checked with a screen reader to confirm link purpose, duplicate-link behavior, and accessible-name outcomes in all editor configurations.

## Prioritized Remediation Plan

### Phase 0 - Establish the Accessibility Gate

This phase should begin immediately and run in parallel with code remediation. It is the only way to turn this review from a one-time cleanup into an ongoing compliance practice.

1. Add automated accessibility scanning to CI. The recommended baseline is the GitHub Accessibility Scanner against a representative page matrix of live URLs, because the project already uses GitHub Actions and the scanner can surface findings directly in GitHub for maintainers.
2. Define the manual assistive-technology matrix: NVDA with Firefox, NVDA with Chrome, JAWS with Chrome, JAWS with Edge, VoiceOver with Safari on macOS, VoiceOver with Safari on iOS, and TalkBack with Chrome on Android where mobile interaction is important.
3. Add accessibility acceptance criteria to custom widget work. Every widget fix should ship with keyboard, focus, name, state, and announcement checks.
4. Create a regression policy: no merge for new serious or critical accessibility issues on representative pages.

### Phase 1 - Fix Global Blockers First

These changes affect the largest number of pages and users.

1. Repair skip navigation and the main landmark target in the page shell.
2. Give the global search submit button an accessible name.
3. Fix pager landmark naming in both full and mini pagers.
4. Review global message rendering so routine status feedback is polite and urgent alerts stay urgent.

### Phase 2 - Repair Custom Widgets That Drift Out of Sync With Assistive Technology

These are the highest-risk component-level issues because they can block keyboard and screen-reader use even when the surrounding page is otherwise sound.

1. Rebuild the select-menu widget so validation, error messaging, focus styling, and accessibility state are consistent.
2. Correct accordion trigger and panel relationships.
3. Fix Slick slide focus restoration across initialization and slide changes.
4. Upgrade the photo-gallery carousel with a carousel name, slide naming, and state announcement.
5. Replace the date picker's application mode and assertive live-region strategy with standard grid or dialog behavior.
6. Strengthen date-picker focus visibility in light themes.

### Phase 3 - Fix Dynamic Content, Filtering, and Focus Handoffs

These items are especially important for screen-reader users and keyboard-only users because the UI changes without a full page reload.

1. Add a persistent polite results status region to the alphabetical listing.
2. Move focus to the destination section after A to Z navigation or allow native anchor behavior.
3. Audit other AJAX or dynamically updated components for status-message behavior, especially search and filter interfaces.

### Phase 4 - Correct Content and Template Semantics

These issues are narrower in scope than the widget problems, but they are still required for WCAG 2.2 AA.

1. Add caption and row-header semantics to publication tables and any similar data tables.
2. Replace forced decorative alt text in Marketing Cloud layouts with real alt propagation.
3. Decide whether Marketing Cloud export routes are fragment-only or browser-facing, then implement the correct document semantics for that usage.
4. Audit all background-image and decorative-image patterns so meaningful images are never conveyed only through CSS.
5. Verify all author-facing media workflows communicate caption, transcript, and alt-text requirements clearly.

### Phase 5 - Prove Compliance on Rendered Pages and Lock It In

Code changes are not enough. This phase turns repaired code into a defensible compliance outcome.

1. Build a representative test page matrix. At minimum include the front page, a standard content page, a sidebar page, a page using the select menu, an alphabetical listing page, a page with a photo gallery, an event page with the date picker, a publication admin table page, a login or authentication-related page, and a Marketing Cloud export route.
2. Run automated scans against every representative page on every pull request.
3. Run manual keyboard-only checks on every representative page before release.
4. Run screen-reader checks on the high-risk widgets and global flows before release.
5. Track issues by severity and component so regressions are visible over time.

## Recommended Work Backlog by Team Area

### Theme and Layout

1. [themes/custom/az_barrio/templates/layout/html.html.twig](themes/custom/az_barrio/templates/layout/html.html.twig)
2. [themes/custom/az_barrio/templates/layout/page.html.twig](themes/custom/az_barrio/templates/layout/page.html.twig)
3. [themes/custom/az_barrio/templates/forms/input--search-block-form--submit.html.twig](themes/custom/az_barrio/templates/forms/input--search-block-form--submit.html.twig)
4. [themes/custom/az_barrio/templates/navigation/pager.html.twig](themes/custom/az_barrio/templates/navigation/pager.html.twig)
5. [themes/custom/az_barrio/templates/views/views-mini-pager.html.twig](themes/custom/az_barrio/templates/views/views-mini-pager.html.twig)
6. [themes/custom/az_barrio/js/toasts.js](themes/custom/az_barrio/js/toasts.js)
7. [themes/custom/az_barrio/js/slick-a11y-patch.js](themes/custom/az_barrio/js/slick-a11y-patch.js)

### Custom Widgets

1. [modules/custom/az_select_menu/src/Plugin/Block/AzSelectMenu.php](modules/custom/az_select_menu/src/Plugin/Block/AzSelectMenu.php)
2. [modules/custom/az_select_menu/js/az-select-menu.js](modules/custom/az_select_menu/js/az-select-menu.js)
3. [modules/custom/az_select_menu/css/az-select-menu.css](modules/custom/az_select_menu/css/az-select-menu.css)
4. [modules/custom/az_accordion/templates/az-accordion.html.twig](modules/custom/az_accordion/templates/az-accordion.html.twig)
5. [modules/custom/az_alphabetical_listing/templates/views-view--az-alphabetical-listing.html.twig](modules/custom/az_alphabetical_listing/templates/views-view--az-alphabetical-listing.html.twig)
6. [modules/custom/az_alphabetical_listing/js/alphabetical_listing.js](modules/custom/az_alphabetical_listing/js/alphabetical_listing.js)
7. [modules/custom/az_paragraphs/az_paragraphs_photo_gallery/templates/photo-gallery-carousel.html.twig](modules/custom/az_paragraphs/az_paragraphs_photo_gallery/templates/photo-gallery-carousel.html.twig)
8. [modules/custom/az_core/lib/vanilla-calendar-pro/index.js](modules/custom/az_core/lib/vanilla-calendar-pro/index.js)
9. [modules/custom/az_core/lib/vanilla-calendar-pro/styles/themes/light.css](modules/custom/az_core/lib/vanilla-calendar-pro/styles/themes/light.css)
10. [modules/custom/az_core/lib/vanilla-calendar-pro/styles/themes/slate-light.css](modules/custom/az_core/lib/vanilla-calendar-pro/styles/themes/slate-light.css)

### Content Templates and Editorial Outputs

1. [modules/custom/az_publication/templates/az-publication-type-listing-table.html.twig](modules/custom/az_publication/templates/az-publication-type-listing-table.html.twig)
2. [modules/custom/az_marketing_cloud/templates/html--export--marketing-cloud.html.twig](modules/custom/az_marketing_cloud/templates/html--export--marketing-cloud.html.twig)
3. [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--hero-layout.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--hero-layout.html.twig)
4. [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--30-70-layout.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--30-70-layout.html.twig)
5. [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout.html.twig)
6. [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout-image-right.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--50-50-layout-image-right.html.twig)
7. [modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--70-30-layout-image-right.html.twig](modules/custom/az_marketing_cloud/templates/node--view--az-marketing-cloud--70-30-layout-image-right.html.twig)
8. [modules/custom/az_media/templates/az-responsive-background-image.html.twig](modules/custom/az_media/templates/az-responsive-background-image.html.twig)

### Tooling and Quality Gates

1. [package.json](package.json)
2. [.github/workflows/ci.yml](.github/workflows/ci.yml)

## Verification Strategy

The following strategy is required if the team wants to move from source-level remediation to a credible WCAG 2.2 AA sign-off.

### Automated Checks

1. Add the GitHub Accessibility Scanner for representative-page scanning against approved live URLs.
2. Establish a brownfield baseline first, then fail pull requests on new serious or critical issues.
3. Track accessibility regressions separately from lint and unit-test failures so they are visible and actionable.
4. Use narrower supplemental checks only where authenticated or hard-to-reach flows cannot be covered by the primary scanner.

### Manual Checks

1. Keyboard-only navigation for every representative page.
2. Focus visibility review at 100 percent and 200 percent zoom.
3. NVDA with Firefox and NVDA with Chrome checks for navigation, search, filters, select menu, carousel, date picker, and tables.
4. JAWS with Chrome and JAWS with Edge checks for the same high-risk Windows flows.
5. VoiceOver checks for the same high-risk flows on macOS and iOS.
6. Screen-reader confirmation that status messages are announced once, politely when appropriate, and without duplicate noise.

### Content Governance Checks

1. Confirm all editorial image workflows enforce meaningful alt text or explicit decoration.
2. Confirm audio and video workflows require captions, transcripts, and accessible playback controls.
3. Confirm Marketing Cloud export authors understand when email layouts may use decorative images and when they must expose informative alt text.

## Exit Criteria

az_quickstart should not be described as WCAG 2.2 AA compliant until all of the following are true.

1. All Phase 1 and Phase 2 issues are fixed and verified on rendered pages.
2. No serious or critical accessibility issues remain on the representative page matrix.
3. Core flows are usable with keyboard only: global navigation, site search, select menu navigation, alphabetical listing, gallery carousel, date picker, table interaction, and authentication-related forms.
4. Manual checks pass with NVDA, JAWS, and VoiceOver on the defined matrix.
5. Accessibility checks run in CI and block regressions.
6. Editorial guidance exists for alt text, decorative images, captions, transcripts, and background-image usage.

## Open Questions and Assumptions

1. The skip-link finding assumes the standard page shell does not receive a runtime-injected `id="content"`. The reviewed source strongly suggests that the target is missing, but this should still be confirmed on a rendered page.
2. The Marketing Cloud export-shell finding assumes the export routes are browser-facing. If they are fragment-only and never directly navigated to, the risk is lower, but the route contract should be explicit.
3. The drag-and-drop and authentication areas were identified as high-priority verification zones because of WCAG 2.2 risk, but they still require runtime testing before they should be classified as failures or passes.

## Recommended Next Step

Start with Phase 0 and Phase 1 together. Fixing the global blockers without adding an accessibility gate will create short-term improvement and long-term churn. Adding the gate without fixing the blockers will create noise and stalled pull requests. The best outcome is to do both in the same remediation window.