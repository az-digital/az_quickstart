# Phase 0.1 Proposal: Representative Page Coverage and Verification Policy

## Purpose

This document proposes the delivery scope for A11Y P0.1. It turns the current accessibility review and issue set into a concrete recommendation for what az_quickstart should verify before release, how that verification should be performed, and what decisions still need explicit team approval.

This proposal is intended to support the existing planning issue for Phase 0.1 and to give the team a practical starting point for implementation planning in Phase 0.2.

## Recommended Direction

1. Use the GitHub Accessibility Scanner as the primary automated accessibility checker for representative live URLs.
2. Use a mixed verification model that combines automated scanning, keyboard-only checks, screen-reader checks, and release-time user acceptance review.
3. Start with a brownfield baseline so the team can inventory existing issues before enforcing a no-regressions gate for new serious or critical findings.
4. Require a smaller but mandatory verification set for patch releases and a broader full-matrix verification set for minor releases.
5. Until the team explicitly decides otherwise, treat Marketing Cloud export routes as browser-facing pages for verification purposes because that is the safer accessibility assumption.

## Why This Fits The Current Repository

The current review already identifies the highest-risk surfaces: skip navigation, search and pager naming, status-message urgency, the select-menu widget, accordion relationships, the alphabetical listing, gallery carousel behavior, the date picker, publication tables, and Marketing Cloud templates.

The repository already uses GitHub Actions, but it does not currently have an explicit accessibility gate in [package.json](package.json) or [.github/workflows/ci.yml](.github/workflows/ci.yml). That means Phase 0.1 should define a policy that fits the existing GitHub-based workflow and produces findings that maintainers can review in the same place they already review code.

The GitHub Accessibility Scanner fits that need well because it scans live URLs, creates GitHub-native findings, and gives the team a practical way to track regressions over time.

## Proposed Representative Page Matrix

The first approved page matrix should cover page types and flows rather than only isolated components. The team can fill in the exact URLs after agreeing on the canonical examples.

1. Front page or top-level landing page using the standard global shell.
2. Standard content page with the default main-content area and skip-link behavior.
3. Sidebar or block-heavy content page that exercises alternate layout regions.
4. Search results page that exposes the search submit control and pager naming.
5. A page with a reliable routine status-message trigger and an error-message trigger.
6. A page that uses the select-menu block.
7. An alphabetical listing page with search filtering and A to Z jump navigation.
8. A page with the photo gallery carousel.
9. An event page or equivalent page that exposes the date picker.
10. A publication table page or publication administration page that exercises table semantics.
11. A login or authentication-related page that is part of the normal user journey.
12. A Marketing Cloud export route.

## Proposed Automated Checking Model

### Primary Tool

Use the GitHub Accessibility Scanner as the primary automated checker in CI.

### Why This Tool Is Recommended

1. It is GitHub-native and fits the repository's existing workflow model.
2. It scans rendered live pages rather than only static source.
3. It creates reviewable findings in GitHub, which improves visibility for maintainers.
4. It supports regression tracking across repeated scans.

### Recommended Operating Model

1. Run an initial baseline scan against the approved representative URLs on the default branch or a stable staging environment.
2. Use that baseline to distinguish existing debt from new regressions.
3. Once the baseline is established, fail on new serious or critical findings on the approved representative pages.
4. Keep moderate and minor findings visible for triage, but do not block on them during initial rollout.

### Environment Requirement

The GitHub Accessibility Scanner requires live URLs.

The preferred target is a preview or staging environment that reflects the branch under review. If per-branch preview URLs are not yet available, the fallback is to run the scanner against a stable staging environment until preview infrastructure exists.

### Supplemental Checks

The GitHub Accessibility Scanner should be the primary automated tool, not the only form of verification. Where the scanner cannot reach an authenticated flow, a preview-only route, or a hard-to-reproduce interaction, the team should supplement it with targeted manual testing and, if needed later in Phase 0.2, a narrower local automation layer.

