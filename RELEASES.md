# Arizona Quickstart Releases

The overarching goal of this release policy is to ensure developers and site maintainers know what to expect when using Arizona Quickstart. Arizona Quickstart follows [semantic versioning](https://semver.org/) and aligns with [Drupal's core release cycle](https://www.drupal.org/about/core/policies/core-release-cycles/release-process-overview) to allow predictable updates, especially for security and stability.

## Release Support Policy

Arizona Digital provides varying levels of support for three minor releases of Arizona Quickstart at a time. Different types of patch releases are issued as needed.

The latest minor version (e.g., 3.2) is supported with standard bug-fix patch releases. Support for the previous minor release (3.1) is limited to security patch releases only.

In addition, when a new major version is planned, we may designate a specific minor release of the existing major release as the **Long Term Support (LTS)** version. In these cases, the LTS version will continue receiving patch releases alongside the latest minor release for the new major version.

### Example Support Scenario

To illustrate how the LTS model works, consider the following example:

- **2.14** is designated as the final Quickstart 2.x minor release and a **Long Term Support (LTS)** release.
- When **3.0** is released, **2.14 (LTS)** and **3.0** will be supported concurrently.
- When **3.1** is released, **3.0** will receive only security support. **2.14 (LTS)** and **3.1** will be supported concurrently.
- When **3.2** is released, **3.0** will no longer be supported. **3.1** will receive only security support. **2.14 (LTS)** and **3.2** will be supported concurrently.

Once the version of Drupal core used by the LTS release (e.g., Drupal 10 for 2.14) reaches end-of-life, support for the LTS release also ends. We then resume our standard support policy of maintaining only the two most recent minor releases unless another LTS version is designated.

## Release Types

### Patch Releases (`x.y.Z`)

Patch releases are limited to changes that are backward-compatible, low-risk, and necessary to maintain stability, accessibility, and compliance. They will avoid visual changes when possible.

There are three types of Quickstart patch releases:
- Bug-fix patch releases
- Security patch releases
- Long Term Support (LTS) patch releases

|Type of change|Included in Bug-fix Patch Releases|Included in Security Patch Releases|Included in LTS Patch Releases|
|---|:---:|:---:|:---:|
|**Arizona Quickstart (install profile, custom modules, theme)**|
|- Bug fixes|✅||✅|
|- Accessibility improvements|✅||✅|
|- Performance improvements|✅||✅|
|- Security updates or improvements|✅|✅|✅|
|- Critical institutional link changes (may include database updates)|✅||✅|
|- Critical brand changes (may include database updates)|✅||✅|
|- Additions and updates to experimental modules|✅|||
||**Included in Bug-fix Patch Releases**|**Included in Security Patch Releases**|**Included in LTS Patch Releases**|
|**Third-party dependencies**|
|**Drupal core**|
|- Security updates|✅|✅|✅|
|- Patch-level version updates*|✅|✅|✅|
|- Removal of no-longer-needed patches|✅|✅|✅|
|- Minor version updates**|||✅|
|**Drupal contrib projects**|
|- Security updates|✅|✅|✅|
|- Patch-level and minor version updates*|✅|✅|✅|
|- Addition and removal of contrib modules or patches|✅||✅|
|- Removal of contrib modules that become unsupported|✅|✅|✅|
|**Other PHP or Javascript packages and libraries**|
|- Security updates|✅|✅|✅|
|- Patch-level and minor version updates*|✅|✅|✅|

_*Patch-level updates (non-security bug-fix updates) are typically included in Quickstart Bug-fix and LTS Patch releases. In the event of a security update for Drupal core, Drupal contrib projects or third party libraries, it may be necessary to include non-security patch-level updates (bug-fix updates) in Quickstart Security Patch releasees._

_**Minor version updates for Drupal core are included in Quickstart LTS (patch-level) releases to maintain alignment with Drupal's long-term support cycle._


> For LTS releases, critical institutional changes (e.g. required footer updates) may include database updates. These should be designed to minimize disruption to site owners and include opt-out paths where applicable.


### Minor Releases (`x.Y.z`)

Minor releases may include all patch-level changes, as well as:

#### Functionality and Frontend

- New features
- Changes to behavior that existing sites may rely on
- Changes to visual appearance, CSS, templates, or markup  
  - Including breaking changes that may require manual adjustment
- Experimental modules promoted to Stable
- Enabling stable modules by default

#### Dependencies

- Drupal core minor version updates
- Drupal contrib major version updates

#### APIs

- New internal APIs or API enhancements (backward-compatible preferred)

#### Code Quality

- Disruptive coding standard or architectural changes
- Risky or regression-prone fixes requiring manual testing or upgrade paths

### Major Releases (`X.y.z`)

- Drupal core major version updates
- Arizona Bootstrap major version updates

## Pre-Release Versions

### Alpha Releases (`alpha`)

Previews of upcoming minor releases for testing and feedback. Alpha versions are feature-complete but may contain known issues or incomplete documentation.

### Beta Releases (`beta`)

Beta releases are considered stable pending final verification. If no critical issues are found, the beta is promoted to a full release. Otherwise, a new beta may be issued.

## Experimental Features

To allow fast iteration and feedback, complex or in-progress features may be released as **Experimental Modules**. These may appear in patch releases and do not follow the full stability policy.

### Best Practices for Experimental Modules

- Only recommended for use on production websites if you are actively following the development cycle of the feature.
- Patch releases for non-LTS minor versions may include breaking changes related to experimental modules. These changes are not guaranteed to be backward-compatible and should be thoroughly tested in staging environments before deployment.

### Defining an Experimental Module

To designate a module as experimental, the following should be included in the `.info.yml` file:

- `package: 'The University of Arizona - Experimental'`
- `lifecycle: experimental`
- `lifecycle_link: 'https://github.com/az-digital/az_quickstart/blob/main/RELEASES.md#experimental-features'`

### Transition from Experimental to Stable

To ensure reliability, experimental modules should:

- Be feature-complete
- Be tested on live sites for at least one minor release cycle
- Have no unresolved critical issues
- Depend only on stable modules

#### Promotion Process

- Full testing in Tugboat
- Complete documentation
- Review and approval from the development team and stakeholders

## Update Hook Numbering

To support independent patch branches, update hooks must follow this convention:

**hook_update_XYZZnn()**
- X = 1 or 2 digits for Drupal core major version compatibility (e.g. 9)
- Y = 1 digit for Quickstart major version compatibility (e.g. 2)
- ZZ = 2 digits for Quickstart minor version compatibility (e.g. 01)
- nn = 2 digits for sequential counting, starting with 01 (e.g. 01)

This will allow us to continue to add DB update hooks to a minor release branch
(e.g. 2.1.x) as needed without updating a site's schema version to a number
that would prevent updates included in a later release branch (e.g. 2.2.x) to
be applied when the site is upgraded.
