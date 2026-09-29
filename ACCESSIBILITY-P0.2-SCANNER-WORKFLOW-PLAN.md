# Phase 0.2 Plan: GitHub Accessibility Scanner Workflow

## Purpose

This document proposes the implementation plan for A11Y P0.2. It translates the Phase 0.1 verification policy into a concrete workflow model for automated accessibility checking, regression gating, and manual verification handoff inside the existing GitHub-based development process.

This document does not implement the workflow yet. It defines the operating model, the files that should be added, the rollout sequence, and the decisions that must be settled before the workflow becomes a required release gate.

## Relationship To Phase 0.1

Phase 0.1 answers what the project should verify.

Phase 0.2 answers how the project should run that verification in practice.

The Phase 0.1 proposal already recommends:

1. The GitHub Accessibility Scanner as the primary automated accessibility checker.
2. A representative page matrix based on real page types and user journeys.
3. A mixed verification model that combines automated checks, keyboard testing, screen-reader checks, and release-time user acceptance.
4. A required Windows screen-reader matrix that includes JAWS with both Chrome and Edge.

Phase 0.2 should implement that policy without changing it.

## Recommended Outcome

At close, A11Y P0.2 should give az_quickstart a working accessibility regression gate that:

1. Uses the GitHub Accessibility Scanner against approved live URLs.
2. Runs inside GitHub Actions and produces results that maintainers can review in GitHub.
3. Establishes a brownfield baseline before blocking on regressions.
4. Fails on new serious or critical accessibility regressions after the baseline is approved.
5. Hands off clearly into required manual keyboard and screen-reader verification for affected flows.

## Recommended Workflow Model

### Primary Automated Tool

Use `github/accessibility-scanner@v2` as the primary automated checker.

### Why This Model Fits The Repository

1. The repository already uses GitHub Actions for CI.
2. The current accessibility planning package is already organized around GitHub-native workflows, issues, and board tracking.
3. The scanner works on rendered live pages, which matches the Phase 0 requirement to verify runtime behavior rather than only source code.
4. The scanner gives maintainers a GitHub-visible way to track accessibility regressions over time.

### What The Workflow Should Not Try To Do Initially

1. It should not try to replace all manual verification.
2. It should not try to scan every possible route on day one.
3. It should not block every existing accessibility issue in a brownfield codebase before a baseline exists.
4. It should not automatically expand into authenticated or unstable preview routes until the environment model is proven.

## Required Inputs And Preconditions

The GitHub Accessibility Scanner requires live URLs. That means the first implementation decision is environmental, not YAML-level.

### Required Preconditions

1. A stable set of live URLs for the approved representative pages.
2. A GitHub token with the permissions required by the scanner workflow.
3. Agreement on whether the first rollout uses a shared staging environment, per-branch preview URLs, or a temporary fallback.
4. Agreement on the patch subset versus full minor-release page matrix.

### Recommended Environment Order

1. Preferred: pull-request preview URLs that reflect the branch under review.
2. Acceptable fallback: a stable staging environment used for proof of concept and baseline creation.
3. Deferred: authenticated scanning for flows that need CAS or similar login behavior.

### Recommended Secrets And Inputs

The exact names can change, but the plan should assume a small and explicit set of workflow inputs:

1. A token secret for the scanner workflow.
2. A base URL or URL manifest source for the scan target environment.
3. Optional authentication context inputs for future authenticated scanning.
4. A cache key strategy so the baseline and regression history remain stable across runs.

## Proposed Repository Artifacts

Phase 0.2 should be implemented as a small set of explicit files rather than a single workflow file that hard-codes policy.

The operational companion documents for this plan are:

1. [ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md](ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md)
2. [ACCESSIBILITY-MANUAL-VERIFICATION-PLAYBOOK.md](ACCESSIBILITY-MANUAL-VERIFICATION-PLAYBOOK.md)
3. [ACCESSIBILITY-SCANNER-TRIAGE-GUIDE.md](ACCESSIBILITY-SCANNER-TRIAGE-GUIDE.md)
4. [ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md](ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md)

