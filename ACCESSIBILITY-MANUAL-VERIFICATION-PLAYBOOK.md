# Accessibility Manual Verification Playbook

## Purpose

This playbook defines how az_quickstart should run manual accessibility verification after the automated scanner pass. It is intended to support Phase 0 verification work, release sign-off, and user acceptance for the highest-risk user journeys identified in the accessibility review.

This document does not replace the representative page matrix or the scanner workflow. It tells reviewers what to do after the automated gate runs and what outcomes should be treated as pass, fail, or release risk.

## How To Use This Playbook

1. Start with the approved representative URLs from [ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md](ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md).
2. Confirm whether the review is for a patch profile or a minor-release profile.
3. Run the automated scan first.
4. Run the manual checks in this document on the affected pages and flows.
5. Record results in [ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md](ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md).

## Required Review Conditions

1. Use the same live environment that the automated scanner used when practical.
2. Review pages at 100 percent zoom and 200 percent zoom.
3. Use a keyboard without a mouse for the keyboard-only pass.
4. Confirm both success and failure paths when a flow includes validation or status messages.
5. Record every skip, partial pass, or uncertainty in the release sign-off checklist rather than treating it as a silent pass.

## Required Manual Matrix

The baseline manual matrix for release verification is:

1. NVDA with Firefox on Windows.
2. NVDA with Chrome on Windows.
3. JAWS with Chrome on Windows.
4. JAWS with Edge on Windows.
5. VoiceOver with Safari on macOS.
6. VoiceOver with Safari on iOS when the release changes mobile interaction, mobile layout, or mobile media behavior.

TalkBack with Chrome on Android should be added when the release materially changes mobile forms, mobile navigation, or mobile media interaction.

## Release Scope Rules

### Patch Profile

Use the patch profile for smaller releases and targeted fixes.

1. Run keyboard-only checks on every changed representative page.
2. Run manual screen-reader checks on every affected high-risk flow.
3. Include JAWS with Chrome and JAWS with Edge when the change affects Windows-facing interactive behavior.
4. Include VoiceOver on iOS when the change affects mobile interaction.

### Minor-Release Profile

Use the minor-release profile for broader work.

1. Run keyboard-only checks across the full representative page matrix.
2. Run the full required screen-reader matrix for all high-risk flows in scope.
3. Record unresolved risks explicitly before release approval.

## Core Flow Checks

The sections below define the minimum expected outcomes for the highest-risk flows already identified in the planning package.

### Global Shell And Skip Link

Run this on the front page and at least one standard content page.

Steps:

1. Load the page from the top.
2. Press `Tab` until the skip link is visible.
3. Activate the skip link.
4. Continue keyboard navigation from the destination.
5. Repeat with the required screen readers.

Expected results:

1. The skip link appears predictably.
2. Activation moves focus to the main content target.
3. The destination is announced clearly as the main content area.
4. The user can continue reading or tabbing from the destination without being thrown back into repeated navigation.

### Search And Pager Controls

Run this on the search results page or another representative page that exposes search and pagination.

Steps:

1. Move to the search input and search submit control by keyboard.
2. Confirm the accessible name of the search submit control.
3. Move through the pagination landmark and links.
4. Repeat with the required screen readers.

Expected results:

1. The search submit control has a clear accessible name.
2. Pagination landmarks use human-readable labels.
3. Screen-reader landmark and rotor views describe the controls understandably.
4. Keyboard use remains complete and predictable.

### Status Messages And Alerts

Run this on a page that can trigger both routine status feedback and an error or urgent warning.

Steps:

1. Trigger a routine success or informational message.
2. Trigger an error or urgent warning if the page supports it.
3. Listen for announcement timing and repetition.
4. Repeat with the required screen readers.

Expected results:

1. Routine informational or success messages are announced politely.
2. Error messages are announced urgently only when appropriate.
3. Messages do not duplicate or repeat unnecessarily.
4. Users can continue their task without disruptive announcement noise.

### Select-Menu Block

Run this on a page that uses the custom select-menu block.

Steps:

1. Tab to the control.
2. Inspect focus visibility before interacting.
3. Trigger the empty or invalid path.
4. Select a valid option and confirm recovery.
5. Repeat with the required screen readers.

Expected results:

1. Focus is clearly visible.
2. The control's announced state matches its real interactive state.
3. Error messaging is associated programmatically with the field.
4. The user can recover from the invalid path without ambiguity.

### Accordion Sections

