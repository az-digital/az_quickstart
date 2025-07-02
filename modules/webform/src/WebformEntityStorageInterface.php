<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for Webform storage.
 */
interface WebformEntityStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Get all webform ids.
   *
   * @return array
   *   An array containing all webform ids.
   */
  public function getWebformIds();

  /**
   * Gets the names of all categories.
   *
   * @param null|bool $template
   *   If TRUE only template categories will be returned.
   *   If FALSE only webform categories will be returned.
   *   If NULL all categories will be returned.
   * @param bool $default
   *   If TRUE append default webform categories.
   *
   * @return string[]
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories($template = NULL, $default = FALSE);

  /**
   * Resets the internal, categories cache.
   */
  public function resetCategoriesCache();

  /**
   * Get all webforms grouped by category.
   *
   * @param null|bool $template
   *   If TRUE only template categories will be returned.
   *   If FALSE only webform categories will be returned.
   *   If NULL all categories will be returned.
   *
   * @return string[]
   *   An array of options grouped by category.
   */
  public function getOptions($template = NULL);

  /**
   * Returns the next serial number.
   *
   * @return int
   *   The next serial number.
   */
  public function getNextSerial(WebformInterface $webform);

  /**
   * Set the next serial number.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param int $next_serial
   *   The next serial number.
   */
  public function setNextSerial(WebformInterface $webform, $next_serial);

  /**
   * Returns the next serial number for a webform's submission.
   *
   * @return int
   *   The next serial number for a webform's submission.
   */
  public function getSerial(WebformInterface $webform);

  /**
   * Returns a webform's max serial number.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int
   *   The next serial number.
   */
  public function getMaxSerial(WebformInterface $webform);

  /**
   * Get total number of results for specified webform or all webforms.
   *
   * @param string|null $webform_id
   *   (optional) A webform id.
   *
   * @return array|int
   *   If no webform id is passed, an associative array keyed by webform id
   *   contains total results for all webforms, otherwise the total number of
   *   results for specified webform
   */
  public function getTotalNumberOfResults($webform_id = NULL);

}
