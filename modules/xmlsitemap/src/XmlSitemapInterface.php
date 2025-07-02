<?php

namespace Drupal\xmlsitemap;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a XmlSitemap entity.
 */
interface XmlSitemapInterface extends ConfigEntityInterface {

  /**
   * Returns the sitemap id.
   *
   * @return string
   *   The sitemap id.
   */
  public function getId();

  /**
   * Returns the sitemap chunks number.
   *
   * @return int|null
   *   The chunks number.
   */
  public function getChunks();

  /**
   * Returns the sitemap links number.
   *
   * @return int|null
   *   The links number.
   */
  public function getLinks();

  /**
   * Returns the sitemap maximum file size.
   *
   * @return int|null
   *   The maximum file size.
   */
  public function getMaxFileSize();

  /**
   * Returns the sitemap context.
   *
   * @return array
   *   The context.
   */
  public function getContext();

  /**
   * Returns the timestamp of when the sitemap was last updated.
   *
   * @return int|null
   *   The timestamp.
   */
  public function getUpdated();

  /**
   * Sets the id of the sitemap.
   *
   * @param string $id
   *   The sitemap id.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The class instance that this method is called on.
   */
  public function setId($id);

  /**
   * Sets the label of the sitemap.
   *
   * @param string $label
   *   The sitemap label.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The class instance that this method is called on.
   */
  public function setLabel($label);

  /**
   * Sets the number of chunks.
   *
   * @param int $chunks
   *   The number of chunks.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The class instance that this method is called on.
   */
  public function setChunks(int $chunks);

  /**
   * Sets the number of links.
   *
   * @param int $links
   *   The number of links.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The class instance that this method is called on.
   */
  public function setLinks(int $links);

  /**
   * Sets the maximum file size of the sitemap.
   *
   * @param string $max_filesize
   *   The maximum file size.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The class instance that this method is called on.
   */
  public function setMaxFileSize(int $max_filesize);

  /**
   * Sets the context for the sitemap.
   *
   * @param string $context
   *   The context.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The class instance that this method is called on.
   */
  public function setContext($context);

  /**
   * Sets the timestamp of when the sitemap was updated.
   *
   * @param int $updated
   *   The timestamp.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   The class instance that this method is called on.
   */
  public function setUpdated(int $updated);

  /**
   * Returns the sitemap with the context specified as parameter.
   *
   * @param array $context
   *   An optional XML Sitemap context array to use to find the correct XML
   *   sitemap. If not provided, the current site's context will be used.
   *
   * @return \Drupal\xmlsitemap\XmlSitemapInterface
   *   Sitemap with the specified context or NULL.
   */
  public static function loadByContext(array $context = NULL);

  /**
   * Save the state information about the sitemap.
   */
  public function saveState(): void;

}