### Workflow File

Recommended file:

`.github/workflows/accessibility-scanner.yml`

This workflow should own the GitHub Accessibility Scanner integration and the regression gate.

### Representative URL Manifests

Recommended files:

1. `.github/accessibility/pages.patch.txt`
2. `.github/accessibility/pages.minor.txt`

These files should list approved live URLs, one per line, so the policy can change without rewriting the workflow logic.

### Contributor And Maintainer Guidance

Recommended file:

`.github/accessibility/README.md`

This document should explain:

1. What the scanner is checking.
2. Which URLs are in scope.
3. What causes a blocking failure.
4. How maintainers should interpret scanner-created findings.
5. What manual verification still must happen after the automated run.

### Release Verification Checklist

The release-time operational checklist is documented in [ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md](ACCESSIBILITY-RELEASE-SIGNOFF-CHECKLIST.md). The workflow implementation should leave room for that checklist because manual sign-off is part of the release gate, not an optional side process.

## Proposed Trigger Model

The workflow should support more than one execution pattern because patch-release verification and broader release-readiness verification are not the same thing.

### Pull Request Trigger

Use pull requests for the smallest reliable automated gate.

Recommended behavior:

1. Run against the approved patch subset by default.
2. Surface new serious or critical regressions clearly.
3. Keep the result lightweight enough to be practical for normal review.

### Workflow Dispatch Trigger

Use manual dispatch for broader verification windows.

Recommended behavior:

1. Allow maintainers to choose the patch or minor profile.
2. Allow maintainers to target a known environment when needed.
3. Use this path for pre-release full-matrix verification.

### Scheduled Trigger

Use a scheduled run on the default branch or stable environment after the first rollout stabilizes.

Recommended behavior:

1. Re-scan the full representative matrix.
2. Catch regressions introduced outside ordinary PR timing.
3. Keep a continuous picture of accessibility drift over time.

## Proposed Page-Profile Model

### Patch Profile

The patch profile should be the minimum mandatory automated set.

It should include:

1. Front page or standard landing page.
2. Standard page shell.
3. Search or pager example.
4. Status-message example.
5. Any representative page type directly affected by the PR or release.

### Minor Profile

The minor profile should include the full representative page matrix approved in Phase 0.1.

That should cover:

1. Front page.
2. Standard content page.
3. Sidebar or alternate layout page.
4. Search results page.
5. Status-message example.
6. Select-menu page.
7. Alphabetical listing page.
8. Photo gallery carousel page.
9. Date picker page.
10. Publication table page.
11. Login or authentication-related page where practical.
12. Marketing Cloud export route.

## Proposed Baseline And Gating Strategy

This repository is a brownfield accessibility project. The workflow should therefore roll out in stages.

### Stage 1 - Proof Of Concept

1. Run the scanner against a small patch subset.
2. Confirm that the target environment, URLs, workflow permissions, and result visibility all behave as expected.
3. Do not make the check required yet.

### Stage 2 - Baseline Creation

1. Run the scanner against the approved initial subset.
2. Review the findings with maintainers.
3. Decide how existing findings will be treated as baseline debt.
4. Confirm that the cache strategy is stable enough for regression detection.

### Stage 3 - Required Regression Gate

1. Turn the workflow into a required PR check for the approved subset.
2. Fail on new serious or critical regressions.
3. Keep moderate and minor findings visible without blocking during the initial enforcement window.

### Stage 4 - Broader Coverage

1. Expand to the full minor-release matrix.
2. Add scheduled runs once the basic PR workflow is stable.
3. Add authenticated coverage later only if the environment model is ready.

## Proposed Finding And Review Policy

### Blocking Threshold

Recommended initial threshold:

1. Block on new serious findings.
2. Block on new critical findings.
3. Record but do not initially block on new moderate or minor findings.

### Result Visibility

The workflow should make results easy to interpret in pull requests.

Recommended behavior:

