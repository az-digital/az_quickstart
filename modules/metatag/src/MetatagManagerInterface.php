<?php

namespace Drupal\metatag;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Class MetatagManager.
 *
 * @package Drupal\metatag
 */
interface MetatagManagerInterface {

  /**
   * Extracts all tags of a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to extract meta tags from.
   *
   * @return array
   *   Array of metatags.
   */
  public function tagsFromEntity(ContentEntityInterface $entity): array;

  /**
   * Extracts all tags of a given entity.
   *
   * And combines them with sitewide, per-entity-type, and per-bundle defaults.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to extract meta tags from.
   *
   * @return array
   *   Array of metatags.
   */
  public function tagsFromEntityWithDefaults(ContentEntityInterface $entity): array;

  /**
   * Extracts all appropriate default tags for an entity.
   *
   * From sitewide, per-entity-type, and per-bundle defaults.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity for which to calculate defaults.
   *
   * @return array
   *   Array of metatags.
   */
  public function defaultTagsFromEntity(ContentEntityInterface $entity): array;

  /**
   * Returns an array of group plugin information sorted by weight.
   *
   * @return array
   *   Array of groups, sorted by weight.
   */
  public function sortedGroups(): array;

  /**
   * Returns an array of tag plugin information sorted by group then weight.
   *
   * @return array
   *   Array of tags, sorted by weight.
   */
  public function sortedTags(): array;

  /**
   * Returns a weighted array of groups containing their weighted tags.
   *
   * @return array
   *   Array of sorted tags, in groups.
   */
  public function sortedGroupsWithTags(): array;

  /**
   * Builds the form element for a Metatag field.
   *
   * If a list of either groups or tags are passed in, those will be used to
   * limit the groups/tags on the form. If nothing is passed in, all groups
   * and tags will be used.
   *
   * @param array $values
   *   Existing values.
   * @param array $element
   *   Existing element.
   * @param array $token_types
   *   Token types to return in the tree.
   * @param array $included_groups
   *   Available group plugins.
   * @param array $included_tags
   *   Available tag plugins.
   * @param bool $verbose_help
   *   Whether to include extra help text at the top of the form or keep it
   *   short.
   *
   * @return array
   *   Render array for metatag form.
   */
  public function form(array $values, array $element, array $token_types = [], array $included_groups = NULL, array $included_tags = NULL, $verbose_help = FALSE): array;

  /**
   * Generate the elements that go in the hook_page_attachments attached array.
   *
   * @param array $tags
   *   The array of tags as plugin_id => value.
   * @param object $entity
   *   Optional entity object to use for token replacements.
   *
   * @return array
   *   Render array with tag elements.
   */
  public function generateElements(array $tags, $entity = NULL): array;

  /**
   * Generate the actual meta tag values.
   *
   * @param array $tags
   *   The array of tags as plugin_id => value.
   * @param object $entity
   *   Optional entity object to use for token replacements.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $cache
   *   (optional) Cacheability metadata.
   *
   * @return array
   *   Render array with tag elements.
   */
  public function generateRawElements(array $tags, $entity = NULL, BubbleableMetadata $cache = NULL): array;

}
