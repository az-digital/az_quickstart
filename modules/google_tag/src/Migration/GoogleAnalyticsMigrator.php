<?php

namespace Drupal\google_tag\Migration;

/**
 * Google Analytics Migration service.
 */
class GoogleAnalyticsMigrator extends GoogleTagMigrateBase {

  /**
   * Migrates google analytics 4.x config object.
   *
   * To Google Tag 2.x default container entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function migrateGaToGoogleTag(): void {
    $ga_settings = $this->configFactory->get('google_analytics.settings');
    $accounts = $ga_settings->get('account');
    $metrics_dimensions = $ga_settings->get('custom.parameters') ?: [];
    if ($accounts === '' && $metrics_dimensions === []) {
      return;
    }
    $accounts = explode(',', $accounts);
    $default_id = reset($accounts);
    // Set the ID and Label based on the first Google Tag.
    $config_id = uniqid($default_id . '.', TRUE);

    // Conditions.
    $condition_definitions = $this->conditionManager->getDefinitions();
    $conditions = [];

    // Request paths.
    $request_paths = $ga_settings->get('visibility.request_path_pages');
    $visibility_path_mode = $ga_settings->get('visibility.request_path_mode');

    $request_path_plugin = 'request_path';
    $request_negate = $visibility_path_mode === 0;
    if ($request_paths !== '' && isset($condition_definitions[$request_path_plugin])) {
      $conditions[$request_path_plugin] = static::getRequestPathCondition($request_paths, $request_negate);
    }

    // User roles.
    $visibility_user_role_mode = $ga_settings->get('visibility.user_role_mode');
    $roles = $ga_settings->get('visibility.user_role_roles');
    $roles_plugin = 'user_role';
    $roles_negate = $visibility_user_role_mode === 1;
    if ($roles !== [] && isset($condition_definitions[$roles_plugin])) {
      $conditions[$roles_plugin] = static::getUserRoleCondition($roles, $roles_negate);
    }

    $tag_container = $this->entityTypeManager->getStorage('google_tag_container')->create([
      'id' => $config_id,
      'label' => $default_id,
      'tag_container_ids' => $accounts,
      'dimensions_metrics' => array_values($metrics_dimensions),
      'events' => $this->getDefaultEventData(),
      'conditions' => $conditions,
    ]);
    $tag_container->save();

    $gtag_settings = $this->configFactory->getEditable('google_tag.settings');
    $gtag_settings->set('default_google_tag_entity', $tag_container->id())->save();
  }

}
