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
  - Security improvements
  - Critical institutional link changes
  - Critical brand changes
  - Add Experimental modules
  - Update Experimental modules
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
  - Experimental modules changed to Stable
  - Enable formally experimental modules by default or via database update.
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

### Beta Releases (beta)
Beta releases serve as a preview of the upcoming stable release and are meant for testing purposes. They provide developers and site maintainers an opportunity to test new features, APIs, and compatibility with existing configurations. Feedback from beta testing is crucial to address bugs, performance issues, or any other concerns before the stable release.

### Release Candidates (rc)
Release candidates are the final step before the stable release. These versions are considered feature complete, with all proposed features implemented and bugs addressed. The primary purpose of an RC is to ensure that there are no critical issues that were missed during beta testing. If no significant problems are identified in an RC, it can be promoted to a stable release. However, if issues are found, they are addressed, and a new RC is issued for testing.

## Experimental Features
When adding new features to Quickstart, particularly large or complicated
features, we usually encapsulate these in modules labeled as "experimental" to indicate to site
owners that these features are not fully tested or feature-complete and will
likely undergo significant or breaking changes. These features should only be
used by site owners that are actively tracking Quickstart development and are
prepared to resolve the consequences of breaking changes. 

Any modules with the experimental status bypass all of the minor release
controls so that ongoing development can be released quickly through patch
releases. This allows for fast development of new sites that are motivating the
creation of the feature.

### Best Practices for Using Experimental Modules
- **Ideal for**: Testing new features on staging or development sites before
  production deployment.
- **Risks**: May introduce breaking changes; not recommended for critical
  production sites without thorough testing.

### Classifying Modules as Experimental
To classify a module as experimental, define the following properties in the module's `.info.yml` file:
- `package`: `'The University of Arizona - Experimental'`
- `lifecycle`: `experimental`
- `lifecycle_link`: `'https://github.com/az-digital/az_quickstart/blob/main/RELEASES.md#experimental-features'`

### Transition Plan for Experimental Modules

To ensure the stability and reliability of Quickstart, the number of
Experimental modules should be minimized, and efforts should be made to convert
Experimental modules to Stable with each minor release.

#### Qualifications for Updating from Experimental to Stable
- **Feature Complete**: The module should have all planned features fully
  implemented.
- **Testing on Live Websites**: Ideally, the module should be installed on live
  websites for at least one complete minor release cycle with all known issues
  resolved.
- **Stable Parent Modules**: All parent modules of the experimental module
  should be in a stable state.

#### Transition Plan
- **Testing Phases**: The module should be fully tested in Probo.ci before being
  moved to Stable. The pre-release testing phase should incorporate complete
  testing on sample sites during the minor release cycle.
- **Documentation**: Update and finalize comprehensive documentation covering
  the module’s features, configurations, and any known issues.
- **Review and Approval**: Conduct a thorough review and seek approval from the
  development team and key stakeholders before transitioning the module to a
  stable release.
  
## Update hook numbering convention

In order to allow update hooks to be added to different minor release branches
independently, DB update hooks implementing `hook_update_N()` should adhere to
the following update numbering convention.

**hook_update_XYZZnn()**
- X = 1 or 2 digits for Drupal core major version compatibility (e.g. `9`)
- Y = 1 digit for Quickstart major version compatibility (e.g. `2`)
- ZZ = 2 digits for Quickstart minor version compatibility (e.g. `01`)
- nn = 2 digits for sequential counting, starting with 01 (e.g. `01`)

This will allow us to continue to add DB update hooks to a minor release branch
(e.g. `2.1.x`) as needed without updating a site's schema version to a number
that would prevent updates included in a later release branch (e.g. `2.2.x`) to
be applied when the site is upgraded.
