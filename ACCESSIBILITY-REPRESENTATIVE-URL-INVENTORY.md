# Representative URL Inventory And Environment Map

## Purpose

This document turns the representative page matrix into an operational inventory for scanner setup, manual verification, and release planning.

Because the planning package is documentation-only at this stage, some entries below intentionally use page types, route patterns, or selection rules rather than final live URLs. The goal is to make the inventory actionable now and easy to finalize once the team confirms the scan environment.

## How To Use This Document

1. Select the approved environment for the scan run.
2. Replace each placeholder route or selection rule with a final approved live URL.
3. Mark whether the page belongs in the patch profile, minor profile, or both.
4. Use the final URL set to populate the scanner manifest files described in [ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md](ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md).

## Environment Map

The table below describes the intended use of each environment in the rollout plan.

| Environment | Intended use | Suitable for scanner | Branch fidelity | Authentication support | Current planning status |
|-------------|--------------|----------------------|-----------------|------------------------|-------------------------|
| Pull request preview environment | Preferred target for PR validation | Yes | High | Depends on preview platform | Preferred but not yet confirmed |
| Shared staging environment | Fallback target for proof of concept and baseline creation | Yes | Medium | Depends on staging access model | Acceptable fallback |
| Default-branch baseline environment | Stable target for scheduled scans | Yes | Low for PRs, high for default branch drift tracking | Depends on deployment model | Likely useful after rollout |
| Local development environment | Developer reproduction and spot checks | No for the primary scanner plan | High for local code, low for shared verification | Varies | Not the primary scanner target |

## Representative Inventory

The table below lists the recommended page types and the role each one plays in the verification model.

| ID | Page type | Candidate route pattern or selection rule | Primary coverage reason | Patch profile | Minor profile | Authentication needed | Status |
|----|-----------|-------------------------------------------|-------------------------|---------------|---------------|-----------------------|--------|
| R1 | Front page | `/` or the primary site landing page | Global shell, navigation, skip-link behavior | Yes | Yes | No | Needs final live URL |
| R2 | Standard content page | Any published content page using the default main-content shell | Main landmark behavior, standard content flow | Yes | Yes | No | Needs named example |
| R3 | Sidebar or alternate layout page | Any published page with sidebar or alternate layout regions | Layout-region variation and focus continuity | No | Yes | No | Needs named example |
| R4 | Search results page | Site search results page with pager controls | Search submit naming and pager semantics | Yes | Yes | No | Needs named example |
| R5 | Status-message example | Any page that can reliably produce success and error feedback | Live-region urgency and message duplication | Yes | Yes | No | Needs agreed test page |
| R6 | Select-menu page | Any page that embeds the az_select_menu block | Custom widget state, validation, and focus | No unless directly affected | Yes | No | Needs named example |
| R7 | Alphabetical listing page | Page using the Quickstart Alphabetical Listing view | Filter announcements and A to Z focus handoff | No unless directly affected | Yes | No | Needs named example |
| R8 | Photo gallery carousel page | Page using the photo gallery carousel paragraph type | Carousel naming, slide state, keyboard reachability | No unless directly affected | Yes | No | Needs named example |
| R9 | Date picker page | Event page or equivalent page that exposes the calendar picker | Date picker semantics, announcement behavior, focus visibility | No unless directly affected | Yes | No | Needs named example |
| R10 | Publication table page | `/publications` or an equivalent publication table page | Table caption, row and column context | No unless directly affected | Yes | No | Candidate route exists but needs confirmation |
| R11 | Authentication-related page | Approved login or authentication-related flow | Keyboard access, field naming, error recovery | No unless directly affected | Yes where practical | Likely yes | Needs environment decision |
| R12 | Marketing Cloud export route | Approved browser-facing or candidate export route | Export semantics and page-level context | No unless directly affected | Yes | Possibly no | Needs explicit route decision |

## Patch Profile Recommendation

The default patch profile should include:

1. R1 - Front page.
2. R2 - Standard content page.
3. R4 - Search results page.
4. R5 - Status-message example.
5. Every additional representative page directly affected by the release.

## Minor-Release Profile Recommendation

The minor-release profile should include the full approved set of representative pages R1 through R12, except where the team explicitly documents that an item is not currently testable in the chosen environment.

## Page Selection Rules

Use these rules when choosing the final live URL for each row:

1. Prefer stable, published examples over temporary content.
2. Prefer examples that reliably reproduce the component or behavior under review.
3. Avoid URLs that depend on one-time editorial state or expiring content when a more stable option exists.
4. If multiple pages expose the same behavior, prefer the one with the simplest surrounding layout unless the surrounding layout is part of the risk.

## Open Decisions

The following decisions still need explicit confirmation before the inventory becomes final:

1. Which environment will provide the live URLs for scanner runs.
2. Which exact page instance should serve as the status-message example.
3. Which authentication-related flow is safe and practical to use as a representative page.
4. Whether the Marketing Cloud export route should be treated as browser-facing for full representative coverage.

## Recommended Next Step

Use this document to finalize the first approved URL set, then convert the approved URLs into the patch and minor manifest files described in the Phase 0.2 workflow plan.