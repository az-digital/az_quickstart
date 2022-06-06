# How to contribute

First off, thanks for taking the time to contribute to AZ Quickstart!

AZ Quickstart is created by [Arizona Digital](https://digital.arizona.edu/), a
team of web-focused volunteers that meet weekly to create projects like [Arizona Bootstrap](https://digital.arizona.edu/ua-bootstrap) and
[Arizona Quickstart](https://quickstart.arizona.edu/).

## Things you'll need to get started

  * A [GitHub account](https://github.com/join).
  * [Slack](https://uarizona.slack.com) is our main source of communications.
    * Use the `#ua-quickstart-d8` channel for questions/comments related to this
      project.
    * Use the `#friday-meetings` channel to ask questions or get updates related
      to Arizona Digital meetings, both physical and via Zoom.
    * Use the `#uadigital-general` channel to ask general questions related to
      Arizona Digital.
  * A basic understanding of [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git).
  * Local containerized (Docker) dev environment tool either lando or ddev
    * [Lando](https://docs.lando.dev/basics/installation.html)
    * [DDev](https://www.ddev.com/get-started/)
  * An IDE with the capability to attach to a remote codeserver or docker
    container. [Visual Studio Code](https://code.visualstudio.com) allows for this.
    * Generic [instructions for connecting to a docker container with Visual
    Studio Code]( https://code.visualstudio.com/docs/remote/containers#_attached-container-config-reference)
    or see [instructions below](#user-content-visual-studio-code-integration).

## Submitting a bug/issue/feature request

### Security Issues

If you found a security vulnerability and it's related to Drupal core or a
Drupal contrib module, please follow
[these](https://www.drupal.org/drupal-security-team/security-team-procedures/drupal-security-team-disclosure-policy-for-security) instructions.

If it's a security issue related to `az_quickstart` code, please email us here:
az-digital-security@list.arizona.edu

### General bug/new feature request

We use [GitHub Issues](https://github.com/az-digital/az_quickstart/issues) to
keep track of issues and bugs.

If you don't see the specific issue or bug after looking at the
[AZ Quickstart Project](https://github.com/orgs/az-digital/projects/1), please
create a new issue with proper description of bug or details related to new
feature request.

## Coding Standards

We follow Drupal
[coding standards](https://www.drupal.org/docs/develop/standards).

## Pull requests

First, make sure there is an issue associated with your pull request.

Use proper branch naming conventions based on your issue type:
 * `feature/<issue-number>`
 * `bug/<issue-number>`

So, bug fix for issue #123 would be on branch `bug/123`

Create a draft pull request if you'd like to run automated tests and/or get
feedback before your pull request is completely ready for review.

**Note:** Probo will build a Drupal site with the committed changes and an admin
user with username and password `azadmin`.

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

**Note:** The Lando and DDEV installs create an admin Drupal user with username
and password `azadmin`.

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

You may run eslint to lint javascript in local development with one of the
following two commands.

All files:
```
lando eslint .
ddev eslint .
```
Single file:
```
lando eslint myfile.js
ddev eslint myfile.js
```

## Theme debugging and the development site mode.

Developing within Drupal can be a real challenge without debugging enabled.

[Devel](https://www.drupal.org/project/devel) and [Drupal Console](https://drupalconsole.com/) are both included in the [development metapackage](https://github.com/az-digital/az-quickstart-dev) that is downloaded when installing a site locally via Lando, or DDev.

You can easily enable them by using the following commands.

### Lando
First you have to make the folder containing /app/web/sites/default/services.yml writable.
```
lando ssh --user root
chmod 755 -R /app/web/sites/default
exit
```
Then enable devel module and put the site into dev mode via drupal console.
```
lando drush en -y devel
lando drupal site:mode dev
```

### DDev
```
ddev drush en -y devel && ddev drupal site:mode dev
```

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
