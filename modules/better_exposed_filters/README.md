# Better Exposed Filters

The Better Exposed Filters module replaces the Views' default single-  or
multi-select boxes with more advanced options such as radio buttons, checkboxes,
toggle links or jQueryUI widgets.

Views Filters are a powerful tool to limit the results of a given view. When you
expose a filter, you allow the user to interact with the view making it easy to
build a customized advanced search.  For example,  exposing a taxonomy filter
lets your site visitor search for articles with specific tags.  Better Exposed
Filters gives you greater control over the rendering of exposed filters.

For a full description of the module, visit the
[project page](https://drupal.org/project/better_exposed_filters).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://drupal.org/project/issues/better_exposed_filters).


## Table of contents

- Requirements
- Installation
- Configuration
- Integration
- Maintainers


## Requirements

- Core modules
  - views

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

### Use a local version of the Slider library

If you want to use a local library for the Slider functionality instead of 
loading the library from a CDN, you need to place the library in the `nouislider`
directory of one of your Drupal installation's `libraries` folders.

As of 7.0.3 we now have a drupal library project to pull from.

#### Install manually

Alternatively, if you don't want to use Composer to manage the library, you could download the library from `https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.5.1/nouislider.min.js` and place it into `libraries/nouislider` manually.

Make sure to clear all caches for the changes to take effect.

## Configuration

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will prevent the links from appearing. To get the links
back, disable the module and clear caches.

## Integrations

Modules that provide additional BEF widgets or support for exposed filters in general:

- [select2](https://www.drupal.org/project/select2)
- [selective_better_exposed_filters](https://www.drupal.org/project/selective_better_exposed_filters)
- [configurable_views_filter_block](https://www.drupal.org/project/configurable_views_filter_block)
- [views_dependent_filters](https://www.drupal.org/project/views_dependent_filters)

## Maintainers

- Andrii Podanenko - [podarok](https://www.drupal.org/u/podarok)
- Martin Keereman - [Etroid](https://www.drupal.org/u/etroid)
- Joshua Kopel - [jkopel](https://www.drupal.org/u/jkopel)
- Mike Keran - [mikeker](https://www.drupal.org/u/mikeker)
- Neslee Canil Pinto - [Neslee Canil Pinto](https://www.drupal.org/u/neslee-canil-pinto)
- Rick Hawkins - [rlhawk](https://www.drupal.org/u/rlhawk)
- Christian Fritsch - [chr.fritsch](https://www.drupal.org/u/chrfritsch)
- Stephen Mustgrave - [smustgrave](https://www.drupal.org/u/smustgrave)
