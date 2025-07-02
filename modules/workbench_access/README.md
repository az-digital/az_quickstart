# Workbench Access

Workbench Access creates editorial access controls based on hierarchies. It is  an extensible system that supports structures created by other Drupal modules.

When creating and editing content, users will be asked to place the content in an editorial section. Other users within that section or its parents will be able to edit the content.

A user may be granted editorial rights to a section specific to their account or  by their assigned role on the site. Content may only be placed in sections that the user has rights to.

## Current versions

* Current development is in the 2.0.x branch and is Drupal 9 and 10 compatible.
* 8.x-1.0 still supports Drupal 8, if needed. However, this release will no longer receive any development or bugfixes, only support for configuration questions. The 8.x-1.x branch is closed.
* The 7.x-1.x branch is likewise closed to new development, and release 7.x-1.6 is the final release for Drupal 7.


## Table of Contents

* [Installation and Configuration](#installation-and-configuration)
  * [Overview](#overview)
  * [Installation](#installation)
  * [Configuration](#configuration)
  * [Permissions](#permissions)
  * [Module Settings](#module-settings)
* [Help](#help)
  * [Terminology](#terminology)
  * [Supported Drupal Modules](#supported-drupal-modules)
  * [Entity Types](#entity-types)
  * [Access Control Fields](#access-control-fields)
  * [Access Hierarchies](#access-hierarchies)
  * [Section Assignments](#section-assignments)
* [Developer Notes](#developer-notes)
  * [Access Controls](#access-controls)
  * [Data Storage](#data-storage)
  * [Contributing](#contributing)
  * [Testing](#testing)

# Installation and Configuration

## Overview

The Workbench Access module creates editorial access controls based on [access hierarchies](#access-hierarchies). The module provides an extensible system that supports structures created by [supported Drupal modules](#supported-drupal-modules). To use the module, you must configure at least one [access control field](#access-control-fields) on each of the [entity types](#entity-types) for which you wish to restrict editorial access.

Note that Workbench Access only denies access to untrusted users. Some other permission set -- such as `Article: Edit any content` -- must be available to  the user in order to perform the desired action.

While Workbench Access is part of a larger module suite, it may be run as a  stand-alone module with no dependencies on other parts of Workbench.

## Installation

To start using the module, install normally and then go to the configuration page at `admin/config/workflow/workbench_access`.

Tip: It is best if you create your hierarchy (say a Taxonomy Vocabulary called `Editorial section` before configuring the module.

If you want to test how the system works, you can run the drush command `drush wa-test` to install a sample Taxonomy hierarchy and field. You will then need to  configure a Taxonomy scheme.

## Configuration

Visit `/admin/config/workflow/workbench_access` and click the `Add access scheme` button. This will take you to a form that asks for three pieces of information:

* *Label*: The name of the access scheme to display.
* *Plural label*: A plural version of the label.
* *Access scheme*: The type of access control plugin to use. The default choices are Taxonomy and Menu.

After filling in the form, click `Save` and you will be taken to the configuration form.

Here, we add options specific to the access scheme. For a Taxonomy scheme, you will be asked to `Select the vocabularies to use for access control` as a series of checkboxes. Each element checked will create a new root section, which is the parent to the entire hierarchy. This setting controls the sections that users and roles may be assigned to.

Once you have selected the parent elements, you may select the fields to use for  access control. (You do not need to save the form before selecting fields, but you may without causing an error.)

Field selection creates a row for each eligible field that may be used for access control. Each row has three elements:

* *Entity type*: The type of entity, such as 'Content'.
* *Bundle*: The entity subtype, such as 'Article'.
* *Field name*: The name of one field that may be used with this access scheme.

Note that:

* If more than one field for the scheme type is available, each field will have its own row.
* Entity types and bundles without an eligible field will not be present. These types will be *ignored* by Workbench Access.
* When saving the configuration, the field will be validated to ensure that it contains the root sections configured for the access scheme.

_Note that no access control will be enforced if no fields are selected._

*Tip: We recommend that only one access scheme is used per entity type, with a common field across all bundles. Other configurations are possible, but can lead to unwanted complexity.*

## Permissions
Once you select the fields, it is time to assign users to editorial sections. For each role that should use Workbench Access, give the role either of the following permissions:

* *Bypass Workbench Access permissions*
  This permission assigns users in the role to all sections automatically. Give only to trusted administrators.

* *Allow all members of this role to be assigned to Workbench Access sections*
  This permission lets users and roles be assigned to specific editorial sections. It is the default permission for most roles.

After permissions are assigned, go to the Sections overview page `admin/config/workflow/workbench_access/{scheme id}/sections`. This page shows a list of all sections in your access hierarchy and provides links for adding roles or users to those sections.

Users may also be assigned using the `Workbench Access` tab on user account pages. To access this tab, an administrator must have one of the following permissions:

* *Assign users to Workbench Access sections*
Users with this permission will be allowed to see and assign all sections in all access schemes.

* *Assign users to selected Workbench Access sections*
Users with this permission will be allowed to see and assign only to sections(and their children) that their account is also assigned to.

This permission structure allows site administrators to allow other users to add members to sections without giving them access to edit user accounts or to administer Workbench Access settings.

Note that when granting access, the hierarchy is enforced such that if you have the following structure:

```
College
- Alumni
-- Events
-- Giving
- Students
-- Graduate
-- Undergraduate
```

A user or role assigned to `Alumni` will also have access to `Events` and`Giving` and does not need to be assigned to all three. A user or role assigned to `College` will have access to all six sections shown above.

Parent-child relationships are governed by the access scheme, so changes to the scheme structure (say, the addition of a new Taxonomy Term, or the re-organization of a Menu) will affect how permissions are enforced. Workbench Access mirrors the configuration of its scheme's provider.

## Module Settings

Aside from access schemes, the module only has one setting: *Deny access to unassigned content*.

This setting is designed for use in cases where you have installed the module but some content has not been created with the _access control field_ present.

If this setting is enabled, any _bundle_ of an _entity type_ that has no field data will issue a *deny* to any editorial action request.

# Help

## Terminology

* *Access control*: A mechanism for denying or allowing system users from performing specific actions, such as editing content. Workbench Access controls the _create_, _edit_, and _delete_ operations for assigned content types.

* *Access scheme* (or _scheme_): A defined system for controlling access, managed by a specific plugin. Schemes are generally referred to by their parent modules (i.e. _the Taxonomy scheme_).

* *Bundle*: A subtype of an _entity type_ such as an _Article_ content type.

* *Children*: A _section_ in a hierarchy that has one or more parents.

* *Editors*: Individual users assigned to a specific _section_ of a _scheme_. Assigning editors to a section removes the *deny* placed on that user when performing editorial actions under _access control_.

* *Entity type*: A content type defined by a Drupal site, such as _content_, _term_, and _custom block_.

* *Field*: A data element attached to a specific _bundle_ on an _entity type_. Fields are how Drupal stores and displays most content.

* *Parent*: A _section_ in a hierarchy that has one or more children.

* *Plugin*: A piece of Drupal extension code that manages functionality. Workbench Access uses the `AccessControlHierarchy` plugin system.

* *Roles*: Groups users defined by Drupal _role_ and assigned to a specific_section_ of a _scheme_. Assigning roles to a section removes the *deny* placed on all users of that role when performing editorial actions under _access control_.

* *Root*: The top-level of a hierarchy, such as a Taxonomy Vocabulary or a Menu.

* *Section*: An element of a _scheme_ that content and users may be assigned to in order to determine access control.

## Supported Drupal Modules

Workbench Access ships with support for *Taxonomy* and *Menu* modules. Other contributed and custom modules may provide additional support.

Access controls may only be placed on entity types that have fields governed by supported modules. By default, that means that the *Menu* scheme only applies to Content, because that is the only entity type with default Menu support.

For *Taxonomy* schemes, any entity type that contains a Taxonomy Term entity reference field may be placed under access control.

## Entity Types

Workbench Access can be applied to any _content_ entity types that support fields and whose fields are supported by an _access scheme_.

When you configure an _access scheme_, you will be able to select from a list of entity types that are meet the requirements of the scheme. Under default usage, that means:

* *Menu* schemes will support any Content type (also called a _bundle_).

* *Taxonomy* schemes will support any entity type that has a Taxonomy Term entity reference field, so long as that field does not create an infinite loop by referring to itself.

## Access Control Fields

Access control fields must be explicitly assigned based on the fields available in your entity types. When you configure an access scheme, you will be required to identify the fields that should be used for access control on each entity bundle.

One advantage to this system is that an access scheme can be applied to existing content, provided that the access control field contains data. (See the _Module Settings_ section for more information.)

_Unlike in Drupal 7, Workbench Access no longer provides its own fields. The assumption here is that your site's current architecture can be leveraged for access control without the need to add additional fields._

## Access Hierarchies

When creating and editing content, users will be asked to place the content in an editorial section. Other users within that section or its parents will be able to edit the content.

A user may be granted editorial rights to a section specific to their account or by their assigned role on the site. To create, edit and delete content in a section, the user must have the core Node module permission (e.g. `Edit all Article content`) *and* the content must be assigned to the same section.

Note that the module only controls access to content editing. It does not provide any content filtering of access restrictions for users trying to view that content.

## Section Assignments

To enforce access control properly, two elements are required:

* Content entities must be assigned according to their access control fields.
* Users or roles must be assigned to specific editorial sections.

The module works by mapping the two. When a user tries to edit an article, the field values for that article will be compared to the user's assigned editorial sections. If the article is *not* within the user's sections, access will be denied.

# Developer Notes

## Access Controls
Workbench Access applies to all content entities if you use the Taxonomy scheme, the Menu scheme only works for Content (nodes).

By design, Workbench Access never _allows_ access. It only responds with`neutral` or `deny`. The intention is that normal editing permissions should apply, but only within the sections that a user is assigned to.

Access controls are controlled by the `WorkbenchAccessManager` class but the individual response is delegated to plugins via the `checkEntityAccess` method provided in the `AccessControlHierarchyBase` plugin. So if you want to change access behavior, you can write your own plugin or extend an existing one.

## Data Storage
Access is granted either at the `user` or `role` level.

Storage is a service, and swappable. See the `UserSectionStorage` and `RoleSectionStorage` interfaces for details.

Base configuration of Workbench Access is config-exportable, but the actual access control assignments are not. This is a limitation of Drupal's design, given that Menus and Taxonomies are content entities (and thus not exportable).

Content-level data is stored on individual fields, which must be created and assigned via the Workbench Access configuration page at `admin/config/workflow/workbench_access/{scheme name}/edit`.

## Contributing

If you'd like to contribute, please do. GitLab forks and pull requests are fine. If you prefer a patch-based workflow, you can attach patches to Drupal.org issues.

## Testing

The module has complete coverage. New features of bugfixes are required to have passing tests. All pull requests will automatically run tests on Drupal.org's testing infrastructure.

### Code linting

We use (and recommend) [PHPCBF](https://phpqa.io/projects/phpcbf.html), [PHP Codesniffer](https://github.com/squizlabs/PHP_CodeSniffer) and [phpstan](https://phpstan.org/) for code quality review.

The following commands are run before commit:

* `vendor/bin/phpcbf web/modules/contrib/workbench_access --standard="Drupal,DrupalPractice" -n --extensions="php,module,inc,install,test,profile,theme"`
* `vendor/bin/phpcs web/modules/contrib/workbench_access --standard="Drupal,DrupalPractice" -n --extensions="php,module,inc,install,test,profile,theme"`
* `vendor/bin/phpstan analyze web/modules/contrib/workbench_access`

### Testing tools

We are using the following composer dev dependencies for local testing:

```
  "drupal/coder": "^8.3",
  "mglaman/drupal-check": "^1.4",
  "squizlabs/php_codesniffer": "^3.6",
```

Note that PHPCBF is installed as part of php_codesniffer.

### phpstan config

We use the following phpstan.neon file:

```
parameters:
  level: 2
  ignoreErrors:
    # new static() is a best practice in Drupal, so we cannot fix that.
    - "#^Unsafe usage of new static#"
    # Ignore common errors for now.
    - "#Drupal calls should be avoided in classes, use dependency injection instead#"
    # Ignore PHPUnit Prophecy class reflection errors.
    - "#^Call to an undefined method Prophecy#"
    - "#^Call to deprecated method prophesize#"
  drupal:
    entityMapping:
      access_scheme:
        class: Drupal\workbench_access\Entity\AccessScheme
        storage: Drupal\Core\Entity\ContentEntityStorageBase
      section_association:
          class: Drupal\workbench_access\Entity\SectionAssociation
          storage: Drupal\workbench_access\SectionAssociationStorage
```

The drupal entityMapping is also provided by `entity_mapping.neon` in the project root, for use with other tests.
