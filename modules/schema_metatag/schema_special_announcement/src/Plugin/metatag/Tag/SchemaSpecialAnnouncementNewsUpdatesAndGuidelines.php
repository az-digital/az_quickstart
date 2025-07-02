<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Plugin 'schema_special_announcement_news_updates_and_guidelines' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_news_updates_and_guidelines",
 *   label = @Translation("newsUpdatesAndGuidelines"),
 *   description = @Translation("Url to a page with news updates and guidelines in the context of COVID-19, if applicable to the announcement. This could be (but is not required to be) the main page containing SpecialAnnouncement markup on a site."),
 *   name = "newsUpdatesAndGuidelines",
 *   group = "schema_special_announcement",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementNewsUpdatesAndGuidelines extends SchemaNameBase {

}
