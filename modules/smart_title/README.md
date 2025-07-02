# About Smart Title

## Contents

 * [Introduction](#introduction)
 * [Similar or related modules](#similar-or-related-modules)
 * [Requirements](#requirements)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Maintainers](#maintainers)

## Introduction

It's a common problem of site builders that the label of an entity should be
configurable on the entity view display configuration form.
 * Site builders want to hide node title completely for some specified view
   modes.
 * Node title should follow an image field.
 * Node title should be printed in a specific layout region.

With Smart Title module, content entity labels are visible and configurable on
_Field UI_ forms.

Smart Title supports _Quick Edit_, _Field Layout_ and RDF core modules and
should work with every entity types which support _Field UI_ (have a
_Manage display_ form).

The _Smart Title_ component could be enabled per `entity_type` and per `bundle`
and can be switched on for each entity view mode.
For instance it can be used for _full_ view mode of the _Article_ content type
only.

## Similar or related modules

 * [Exclude Node Title](https://www.drupal.org/project/exclude_node_title)
 * [Style Node Title](https://www.drupal.org/project/style_node_title)
 * [Node Title](https://www.drupal.org/project/nodetitle)
 * [Simple Hide Node Title](https://www.drupal.org/project/hide_node_title)
 * [Automatic Nodetitles](https://www.drupal.org/project/auto_nodetitle)

## Requirements

The module does not define any hard dependencies.

## Installation

 * Install as you would normally install a contributed Drupal module.

## Configuration

Enable the __Smart Title UI__ submodule. After that, Smart Title can be
configured on the _Administration » Configuration » Content authoring »
Smart Title Settings_ page for any user with the permission
_Manage Smart Title configuration_ `administer smart title`.

Note that the UI module isn't needed for a production site and could be safely
disabled.

After Smart Title was enabled for a specific entity bundle, you can visit
the _Field UI_ form of that entity type and configure it per entity view mode.

Example: you need to place _Article_ title after an _Image_ field.

To enable _Smart Title_ for _Article_ content type's _teaser_ view mode ,
 * Enable _Smart Title_ for _Article_ content type: visit
   `/admin/config/content/smart-title` form, check _Article_ and save.
 * Enable _Smart Title_ for the teaser view mode: visit
   `/admin/structure/types/manage/article/display/teaser` and at the bottom
   of the page at _Smart Title_ check _Make entity title configurable_. Hit
   save again. Every other view modes of _Article_ will remain the same.
 * From this point, you'll have a _Smart Title_ element for this view mode.
   Because of it's hidden by default, the title of any _Basic page_ content will
   be hidden in every teaser view mode, such as on the `/node` page.
 * Make _Smart Title_ visible by dragging it after your _Image_ field.
   You may configure the _HTML tag_, _Title HTML classes_ or whether you want
   title printed with a link to the content or not.
   Default configuration should be fine for teaser view mode.

## Maintainers

Current maintainers:

 * Zoltán A. Horváth (huzooka) - https://drupal.org/user/281301
