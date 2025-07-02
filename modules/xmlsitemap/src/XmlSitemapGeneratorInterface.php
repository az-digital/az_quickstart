<?php

namespace Drupal\xmlsitemap;

/**
 * Provides an interface defining a XmlSitemapGenerator service.
 */
interface XmlSitemapGeneratorInterface {

  /**
   * Given an internal Drupal path, return the alias for the path.
   *
   * This is similar to drupal_get_path_alias(), but designed to fetch all
   * aliases at once so that only one database query is executed instead of
   * severa or possibly thousands during sitemap generation.
   *
   * @param string $path
   *   An internal Drupal path.
   * @param string $language
   *   A language code to use when looking up the paths.
   */
  public function getPathAlias($path, $language);

  /**
   * Perform operations before rebuilding the sitemap.
   */
  public function regenerateBefore();

  /**
   * Get how much memory was used.
   *
   * @param bool $start
   *   Start value.
   *
   * @return int
   *   Used memory.
   */
  public function getMemoryUsage($start = FALSE);

  /**
   * Calculate the optimal PHP memory limit for sitemap generation.
   *
   * This function just makes a guess. It does not take into account
   * the currently loaded modules.
   *
   * @return int
   *   Optimal memory limit.
   */
  public function getOptimalMemoryLimit();

  /**
   * Calculate the optimal memory level for sitemap generation.
   *
   * @param int $new_limit
   *   An optional PHP memory limit in bytes. If not provided, the value of
   *   getOptimalMemoryLimit() will be used.
   */
  public function setMemoryLimit($new_limit = NULL);

  /**
   * Generate one page (chunk) of the sitemap.
   *
   * @param XmlSitemapInterface $sitemap
   *   An unserialized data array for an XML Sitemap.
   * @param string $page
   *   An integer of the specific page of the sitemap to generate.
   */
  public function generatePage(XmlSitemapInterface $sitemap, $page);

  /**
   * Generates one chunk of the sitemap.
   *
   * @param \Drupal\xmlsitemap\XmlSitemapInterface $sitemap
   *   The XML Sitemap config entity.
   * @param \Drupal\xmlsitemap\XmlSitemapWriter $writer
   *   XML writer object.
   * @param int $chunk
   *   Chunk value.
   */
  public function generateChunk(XmlSitemapInterface $sitemap, XmlSitemapWriter $writer, $chunk);

  /**
   * Generate the index sitemap.
   *
   * @param \Drupal\xmlsitemap\XmlSitemapInterface $sitemap
   *   The XML Sitemap config entity.
   * @param int|null $pages
   *   The number of pages to write in the sitemap. Defaults to the value of
   *   $sitemap->getChunks().
   */
  public function generateIndex(XmlSitemapInterface $sitemap, $pages = NULL);

  /**
   * Batch callback; generate all pages of a sitemap.
   *
   * @param string $smid
   *   Sitemap id.
   * @param array|\ArrayAccess $context
   *   Sitemap context.
   */
  public function regenerateBatchGenerate($smid, &$context);

  /**
   * Batch callback; generate the index page of a sitemap.
   *
   * @param string $smid
   *   Sitemap id.
   * @param array|\ArrayAccess $context
   *   Sitemap context.
   */
  public function regenerateBatchGenerateIndex($smid, &$context);

  /**
   * Batch callback; sitemap regeneration finished.
   *
   * @param bool $success
   *   Checks if regeneration batch process was successful.
   * @param array $results
   *   Results for the regeneration process.
   * @param array $operations
   *   Operations performed.
   * @param int $elapsed
   *   Elapsed.
   *   Time elapsed.
   */
  public function regenerateBatchFinished($success, array $results, array $operations, $elapsed);

  /**
   * Batch callback; clear sitemap links for entites.
   *
   * @param array $entity_type_ids
   *   Entity types to rebuild.
   * @param bool $save_custom
   *   Save custom data.
   * @param array|\ArrayAccess $context
   *   Context to be rebuilt.
   */
  public function rebuildBatchClear(array $entity_type_ids, $save_custom, &$context);

  /**
   * Batch callback; fetch and add the sitemap links for a specific entity.
   *
   * @param string $entity_type_id
   *   Entity type to be rebuilt.
   * @param array|\ArrayAccess $context
   *   Context to be rebuilt.
   */
  public function rebuildBatchFetch($entity_type_id, &$context);

  /**
   * Batch callback; sitemap rebuild finished.
   *
   * @param bool $success
   *   Checks if regeneration batch process was successful.
   * @param array $results
   *   Results for the regeneration process.
   * @param array $operations
   *   Operations performed.
   * @param int $elapsed
   *   Elapsed.
   *   Time elapsed.
   */
  public function rebuildBatchFinished($success, array $results, array $operations, $elapsed);

  /**
   * Set variables during the batch process.
   *
   * @param array $variables
   *   Variables to be set.
   */
  public function batchVariableSet(array $variables);

}
