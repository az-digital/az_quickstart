# FIELD GROUP

History:
  Field_group was originally written when drupal 7 was released. For drupal 6,
  the module is located in the CCK module (http://drupal.org/project/cck).
  As drupal core has a fields API drupal > 6, the field_group module
  is considered a contribution.

Description:
  field_group is a module that will group a set of fields. In Drupal8,
  with fields, one means all fields that come from fieldable entities.
  You can add fieldgroups in several types with their own format settings.
  field_group uses plugins to add your own formatter and rendering for
  it.
  One of the biggest improvements to previous versions, is that fieldgroups
  have unlimited nesting, better display control.
  Note that field_group will only group fields, it can not be used to hide
  certain fields since this a permission matter.

For a full description of the module, visit the
[project page](http://drupal.org/project/field_group).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](http://drupal.org/project/issues/field_group)


## Table of contents

- Requirements
- Installation
- Configuration
- Create field groups
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. You can configure the field groups for different displays like, in
   managed_form_display and managed_display of the entity type.
1. You can create different field groups under managed_form_display by
   adding a new group under "Add new group" label and the format the
   grouping using the desired formatter for displaying the same.
1. Same thing can be done in managed_display.
1. The field grouping done in managed display will be reflected on the
   view detail page of the entity, while that done in the
   managed_form_display will be reflected in the add/edit form of the entity.

## Create field groups
This section explains how to create groups of fields according to the type
chosen.
- Fieldsets : This group of fields makes the internal content in a fieldset.
              It is possible to add a title and a caption (which appears at
              the bottom of the fieldset).
- Details : Similar to Fieldsets. You can configure them to be open (normal
            fieldset) or collapsed.
- Details Sidebar: Similar to Details. You can configure them to be open
                   (normal fieldset) or collapsed and move them in the sidebar
                   on the node form.
- Html element : This fieldgroup renders the inner content in a HTML
                 element. You can configure attributes and label element.
                 The following two groupings works differently because you must
                 associate them with another grouping.
- Tabs : This fieldgroup renders child groups in its own tabs wrapper.
         As a first step you must create an Tabs group. You can set
         choose if you want that your tabs are show horizontally or
         vertically. Then, you can create Tab as a child and choose
         one to be open by default.
         This group can contain fields.
         For all groups, you can add id or classes.
         You can also choose if you want to mark a group as required if one of
         his fields is require (except for Accordions and Tabs : you must passed
         by their children).


## Maintainers

- stalski - [stalski](http://drupal.org/user/322618)
- zuuperman - [zuuperman](http://drupal.org/user/361625)
- swentel - [swentel](http://drupal.org/user/107403)

Inspirers:
- yched - [yched](http://drupal.org/user/39567)
