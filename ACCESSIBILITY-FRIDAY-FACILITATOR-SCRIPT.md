# Friday Facilitator Script - az_quickstart Accessibility Program

## Purpose

This script is a facilitator aid for the Friday accessibility planning meeting. It is designed to keep the conversation focused on decisions, sequencing, and ownership rather than drifting into solution design.

Use this alongside:

- [ACCESSIBILITY-FRIDAY-MEETING-AGENDA.md](ACCESSIBILITY-FRIDAY-MEETING-AGENDA.md)
- [GitHub project board](https://github.com/orgs/az-digital/projects/285)
- [Umbrella program issue #5533](https://github.com/az-digital/az_quickstart/issues/5533)

## Facilitator Goal

At the end of the meeting, the team should be aligned on:

1. What the program includes.
2. What decisions are still open.
3. Which work should be considered first.
4. What must be true before implementation begins.

## Opening Script

Suggested opening:

"The purpose of today’s meeting is to review the accessibility program structure, confirm the decisions that affect scope and release planning, and agree on the first wave of work. We are not starting implementation today. We are making sure the work is organized correctly before implementation begins."

## Section-by-Section Talking Points

### 1. Program Overview

Open with:

- [Project board](https://github.com/orgs/az-digital/projects/285)
- [Umbrella issue #5533](https://github.com/az-digital/az_quickstart/issues/5533)

Talking points:

1. The work is now structured as a program, not a flat accessibility backlog.
2. The issues are grouped into phases so the team can prioritize by user impact and delivery risk.
3. The project board contains the umbrella issue, the phase parents, and the child issues needed for planning.

Prompt:

"Does the program structure itself feel right before we discuss individual items?"

Likely decision to capture:

- Confirm or adjust the five-phase structure.

### 2. Prioritization Lens

Talking points:

1. The current backlog is written to prioritize the user experience, not just technical cleanup.
2. Global barriers and high-risk interaction failures should generally come before lower-visibility semantic cleanup.
3. The board metadata now distinguishes likely patch work, minor work, and items that still need a release decision.

Prompt:

"Do we agree that the default order should be global blockers first, then high-risk components, then semantic and governance cleanup?"

Likely decision to capture:

- Confirm the prioritization model or note any institutional override.

### 3. Phase Review

Walk the team through these parent issues in order:

1. [#5532](https://github.com/az-digital/az_quickstart/issues/5532)
2. [#5535](https://github.com/az-digital/az_quickstart/issues/5535)
3. [#5531](https://github.com/az-digital/az_quickstart/issues/5531)
4. [#5536](https://github.com/az-digital/az_quickstart/issues/5536)
5. [#5534](https://github.com/az-digital/az_quickstart/issues/5534)

For each phase, ask:

1. "Is this phase scoped correctly?"
2. "Is anything missing?"
3. "Is anything here actually a later or earlier phase item?"
4. "Do the current release target assumptions feel right?"

Likely decisions to capture:

- Scope confirmation by phase
- Phase shifts if any issues are misplaced

### 4. Friday Decision Queue

Use these issues as the explicit decision queue:

- [#5533](https://github.com/az-digital/az_quickstart/issues/5533)
- [#5541](https://github.com/az-digital/az_quickstart/issues/5541)
- [#5550](https://github.com/az-digital/az_quickstart/issues/5550)
- [#5544](https://github.com/az-digital/az_quickstart/issues/5544)
- [#5545](https://github.com/az-digital/az_quickstart/issues/5545)

These issues now carry the `needs discussion` label and are intended to represent the narrowest useful Friday decision queue.

Talking points:

1. [#5541](https://github.com/az-digital/az_quickstart/issues/5541) determines the verification standard for everything else.
2. [#5550](https://github.com/az-digital/az_quickstart/issues/5550) is an architectural and product-boundary decision.
3. [#5544](https://github.com/az-digital/az_quickstart/issues/5544) and [#5545](https://github.com/az-digital/az_quickstart/issues/5545) are currently set to `Undecided` because they are the most likely to shift between a narrower patch approach and a fuller minor-release approach.

Prompts:

1. "Which of these truly need a team decision before work begins?"
2. "Which of these are clearly minor-release work, and which might still be narrowed into patch-safe work?"
3. "Which open questions require discovery versus a policy decision?"

Likely decisions to capture:

- Final Friday decision list
- Patch versus minor direction for the ambiguous items
- Any spikes or follow-up analysis needed

### 5. First Implementation Wave

Recommended candidates to confirm:

- [#5541](https://github.com/az-digital/az_quickstart/issues/5541)
- [#5539](https://github.com/az-digital/az_quickstart/issues/5539)
- [#5538](https://github.com/az-digital/az_quickstart/issues/5538)
- [#5540](https://github.com/az-digital/az_quickstart/issues/5540)
- [#5537](https://github.com/az-digital/az_quickstart/issues/5537)
- [#5514](https://github.com/az-digital/az_quickstart/issues/5514)

Prompt:

"If we had to define the first implementation wave today, which of these are truly ready once the decision items are settled?"

Likely decision to capture:

- Confirm the first wave or identify dependencies that still block it.

### 6. Ownership and Next Steps

Prompts:

1. "Who owns the Phase 0 policy and verification decisions?"
2. "Who owns keeping the board metadata current after Friday?"
3. "Which issues need updates immediately after the meeting?"
4. "What is the explicit signal that allows implementation planning to begin?"

Likely decisions to capture:

- Owners
- Immediate follow-up edits
- Next planning checkpoint

## Redirect Lines for Common Meeting Drift

If the conversation becomes too implementation-specific:

"Let’s capture that as an implementation note, but keep this meeting focused on whether the work belongs in scope, what phase it belongs in, and whether it is patch-safe or minor-release work."

If the conversation becomes too broad:

"Let’s bring it back to the current decision queue and make sure we leave with clear release and verification decisions."

If the conversation gets stuck on one issue:

"Let’s decide whether this needs a policy decision, a spike, or a delivery issue update, and then keep moving."

## Suggested Closing Script

"We now have a structured program, a defined decision queue, and a clearer sense of what belongs in the first wave. The next step is to reflect today’s decisions in the issues and board metadata, and only then move into implementation planning."