<?php

namespace Drupal\schema_how_to\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'QAPage' and 'FAQPage' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_how_to",
 *   label = @Translation("Schema.org: HowTo"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>, Google's recommendations at <a href="":google_url"">:google_url</a>.", arguments = {
 *     ":url" = "https://schema.org/HowTo",
 *     ":google_url" = "https://developers.google.com/search/docs/data-types/how-to",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaHowTo extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
