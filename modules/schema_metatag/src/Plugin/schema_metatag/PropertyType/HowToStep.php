<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'HowToStep' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "how_to_step",
 *   label = @Translation("HowToStep"),
 *   tree_parent = {
 *     "HowToStep",
 *   },
 *   tree_depth = -1,
 *   property_type = "HowToStep",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The word or short phrase summarizing the step (for example, ""Attach wires to post"" or ""Dig""). Don't use non-descriptive text."),
 *     },
 *     "text" = {
 *       "id" = "text",
 *       "label" = @Translation("text"),
 *       "description" = @Translation("REQUIRED BY GOOGLE. The full instruction text of this step."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. A URL that directly links to the step (if one is available). For example, an anchor link fragment."),
 *     },
 *     "image" = {
 *       "id" = "image_object",
 *       "label" = @Translation("image"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. An image of the step."),
 *       "tree_parent" = {
 *         "ImageObject",
 *       },
 *       "tree_depth" = 0,
 *     },
 *   },
 * )
 */
class HowToStep extends PropertyTypeBase {

}
