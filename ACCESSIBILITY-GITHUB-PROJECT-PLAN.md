# GitHub Project Plan for the az_quickstart Accessibility Program

## Purpose

This document translates the accessibility review into a GitHub-ready delivery plan for az_quickstart.

Use this document before implementation starts to:

1. Set up the GitHub project structure.
2. Open the right issues in the right order.
3. Describe each issue in user-centered language so prioritization is clear to product, engineering, design, QA, and maintainers.
4. Keep the current short-term carousel work scoped tightly while the broader program is planned and delivered in phases.

This document is a companion to [ACCESSIBILITY-REVIEW-PLAN.md](ACCESSIBILITY-REVIEW-PLAN.md).

## Live GitHub Artifacts

The planning structure described in this document has now been created in GitHub.

### Project Dashboard

- Project board: [Accessibility Program - az_quickstart](https://github.com/orgs/az-digital/projects/285)

### Program Issue

- Umbrella issue: [#5533 - A11Y Program: WCAG 2.2 AA accessibility remediation for az_quickstart](https://github.com/az-digital/az_quickstart/issues/5533)

### Phase Parent Issues

- [#5532 - A11Y Phase 0: Accessibility guardrails and verification setup](https://github.com/az-digital/az_quickstart/issues/5532)
- [#5535 - A11Y Phase 1: Global user experience blockers](https://github.com/az-digital/az_quickstart/issues/5535)
- [#5531 - A11Y Phase 2: High-risk interactive components](https://github.com/az-digital/az_quickstart/issues/5531)
- [#5536 - A11Y Phase 3: Content and template semantics](https://github.com/az-digital/az_quickstart/issues/5536)
- [#5534 - A11Y Phase 4: Verification and release readiness](https://github.com/az-digital/az_quickstart/issues/5534)

### Delivery Issues

#### Phase 0

- [#5541 - A11Y P0.1: Define representative page coverage and verification policy for accessibility testing](https://github.com/az-digital/az_quickstart/issues/5541)
- [#5539 - A11Y P0.2: Add accessibility regression checks to CI for representative Quickstart pages](https://github.com/az-digital/az_quickstart/issues/5539)

#### Phase 1

- [#5538 - A11Y P1.1: Make repeated page navigation skippable so users can reach content faster](https://github.com/az-digital/az_quickstart/issues/5538)
- [#5540 - A11Y P1.2: Make search and pagination controls understandable at first use](https://github.com/az-digital/az_quickstart/issues/5540)
- [#5537 - A11Y P1.3: Make site messages announce the right information at the right urgency](https://github.com/az-digital/az_quickstart/issues/5537)

#### Phase 2

- [#5542 - A11Y P2.1: Make the select-menu block clear, valid, and easy to recover from when no option is chosen](https://github.com/az-digital/az_quickstart/issues/5542)
- [#5543 - A11Y P2.2: Make accordion sections announce the right section name and relationship](https://github.com/az-digital/az_quickstart/issues/5543)
- [#5514 - AZ Carousel: Slick library generates multiple critical ARIA violations (WCAG 4.1.2, 1.3.1)](https://github.com/az-digital/az_quickstart/issues/5514)
- [#5544 - A11Y P2.4: Make photo gallery carousels understandable to screen-reader and keyboard users](https://github.com/az-digital/az_quickstart/issues/5544)
- [#5546 - A11Y P2.5: Make alphabetical listing filtering and jump navigation clear to assistive-technology users](https://github.com/az-digital/az_quickstart/issues/5546)
- [#5545 - A11Y P2.6: Make the calendar picker behave in a familiar way for keyboard and screen-reader users](https://github.com/az-digital/az_quickstart/issues/5545)

#### Phase 3

- [#5547 - A11Y P3.1: Make publication tables easier to understand cell by cell for screen-reader users](https://github.com/az-digital/az_quickstart/issues/5547)
- [#5551 - A11Y P3.2: Preserve meaningful image descriptions in Marketing Cloud layouts](https://github.com/az-digital/az_quickstart/issues/5551)
- [#5550 - A11Y P3.3: Decide whether Marketing Cloud export routes are fragments or full browser-facing pages](https://github.com/az-digital/az_quickstart/issues/5550)
- [#5549 - A11Y P3.4: Document and tighten guidance for meaningful versus decorative image patterns](https://github.com/az-digital/az_quickstart/issues/5549)

#### Phase 4

- [#5548 - A11Y P4.1: Run accessibility verification on representative pages before release](https://github.com/az-digital/az_quickstart/issues/5548)

### Current GitHub Setup Notes

- The project board is organization-scoped because GitHub Projects v2 cannot be repository-scoped.
- The board is linked to `az-digital/az_quickstart` and contains the umbrella issue, all phase issues, and all delivery issues listed above.
- The project board is a standing program dashboard and is not tied to a feature or bug branch.
- The existing Slick accessibility issue [#5514](https://github.com/az-digital/az_quickstart/issues/5514) was intentionally reused as the Phase 2 carousel defect instead of creating a duplicate.
- Parent-child issue relationships are live in GitHub.
- The project description and readme have been updated to reflect the accessibility program and source planning documents.
- The dedicated planning branch stores supporting documents only. It is not an implementation branch.

### Friday Support Documents

- Meeting agenda: [ACCESSIBILITY-FRIDAY-MEETING-AGENDA.md](ACCESSIBILITY-FRIDAY-MEETING-AGENDA.md)
- Facilitator script: [ACCESSIBILITY-FRIDAY-FACILITATOR-SCRIPT.md](ACCESSIBILITY-FRIDAY-FACILITATOR-SCRIPT.md)
- Post-meeting notes template: [ACCESSIBILITY-POST-MEETING-NOTES-TEMPLATE.md](ACCESSIBILITY-POST-MEETING-NOTES-TEMPLATE.md)
- Live presentation talk track: [ACCESSIBILITY-LIVE-PRESENTATION-TALK-TRACK.md](ACCESSIBILITY-LIVE-PRESENTATION-TALK-TRACK.md)
- Saved-view handoff for manual GitHub setup: [ACCESSIBILITY-PROJECT-VIEWS-HANDOFF.md](ACCESSIBILITY-PROJECT-VIEWS-HANDOFF.md)
- Email draft summarizing today's work: [ACCESSIBILITY-PROGRAM-EMAIL-DRAFT.md](ACCESSIBILITY-PROGRAM-EMAIL-DRAFT.md)
- Executive email draft: [ACCESSIBILITY-EXECUTIVE-EMAIL-DRAFT.md](ACCESSIBILITY-EXECUTIVE-EMAIL-DRAFT.md)
- Consolidated send-out brief: [ACCESSIBILITY-SENDOUT-BRIEF.md](ACCESSIBILITY-SENDOUT-BRIEF.md)
- Final send-ready email: [ACCESSIBILITY-FINAL-REVIEW-EMAIL.md](ACCESSIBILITY-FINAL-REVIEW-EMAIL.md)
- One-page Friday handout: [ACCESSIBILITY-FRIDAY-HANDOUT.md](ACCESSIBILITY-FRIDAY-HANDOUT.md)

## Recommended GitHub Operating Model

Use the existing repository issue templates rather than inventing a separate workflow.

### Umbrella Program Issue

Suggested template: Feature request

Suggested title: WCAG 2.2 AA accessibility remediation program for az_quickstart

Use the umbrella issue to explain the program goal, link the audit, link every phase issue, and keep one public status summary for the whole initiative.

### Phase Issues

Suggested template: Task

Create one task issue per phase:

1. Phase 0 - Accessibility guardrails and verification setup
2. Phase 1 - Global user experience blockers
3. Phase 2 - High-risk interactive components
4. Phase 3 - Content and template semantics
5. Phase 4 - Verification and release readiness

Each phase issue should contain a checklist of child issues and link back to the umbrella program issue.

### Delivery Issues

Use these templates by issue type:

1. User-facing behavior change: User story
2. Implementation or tooling work: Task
3. Investigation or decision work: Spike
4. Narrow defect with reproducible behavior: Bug report

### How to Treat the Current Carousel PR

The active pull request at [PR #5528](https://github.com/az-digital/az_quickstart/pull/5528) should stay narrow.

Recommended position:

1. Keep it as the short-term Slick stabilization change only.
2. Link it to a single delivery issue about keyboard access in Slick slides.
3. Do not expand that PR into the full carousel, gallery, or program-wide accessibility initiative.

That keeps review risk low and avoids mixing a tactical patch with broader structural remediation.

## Recommended GitHub Project Setup

Create one GitHub Project v2 called: Accessibility Program - az_quickstart

Recommended custom fields:

1. Phase: Phase 0, Phase 1, Phase 2, Phase 3, Phase 4
2. Priority: P0, P1, P2
3. Area: Theme, Widget, Content, Tooling, Verification
4. User Impact: Broad, High-risk flow, Targeted, Preventive
5. Verification Type: Automated, Manual, Both
6. WCAG Area: Navigation, Forms, Dynamic updates, Media, Tables, Tooling
7. Release Target: Patch, Minor, Undecided

Recommended status columns:

1. Planned
2. Ready
3. In Progress
4. In Review
5. Needs Verification
6. Done

Recommended labels:

1. `a11y`
2. `a11y:global`
3. `a11y:component`
4. `a11y:content`
5. `a11y:tooling`
6. `a11y:verification`
7. `priority:p0`
8. `priority:p1`
9. `priority:p2`
10. `release:patch`
11. `release:minor`

## Program Sequence

The work should be opened and delivered in this order:

1. Open the umbrella program issue.
2. Open the Phase 0 spike and Phase 0 CI task.
3. Open Phase 1 issues for skip navigation, search naming, pager naming, and status message behavior.
4. Open Phase 2 issues for select menu, accordion, carousel, alphabetical listing, and date picker work.
5. Open Phase 3 issues for tables, Marketing Cloud templates, and background-image guidance.
6. Use Phase 4 as the release gate and close it only when verification is complete.

## Decision Points to Settle Before Coding Starts

These should be resolved first because they affect scope and acceptance criteria.

1. Confirm whether Marketing Cloud export routes are browser-facing pages or fragment-only outputs.
2. Confirm the representative page matrix the team will use for CI and manual verification.
3. Confirm whether accessibility regressions will block patch releases, minor releases, or both.
4. Confirm which issues can be delivered in patch releases versus which ones are disruptive enough to target a minor release.

## Assessment of User-Facing Issue Language

The current issue set is strong enough for Friday's planning discussion. The issue bodies already explain:

1. What the user is trying to do.
2. What is getting in the way today.
3. Why the problem matters in user terms.
4. What success should look like after the work is complete.

No blocking rewrite is required before discussion.

If the team wants even tighter product framing after Friday, the most valuable optional improvements would be:

1. Add a short `Who is affected` line to the highest-priority issues.
2. Add a short `How we will know this is better` line to the highest-priority issues.
3. Standardize a `Dependencies and release notes` section only on issues that are likely to cross patch versus minor release boundaries.

Those improvements would polish the backlog, but they are not necessary to make prioritization understandable now. The larger need was the program structure, hierarchy, and board visibility, and that is now in place.

Since the initial planning pass, the Friday decision queue has been tightened further by applying the `needs discussion` label only to the issues that still require explicit team decisions and by keeping those items set to `Release Target = Undecided` on the project board.

## Phase 0 - Accessibility Guardrails and Verification Setup

Phase goal: make accessibility work measurable, repeatable, and safe to ship.

### Issue 0.1 - Define the accessibility page matrix and verification policy

Suggested template: Spike

Suggested title: Define representative page coverage and verification policy for accessibility testing

What is the problem that we want to solve?

The audit identified broad accessibility risk, but the project does not yet have a shared agreement on which pages and user journeys must pass before release.

Why this matters to users

Users do not experience Quickstart as isolated widgets. They experience a full site. If the team only tests one component at a time, important accessibility problems can still ship in the real journeys that people use every day.

Conditions of satisfaction

- [ ] The representative page matrix is documented.
- [ ] The assistive-technology test matrix is documented.
- [ ] The minimum verification bar for patch and minor releases is documented.
- [ ] A recommended next step and estimated effort are documented.

Suggested labels: `a11y`, `a11y:verification`, `priority:p0`

### Issue 0.2 - Add an accessibility regression gate to CI

Suggested template: Task

Suggested title: Add accessibility regression checks to CI for representative Quickstart pages

Why this matters to users

Accessibility fixes only help users if they stay fixed. Without an automated regression gate, the same barriers can quietly return in later work and users end up losing access again.

Conditions of satisfaction

- [ ] CI runs accessibility checks against the approved representative pages.
- [ ] New serious or critical accessibility regressions fail the check.
- [ ] Results are easy for maintainers to review in pull requests.
- [ ] The workflow is documented for contributors.

Suggested labels: `a11y`, `a11y:tooling`, `priority:p0`, `release:patch`

## Phase 1 - Global User Experience Blockers

Phase goal: remove the issues that affect the largest share of users across most pages.

### Issue 1.1 - Make it easy to skip repeated navigation and reach the main content

Suggested template: User story

Suggested title: Make repeated page navigation skippable so users can reach content faster

User Story(s)

As a keyboard user, I'd like to skip repeated page navigation and move directly to the main content, in order to reach the part of the page I came for without unnecessary effort.

As a screen-reader user, I'd like the skip link to land on a reliable main content target, in order to understand that I have reached the primary content area.

Why this matters to users

When this does not work, people who use a keyboard or screen reader may need to move through the same header and navigation on every page before they can start reading. That makes ordinary browsing slower, more tiring, and less predictable.

Conditions of satisfaction

- [ ] The skip link points to one stable main-content target on standard pages.
- [ ] The main landmark can receive focus programmatically after skip navigation.
- [ ] Keyboard testing confirms the user lands in the primary content area.
- [ ] Screen-reader testing confirms the destination is announced clearly.

Suggested labels: `a11y`, `a11y:global`, `priority:p0`, `release:patch`

### Issue 1.2 - Give global search and pagination controls clear names

Suggested template: User story

Suggested title: Make search and pagination controls understandable at first use

User Story(s)

As a visitor using assistive technology, I'd like search and pagination controls to have clear accessible names, in order to understand what each control does without guessing.

Why this matters to users

Unnamed or poorly named controls create hesitation at core site entry points. Users can end up on a page where they can type a search but cannot tell what the submit button is, or they can reach a pagination landmark that is described with an internal token rather than a human-readable label.

Conditions of satisfaction

- [ ] The global search submit button has a clear accessible name.
- [ ] Pagination landmarks use human-readable labels.
- [ ] Search and pagination controls are understandable in screen-reader rotor and landmark views.
- [ ] Keyboard testing confirms the controls remain fully usable.

Suggested labels: `a11y`, `a11y:global`, `priority:p0`, `release:patch`

### Issue 1.3 - Make status messages helpful without being disruptive

Suggested template: User story

Suggested title: Make site messages announce the right information at the right urgency

User Story(s)

As a screen-reader user, I'd like routine confirmations to be announced politely and urgent failures to be announced urgently, in order to stay informed without being interrupted unnecessarily.

Why this matters to users

If every message is treated like an emergency, routine updates become noisy and stressful. Users can miss what actually matters because everything sounds equally urgent.

Conditions of satisfaction

- [ ] Routine informational and success messages are announced politely.
- [ ] Error and urgent warning messages are announced assertively only when appropriate.
- [ ] Duplicate or repeated announcements are avoided.
- [ ] Manual screen-reader verification confirms the behavior is clear and not noisy.

Suggested labels: `a11y`, `a11y:global`, `priority:p0`, `release:patch`

## Phase 2 - High-Risk Interactive Components

Phase goal: repair the custom widgets and interactive flows most likely to block access entirely.

### Issue 2.1 - Make select-menu navigation understandable and recoverable

Suggested template: User story

Suggested title: Make the select-menu block clear, valid, and easy to recover from when no option is chosen

User Story(s)

As a visitor using keyboard navigation or assistive technology, I'd like the select-menu block to clearly tell me its current state and any error I need to fix, in order to complete navigation confidently.

Why this matters to users

Right now the widget can tell a user that the control is disabled while still appearing usable, and it relies on a popover-style error that may not be announced correctly. That creates uncertainty and can make a simple navigation task feel broken.

Conditions of satisfaction

- [ ] The widget uses programmatically associated error messaging.
- [ ] Accessibility state matches the real interactive state.
- [ ] Focus styling is clearly visible.
- [ ] Screen-reader and keyboard testing confirm the widget is understandable and recoverable.

Suggested labels: `a11y`, `a11y:component`, `priority:p0`, `release:patch`

### Issue 2.2 - Make accordion sections announce the correct context

Suggested template: User story

Suggested title: Make accordion sections announce the right section name and relationship

User Story(s)

As a screen-reader user, I'd like each accordion panel to be correctly tied to its trigger, in order to know which section I opened and where I am on the page.

Why this matters to users

When section relationships are wired incorrectly, users can hear incomplete or misleading context when they move into accordion content. That makes scanning and comparing sections harder.

Conditions of satisfaction

- [ ] Each accordion trigger has a stable ID.
- [ ] Each panel is labelled by its corresponding trigger.
- [ ] The accordion parent relationship is correctly configured.
- [ ] Screen-reader testing confirms the open section is announced clearly.

Suggested labels: `a11y`, `a11y:component`, `priority:p1`, `release:patch`

### Issue 2.3 - Keep Slick carousel content reachable by keyboard

Suggested template: Bug report

Suggested title: Restore keyboard access to interactive content in Slick slides after slide changes

Problem/Motivation

The short-term Slick accessibility patch removes focusability from controls inside hidden slides, but it does not restore those controls when the slide becomes visible.

Why this matters to users

Users can reach a carousel and still be blocked from links or buttons that appear on later slides. From the user perspective, the content is visible but not actually reachable.

Proposed resolution

Store original focusability information and recalculate interactive descendants on initialization and slide changes so only truly hidden slides are removed from the tab order.

Expected behavior

When a slide becomes active, its interactive content becomes reachable by keyboard again.

Suggested labels: `a11y`, `a11y:component`, `priority:p0`, `release:patch`

### Issue 2.4 - Make gallery carousels understandable and predictable

Suggested template: User story

Suggested title: Make photo gallery carousels understandable to screen-reader and keyboard users

User Story(s)

As a screen-reader user, I'd like the gallery carousel to tell me what it is, which slide I am on, and how many slides exist, in order to navigate the gallery with confidence.

As a keyboard user, I'd like gallery controls and slide content to remain reachable and predictable, in order to browse the gallery without confusion.

Why this matters to users

Without a clear carousel name, slide numbering, and state updates, users can move through the gallery without knowing where they are or whether new content has appeared.

Conditions of satisfaction

- [ ] The carousel has a clear accessible name.
- [ ] Slides expose understandable position information.
- [ ] Active slide changes are communicated clearly.
- [ ] Keyboard interaction remains complete and predictable.

Suggested labels: `a11y`, `a11y:component`, `priority:p1`, `release:minor`

### Issue 2.5 - Make alphabetical listings respond clearly to search and A to Z navigation

Suggested template: User story

Suggested title: Make alphabetical listing filtering and jump navigation clear to assistive-technology users

User Story(s)

As a screen-reader user, I'd like filtering changes to be announced clearly, in order to know whether my search found results.

As a keyboard user, I'd like A to Z navigation to move me to the destination section, in order to continue reading from the place I chose.

Why this matters to users

Today the page changes visually, but users may not hear that results changed and may not land in the selected section after using the alphabet navigation. That makes the feature feel unreliable.

Conditions of satisfaction

- [ ] Result counts or no-results status are announced through a polite live region.
- [ ] A to Z navigation moves focus to the destination section or preserves reliable native navigation behavior.
- [ ] Keyboard-only testing confirms the jump behavior is usable.
- [ ] Screen-reader testing confirms filter changes are understandable.

Suggested labels: `a11y`, `a11y:component`, `priority:p0`, `release:patch`

### Issue 2.6 - Make the date picker work with standard assistive-technology patterns

Suggested template: User story

Suggested title: Make the calendar picker behave in a familiar way for keyboard and screen-reader users

User Story(s)

As a screen-reader user, I'd like the calendar picker to use familiar navigation patterns, in order to choose a date without fighting the control.

As a keyboard user, I'd like focus in the calendar picker to stay visible and easy to track, in order to complete date entry confidently.

Why this matters to users

The current picker uses aggressive announcement behavior and application-style semantics that can interrupt reading commands and create an unusually noisy experience. For many users, that turns a basic date selection task into a frustrating interaction.

Conditions of satisfaction

- [ ] The picker no longer relies on application mode.
- [ ] Routine navigation is not announced as urgent.
- [ ] Focus indication is clear in supported light themes.
- [ ] Manual NVDA and VoiceOver checks confirm the picker follows familiar patterns.

Suggested labels: `a11y`, `a11y:component`, `priority:p0`, `release:minor`

## Phase 3 - Content and Template Semantics

Phase goal: make structured content understandable and preserve meaning in author-driven outputs.

### Issue 3.1 - Make publication tables easier to understand row by row

Suggested template: User story

Suggested title: Make publication tables easier to understand cell by cell for screen-reader users

User Story(s)

As a screen-reader user, I'd like publication tables to expose clear row and column context, in order to understand each value without having to guess what it belongs to.

Why this matters to users

When a data table lacks a caption or proper row headers, users can hear cell content without enough context to know what they are looking at. That turns structured information into a memory exercise.

Conditions of satisfaction

- [ ] Publication tables include a caption or equivalent table name.
- [ ] Row and column header relationships are clear.
- [ ] Screen-reader table navigation confirms context is preserved cell by cell.

Suggested labels: `a11y`, `a11y:content`, `priority:p1`, `release:patch`

### Issue 3.2 - Make Marketing Cloud images preserve their meaning

Suggested template: User story

Suggested title: Preserve meaningful image descriptions in Marketing Cloud layouts

User Story(s)

As a person using assistive technology, I'd like meaningful Marketing Cloud images to expose the same meaning that sighted users get, in order to receive the full message instead of an incomplete version.

Why this matters to users

If a content image is always marked decorative, users who rely on alternative text can miss key information, branding context, or campaign meaning entirely.

Conditions of satisfaction

- [ ] Meaningful images can carry author-provided alternative text.
- [ ] Only explicitly decorative images are hidden from assistive technology.
- [ ] Documentation explains when empty alt text is appropriate.

Suggested labels: `a11y`, `a11y:content`, `priority:p0`, `release:patch`

### Issue 3.3 - Decide how browser-facing Marketing Cloud exports should behave

Suggested template: Spike

Suggested title: Decide whether Marketing Cloud export routes are fragments or full browser-facing pages

What is the problem that we want to solve?

The current export shell strips normal page-level semantics. That may be fine for fragments, but it is not fine for a browser-facing page.

Why this matters to users

If an export page is opened directly in a browser without proper page structure, assistive-technology users can lose navigation landmarks and page identity. That makes the content harder to review independently.

Conditions of satisfaction

- [ ] The intended route behavior is documented.
- [ ] A recommendation is made for either fragment-only use or full document semantics.
- [ ] Follow-up implementation work is estimated and linked.

Suggested labels: `a11y`, `a11y:content`, `priority:p1`

### Issue 3.4 - Audit background-image and decorative-image patterns

Suggested template: Task

Suggested title: Document and tighten guidance for meaningful versus decorative image patterns

Why this matters to users

If meaningful content is delivered through CSS backgrounds or mistakenly treated as decorative, some users will never receive that information at all.

Conditions of satisfaction

- [ ] High-risk background-image and decorative-image patterns are documented.
- [ ] Guidance exists for when images must be exposed as content.
- [ ] Follow-up implementation issues are opened for any confirmed problems.

Suggested labels: `a11y`, `a11y:content`, `priority:p1`

## Phase 4 - Verification and Release Readiness

Phase goal: prove that the repaired experience works on rendered pages and keep it working.

### Issue 4.1 - Verify the repaired experience on representative pages before release

Suggested template: Task

Suggested title: Run accessibility verification on representative pages before release

Why this matters to users

Users do not benefit from code that looks compliant in review but fails in the browser. This issue is the final quality check that confirms repaired behavior actually works where users encounter it.

Conditions of satisfaction

- [ ] Automated checks pass on the representative page matrix.
- [ ] Keyboard-only verification passes on the representative page matrix.
- [ ] NVDA and VoiceOver checks pass for the highest-risk flows.
- [ ] Any remaining risks are documented before release.

Suggested labels: `a11y`, `a11y:verification`, `priority:p0`

## Suggested First Two Iterations

### Iteration 1

Open and deliver these issues first:

1. Issue 0.1 - verification spike
2. Issue 0.2 - CI regression gate
3. Issue 1.1 - skip navigation
4. Issue 1.2 - search and pagination naming
5. Issue 1.3 - status message urgency
6. Issue 2.3 - Slick keyboard access defect

This set gives immediate user value and reduces the chance that new fixes regress.

### Iteration 2

Then open and deliver these issues:

1. Issue 2.1 - select menu
2. Issue 2.2 - accordion
3. Issue 2.5 - alphabetical listing
4. Issue 2.6 - date picker
5. Issue 3.2 - Marketing Cloud alt text
6. Issue 3.1 - publication table semantics

## Recommended Next GitHub Actions

1. Open the umbrella feature request issue and link [ACCESSIBILITY-REVIEW-PLAN.md](ACCESSIBILITY-REVIEW-PLAN.md) and this document.
2. Create the GitHub Project v2 board with the recommended fields.
3. Open Issue 0.1 and Issue 0.2 immediately.
4. Open the Phase 1 issues and mark them `priority:p0`.
5. Create a single issue for Slick keyboard access and link [PR #5528](https://github.com/az-digital/az_quickstart/pull/5528) to that issue only.
6. Do not begin broader implementation until the team agrees on the page matrix, release policy, and whether Marketing Cloud exports are browser-facing.

These steps have now been completed in GitHub, with the exception of future implementation work.