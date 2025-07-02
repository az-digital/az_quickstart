<?php

namespace Drupal\google_tag\Migration;

use Drupal\google_tag\Entity\TagContainer;

/**
 * Google tag upgrade service.
 *
 * To upgrade container entities from 1.x to 2.x.
 */
class GoogleTagUpgradeManager extends GoogleTagMigrateBase {

  /**
   * Upgrades google tag entities from 1.x to 2.x.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function upgradeGoogleTagEntities(): void {
    $storage = $this->entityTypeManager->getStorage('google_tag_container');
    $entity_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->sort('weight')
      ->execute();
    $gtag_settings = $this->configFactory->getEditable('google_tag.settings');
    $default_entity = $gtag_settings->get('default_google_tag_entity') ?? '';
    $use_collection = $gtag_settings->get('use_collection') ?? FALSE;
    if ($entity_ids) {
      /** @var \Drupal\google_tag\Entity\TagContainer[] $entities */
      $entities = $storage->loadMultiple($entity_ids);
      $use_collection = count($entities) > 1;
      foreach ($entities as $entity) {
        // First iteration.
        if ($default_entity === '') {
          $default_entity = $entity->id();
        }
        // Skip if it's a new entity.
        if ($entity->get('tag_container_ids') !== []) {
          continue;
        }
        // Convert container_id string into tag_container_ids array.
        $entity->set('tag_container_ids', [$entity->get('container_id')]);

        // Convert advanced settings from old to new format.
        $this->convertGtmSettings($entity);

        // Convert conditions.
        $this->convertConditions($entity);

        // Configure events.
        $events = $entity->get('events');
        if ($events === []) {
          $entity->set('events', $this->getDefaultEventData());
        }
        $entity->save();
      }
    }
    $new_gtag_settings['default_google_tag_entity'] = $default_entity;
    $new_gtag_settings['use_collection'] = $use_collection;
    $gtag_settings->setData($new_gtag_settings)->save();
  }

  /**
   * Converts gtm settings for google tag entity.
   *
   * @param \Drupal\google_tag\Entity\TagContainer $entity
   *   Google tag entity.
   */
  protected function convertGtmSettings(TagContainer $entity): void {
    $gtm_settings = [];
    $gtm_id = $entity->getGtmId();
    $gtm_settings['data_layer'] = $entity->get('data_layer') ?? 'dataLayer';
    $gtm_settings['include_classes'] = $entity->get('include_classes') ?? FALSE;
    $gtm_settings['allowlist_classes'] = $entity->get('whitelist_classes') ?? '';
    $gtm_settings['blocklist_classes'] = $entity->get('blacklist_classes') ?? '';
    $gtm_settings['include_environment'] = $entity->get('include_environment') ?? FALSE;
    $gtm_settings['environment_id'] = $entity->get('environment_id') ?? '';
    $gtm_settings['environment_token'] = $entity->get('environment_token') ?? '';
    $entity->set('advanced_settings', ['gtm' => [$gtm_id => $gtm_settings]]);
  }

  /**
   * Converts conditions for google tag entity.
   *
   * @param \Drupal\google_tag\Entity\TagContainer $entity
   *   Google tag entity.
   */
  protected function convertConditions(TagContainer $entity): void {
    // Convert conditions.
    // Get saved conditions.
    $old_conditions = $entity->get('conditions');
    $conditions = [];
    $condition_definitions = $this->conditionManager->getDefinitions();
    $negate_toggle = 'exclude listed';
    foreach ($old_conditions as $condition_id => $condition_config) {
      // Only add to the config if condition plugin is available.
      if (isset($condition_definitions[$condition_id])) {
        $conditions[$condition_id] = $condition_config;
        continue;
      }
      // Convert custom gtag_language into language condition.
      if ($condition_id === 'gtag_language') {
        $gtag_language_config = $old_conditions['gtag_language'] ?? [];
        $language_plugin = 'language';
        $language_negate = $gtag_language_config['language_toggle'] === $negate_toggle;
        $langcodes = $gtag_language_config['language_list'] ?? [];
        $langcodes = array_combine($langcodes, $langcodes);
        $language_config = [
          'id' => $language_plugin,
          'langcodes' => $langcodes,
          'negate' => $language_negate,
        ];
        $language_config['context_mapping'] = $gtag_language_config['context_mapping'];
        $conditions[$language_plugin] = $language_config;
      }
    }
    // Convert roles, request paths, status code
    // custom conditions from 1.x to actual condition plugins.
    // Request path custom condition.
    $request_negate = $entity->get('path_toggle') === $negate_toggle;
    $request_paths = $entity->get('path_list');
    $request_path_plugin = 'request_path';
    if ($request_paths !== '' && isset($condition_definitions[$request_path_plugin])) {
      $conditions[$request_path_plugin] = static::getRequestPathCondition($request_paths, $request_negate);
    }
    // Response code custom condition.
    $response_code_negate = $entity->get('status_toggle') === $negate_toggle;
    $response_codes = $entity->get('status_list');
    $response_code_plugin = 'response_code';
    if ($response_codes !== '' && isset($condition_definitions[$response_code_plugin])) {
      $response_code_config = [
        'id' => $response_code_plugin,
        'response_codes' => $response_codes,
        'negate' => $response_code_negate,
      ];
      $conditions[$response_code_plugin] = $response_code_config;
    }
    // Roles custom condition.
    $roles_negate = $entity->get('role_toggle') === $negate_toggle;
    $roles = $entity->get('role_list');
    $roles_plugin = 'user_role';
    if ($roles !== [] && isset($condition_definitions[$roles_plugin])) {
      $conditions[$roles_plugin] = static::getUserRoleCondition($roles, $roles_negate);
    }
    $entity->set('conditions', $conditions);
  }

}
