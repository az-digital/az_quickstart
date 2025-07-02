<?php

namespace Drupal\extlink\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an external endpoint from which extlink settings JS can be loaded.
 */
class ExternalSettingsJsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = parent::create($container);
    $controller->configFactory = $container->get('config.factory');
    $controller->moduleHandler = $container->get('module_handler');

    return $controller;
  }

  /**
   * Creates response for external settings JS file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function extlinkJsFile() {
    $config = $this->configFactory->get('extlink.settings');
    $settings = _extlink_get_settings_from_config($config);

    // Need to double backslashes to escape the JS string literal.
    $settings_json = str_replace('\\', '\\\\', Json::encode($settings));
    $js = <<< EOT
(function (drupalSettings) {

  'use strict';

  drupalSettings.data = drupalSettings.data || {};

  drupalSettings.data.extlink = JSON.parse('$settings_json');

})(drupalSettings);
EOT;

    return (new CacheableResponse($js, 200, [
      'Content-Type' => 'application/javascript',
    ]))->addCacheableDependency($config);
  }

  /**
   * Checks access external extlink JS file.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    // Access is strictly controlled by whether module is configured to use an
    // external JS file.
    $config = $this->configFactory->get('extlink.settings');
    return AccessResult::allowedIf($config->get('extlink_use_external_js_file'))
      ->addCacheableDependency($config);
  }

}
