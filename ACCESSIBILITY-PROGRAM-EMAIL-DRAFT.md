# Email Draft - Accessibility Planning Work Completed Today

## Subject Line Options

1. Accessibility planning and GitHub program setup completed for az_quickstart
2. az_quickstart accessibility program is now organized for Friday review
3. WCAG 2.2 AA planning artifacts and GitHub dashboard are ready for Friday discussion

## Email Draft

Hello team,

I completed the accessibility planning and GitHub organization work for the az_quickstart WCAG 2.2 AA effort today.

The main outcome is that the work is now structured as a program rather than a flat list of defects. That means we have a clear umbrella issue, phase-based parent issues, delivery issues grouped by area, and a GitHub project board that can support Friday's discussion without starting implementation work yet.

What is now in place:

- A source-level accessibility review and remediation plan in GitHub:
  - https://github.com/az-digital/az_quickstart/blob/docs/accessibility-planning-2026-04-23/ACCESSIBILITY-REVIEW-PLAN.md
- A GitHub execution plan with issue structure and phase logic:
  - https://github.com/az-digital/az_quickstart/blob/docs/accessibility-planning-2026-04-23/ACCESSIBILITY-GITHUB-PROJECT-PLAN.md
- A live GitHub project board:
  - https://github.com/orgs/az-digital/projects/285
- An umbrella program issue:
  - https://github.com/az-digital/az_quickstart/issues/5533

The issue hierarchy is already created in GitHub and organized into these phases:

- Phase 0: Accessibility guardrails and verification setup
- Phase 1: Global user experience blockers
- Phase 2: High-risk interactive components
- Phase 3: Content and template semantics
- Phase 4: Verification and release readiness

The existing Slick accessibility issue https://github.com/az-digital/az_quickstart/issues/5514 was reused inside the program rather than duplicated, so the current carousel work remains connected to the broader effort.

I also prepared supporting documents for Friday:

- Meeting agenda:
  - https://github.com/az-digital/az_quickstart/blob/docs/accessibility-planning-2026-04-23/ACCESSIBILITY-FRIDAY-MEETING-AGENDA.md
- Recommended project views and manual setup guide:
  - https://github.com/az-digital/az_quickstart/blob/docs/accessibility-planning-2026-04-23/ACCESSIBILITY-PROJECT-VIEWS-HANDOFF.md
- Facilitator script:
  - https://github.com/az-digital/az_quickstart/blob/docs/accessibility-planning-2026-04-23/ACCESSIBILITY-FRIDAY-FACILITATOR-SCRIPT.md
- Consolidated send-out brief:
  - https://github.com/az-digital/az_quickstart/blob/docs/accessibility-planning-2026-04-23/ACCESSIBILITY-SENDOUT-BRIEF.md

One limitation to note: GitHub Projects v2 does not currently expose saved view creation through the available automation surface, so the board itself is created and populated, but the recommended saved views still need to be created manually in the GitHub UI. I documented the exact view definitions in the handoff note above.

From a content and product perspective, I do not think the issue descriptions need a major rewrite before Friday. They already explain the user goal, the current friction, why the problem matters, and what success looks like. If we want to polish them further after the meeting, the best optional improvement would be to add a short `Who is affected` and `How we will know this is better` line to the highest-priority issues.

For Friday, I recommend we focus the discussion on:

1. Confirming the release and verification decisions that affect scope.
2. Confirming which issues are safe patch candidates versus minor-release work.
3. Confirming the first implementation wave, without starting execution yet.

This message is ready to send as written. The board remains a program-level GitHub asset, and the supporting documents are published from the dedicated planning branch so the links are browser-accessible in GitHub.

Thanks,

Jeff