<?php

namespace Drupal\schema_review\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Review' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_review",
 *   label = @Translation("Schema.org: Review"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Review",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/review-snippet",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaReview extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
