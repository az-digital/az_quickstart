<?php

namespace Drupal\webform\Commands;

use Drupal\webform\Entity\Webform;
use Drush\Commands\DrushCommands;

/**
 * Webform commands for Drush 9.x and 10.x.
 */
abstract class WebformCommandsBase extends DrushCommands {

  /**
   * JSON encoding flags.
   */
  const JSON_ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

  /**
   * Validate webform_id argument and source entity-type and entity-id options.
   *
   * @param string $webform
   *   THe webform id being validated.
   */
  protected function validateWebform($webform = NULL) {
    $webform = $webform ?? $this->input()->getArgument('webform');

    if (empty($webform)) {
      throw new \Exception(dt('Webform id required'));
    }

    if (!Webform::load($webform)) {
      throw new \Exception(dt('Webform @id not recognized.', ['@id' => $webform]));
    }

    $entity_type = $this->input()->getOption('entity-type');
    $entity_id = $this->input()->getOption('entity-id');
    if ($entity_type || $entity_id) {
      if (empty($entity_type)) {
        throw new \Exception(dt('Entity type is required when entity id is specified.'));
      }
      if (empty($entity_id)) {
        throw new \Exception(dt('Entity id is required when entity type is specified.'));
      }

      $dt_args = [
        '@webform_id' => $webform,
        '@entity_type' => $entity_type,
        '@entity_id' => $entity_id,
      ];

      $source_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
      if (!$source_entity) {
        throw new \Exception(dt('Unable to load @entity_type:@entity_id', $dt_args));
      }

      $dt_args['@title'] = $source_entity->label();

      if (empty($source_entity->webform) || empty($source_entity->webform->target_id)) {
        throw new \Exception(dt("'@title' (@entity_type:@entity_id) does not reference a webform.", $dt_args));
      }

      if ($source_entity->webform->target_id !== $webform) {
        throw new \Exception(dt("'@title' (@entity_type:@entity_id) does not have a '@webform_id' webform associated with it.", $dt_args));
      }
    }
  }

}
