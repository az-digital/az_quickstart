# Easy Breadcrumb

The Easy Breadcrumb module updates the core Breadcrumb block to include
the current page title in the breadcrumb. The module also comes with additional
settings that are common features needed in breadcrumbs.

Breadcrumbs use the current URL (path alias) and the current page title to
build the crumbs. The module is designed to work out of the box with no adjustments,
and it comes with settings to customize the crumbs.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/easy_breadcrumb).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/easy_breadcrumb).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Navigate to Administration > Extend and enable the module. The system
   breadcrumb block has now been updated.
2. Navigate to Administration > Configuration > User Interface > Easy
   Breadcrumb for configurations. Save Configurations.

3. Configurable parameters:
   - Include / Exclude the front page as a segment in the breadcrumb.
   - Include / Exclude the current page as the last segment in the breadcrumb.
   - Use the real page title when it is available instead of always deducing it
     from the URL.
   - Print the page's title segment as a link.
   - Make the language path prefix a segment on multilingual sites where a path
     prefix ("/en") is used.
   - Use menu title as fallback instead of raw path component.
   - Remove segments of the breadcrumb that are identical.
   - Use a custom separator between the breadcrumb's segments. (TODO)
   - Choose a transformation mode for the segments' title.
   - Make the 'capitalizator' ignore some words.


## Maintainers 

- Roger Padilla - [sonemonu](https://www.drupal.org/u/sonemonu)
- Greg Boggs - [Greg Boggs](https://www.drupal.org/u/greg-boggs)
- Brooke Mahoney - [loopduplicate](https://www.drupal.org/u/loopduplicate)
- Balazs Janos Tatar - [tatarbj](https://www.drupal.org/u/tatarbj)
- AmyJune Hineline - [volkswagenchick](https://www.drupal.org/u/volkswagenchick)
- Hennie Martens - [hmartens](https://www.drupal.org/u/hmartens)
- Neslee Canil Pinto - [Neslee Canil Pinto](https://www.drupal.org/u/neslee-canil-pinto)
- Jason Savino - [jasonsavino](https://www.drupal.org/u/jasonsavino)
- Rakesh James - [rakesh.gectcr](https://www.drupal.org/u/rakeshgectcr)
- Brian Gallagher - [diamondsea](https://www.drupal.org/u/diamondsea)
- Renato Gonçalves H - [RenatoG](https://www.drupal.org/u/renatog)
- Anne Bonham - [banoodle](https://www.drupal.org/u/banoodle)