## Proposed Manual Verification And User Acceptance Model

Automation will not cover announcement quality, real keyboard experience, or browser and assistive-technology differences. Phase 0.1 should therefore define a manual verification and user acceptance matrix alongside the automated gate.

### Keyboard And Visual Checks

1. Keyboard-only navigation on every representative page in scope for the release.
2. Focus visibility review at 100 percent and 200 percent zoom.
3. Verification that skip links, landmarks, dialogs, live regions, and custom widgets behave predictably without a mouse.

### Required Screen Reader And Browser Matrix

The required manual matrix for release verification should be:

1. NVDA with Firefox on Windows.
2. NVDA with Chrome on Windows.
3. JAWS with Chrome on Windows.
4. JAWS with Edge on Windows.
5. VoiceOver with Safari on macOS.
6. VoiceOver with Safari on iOS.

### Conditional Mobile Coverage

TalkBack with Chrome on Android should be added when the release materially changes mobile navigation, mobile forms, or mobile media interactions.

### Why JAWS On Both Chrome And Edge Should Be Included

Chrome and Edge can expose small but meaningful differences in how enterprise Windows users experience the same page with JAWS. Requiring both combinations makes the acceptance model more resilient and reduces the chance that a fix looks correct in one Windows browser but degrades in another common deployment path.

## Proposed Verification Bar By Release Type

### Patch Releases

Patch releases should use a smaller but mandatory verification set.

1. Scan the patch representative subset with the GitHub Accessibility Scanner.
2. Include at minimum the front page, a standard page shell, a search or pager page, a status-message example, and every page type directly affected by the release.
3. Run keyboard-only checks on every changed flow.
4. Run manual screen-reader checks on affected high-risk flows, including JAWS with Chrome and JAWS with Edge when the release changes Windows-facing interactive behavior.
5. Block the release on new serious or critical findings in the approved patch subset.

### Minor Releases

Minor releases should use the full representative page matrix.

1. Scan the full representative matrix with the GitHub Accessibility Scanner.
2. Run keyboard-only checks across the full matrix.
3. Complete the full required screen-reader and browser matrix for high-risk flows.
4. Produce a short release-readiness summary of unresolved known risks before release approval.

## Proposed User Acceptance Outcome

Phase 0.1 should treat user acceptance as a release decision input rather than a final afterthought.

Before a release is considered ready, the team should be able to answer these questions clearly:

1. Did the GitHub Accessibility Scanner find any new serious or critical regressions on the approved representative pages?
2. Did keyboard-only testing confirm that the changed flows remain usable?
3. Did NVDA, JAWS, and VoiceOver checks confirm that names, roles, states, focus handling, and announcements remain understandable?
4. Are any remaining known risks documented clearly enough for maintainers to make a release decision?

## Decisions Needed To Close Phase 0.1

1. Which deployed environment will provide the live URLs for the GitHub Accessibility Scanner?
2. Which exact page instances will become the approved representative URLs for each page type in the matrix?
3. Should the blocking threshold be new serious and critical findings only, or should the team include moderate findings later after the baseline stabilizes?
4. Which role owns final manual verification sign-off before release?

## Recommended Phase 0.1 Deliverables

At close, A11Y P0.1 should produce:

1. An approved representative page matrix.
2. An approved browser and assistive-technology matrix.
3. An approved verification bar for patch releases and minor releases.
4. An explicit decision to use the GitHub Accessibility Scanner as the primary automated checking tool.
5. A written handoff into A11Y P0.2 so the CI implementation work starts with a settled policy.

## Estimated Effort And Recommended Next Step

1. Half a day to confirm page types, owners, and candidate URLs.
2. Half a day to confirm the required manual matrix and release thresholds.
3. One to two days to translate the approved policy into a working A11Y P0.2 implementation plan.

The recommended next step is to approve this policy direction, then implement a proof of concept GitHub Accessibility Scanner workflow against a small representative subset before expanding it to the full matrix. The concrete implementation model is documented in [ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md](ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md).