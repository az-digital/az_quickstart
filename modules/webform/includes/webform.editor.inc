<?php

/**
 * @file
 * Webform module editor file upload hooks.
 *
 * Because Webforms are config entities the editor.module's file uploading
 * is not supported.
 *
 * The below code adds file upload support to Webform config entities and
 * 'webform.settings' config.
 *
 * Below functions are copied from editor.module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\WebformInterface;

/**
 * Implements hook_ENTITY_TYPE_access().
 */
function webform_filter_format_access(FilterFormatInterface $filter_format, $operation, AccountInterface $account) {
  if ($filter_format->id() === WebformHtmlEditor::DEFAULT_FILTER_FORMAT
    && $operation !== 'use') {
    return AccessResult::forbidden('This text format can only be managed by the Webform module.');
  }

  return AccessResult::neutral();
}

/**
 * Implements hook_ENTITY_TYPE_load().
 */
function webform_filter_format_load($entities) {
  foreach ($entities as $entity) {
    // Hide the default webform filter format on the filter admin overview page.
    if ($entity->id() === WebformHtmlEditor::DEFAULT_FILTER_FORMAT
      && \Drupal::routeMatch()->getRouteName() === 'filter.admin_overview') {
      // Set status to FALSE which hides the default webform filter format.
      $entity->set('status', FALSE);
    }
  }
}

/**
 * Implements hook_editor_js_settings_alter().
 */
function webform_editor_js_settings_alter(array &$settings) {
  $settings['editor']['formats'][WebformHtmlEditor::DEFAULT_FILTER_FORMAT]['editorSettings']['autoGrow_minHeight'] = '80';
}

/* ************************************************************************** */
// Webform entity hooks.
/* ************************************************************************** */

/**
 * Implements hook_webform_insert().
 *
 * @see editor_entity_insert()
 */
function webform_webform_insert(WebformInterface $webform) {
  $uuids = _webform_get_config_entity_file_uuids($webform);
  _webform_record_file_usage($uuids, $webform->getEntityTypeId(), $webform->id());
}

/**
 * Implements hook_webform_update().
 *
 * @see editor_entity_update()
 */
function webform_webform_update(WebformInterface $webform) {
  $original_uuids = _webform_get_config_entity_file_uuids($webform->original);
  $uuids = _webform_get_config_entity_file_uuids($webform);

  // Detect file usages that should be incremented.
  $added_files = array_diff($uuids, $original_uuids);
  _webform_record_file_usage($added_files, $webform->getEntityTypeId(), $webform->id());

  // Detect file usages that should be decremented.
  $removed_files = array_diff($original_uuids, $uuids);
  _webform_delete_file_usage($removed_files, $webform->getEntityTypeId(), $webform->id(), 1);
}

/**
 * Implements hook_webform_delete().
 *
 * @see editor_entity_delete()
 */
function webform_webform_delete(WebformInterface $webform) {
  $uuids = _webform_get_config_entity_file_uuids($webform);
  _webform_delete_file_usage($uuids, $webform->getEntityTypeId(), $webform->id(), 0);
}

/* ************************************************************************** */
// Webform config (settings) hooks.
// @see \Drupal\webform\Form\AdminConfig\WebformAdminConfigBaseForm::loadConfig
// @see \Drupal\webform\Form\AdminConfig\WebformAdminConfigBaseForm::saveConfig
/* ************************************************************************** */

/**
 * Update config editor file references.
 *
 * @param \Drupal\Core\Config\Config $config
 *   An editable configuration object.
 */
function _webform_config_update(Config $config) {
  $original_uuids = _webform_get_array_file_uuids($config->getOriginal());
  $uuids = _webform_get_array_file_uuids($config->getRawData());

  // Detect file usages that should be incremented.
  $added_files = array_diff($uuids, $original_uuids);
  _webform_record_file_usage($added_files, 'config', $config->getName());

  // Detect file usages that should be decremented.
  $removed_files = array_diff($original_uuids, $uuids);
  _webform_delete_file_usage($removed_files, 'config', $config->getName(), 1);
}

/**
 * Delete config editor file references.
 *
 * @param \Drupal\Core\Config\Config $config
 *   An editable configuration object.
 *
 * @see webform_uninstall()
 */
function _webform_config_delete(Config $config) {
  $uuids = _webform_get_array_file_uuids($config->getRawData());
  _webform_delete_file_usage($uuids, 'config', $config->getName(), 0);
}

/* ************************************************************************** */
// Config entity functions.
/* ************************************************************************** */

/**
 * Finds all files referenced (data-entity-uuid) by config entity.
 *
 * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
 *   An entity whose fields to analyze.
 *
 * @return array
 *   An array of file entity UUIDs.
 *
 * @see _editor_get_file_uuids_by_field()
 */
