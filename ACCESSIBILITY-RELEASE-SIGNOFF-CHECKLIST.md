# Accessibility Release Sign-Off Checklist

## Purpose

This checklist is the release-time record for accessibility verification, sign-off, and unresolved risk. It is intended to be used after the automated scanner run and after manual verification has been completed for the approved release scope.

Use this document together with:

1. [ACCESSIBILITY-P0.1-VERIFICATION-PROPOSAL.md](ACCESSIBILITY-P0.1-VERIFICATION-PROPOSAL.md)
2. [ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md](ACCESSIBILITY-P0.2-SCANNER-WORKFLOW-PLAN.md)
3. [ACCESSIBILITY-MANUAL-VERIFICATION-PLAYBOOK.md](ACCESSIBILITY-MANUAL-VERIFICATION-PLAYBOOK.md)
4. [ACCESSIBILITY-SCANNER-TRIAGE-GUIDE.md](ACCESSIBILITY-SCANNER-TRIAGE-GUIDE.md)
5. [ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md](ACCESSIBILITY-REPRESENTATIVE-URL-INVENTORY.md)

## Release Metadata

- Release name:
- Release type: patch or minor
- Branch:
- Environment reviewed:
- Release owner:
- Verification owner:
- Date:

## Automated Verification Checklist

- [ ] The approved scan profile was used.
- [ ] The approved representative URLs were used.
- [ ] The GitHub Accessibility Scanner run completed successfully.
- [ ] New critical findings were reviewed.
- [ ] New serious findings were reviewed.
- [ ] Moderate and minor findings were reviewed for visibility even if they were not blocking.
- [ ] Workflow or environment failures were separated from true accessibility findings.

## Manual Verification Checklist

- [ ] Keyboard-only verification completed for all required representative pages in scope.
- [ ] Focus visibility review completed at 100 percent and 200 percent zoom.
- [ ] NVDA with Firefox checks completed for affected flows.
- [ ] NVDA with Chrome checks completed for affected flows.
- [ ] JAWS with Chrome checks completed for affected Windows-facing flows.
- [ ] JAWS with Edge checks completed for affected Windows-facing flows.
- [ ] VoiceOver with Safari on macOS checks completed for affected flows.
- [ ] VoiceOver with Safari on iOS checks completed when mobile interaction was in scope.
- [ ] TalkBack with Chrome checks completed when mobile forms, navigation, or media behavior changed.

## Flow Coverage Checklist

Mark only the flows that were in scope for the release.

- [ ] Global shell and skip-link behavior reviewed.
- [ ] Search and pager controls reviewed.
- [ ] Status-message behavior reviewed.
- [ ] Select-menu block reviewed.
- [ ] Accordion behavior reviewed.
- [ ] Alphabetical listing reviewed.
- [ ] Photo gallery carousel reviewed.
- [ ] Date picker reviewed.
- [ ] Publication tables reviewed.
- [ ] Marketing Cloud layouts reviewed.
- [ ] Marketing Cloud export route reviewed.
- [ ] Authentication-related flow reviewed.

## Sign-Off Decision

- Decision: go, conditional go, or no-go
- Summary of decision:
- Conditions that still must be met:

## Approvals

- Engineering owner:
- Verification owner:
- Release owner:
- Product or stakeholder acknowledgment if required:

## Risk Log

The following table records unresolved risks, blocked verification, or accepted debt that decision-makers reviewed before release.

| Risk ID | Representative page or flow | Severity | Description | Mitigation or follow-up | Owner | Release impact |
|---------|-----------------------------|----------|-------------|-------------------------|-------|----------------|
| R1 | | | | | | |
| R2 | | | | | | |
| R3 | | | | | | |

## Issues And Follow-Up Actions

- Related GitHub issues:
- Issues opened during verification:
- Issues updated during verification:
- Follow-up work required after release:

## Notes For Maintainers

1. A pass is not the absence of scanner output. A pass means the scanner output was triaged and the manual checks required by scope were completed.
2. A conditional go should always include a recorded risk and named owner.
3. A no-go should identify whether the blocker is a new regression, unresolved baseline risk, or missing verification.

## Recommended Next Step

Create a copy of this checklist for each release verification window and keep it linked from the release planning notes or board card so accessibility sign-off is visible rather than informal.