1. Post a clear pass or fail result in GitHub Actions.
2. Keep accessibility results distinct from lint and unit-test failures.
3. Link maintainers to the relevant scanner output or findings summary.

### Scanner-Created Issues

The GitHub Accessibility Scanner can create GitHub issues for findings. That can be useful, but it can also create noise if enabled too early.

Recommended position for the first rollout:

1. Allow the proof of concept and baseline decision to happen before turning on broad issue creation against the main repository.
2. If issue creation is enabled early, use a tightly scoped proof-of-concept subset to avoid flooding the backlog.
3. Do not rely on automatic Copilot assignment in the first rollout.
4. Revisit issue automation after the team is comfortable with the volume and triage pattern.

## Proposed Manual Verification Handoff

The automated workflow should end in a clear handoff, not in a false claim that automation is sufficient.

### Required Manual Checks After Automated Pass

1. Keyboard-only verification on affected representative pages.
2. Focus visibility review on affected flows.
3. NVDA with Firefox verification for affected flows.
4. NVDA with Chrome verification for affected flows.
5. JAWS with Chrome verification for affected Windows-facing interactive flows.
6. JAWS with Edge verification for affected Windows-facing interactive flows.
7. VoiceOver with Safari on macOS verification for affected flows.
8. VoiceOver with Safari on iOS when the change materially affects mobile interaction.

### Manual Sign-Off Rule

Phase 0.2 should define who records the manual acceptance outcome before release. That can be QA, maintainers, Campus Web Services, or another named owner, but the plan should not leave the sign-off role implicit.

## Proposed Workflow Skeleton

The implementation should stay simple. The exact YAML can change, but the workflow shape should look roughly like this:

```yaml
name: Accessibility Scanner

on:
  pull_request:
  workflow_dispatch:
    inputs:
      profile:
        description: Scan profile
        required: true
        default: patch
  schedule:
    - cron: '0 12 * * 1-5'

jobs:
  accessibility-scan:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Resolve scan profile
        run: |
          # Select patch or minor URL manifest

      - name: Run GitHub Accessibility Scanner
        uses: github/accessibility-scanner@v2
        with:
          urls: ${{ steps.resolve.outputs.urls }}
          repository: az-digital/az_quickstart
          token: ${{ secrets.A11Y_SCANNER_TOKEN }}
          cache_key: cached_results-azqs-${{ inputs.profile || 'patch' }}.json
          skip_copilot_assignment: true

      - name: Summarize results
        run: |
          # Make pass or fail status easy to review
```

This is only a structural outline. The final implementation should reflect the approved environment model and the approved URL manifests.

## Dependencies

Phase 0.2 depends on these decisions being settled:

1. Which environment will supply live URLs.
2. Which exact URLs belong in the patch profile.
3. Which exact URLs belong in the minor profile.
4. Whether scanner-created issues should be enabled in the main repository immediately or deferred until after the baseline review.
5. Who owns manual verification sign-off.

## Recommended Deliverables For A11Y P0.2

At close, A11Y P0.2 should produce:

1. One workflow file for the GitHub Accessibility Scanner.
2. One approved patch URL manifest.
3. One approved minor URL manifest.
4. One maintainer-facing readme for interpreting scanner results.
5. One documented baseline and gating policy.
6. One explicit manual-verification handoff rule.
7. One operational document set for URL inventory, manual verification, triage, and sign-off.

## Estimated Effort

1. Half a day to settle the environment and URL source model.
2. Half a day to build the first patch subset manifests.
3. Half a day to implement the proof-of-concept workflow.
4. Half a day to review baseline output and tune the rollout.

The practical estimate is one to two days for a proof-of-concept implementation and policy tuning pass, assuming the live environment question is already settled.

## Recommended Next Step

Approve the workflow model in this document, then implement a proof-of-concept GitHub Accessibility Scanner workflow against the patch subset first.

After that proof of concept, the team should review the output volume, confirm the baseline strategy, and only then turn the check into a required regression gate.