function _webform_get_config_entity_file_uuids(ConfigEntityInterface $entity) {
  return _webform_get_array_file_uuids($entity->toArray());
}

/* ************************************************************************** */
// Config settings functions.
/* ************************************************************************** */

/**
 * Finds all files referenced (data-entity-uuid) in an associative array.
 *
 * @param array $data
 *   An associative array.
 *
 * @return array
 *   An array of file entity UUIDs.
 *
 * @see _editor_get_file_uuids_by_field()
 */
function _webform_get_array_file_uuids(array $data) {
  $text = Yaml::encode($data);
  return _webform_parse_file_uuids($text);
}

/* ************************************************************************** */
// File usage functions.
/* ************************************************************************** */

/**
 * Records file usage of files referenced by formatted text fields.
 *
 * Every referenced file that does not yet have the FILE_STATUS_PERMANENT state,
 * will be given that state.
 *
 * @param array $uuids
 *   An array of file entity UUIDs.
 * @param string $type
 *   The type of the object that contains the referenced file.
 * @param string $id
 *   The unique ID of the object containing the referenced file.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 *
 * @see _editor_record_file_usage()
 */
function _webform_record_file_usage(array $uuids, $type, $id) {
  if (empty($uuids) || !\Drupal::moduleHandler()->moduleExists('file')) {
    return;
  }

  /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
  $entity_repository = \Drupal::service('entity.repository');

  /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
  $file_usage = \Drupal::service('file.usage');

  foreach ($uuids as $uuid) {
    if ($file = $entity_repository->loadEntityByUuid('file', $uuid)) {
      if ($file->status !== FileInterface::STATUS_PERMANENT) {
        $file->status = FileInterface::STATUS_PERMANENT;
        $file->save();
      }
      $file_usage->add($file, 'editor', $type, $id);
    }
  }
}

/**
 * Deletes file usage of files referenced by formatted text fields.
 *
 * @param array $uuids
 *   An array of file entity UUIDs.
 * @param string $type
 *   The type of the object that contains the referenced file.
 * @param string $id
 *   The unique ID of the object containing the referenced file.
 * @param int $count
 *   The number of references to delete. Should be 1 when deleting a single
 *   revision and 0 when deleting an entity entirely.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 *
 * @see \Drupal\file\FileUsage\FileUsageInterface::delete()
 * @see _editor_delete_file_usage()
 */
function _webform_delete_file_usage(array $uuids, $type, $id, $count) {
  if (empty($uuids) || !\Drupal::moduleHandler()->moduleExists('file')) {
    return;
  }

  /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
  $entity_repository = \Drupal::service('entity.repository');

  /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
  $file_usage = \Drupal::service('file.usage');

  $make_unused_managed_files_temporary = \Drupal::config('webform.settings')->get('html_editor.make_unused_managed_files_temporary');
  foreach ($uuids as $uuid) {
    if ($file = $entity_repository->loadEntityByUuid('file', $uuid)) {
      $file_usage->delete($file, 'editor', $type, $id, $count);
      // Make unused files temporary.
      if ($make_unused_managed_files_temporary && empty($file_usage->listUsage($file)) && !$file->isTemporary()) {
        $file->setTemporary();
        $file->save();
      }
    }
  }
}

/* ************************************************************************** */
// File parsing functions.
/* ************************************************************************** */

/**
 * Parse text for any linked files with data-entity-uuid attributes.
 *
 * @param string $text
 *   The text to parse.
 *
 * @return array
 *   An array of all found UUIDs.
 *
 * @see _editor_parse_file_uuids()
 */
function _webform_parse_file_uuids($text) {
  if (strpos($text, 'data-entity-uuid') === FALSE) {
    return [];
  }

  $uuids = [];

  // Look through all images and hyperlinks for files.
  if (preg_match_all('/<[^>]+data-entity-type[^>]+>/', $text, $matches)) {
    foreach ($matches[0] as $match) {
      // Cleanup quotes escaped via YAML.
      // Please note, calling stripslashes() twice because elements are
      // double escaped.
      $match = stripslashes(stripslashes($match));

      // Look for a file and record UUID when found.
      $dom = Html::load($match);
      $xpath = new \DOMXPath($dom);
      $nodes = $xpath->query('//*[@data-entity-type="file" and @data-entity-uuid]');
      if (count($nodes) && $nodes->item(0)) {
        $uuids[] = $nodes->item(0)->getAttribute('data-entity-uuid');
      }
    }
  }

  // Use array_unique() to collect one uuid per uploaded file.
  // This prevents cut-n-pasted uploaded files from having multiple usages.
  return array_unique($uuids);
}
