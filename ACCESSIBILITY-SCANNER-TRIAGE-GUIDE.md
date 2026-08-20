# Accessibility Scanner Triage Guide

## Purpose

This guide defines how maintainers should triage results from the GitHub Accessibility Scanner after it is introduced as the primary automated accessibility checker for az_quickstart.

The goal is to prevent two common failure modes:

1. Treating every scanner result as an implementation blocker without context.
2. Treating scanner failures as noise and ignoring new regressions.

## Scope

This guide applies to:

1. Pull request scanner runs.
2. Manual workflow dispatch runs.
3. Scheduled scanner runs on the approved representative page matrix.

It should be used with [ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md](ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md) and [ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md](ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md).

## First Triage Question

Always start here:

Is this a scanner infrastructure problem, an environment problem, an existing baseline finding, or a new accessibility regression?

Do not start by assuming every failure means the PR introduced a new code problem.

## Triage Categories

### Category 1 - Workflow Or Infrastructure Failure

Examples:

1. The scanner workflow did not complete.
2. The target URL could not be reached.
3. The token, cache, or workflow input is misconfigured.
4. The environment is down or unstable.

Action:

1. Do not convert this directly into a product accessibility finding.
2. Treat it as a workflow or environment issue.
3. Re-run only after the infrastructure problem is understood.
4. Record the interruption in the release sign-off checklist if it affects release readiness.

### Category 2 - Existing Baseline Debt

Examples:

1. A known serious or critical issue appears again after the baseline has already captured it.
2. The result matches previously accepted legacy debt on an approved representative page.

Action:

1. Confirm it is really part of the approved baseline.
2. Do not treat it as a new regression.
3. Keep it visible for backlog tracking and release awareness.
4. If it appears outside the approved baseline scope, reclassify it instead of ignoring it.

### Category 3 - New Regression

Examples:

1. A serious or critical finding appears on a representative page and was not present in the baseline.
2. A PR introduces a new issue in naming, landmarks, forms, focus, or status-message behavior.

Action:

1. Treat it as a blocking regression if it meets the agreed threshold.
2. Correlate it with the changed files, issue scope, or affected flow.
3. Fix it in the PR or explicitly remove the change from the release.
4. Do not close the triage until the regression is fixed or the release decision is changed.

### Category 4 - Manual Follow-Up Required

Examples:

1. The scanner finds an issue that needs human judgment to confirm impact.
2. The scanner cannot determine whether the automated result reflects actual user-facing breakage.
3. The flow depends on announcement quality, focus handoff, or multi-step interaction.

Action:

1. Keep the scanner result visible.
2. Trigger manual verification using [ACCESSIBILITY-MANUAL-VERIFICATION-PLAYBOOK.md](ACCESSIBILITY-MANUAL-VERIFICATION-PLAYBOOK.md).
3. Record the manual outcome before deciding whether to block, defer, or log risk.

### Category 5 - Candidate False Positive Or Non-Representative Finding

Examples:

1. The page did not render as expected.
2. The scanner hit a route or state that is outside the approved representative scope.
3. The issue cannot be reproduced manually.

Action:

1. Do not silently dismiss it.
2. Re-check the URL, environment, and scan profile.
3. Confirm whether the finding came from the correct representative page.
4. Record why it was treated as non-blocking if the finding is not actionable.

## Triage Sequence

Use the following sequence every time a scanner run produces actionable output.

1. Confirm the scan profile that ran: patch or minor.
2. Confirm the target environment that was scanned.
3. Confirm the representative URLs that were actually in scope.
4. Separate workflow or environment failures from accessibility findings.
5. Classify each accessibility finding as baseline debt, new regression, manual follow-up, or false-positive candidate.
6. Record the decision in the release sign-off checklist when the result affects release readiness.

## Blocking Rules

The initial recommended blocking threshold is:

1. Block on new critical findings.
2. Block on new serious findings.
3. Record moderate and minor findings without automatically blocking during the first rollout.

If the team changes the threshold later, update this guide and the Phase 0 planning docs together.

## What To Do When The Workflow Fails

If the workflow fails before producing valid scanner results:

1. Treat it as a workflow failure first.
2. Check whether the live URLs were available.
3. Check whether the profile or environment input was wrong.
4. Check whether the scanner token or permissions failed.
5. Re-run only after the likely cause is understood.

Do not convert a scanner infrastructure failure into a product accessibility issue unless the failure itself reflects a product problem such as the page being unavailable.

## What To Do When A Finding Blocks The PR

1. Confirm it is a new serious or critical finding.
2. Confirm it is on an approved representative page.
3. Correlate it to the code or configuration changes in scope.
4. Decide whether the finding should be fixed in the same PR, backed out from the PR, or moved out of the release.
5. If it cannot be fixed in time, record it explicitly as release risk and treat the PR as not ready under the current policy.

## When To Open Or Update A GitHub Issue

Open or update a GitHub issue when:

1. The finding maps to a real user-facing defect not already tracked.
2. The problem survives triage and manual confirmation.
3. The issue is part of accepted baseline debt that still needs ownership.
4. The same finding continues to recur and needs durable tracking.

Do not open a new issue when:

1. The finding is clearly a duplicate of an existing tracked issue.
2. The workflow failed before a valid result existed.
3. The result is a confirmed false positive or environment mismatch.

## When To Record A Release Risk Instead Of Opening A New Issue

Use the release sign-off risk log when:

1. The issue is already tracked elsewhere.
2. The finding is real but the release decision is still pending.
3. The environment prevented full verification.
4. The team needs explicit go or no-go acknowledgement from decision-makers.

## Baseline Handling Rules

During the first rollout, baseline debt must stay visible without being confused with new regressions.

Recommended handling:

1. Keep the baseline documented and stable.
2. Do not silently expand the baseline after a regression.
3. If a finding appears new because the URL set changed, update the representative inventory before changing the baseline.
4. Revisit baseline scope only through an explicit team decision.

## Manual Follow-Up Expectations

Use manual follow-up when a scanner result touches any of these areas:

1. Skip-link behavior.
2. Search and pager naming.
3. Status-message urgency and duplication.
4. Select-menu state, validation, or focus.
5. Accordion section relationships.
6. Alphabetical listing filter announcements or focus handoff.
7. Gallery carousel naming, slide state, or navigation.
8. Date picker announcement behavior or focus treatment.
9. Publication table context.
10. Marketing Cloud image meaning or export-route semantics.

## Suggested Triage Record Fields

For every finding that affects release readiness, record:

1. Scan profile.
2. Representative page ID or URL.
3. Scanner severity.
4. Triage category.
5. Release action.
6. Linked issue if one exists.
7. Manual follow-up requirement.

## Recommended Next Step

Use this guide as the operating companion to the scanner workflow plan. Once the GitHub Accessibility Scanner workflow exists, the first proof-of-concept run should be triaged with this document so the team can refine the rollout before the gate becomes required.