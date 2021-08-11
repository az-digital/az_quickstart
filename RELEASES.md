# Draft: Needs community review
This document is currently a work in progress, and will need more feedback from the community before it can be finalized.

The overarching goal of this release policy is to make sure that developers know what to expect when they use Quickstart 2.0 for their websites. Quickstart 2.0 has adopted semantic versioning to allow site maintainers to keep sites up to date with security updates and other critical improvements without having to track the ongoing feature development of Quickstart. 

*This policy explains what changes can be made to Quickstart following the release of 2.0.0.*
## Release Overview
Quickstart 2 introduces a six-month minor release cycle with releases targeted for March and September. Minor releases provide new improvements and functionality without breaking backward compatibility and will usually include the latest minor release version of Drupal.

Arizona Digital will support two minor releases at a time with ongoing patch releases.

This process will allow for flexibility of development while allowing site owners to have more stable websites with fewer ongoing updates.
### Patch releases (2.0.x)
Patch releases will be applied to the current development branch first and then backported to each of the two currently supported Minor releases. These will be released as necessary and limited to:
- AZ Quickstart (install profile, custom modules, custom theme)
  - Bug fixes
  - Accessibility improvements
  - Critical institutional link changes
  - Critical brand changes
- Third-party code / dependencies
  - Drupal core
    - Security updates
    - Patch level releases (non-security bug-fix releases)
    - Removal of patches that are no longer necessary
  - Drupal contrib projects
    - Security updates
    - Patch and minor level updates
    - Addition of new modules
    - Removal of patches that are no longer necessary
### Minor releases (2.x.0)
Minor releases will occur every six months and will be targeted for March and September.
The following types of changes are allowed for minor releases in addition to those allowed for patch releases.
- Functionality and frontend
  - New features
  - Changes to behavior that existing sites might rely on
  - CSS, markup or template changes
  - Improvements to accessibility
- Third-party code / dependencies
  - Drupal core
    - Minor level releases
  - Drupal contrib projects
    - Major level releases
- APIs
-- New APIs or API improvements with backwards compatibility
- Coding Standards
-- risky or disruptive cleanups to comply with coding standards
- High-risk and disruptive changes
-- changes requiring an upgrade path
-- changes that risk regressions
-- other disruptive bug fixes or high-risk changes
### Major releases (x.0.0)
No decisions have yet been made about what will constitute the next major release.
## Release Process
Each release will go through the same phases:
### Alpha phase (6 months)
The goal of the alpha phase is to work on new features, improvements and integrations to Quickstart without impacting live websites. 
Each time a new release enters beta in anticipation of launch, a new branch will be created for the next minor release with an alpha tag. This is where all active development will take place.
### Alpha - Release Candidate phase (2-4 weeks prior to release)
The goal of this phase is to prepare a polished and stable release:
- Remove any “work in progress” code that had been added as part of a larger feature that is not yet complete. 
- Complete a full accessibility review
- Complete integration testing
- Document on the website a complete list of all new features and changes. Highlight key new features.
### Stable Release (each March and September)
The goal of the stable release is to provide a version of Quickstart for The University of Arizona community to use that will be supported for a full year with security updates and bug fixes while minimizing any other disruptions.
### End of Life
Once two more releases of Quickstart are available (approximately one year from initial release), releases will no longer be supported. This means that they will not receive any further security updates or bug fixes. 
