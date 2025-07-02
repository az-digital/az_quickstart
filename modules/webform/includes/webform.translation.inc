<?php

/**
 * @file
 * Webform module translation hooks.
 *
 * @see webform_preprocess_table()
 */

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Utility\WebformYaml;

/**
 * Implements hook_form_FORM_ID_alter() for language content settings form.
 */
function webform_form_language_content_settings_form_alter(array &$form, FormStateInterface $form_state) {
  // Completely remove webform_submission from Content language admin
  // settings form, only when there are no previously saved
  // 'language.content_settings.webform_submission.*' config files.
  $has_saved_webform_submissions = count(\Drupal::configFactory()->listAll('language.content_settings.webform_submission.')) ? TRUE : FALSE;
  if (!$has_saved_webform_submissions) {
    unset($form['#label']['webform_submission']);
    unset($form['entity_types']['#options']['webform_submission']);
    unset($form['settings']['webform_submission']);
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for locale translate edit form.
 */
function webform_form_locale_translate_edit_form_alter(&$form, FormStateInterface $form_state) {
  // Don't allow YAML to be validated using locale string translation.
  foreach (Element::children($form['strings']) as $key) {
    $element = &$form['strings'][$key];
    if ($element['original']
      && !empty($element['original']['#plain_text'])
      && preg_match("/'#[^']+':/", $element['original']['#plain_text'])
      && WebformYaml::isValid($element['original']['#plain_text'])) {
      $element['original'] = [
        '#theme' => 'webform_codemirror',
        '#code' => $element['original']['#plain_text'],
        '#type' => 'yaml',
      ];
      $element['translations'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => t("Webforms can only be translated via the Webform's (Configuration) Translate tab."),
      ];
    }
  }
}

/* ************************************************************************** */
// Configuration translation.
/* ************************************************************************** */

/**
 * Implements hook_form_FORM_ID_alter() for config translation add form.
 */
function webform_form_config_translation_add_form_alter(&$form, FormStateInterface $form_state, $is_new = TRUE) {
  /** @var \Drupal\webform\WebformTranslationConfigManagerInterface $translation_config_manager */
  $translation_config_manager = \Drupal::service('webform.translation_config_manager');
  $translation_config_manager->alterForm($form, $form_state);
}

/**
 * Implements hook_form_FORM_ID_alter() for config translation edit form.
 */
function webform_form_config_translation_edit_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformTranslationConfigManagerInterface $translation_config_manager */
  $translation_config_manager = \Drupal::service('webform.translation_config_manager');
  $translation_config_manager->alterForm($form, $form_state);
}

/* ************************************************************************** */
// Lingotek integration.
/* ************************************************************************** */

/**
 * Implements hook_lingotek_config_entity_document_upload().
 */
function webform_lingotek_config_entity_document_upload(array &$source_data, ConfigEntityInterface &$entity, &$url) {
  /** @var \Drupal\webform\WebformTranslationLingotekManagerInterface $translation_lingotek_manager */
  $translation_lingotek_manager = \Drupal::service('webform.translation_lingotek_manager');
  $translation_lingotek_manager->configEntityDocumentUpload($source_data, $entity, $url);
}

/**
 * Implements hook_lingotek_config_entity_translation_presave().
 */
function webform_lingotek_config_entity_translation_presave(ConfigEntityInterface &$translation, $langcode, &$data) {
  /** @var \Drupal\webform\WebformTranslationLingotekManagerInterface $translation_lingotek_manager */
  $translation_lingotek_manager = \Drupal::service('webform.translation_lingotek_manager');
  $translation_lingotek_manager->configEntityTranslationPresave($translation, $langcode, $data);
}

/**
 * Implements hook_lingotek_config_object_document_upload().
 */
function webform_lingotek_config_object_document_upload(array &$data, $config_name) {
  /** @var \Drupal\webform\WebformTranslationLingotekManagerInterface $translation_lingotek_manager */
  $translation_lingotek_manager = \Drupal::service('webform.translation_lingotek_manager');
  $translation_lingotek_manager->configObjectDocumentUpload($data, $config_name);
}

/**
 * Implements hook_lingotek_config_object_translation_presave().
 */
function webform_lingotek_config_object_translation_presave(array &$data, $config_name) {
  /** @var \Drupal\webform\WebformTranslationLingotekManagerInterface $translation_lingotek_manager */
  $translation_lingotek_manager = \Drupal::service('webform.translation_lingotek_manager');
  $translation_lingotek_manager->configObjectTranslationPresave($data, $config_name);
}
