<?php

namespace Drupal\extlink\EventSubscriber;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears JS and asset libraries in response to changes in extlink settings.
 */
class ExtlinkSettingsSaveEventSubscriber implements EventSubscriberInterface {

  /**
   * ExtlinkSettingsSaveEventSubscriber constructor.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The CSS/JS asset library discovery service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $jsOptimizer
   *   The JS asset optimizer service.
   * @param \Drupal\Core\Asset\AssetQueryStringInterface $assetQueryString
   *   The asset query string service.
   */
  public function __construct(
    protected LibraryDiscoveryInterface $libraryDiscovery,
    protected AssetCollectionOptimizerInterface $jsOptimizer,
    protected AssetQueryStringInterface $assetQueryString,
  ) {
  }

  /**
   * Acts on changes to extlink settings to flush JS library and assets.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $config = $event->getConfig();
    if ($config->getName() === 'extlink.settings') {
      $flush_js_files = $config->get('extlink_use_external_js_file');

      if ($event->isChanged('extlink_use_external_js_file')) {
        // When using external JS file is enabled or disabled, need to flush the
        // library discovery cache to update the dependencies of drupal.extlink
        // library.
        $this->libraryDiscovery->clearCachedDefinitions();
        $flush_js_files = TRUE;
      }

      if ($flush_js_files) {
        // Flush the optimized JS files if using an external JS file when the
        // settings are saved. Also flush the optimized JS files when disabling
        // or enabling using the external JS files.
        $this->jsOptimizer->deleteAll();
        $this->assetQueryString->reset();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::SAVE => 'onConfigSave'];
  }

}
