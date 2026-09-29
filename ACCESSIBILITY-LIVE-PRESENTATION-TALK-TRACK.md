# Accessibility Program Live Presentation Talk Track

## Purpose

Use this talk track for a 5 to 7 minute walkthrough of the accessibility program during the Friday meeting.

## Goal of the Walkthrough

Explain what was completed, how the work is now organized, what decisions still need to be made, and what the team should align on before implementation starts.

## 5 to 7 Minute Speaking Order

### Minute 0 to 1 - What Was Completed

Suggested script:

"Today’s work focused on organization, not implementation. I completed the accessibility review and turned it into a structured GitHub program with an umbrella issue, phase parent issues, delivery issues, and a project board. I also added preliminary Phase 0 working drafts for the verification policy and scanner rollout so Friday’s conversation can stay focused on decisions instead of starting from a blank page."

References:

- Project board: https://github.com/orgs/az-digital/projects/285
- Umbrella issue: https://github.com/az-digital/az_quickstart/issues/5533
- Phase 0 parent: https://github.com/az-digital/az_quickstart/issues/5532
- P0.1 verification policy: https://github.com/az-digital/az_quickstart/issues/5541
- P0.2 scanner gate: https://github.com/az-digital/az_quickstart/issues/5539

### Minute 1 to 2 - What Exists in GitHub Now

Suggested script:

"The work is now grouped into five phases: guardrails and verification, global blockers, high-risk interactive components, content and template semantics, and final verification and release readiness. That gives us a way to discuss order, release risk, and ownership clearly. The Phase 0 issues now also have preliminary working drafts behind them, which means we can review a concrete direction for verification without pretending those documents are final."

References:

- Phase 0: https://github.com/az-digital/az_quickstart/issues/5532
- Phase 1: https://github.com/az-digital/az_quickstart/issues/5535
- Phase 2: https://github.com/az-digital/az_quickstart/issues/5531
- Phase 3: https://github.com/az-digital/az_quickstart/issues/5536
- Phase 4: https://github.com/az-digital/az_quickstart/issues/5534

### Minute 2 to 3 - How the Work Is Prioritized

Suggested script:

"The issues are written to prioritize user friction and delivery risk. The current structure puts global blockers and high-risk interaction failures ahead of lower-visibility cleanup. That is the default recommendation unless the team sees a reason to reorder based on institutional risk or release commitments."

### Minute 3 to 4 - What Still Needs Decisions

Suggested script:

"I narrowed the Friday decision queue to the items that still need explicit team direction. Those are the umbrella program issue, the verification policy spike, the Marketing Cloud export decision, and two items that still sit between patch-safe and minor-release territory: the gallery carousel and the date picker. Supporting that, there is now a preliminary Phase 0 working set for #5541 and #5539 covering the scanner workflow, manual verification, triage, release sign-off, and representative URL inventory."

Decision queue:

- https://github.com/az-digital/az_quickstart/issues/5533
- https://github.com/az-digital/az_quickstart/issues/5541
- https://github.com/az-digital/az_quickstart/issues/5550
- https://github.com/az-digital/az_quickstart/issues/5544
- https://github.com/az-digital/az_quickstart/issues/5545

### Minute 4 to 5 - Recommended First Wave

Suggested script:

"Assuming the team confirms the release and verification decisions, the most sensible first wave is the verification policy, the CI regression gate, the skip link, search and pager naming, status message behavior, and the existing Slick accessibility defect. That gives us both immediate user value and a safer foundation for later work."

References:

- https://github.com/az-digital/az_quickstart/issues/5541
- https://github.com/az-digital/az_quickstart/issues/5539
- https://github.com/az-digital/az_quickstart/issues/5538
- https://github.com/az-digital/az_quickstart/issues/5540
- https://github.com/az-digital/az_quickstart/issues/5537
- https://github.com/az-digital/az_quickstart/issues/5514

### Minute 5 to 6 - What We Need From the Team Today

Suggested script:

"What I need from the team today is not implementation detail. I need agreement on scope, release direction, verification expectations, the first implementation wave, and whether the new Phase 0 drafts are the right working direction for #5541 and #5539. Once those are settled, we can update the board and move into implementation planning cleanly."

### Optional Minute 6 to 7 - Close

Suggested script:

"If we leave today with those decisions made, the next step is straightforward: update the issue metadata, confirm ownership, and begin implementation planning without having to reopen basic program-structure questions."

## Short Version If Time Is Tight

If you only have 2 to 3 minutes, say this:

"I completed the accessibility planning and GitHub setup so the work is now organized as a real program. We have a live board, an umbrella issue, phase parent issues, and child issues grouped by user impact and release risk. The main decisions still open are the verification policy, the Marketing Cloud export direction, and whether the gallery carousel and date picker stay minor-release work or can be narrowed further. If we agree on those decisions today, we can move into implementation planning with a much cleaner first wave."

Suggested add-on if there is another 20 seconds:

"We also now have preliminary Phase 0 working drafts tied to #5532, #5541, and #5539. Those drafts are not final policy, but they give the team something concrete to approve, adjust, or reject instead of discussing verification in the abstract."

## Presenter Notes

1. Stay at the level of program design, not implementation detail.
2. Use the board and umbrella issue as visual anchors.
3. If people dive into solution specifics too early, redirect to phase placement, release target, or verification requirement.