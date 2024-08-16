# How to contribute

First off, thanks for taking the time to contribute to AZ Quickstart!

AZ Quickstart is created by [Arizona Digital](https://digital.arizona.edu/), a
team of web-focused volunteers that meet weekly to create projects like [Arizona Bootstrap](https://digital.arizona.edu/arizona-bootstrap) and
[Arizona Quickstart](https://quickstart.arizona.edu/).

## Things you'll need to get started

  * A [GitHub account](https://github.com/join).
  * [Slack](https://uarizona.slack.com) is our main source of communications.
    * Use the `#azdigital-quickstart` channel for questions/comments related to this
      project.
    * Use the `#azdigital-meetings` channel to ask questions or get updates related
      to Arizona Digital meetings and workshops.
    * Use the `#azdigital-support` channel to ask general questions related to
      Arizona Digital and get support for Arizona Digital products.
  * A basic understanding of [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git).
  * Local containerized (Docker) dev environment tool either lando or ddev
    * [Lando](https://docs.lando.dev/basics/installation.html)
    * [DDev](https://www.ddev.com/get-started/)
  * An IDE with the capability to attach to a remote codeserver or docker
    container. [Visual Studio Code](https://code.visualstudio.com) allows for this.
    * Generic [instructions for connecting to a docker container with Visual
    Studio Code]( https://code.visualstudio.com/docs/remote/containers#_attached-container-config-reference)
    or see [instructions below](#user-content-visual-studio-code-integration).

## Creating Issues and Pull Requests

[Create an issue](https://docs.github.com/en/issues/tracking-your-work-with-issues/creating-an-issue) in the associated repository to a request a change. That issue can then be added to one of our [milestones](https://github.com/az-digital/az_quickstart/milestones) to plan work for a future release.

Milestones are specific to individual repositories and cannot be shared across different repositories within the organization. For the az_quickstart repository, we create unique milestones for each release. To include an issue from a different repository in a milestone, link it to a corresponding issue in az_quickstart. For example, a hypothetical issue titled "AZ Bootstrap issues for this release" would be added in the az_quickstart repository. Pull Requests from any repository can be freely added to the corresponding release project. For more information on managing milestones in GitHub, refer to the [GitHub documentation on milestones](https://docs.github.com/en/issues/using-labels-and-milestones-to-track-work/about-milestones).

[Create a pull request (PR)](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request) to change the code within a specific repository. PRs often have an issue associated with them, but not always (like in the case of [Dependabot](https://docs.github.com/en/code-security/dependabot/dependabot-security-updates/about-dependabot-security-updates) making PRs). When possible, always link an issue to a PR. 

[Projects](https://docs.github.com/en/issues/planning-and-tracking-with-projects/learning-about-projects/about-projects) are created in conjunction with specific [releases](https://github.com/az-digital/az_quickstart/releases) in accordance with our [release policy](https://github.com/az-digital/az_quickstart/blob/main/RELEASES.md) -- patch, minor, or major. All PRs must be added to at least one project to indicate the associated Quickstart release. These projects will aid in advance planning of release testing, writing release notes, etc.

### Bug Reports and Feature Requests

We use [GitHub Issues](https://github.com/az-digital/az_quickstart/issues) to
keep track of issues and bugs.

If you don't see the specific issue or bug in the [az_quickstart issue list](https://github.com/az-digital/az_quickstart/issues) 
or [arizona_bootstrap issue list](https://github.com/az-digital/arizona-bootstrap/issues), please create a new issue with as much detail as possible about the bug or feature request.

### Security Issues

If you found a security vulnerability and it's related to Drupal core or a
Drupal contrib module, please follow
[these](https://www.drupal.org/drupal-security-team/security-team-procedures/drupal-security-team-disclosure-policy-for-security) instructions.

If it's a security issue related to `az_quickstart` code, please email us here:
az-digital-security@list.arizona.edu

## Coding Standards

We follow Drupal
[coding standards](https://www.drupal.org/docs/develop/standards).

## Pull Request Guidelines

First, make sure there is an issue and project associated with your pull request.

Use proper branch naming conventions based on your issue type:
 * `feature/<issue-number>`
 * `bug/<issue-number>`

So, bug fix for issue #123 would be on branch `bug/123`

Create a draft pull request if you'd like to run automated tests and/or get
feedback before your pull request is completely ready for review.

**Note:** Probo will build a Drupal site with the committed changes and an admin
user with username `azadmin` and password `azadminXXXX` (replacing `XXXX` with the current year).

Follow the pull request template and use proper formatting for commit messages:
 * Use the present tense ("Add feature" not "Added feature")
 * Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
 * Limit the first line to 72 characters or less
 * When only changing documentation, include [ci skip] in the commit title
 * Use `fix/close/resolve` [keywords](https://help.github.com/en/github/managing-your-work-on-github/closing-issues-using-keywords) in commit messages
 to close associated issues
```
Add new feature X...<at most 72 characters>

Closes #123 by creating y and z. This can be a paragraph of explanation.
```

## Local development

To create a local copy of az_quickstart and build a working Drupal 8 site from
it, use the following commands.

**Important:** The Drupal code base will only be created inside the lando/ddev
container, so if you want to see the code use `lando ssh` or  `ddev ssh` to ssh
into the container, or follow the [instructions below for accessing code via
Visual Studio Code](#user-content-visual-studio-code-integration).

**Note:** The Lando and DDEV installs create an admin Drupal user with username `azadmin` and password `azadminXXXX` (replacing `XXXX` with the current year).

### Lando
```
git clone https://github.com/az-digital/az_quickstart.git foldername
cd foldername
lando start
lando install
```

### DDEV
```
git clone https://github.com/az-digital/az_quickstart.git foldername
cd foldername
ddev config --project-type php
ddev start
ddev install
```

Note: Use `ddev pause` and `ddev start` to restart the container.
Using `ddev restart` will require a re-install with `ddev install`.

### Visual Studio Code integration

Since the codebase you'll be editing exists **inside the lando or ddev docker
container**, you may need to use an IDE to edit code, especially if you don't
want to ssh into a docker container and use nano, or rsync code from local into
the docker container 100 times.

Note: These instructions may not work for DDEV.

Setup:
  1. Make sure you have [Visual Studio Code installed](https://code.visualstudio.com/docs/introvideos/basics),
  2. Install the [Visual Studio Code Remote Development Extension Pack
](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.vscode-remote-extensionpack)
  3. In the [Command Palette](https://code.visualstudio.com/docs/getstarted/userinterface#_command-palette),
     which can be found in View menu or (⌘⇧P on Mac) type
     `Remote-containers: Attach to Running Container...`
     and click on the result to bring up a list of running containers.
  4. If using lando, find the container whose name ends with `_appserver_1`.
  Click it to attach to that container.
  5. The last thing you'll have to do is add a folder from within the container
    to your workspace.
    To do this, use the file menu within Visual Studio Code,
    to Add Folder to Workspace.
    Then replace `/root` with the `/app` folder.
  6. You are ready to begin development.

Notes: Visual Studio Code has git integration, so you can use that to create new
branches and push up to github.
Visual Studio Code can automatically add the app folder to your workspace
through [Attached Container Configuration Files](https://code.visualstudio.com/docs/remote/containers#_attached-container-configuration-files),
which are created for you automatically by Visual Studio Code.

## Testing with phpunit prior to making a pull request
As a general rule of thumb, Arizona Digital has a goal of having unit,
functional, or kernel tests for all parts of AZ Quickstart.  This goal is meant
to ensure that committed code works in perpetuity, without unexpected bugs after
merging new code.  **We run tests on every pull request**, but it is often up to
each individual contributor to write tests for their new code.

Here is a rudimentary guide to running tests on your code with a local
development environment like Lando, or DDev.

When you install either of the recommended environments with the configuration
provided by this project, you will have our testing environment built in, but it
can still be a bit complicated.

## Testing with phpstan prior to making a pull request
PHPStan focuses on finding errors in your code without actually running it. It
catches whole classes of bugs even before you write tests for the code. It moves
PHP closer to compiled languages in the sense that the correctness of each line
of the code can be checked before you run the actual line.

`lando phpstan`
`ddev phpstan`
### Local testing on Lando
With lando running and quickstart installed and the branch with the changes you
want to run tests on checked out.

Steps for running phpunit tests on the Quickstart installation profile.
```
git clone https://github.com/az-digital/az_quickstart.git azqs-71
cd azqs-71
git checkout -b feature/71
lando start
lando phpunit
```

### Local testing on DDev

```
git clone https://github.com/az-digital/az_quickstart.git azqs-71
cd azqs-71
git checkout -b feature/71
ddev config --project-type php
ddev start
ddev phpunit
```

## Paragraph Styles

Much of the content editor experience for pages in Quickstart consists
of constructing pages using paragraph items as content building blocks. In
crafting new paragraph types, it will often be necessary to include settings
that are tracked as part of individual paragraph items that control the
display of the related paragraph item. The recommended approach for including
this sort of setting is with
[Paragraph Behavior Plugins](https://www.drupal.org/project/paragraphs/issues/2832884).

This allows developers to attach [Form API](https://www.drupal.org/docs/drupal-apis/form-api)
elements to the paragraph form and handles saving this configuration on a
per-paragraph basis.

The interface for Paragraph Behavior Plugins is
[located here](https://git.drupalcode.org/project/paragraphs/-/blob/8.x-1.12/src/ParagraphsBehaviorInterface.php).

If you wish your Behavior Plugin to also incldue the standard Quickstart
paragraph behaviors, extend **AZDefaultParagraphsBehavior**.

Note that by default, Behavior form elements are shown on a **Behavior** tab
on the paragraph. This can be avoided currently by
[a workaround](https://www.drupal.org/project/paragraphs/issues/2928759).
There will likely be a more official paragraphs API for this in the future.

## Compiling Javascript in Local Development

This project uses an ES6 to ES5 transpile process
[similar to Drupal core](https://www.drupal.org/node/2815083).

This means that you should only update `.js` files named `.es6.js` and should
never manually edit files named `.js` as these are machine-generated.

This can be done on demand with `yarn build`, or in response to changes
with `yarn watch`. When in watch mode, javascript files will be transpiled as
they are updated.

### ES6 Transpiling on Lando

```
lando yarn build
OR
lando yarn watch
```

### ES6 Transpiling on Dev

```
ddev yarn build
OR
ddev yarn watch
```

## ESLint in Local Development

To maintain high-quality JavaScript code, contributors are encouraged to use
ESLint in their local development environment. This tool helps in identifying
and reporting on patterns found in ECMAScript/JavaScript code, making it easier
to adhere to the project's coding standards.

###  Running ESLint

To lint all files in the project, contributors can use the command `lando
eslint .` or `ddev eslint .`, depending on whether you're using Lando or DDev for
your local development environment.

For a single file, the command changes slightly to include the filename, like
`lando eslint myfile.js` or `ddev eslint myfile.js`.

### Auto-fixing Code with ESLint

ESLint provides an auto-fix feature that can automatically fix some of the
linting errors. This is done by appending `--fix` to the eslint command: `lando
eslint . --fix` or `ddev eslint . --fix`.

## Theme Debugging

Developing within Drupal can be enhanced with Twig debugging. For AZ Quickstart,
we outline the most efficient methods to enable Twig debugging: via the Drupal
UI and using Drush, with a special emphasis on the latter for its convenience.

### Enable Twig Debugging via the Drupal UI

To enable Twig debugging through the Drupal UI:

1. Go to `/admin/config/development/settings` on your Drupal site.
2. Check the box for "Twig development mode".
3. Click on "Save settings".

### Enable Twig Debugging via Drush

Drush provides a powerful and quick way to enable or disable Twig debugging.
Below are the commands for enabling and subsequently disabling debugging:

To enable Twig debugging:

```bash
drush state:set twig_debug 1 --input-format=integer && \
drush state:set twig_cache_disable 1 --input-format=integer && \
drush state:set disable_rendered_output_cache_bins 1 --input-format=integer && \
drush cache:rebuild
```

To disable Twig debugging:

```bash
drush state:set twig_debug 0 --input-format=integer && \
drush state:set twig_cache_disable 0 --input-format=integer && \
drush state:set disable_rendered_output_cache_bins 0 --input-format=integer && \
drush cache:rebuild
```

### Additional Developer Resources

For more tools and tips on Drupal development, [visit the Drupal development
tools page](https://www.drupal.org/docs/develop/development-tools). Here, you'll
find a wealth of resources on debugging, performance optimization, and
development best practices to enhance your Drupal projects.

[Devel](https://www.drupal.org/project/devel) is included in the
[development metapackage](https://github.com/az-digital/az-quickstart-dev) 
that is downloaded when installing a site locally via Lando, or DDev.  See 
"Visual Studio Code Integration"

## Exporting Configuration

While working on features in local development in Quickstart, there are
situations where the local build will have configuration changes that are not
reflected in the saved configuration files of the distribution. This means
that the changes made will not be reflected in new site builds.

In order for changes to the local site to be reflected in the file tree
of the feature branch, configuration changes must be exported from the
development site.

### Exporting Individual Configuration Items

If it is known exactly which configuration entities are changed, they 
can be exported via the following:

```
drush az-core-config-export-single name.of.configuration.item
```

For example, to export `az_cas.settings`, use:

```
drush az-core-config-export-single az_cas.settings
```

Note that the `.yml` suffix should not be used when referring to exporting
these items.

Alternatively, configuration is also available to export through the
Drupal UI in the local site at the path `/admin/config/development/configuration/full/export`
or `/admin/config/development/configuration/single/export`

### Exporting Complex Configuration Changes

Some types of changes affect multiple configuration entities. For example, adding new
fields to a content type typically results in changes to the form display and view display
of that content type, along with additional field instances and field storage for the fields
added. It can sometimes be difficult to track down all changes that need to be exported in
these cases. Quickstart contains a Drush command to automate some of this:

```
drush az-core-distribution-config
```

This command examines the site of the local build and compares them to the saved configuration
files in the distribution, and raises an alert if changes are detected. It will prompt asking
whether the configuration in question should be exported.

```
    -- unmodified -- core.entity_view_display.block_content.basic.default
    -- unmodified -- core.entity_view_display.user.user.compact
    -- unmodified -- core.entity_view_display.user.user.default
     Update [az_quickstart/config/install] easy_breadcrumb.settings? (yes/no) [yes]:
```

When a prompt is raised, selecting `yes` will update the configuration in the source module
with the copy that is in the current active local development site.

Dependency analysis takes place to determine if new dependent configuration exists that
does not yet exist in the module files, but may need to be exported. This happens most
commonly in the case of new fields.

```
Examining dependencies...
 Export NEW dependent configuration field.field.node.az_event.field_az_brand_new_field? (yes/no) [yes]:
 > 
```
If `yes` is selected, the command  will prompt asking which module should receive the exported
configuration.

### Configuration Not to Export

Not all changes need to be exported. Some changes are made in the local development site
that do not necessarily relate to new features being created. If a difference is detected
in configuration unrelated to the contribution, it may not need to be exported. Select `no`
in these cases if they should not be exported.

### Configuration from Optional Modules

This can be particularly complex in the cases of overridden configuration, most notably
in entity view displays which contain `metatag` fields or settings for the `az_finder` 
module. This is because multiple copies of this configuration exists, copies of 
configuration with the optional module enabled, and copies without the optional module enabled.
These config entities still require some edits to produce the variants where the optional
module has not been enabled. Future versions of the `az-core-distribution-config` 
command may make this process simpler.

That effect is most notable in some of the following configuration items that have
optional overrides:

```
core.entity_form_display.node.az_flexible_page.default
core.entity_form_display.node.az_event.default
core.entity_form_display.node.az_person.default
core.entity_form_display.node.az_news.default
views.view.az_person
views.view.az_news
views.view.az_events
views.view.az_page_by_category
```

When exporting these configuration items, alternate versions must be produced that
do not have optional `metatag` or `az_finder` configuration.

## Configuration Management and Database Updates

A question that frequently arises for Quickstart contributors is whether a change that they are making requires a database update or if configuration file changes are sufficient.

### Context

`az-digital/az_quickstart` is a Drupal distribution that is hosted on many different platforms by a variety of teams employing different strategies and cadences when it comes to updating their Quickstart websites. This necessitates relatively strict guidelines that should be followed by the Arizona Digital team when making changes to Quickstart itself.

### Configuration changes that require database updates

Configuration changes will require database updates if:
-  Your code adds a new setting that never existed before.
-  You are changing a default setting and it would be best for it to be the new default on sites immediately, regardless of downstream decisions.
-  Your code replaces an existing setting and has a new key name.

### Configuration changes that do not require database updates

Configuration changes will not require database updates if:
- Your code adds another option to an existing setting and doesn't need to be selected by default.
- Your code changes an existing setting, but doesn't have breaking implications if configuration updates aren't applied right away downstream.
