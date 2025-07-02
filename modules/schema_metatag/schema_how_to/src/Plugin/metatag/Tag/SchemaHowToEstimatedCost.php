<?php

namespace Drupal\schema_how_to\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_how_to_estimated_cost' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_how_to_estimated_cost",
 *   label = @Translation("estimatedCost"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The estimated cost of the supplies consumed when performing instructions."),
 *   name = "estimatedCost",
 *   group = "schema_how_to",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "monetary_amount",
 *   tree_parent = {
 *     "MonetaryAmount",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaHowToEstimatedCost extends SchemaNameBase {

}
