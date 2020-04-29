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
  * Local containerized (Docker) development environment tool either lando or ddev
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

Note: Use `ddev pause` and `ddev start` to restart the container. Using `ddev restart` will require a re-install with `ddev install`.

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
     which can be found in View menu or (⌘⇧P on Mac) type `Remote-containers: Attach to Running Container...`
     and click on the result to bring up a list of running containers on your computer.
  4. If using lando, find the container whose name ends with `_appserver_1` and click
    it to attach to that container.
  5. The last thing you'll have to do is add a folder from within the container
    to your workspace.  To do this, use the file menu within Visual Studio Code,
    to Add Folder to Workspace.  Then replace `/root` with the `/app` folder, and you are ready to
    begin development.

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
