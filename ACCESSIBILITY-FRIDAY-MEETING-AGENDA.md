# Friday Meeting Agenda - az_quickstart Accessibility Program

## Purpose

This meeting is intended to align the team on the accessibility program structure, confirm the release and verification decisions that affect scope, and agree on what work should move forward first.

This is a planning and prioritization meeting. It is not an implementation kickoff.

## Pre-Read

Review these materials before the meeting if possible:

- [ACCESSIBILITY-REVIEW-PLAN.md](ACCESSIBILITY-REVIEW-PLAN.md)
- [ACCESSIBILITY-GITHUB-PROJECT-PLAN.md](ACCESSIBILITY-GITHUB-PROJECT-PLAN.md)
- [ACCESSIBILITY-PROJECT-VIEWS-HANDOFF.md](ACCESSIBILITY-PROJECT-VIEWS-HANDOFF.md)
- [GitHub project board](https://github.com/orgs/az-digital/projects/285)
- [Umbrella program issue #5533](https://github.com/az-digital/az_quickstart/issues/5533)

## Meeting Goal

Leave the meeting with:

1. Agreement on the scope and structure of the accessibility program.
2. Agreement on the release and verification decisions that affect prioritization.
3. Agreement on the first implementation wave, without starting work yet.

## Proposed Agenda

### 1. Program Overview - 10 minutes

Start here:

- [Project board](https://github.com/orgs/az-digital/projects/285)
- [Umbrella program issue #5533](https://github.com/az-digital/az_quickstart/issues/5533)

Discussion points:

1. What was completed today.
2. Why the work is now organized as a phased program rather than a single defect list.
3. How the board, parent issues, and child issues should be used going forward.

### 2. User Impact and Prioritization Lens - 10 minutes

Discussion points:

1. Confirm that the team wants to prioritize the work by user friction and risk to task completion.
2. Confirm that global blockers and high-risk components should come before lower-visibility semantic cleanup.
3. Confirm whether any institution-specific risks should change the order.

### 3. Phase-by-Phase Review - 20 minutes

Review in this order:

1. [Phase 0 - #5532](https://github.com/az-digital/az_quickstart/issues/5532)
2. [Phase 1 - #5535](https://github.com/az-digital/az_quickstart/issues/5535)
3. [Phase 2 - #5531](https://github.com/az-digital/az_quickstart/issues/5531)
4. [Phase 3 - #5536](https://github.com/az-digital/az_quickstart/issues/5536)
5. [Phase 4 - #5534](https://github.com/az-digital/az_quickstart/issues/5534)

For each phase, answer:

1. Does the scope feel correct?
2. Is anything missing?
3. Does anything belong in a different phase?
4. Is the release target direction reasonable?

### 4. Decision Checkpoints - 15 minutes

These decisions should be made before implementation starts:

1. Are Marketing Cloud export routes browser-facing pages or fragment-only outputs?
2. What is the representative page matrix for CI and manual verification?
3. Should accessibility regressions block patch releases, minor releases, or both?
4. Which issues clearly belong in patch releases, and which should remain minor-release candidates?

Recommended starting positions for discussion:

1. Treat Marketing Cloud export routes as browser-facing until the team explicitly confirms fragment-only usage, because that is the safer accessibility assumption.
2. Start with a targeted representative page matrix built around common journeys and high-risk components rather than trying to certify the whole site at once.
3. Block new serious or critical accessibility regressions on the approved representative pages for both patch and minor releases, so the verification bar is real but still scoped.
4. Keep the first implementation wave focused on immediate, noticeable user-facing change on common pages, and leave broader structural rewrites in later phases unless the scope can be narrowed safely.

### 5. Friday Triage Items - 15 minutes

Review the items most likely to need explicit team decisions:

- [#5533](https://github.com/az-digital/az_quickstart/issues/5533)
- [#5541](https://github.com/az-digital/az_quickstart/issues/5541)
- [#5550](https://github.com/az-digital/az_quickstart/issues/5550)
- [#5539](https://github.com/az-digital/az_quickstart/issues/5539)
- [#5545](https://github.com/az-digital/az_quickstart/issues/5545)
- [#5544](https://github.com/az-digital/az_quickstart/issues/5544)

Suggested focus:

1. Which items are safe patch-release candidates.
2. Which items should remain minor-release work.
3. Which items need more discovery before they are implementation-ready.

### 6. Confirm First Implementation Wave - 10 minutes

Recommended immediate-impact first wave for discussion:

1. [#5541](https://github.com/az-digital/az_quickstart/issues/5541)
2. [#5539](https://github.com/az-digital/az_quickstart/issues/5539)
3. [#5538](https://github.com/az-digital/az_quickstart/issues/5538)
4. [#5540](https://github.com/az-digital/az_quickstart/issues/5540)
5. [#5537](https://github.com/az-digital/az_quickstart/issues/5537)
6. [#5514](https://github.com/az-digital/az_quickstart/issues/5514)

The goal is to confirm a well-targeted first wave that creates meaningful and noticeable improvement quickly, not to start coding in the meeting.

### 7. Close With Assignments and Next Steps - 10 minutes

Confirm:

1. Who owns Phase 0 decisions.
2. Who will maintain the board and issue metadata.
3. Which issues need follow-up edits after the meeting.
4. What constitutes approval to begin implementation planning.

## Suggested Outcome Notes Template

Use this structure to capture decisions live during the meeting:

### Confirmed

- Scope decisions:
- Release decisions:
- Verification decisions:
- First-wave decisions:

### Needs Follow-Up

- Open questions:
- Missing data:
- Metadata or issue cleanup:

### Deferred

- Items intentionally postponed:

## Facilitator Notes

1. Keep the conversation focused on sequence and decision quality, not solution design.
2. Use the project board and the umbrella issue as the anchor for discussion.
3. If the team starts drifting into implementation detail, pull the conversation back to scope, release, and verification decisions.