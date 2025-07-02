# Quick Node Clone

The Quick Node Clone module adds a "Clone" tab to a node. When selected, a new
node is created and fields from the previous node are populated into the new
fields.

This is potentially duplicate work of Node Clone
[project page](https://www.drupal.org/project/node_clone), but as of release they don't have
a stable D8 version and this code was created for a project from scratch in a
reusable manner. This is focused on supporting more variety in field types than
core's default cloning.

Future @TODO: Support more than just nodes! It could be expanded to all Content
Entities fairly easily. This will likely be in its own properly named module
with a better method for adding a UI to other content entities.

- For a full description of the module visit:
  [project page](https://www.drupal.org/project/quick_node_clone)

- To submit bug reports and feature suggestions, or to track changes visit:
  [issue queue](https://www.drupal.org/project/issues/quick_node_clone)


## Content of this file

- Requirements
- Recommended Modules
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Recommended modules

It currently supports cloning of most field types including Inline Entity Form
and Field Collection.

- [Inline Entity Form](https://www.drupal.org/project/inline_entity_form)
- [Field collection](https://www.drupal.org/project/field_collection)


## Installation

Install the Quick Node Clone module as you would normally install a contributed
Drupal module. For further information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)


## Configuration

1. Navigate to Administration > Extend and enable the Quick Node Clone
   module.
2. A Clone tab is now available on nodes.
3. Select the Clone tab and a new node is created and the fields from the
   previous node are populated.
4. Make appropriate edits and Save New Clone.


## Maintainers

- David Lohmeyer - [vilepickle](https://www.drupal.org/u/vilepickle)
- Neslee Canil Pinto - [Neslee Canil Pinto](https://www.drupal.org/u/neslee-canil-pinto)
