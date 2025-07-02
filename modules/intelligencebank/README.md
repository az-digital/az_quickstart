# IntelligenceBank DAM Integration

## Introduction

The IntelligenceBank DAM Connector for Drupal provides an easy way to access IntelligenceBank assets for importing,
or embedding directly from the Drupal core Media Library module.

There are two ways to insert assets from IntelligenceBank:

  * By creating a local copy of the asset into Drupal’s media storage
  * By embedding a public CDN link to the asset in IntelligenceBank

------

## Requirements

For the basic integration:

  * PHP 8+
  * Drupal 9+
  * core [Media Library](https://www.drupal.org/docs/core-modules-and-themes/core-modules/media-library-module) module

For the integration with CKEditor 

  * core modules: Field, Filter, CKEditor / CKEditor 5.

## Installation and configuration

Review the [IntelligenceBank Helpdesk](https://help.intelligencebank.com/hc/en-us/sections/360000214283-Intelligencebank-Connector-for-Drupal) for detailed information about installation and configuration.

## Limitations

Possible problems with using Media Library, IB DAM in single modal mode.
Usually for sites that use lots of Layout Builder modules that enhances UI with misc modals.
There is [placeholder](https://git.drupalcode.org/project/intelligencebank/-/blob/4.0.x/modules/ib_dam_media/src/Form/MediaConfigurationForm.php#L100) for option to fix this issue.
Related discussion - [Fix Nested Modals by Drupal core and Media Library Form API Element](https://www.drupal.org/project/media_library_form_element/issues/3155697#comment-13725927).
