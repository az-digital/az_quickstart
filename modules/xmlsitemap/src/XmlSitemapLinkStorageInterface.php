<?php

namespace Drupal\xmlsitemap;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides an interface defining a XmlSitemapLinkStorage service.
 */
interface XmlSitemapLinkStorageInterface {

  /**
   * Create a sitemap link from an entity.
   *
   * The link will be saved as $entity->xmlsitemap.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose sitemap link will be created.
   */
  public function create(EntityInterface $entity);

  /**
   * Saves or updates a sitemap link.
   *
   * @param array $link
   *   An array with a sitemap link.
   */
  public function save(array $link);

  /**
   * Check if there is sitemap link is changed from the existing data.
   *
   * @param array $link
   *   An array of the sitemap link.
   * @param array $original_link
   *   An optional array of the existing data. This should only contain the
   *   fields necessary for comparison. If not provided the existing data will
   *   be loaded from the database.
   * @param bool $flag
   *   An optional boolean that if TRUE, will set the regenerate needed flag if
   *   there is a match. Defaults to FALSE.
   *
   * @return bool
   *   TRUE if the link is changed, or FALSE otherwise.
   */
  public function checkChangedLink(array $link, array $original_link = NULL, $flag = FALSE);

  /**
   * Check if there is a visible sitemap link given a certain set of conditions.
   *
   * @param array $conditions
   *   An array of values to match keyed by field.
   * @param array $updates
   *   Updates to be made.
   * @param bool $flag
   *   An optional boolean that if TRUE, will set the regenerate needed flag if
   *   there is a match. Defaults to FALSE.
   *
   * @return bool
   *   TRUE if there is a visible link, or FALSE otherwise.
   */
  public function checkChangedLinks(array $conditions = [], array $updates = [], $flag = FALSE);

  /**
   * Delete a specific sitemap link from the database.
   *
   * If a visible sitemap link was deleted, this will automatically set the
   * regenerate needed flag.
   *
   * @param string $entity_type
   *   A string with the entity type.
   * @param string $entity_id
   *   Entity ID to be deleted.
   * @param string $langcode
   *   (optional) The language code for the link that should be deleted.
   *   If omitted, links for that entity will be removed in all languages.
   *
   * @return int
   *   The number of links that were deleted.
   */
  public function delete($entity_type, $entity_id, $langcode = NULL);

  /**
   * Delete multiple sitemap links from the database.
   *
   * If visible sitemap links were deleted, this will automatically set the
   * regenerate needed flag.
   *
   * @param array $conditions
   *   An array of conditions on the {xmlsitemap} table in the form
   *   'field' => $value.
   *
   * @return int
   *   The number of links that were deleted.
   */
  public function deleteMultiple(array $conditions);

  /**
   * Perform a mass update of sitemap data.
   *
   * If visible links are updated, this will automatically set the regenerate
   * needed flag to TRUE.
   *
   * @param array $updates
   *   An array of values to update fields to, keyed by field name.
   * @param array $conditions
   *   An array of values to match keyed by field.
   * @param bool $check_flag
   *   An bool with check flag.
   *
   * @return int
   *   The number of links that were updated.
   */
  public function updateMultiple(array $updates = [], array $conditions = [], $check_flag = TRUE);

  /**
   * Load a specific sitemap link from the database.
   *
   * @param string $entity_type
   *   A string with the entity type id.
   * @param string $entity_id
   *   Entity ID.
   *
   * @return array
   *   A sitemap link (array) or FALSE if the conditions were not found.
   */
  public function load($entity_type, $entity_id);

  /**
   * Load sitemap links from the database.
   *
   * @param array $conditions
   *   An array of conditions on the {xmlsitemap} table in the form
   *   'field' => $value.
   *
   * @return array
   *   An array of sitemap link arrays.
   */
  public function loadMultiple(array $conditions = []);

  /**
   * Get a select query for entity XML sitemap link IDs.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string[] $bundles
   *   The entity bundle IDs.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query.
   */
  public function getEntityLinkQuery(string $entity_type_id, array $bundles = []): SelectInterface;

  /**
   * Get an entity query for XML sitemap indexing or querying.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string[] $bundles
   *   The entity bundle IDs.
   * @param null|\Drupal\Core\Database\Query\SelectInterface $subquery
   *   The optional subquery on the xmlsitemap table to match against the
   *   entity ID values.
   * @param string $subquery_operator
   *   The optional subquery operator. Possible values are 'IN' or 'NOT IN'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query object.
   */
  public function getEntityQuery(string $entity_type_id, array $bundles = [], SelectInterface $subquery = NULL, string $subquery_operator = 'IN'): QueryInterface;

}
