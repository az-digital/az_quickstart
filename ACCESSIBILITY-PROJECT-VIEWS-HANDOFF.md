# Accessibility Project Views Handoff

## Purpose

This document defines the recommended saved views for the GitHub project board used by the az_quickstart accessibility program.

Project board:

- [Accessibility Program - az_quickstart](https://github.com/orgs/az-digital/projects/285)

## Important Limitation

GitHub Projects v2 currently exposes saved views as readable through the available CLI and GraphQL tooling, but not writable. That means these views could not be created programmatically through the available agent tools.

The board and issue metadata are in place. The views below should be created manually in the GitHub project UI.

## Recommended Views

### 1. Phase View

Purpose:

Use this as the main planning view for Friday. It gives a full-program view grouped by phase.

Recommended configuration:

- Layout: Table or Board
- Group by: `Phase`
- Sort by: `Priority`, then `Status`
- Visible fields:
  - Title
  - Phase
  - Priority
  - User Impact
  - Release Target
  - Status

Recommended use:

Use this view when discussing whether the work is sequenced correctly.

### 2. Patch Candidates

Purpose:

Show only issues that are currently positioned as likely patch-release work.

Recommended configuration:

- Layout: Table
- Filter: `Release Target` is `Patch`
- Sort by: `Priority`, then `Phase`
- Visible fields:
  - Title
  - Phase
  - Priority
  - User Impact
  - Verification Type
  - Status

Recommended use:

Use this view when deciding what is low-risk enough to include in short-term delivery.

### 3. Needs Decision

Purpose:

Show the issues that need explicit discussion before implementation should begin.

Recommended configuration:

- Layout: Table
- Filter recommendation:
  - `Release Target` is `Undecided`
  - Or issue label contains `needs discussion`
  - Or issue is a spike
- Sort by: `Priority`, then `Phase`
- Visible fields:
  - Title
  - Phase
  - Priority
  - Release Target
  - Status

Recommended use:

Use this view for Friday triage and scope-confirmation discussions.

Note:

This view will work better if the team consistently uses `needs discussion` on issues that require an explicit decision.

### 4. Verification Queue

Purpose:

Show the items that require planned validation and release-readiness checks.

Recommended configuration:

- Layout: Table
- Preferred filter:
  - `Status` is `Needs Verification`
  - Optional secondary filter: `Verification Type` is `Manual` or `Both`
- Sort by: `Phase`, then `Priority`
- Visible fields:
  - Title
  - Phase
  - Verification Type
  - Priority
  - Status

Recommended use:

Use this view near release planning or whenever the team is preparing manual validation.

Note:

This view becomes most useful once items begin moving into `Needs Verification`.

### 5. Leadership Summary

Purpose:

Provide a concise view for non-engineering stakeholders who want status and impact rather than implementation detail.

Recommended configuration:

- Layout: Table
- Filter: none required
- Sort by: `Priority`, then `Phase`
- Visible fields:
  - Title
  - Phase
  - Priority
  - User Impact
  - Release Target
  - Status

Recommended use:

Use this view when summarizing program status, scope, and release direction for leadership or product stakeholders.

## Suggested Metadata Hygiene Before and After Friday

To make the views more useful, consider the following light governance steps:

1. Use `needs discussion` only on issues that truly require Friday decisions.
2. Move issues into `Needs Verification` only when they are actually ready for manual or release-gate validation.
3. Keep `Release Target` current so the Patch Candidates view stays trustworthy.
4. Keep `User Impact` and `Priority` aligned so the Leadership Summary view remains useful.

## Current Friday Decision Queue

The following issues are the current recommended Friday discussion queue and now carry the `needs discussion` label:

1. [#5533 - A11Y Program: WCAG 2.2 AA accessibility remediation for az_quickstart](https://github.com/az-digital/az_quickstart/issues/5533)
2. [#5541 - A11Y P0.1: Define representative page coverage and verification policy for accessibility testing](https://github.com/az-digital/az_quickstart/issues/5541)
3. [#5550 - A11Y P3.3: Decide whether Marketing Cloud export routes are fragments or full browser-facing pages](https://github.com/az-digital/az_quickstart/issues/5550)
4. [#5544 - A11Y P2.4: Make photo gallery carousels understandable to screen-reader and keyboard users](https://github.com/az-digital/az_quickstart/issues/5544)
5. [#5545 - A11Y P2.6: Make the calendar picker behave in a familiar way for keyboard and screen-reader users](https://github.com/az-digital/az_quickstart/issues/5545)

These items are also set to `Release Target = Undecided` on the project board so they stand out as genuine decision items rather than implied implementation commitments.

## Suggested Friday Core View Set

If the team only creates a few saved views before Friday, create these first:

1. Phase View
2. Needs Decision
3. Patch Candidates

Those three views will support the most important planning conversations.