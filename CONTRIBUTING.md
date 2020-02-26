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
  * [Lando](https://docs.lando.dev/basics/installation.html) or [DDEV](https://www.ddev.com/get-started/) for local development.

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

**Note:** The Drupal code base will only be created inside the lando/ddev container,
so if you want to see the code use `lando ssh` or  `ddev ssh` to ssh into the container.

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
