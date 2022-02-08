# Arizona Quickstart Releases
The overarching goal of this release policy is to make sure that developers know what to expect when they use Arizona Quickstart for their websites. Arizona Quickstart has adopted semantic versioning to allow site maintainers to keep websites up to date with security updates and other critical improvements without having to track the ongoing feature development of Arizona Quickstart.

## Release Overview
Arizona Digital will support two minor releases at a time with ongoing patch releases. This process will allow for flexibility of development while allowing site owners to have more stable websites with fewer ongoing updates.

_**Note:** When an Arizona Quickstart minor release contains a major or minor Drupal core release, it will be vital that existing sites are updated to the latest patch release of the previous minor version of Arizona Quickstart and ensure that Drupal database updates have been applied BEFORE updating to the new minor release._

### Patch Releases (x.y.Z)
Patch releases will be applied to the current development branch first and then backported to the currently supported minor release(s). These will be released as necessary and limited to:
- Arizona Quickstart (install profile, custom modules, custom theme)
  - Bug fixes
  - Accessibility improvements
  - Performance improvements
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

### Minor Releases (x.Y.z)
The following types of changes are allowed for minor releases in addition to those allowed for patch releases.
- Functionality and frontend
  - New features
  - Changes to behavior that existing sites might rely on
  - CSS, markup or template changes
    - Not critical brand changes (see patch release)
    - Breaking changes that require manual action to return content to previous visual state
    - Changes to visual appearance of a website
- Third-party code / dependencies
  - Drupal core
    - Major or minor level releases
  - Drupal contrib projects
    - Major level releases
- APIs
  - New internal APIs or API improvements with backwards compatibility
- Coding Standards
  - Risky or disruptive cleanups to comply with coding standards
- High-risk and disruptive changes
  - Changes requiring an upgrade path
  - Changes that risk regressions
  - Other disruptive bug fixes or high-risk changes

### Major Releases (X.y.z)
No decisions have been made about what will constitute the next major release yet.

<!-- The following may be revisited in the future -->
<!-- 
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
-->
