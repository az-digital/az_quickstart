<?php

namespace Drupal\schema_qa_page\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'QAPage' and 'FAQPage' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_qa_page",
 *   label = @Translation("Schema.org: QAPage, FAQPage"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a> and <a href="":url2"">:url2</a>, Google's recommendations at <a href="":google_url"">:google_url</a> and <a href="":google_url2"">:google_url2</a>.", arguments = {
 *     ":url" = "https://schema.org/QAPage",
 *     ":url2" = "https://schema.org/FAQPage",
 *     ":google_url" = "https://developers.google.com/search/docs/data-types/qapage",
 *     ":google_url2" = "https://developers.google.com/search/docs/data-types/faqpage",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaQAPage extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
