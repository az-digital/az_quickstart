<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'offers' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_event_offers",
 *   label = @Translation("offers"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Offers associated with the event."),
 *   name = "offers",
 *   group = "schema_event",
 *   weight = 6,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "offer",
 *   tree_parent = {
 *     "Offer",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaEventOffers extends SchemaNameBase {

}
