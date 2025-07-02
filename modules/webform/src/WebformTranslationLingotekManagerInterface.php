<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for webform Lingotek translation classes.
 */
interface WebformTranslationLingotekManagerInterface {

  /**
   * Implements hook_lingotek_config_entity_document_upload().
   *
   * @param array &$source_data
   *   The data that will be uploaded, as an associative array.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface &$entity
   *   The config entity where the data is extracted from and will be associated
   *   to the Lingotek document.
   * @param string &$url
   *   The url which will be associated to this document, e.g. for context review.
   *
   * @see hook_lingotek_config_entity_document_upload()
   */
  public function configEntityDocumentUpload(array &$source_data, ConfigEntityInterface &$entity, &$url);

  /**
   * Implements hook_lingotek_config_entity_translation_presave().
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface &$translation
   *   The config entity that is going to be saved.
   * @param string $langcode
   *   Drupal language code that has been downloaded.
   * @param array &$data
   *   Data returned from the Lingotek service when asking for the translation.
   *
   * @see hook_lingotek_config_entity_translation_presave()
   */
  public function configEntityTranslationPresave(ConfigEntityInterface &$translation, $langcode, array &$data);

  /**
   * Implements hook_lingotek_config_object_document_upload().
   *
   * @param array &$data
   *   Data returned from the Lingotek service when asking for the translation.
   * @param string $config_name
   *   The simple configuration name.
   *
   * @see hook_lingotek_config_object_document_upload()
   */
  public function configObjectDocumentUpload(array &$data, $config_name);

  /**
   * Implements hook_lingotek_config_object_translation_presave().
   *
   * @param array &$data
   *   Data returned from the Lingotek service when asking for the translation.
   * @param string $config_name
   *   The simple configuration name.
   *
   * @see hook_lingotek_config_object_translation_presave()
   */
  public function configObjectTranslationPresave(array &$data, $config_name);

}
