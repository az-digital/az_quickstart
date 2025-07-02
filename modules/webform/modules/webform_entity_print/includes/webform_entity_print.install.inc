<?php

/**
 * @file
 * Webform Entity Print install helper functions.
 */

/**
 * Add new 'Download PDF' submission action.
 */
function _webform_entity_print_update_add_new_download_pdf_submission_action() {
  $config = \Drupal::configFactory()->getEditable('webform.settings');
  $actions = $config->get('settings.webform_submission_bulk_form_actions') ?: [];
  $actions[] = 'webform_submission_print_download_action';
  $config->set('settings.webform_submission_bulk_form_actions', array_unique($actions))->save();
}