Run this on a page that uses the custom accordion component.

Steps:

1. Move through accordion triggers by keyboard.
2. Open and close multiple sections.
3. Move into the expanded content.
4. Repeat with the required screen readers.

Expected results:

1. Each trigger announces the correct section context.
2. Expanded content is tied to the correct trigger.
3. Keyboard access remains complete.
4. Users do not lose track of which section is open.

### Alphabetical Listing

Run this on a page using the alphabetical listing view.

Steps:

1. Use the filter or search field.
2. Trigger both a result case and a no-results case.
3. Use A to Z jump navigation.
4. Confirm where focus lands after navigation.
5. Repeat with the required screen readers.

Expected results:

1. Result changes are announced clearly.
2. No-results status is announced clearly.
3. A to Z navigation lands the user in the destination section or preserves reliable native behavior.
4. The updated state is understandable without visual cues alone.

### Photo Gallery Carousel

Run this on a page with the photo gallery carousel.

Steps:

1. Move to the carousel by keyboard.
2. Identify the accessible name of the carousel.
3. Move between slides.
4. Confirm what is announced when the active slide changes.
5. Repeat with the required screen readers.

Expected results:

1. The carousel has a clear accessible name.
2. Slide position is understandable, such as slide number and total count.
3. Active slide changes are communicated clearly.
4. Keyboard access remains complete and predictable.

### Date Picker

Run this on an event page or other representative page that exposes the date picker.

Steps:

1. Open the picker by keyboard.
2. Move between dates, months, or years as supported.
3. Listen for announcement behavior during routine navigation.
4. Review focus visibility during movement.
5. Repeat with the required screen readers.

Expected results:

1. Routine navigation is not announced as urgent.
2. The picker behaves like a familiar date-selection control rather than an application-mode trap.
3. Focus remains easy to track.
4. Users can select a date without fighting the control.

### Publication Tables

Run this on the publication table page or publication administration table page.

Steps:

1. Move to the table.
2. Confirm whether the table exposes a usable name or caption.
3. Navigate cell by cell with the screen reader.
4. Confirm row and column context while moving.

Expected results:

1. The table has a clear name or caption.
2. Row and column context remains understandable during navigation.
3. The primary label cell behaves like a row header where needed.
4. Users do not hear ambiguous values without context.

### Marketing Cloud Layouts And Export Route

Run this on the approved representative Marketing Cloud layout and export route.

Steps:

1. Confirm whether the layout includes meaningful images.
2. Review whether meaningful images expose informative alternative text.
3. Load the export route directly in a browser.
4. Confirm whether landmarks, page identity, and document context remain understandable.

Expected results:

1. Meaningful images are not hidden as decorative by default.
2. Explicitly decorative images stay appropriately decorative.
3. Browser-facing export pages preserve enough page-level semantics to remain understandable.
4. Any unresolved export-route ambiguity is recorded as a release risk, not ignored.

### Authentication-Related Flow

Run this only on an approved representative authentication-related page that can be tested safely.

Steps:

1. Move through the login or authentication flow by keyboard.
2. Confirm focus order, field naming, and error recovery.
3. Repeat with the required Windows and Apple screen-reader combinations that are in scope.

Expected results:

1. The flow is keyboard operable.
2. Fields and actions are clearly named.
3. Error handling is understandable and recoverable.
4. The result should be recorded as verified, deferred, or blocked by environment limitations.

## Pass, Fail, And Risk Handling

### Pass

Mark a check as pass only when the outcome is clear and repeatable in the tested environment.

### Fail

Mark a check as fail when a user cannot complete the task reliably, when state or announcement behavior is misleading, or when focus and navigation break task completion.

### Release Risk

Mark a check as release risk when:

1. The flow could not be verified because the environment was not available.
2. The behavior is ambiguous and needs team review.
3. The issue does not fully block the release but creates real user risk that decision-makers must acknowledge.

## Evidence To Capture

For each failed or risky check, record:

1. Representative page ID or URL.
2. Flow tested.
3. Browser and assistive technology used.
4. Short description of the observed behavior.
5. Whether the issue appears to be a regression, an existing baseline issue, or an environment limitation.

## Recommended Next Step

Use this playbook together with [ACCESSIBILITY-SCANNER-TRIAGE-GUIDE.md](ACCESSIBILITY-SCANNER-TRIAGE-GUIDE.md), [ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md](ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md), and [ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md](ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md) so the automated and manual parts of Phase 0 use the same scope and terminology.