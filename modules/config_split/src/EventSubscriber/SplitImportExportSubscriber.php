<?php

namespace Drupal\config_split\EventSubscriber;

use Drupal\config_split\ConfigSplitManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to react to config transformations.
 */
final class SplitImportExportSubscriber implements EventSubscriberInterface {

  /**
   * The manager class which does the heavy lifting.
   *
   * @var \Drupal\config_split\ConfigSplitManager
   */
  protected $manager;

  /**
   * The config factory to load config from.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SplitImportExportSubscriber constructor.
   *
   * @param \Drupal\config_split\ConfigSplitManager $manager
   *   The manager class which does the heavy lifting.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory to load config from.
   */
  public function __construct(ConfigSplitManager $manager, ConfigFactoryInterface $configFactory) {
    $this->manager = $manager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // We get the splits priority from the settings.
    // Unfortunately we can not load the existing splits from the drupal because
    // the subscribed events is compiled into the container in a compiler pass
    // and at that point we can not access the container yet of course.
    // Splits which do not have their priority explicitly set will still be
    // ordered as usual but will be subscribed with priority 0.
    $splits = Settings::get('config_split_priorities', []);

    // The splits not explicitly listed go in the default.
    $exportSubscriptions = [['exportDefaultPriority', 0]];
    $importSubscriptions = [['importDefaultPriority', 0]];
    foreach ($splits as $name => $priority) {
      // Use the priority for exporting.
      $exportSubscriptions[] = ['_exportExplicit_' . $name, $priority];
      // Use the reverse priority for importing.
      $importSubscriptions[] = ['_importExplicit_' . $name, -$priority];
    }

    // Subscribe all the splits mentioned.
    return [
      'config.transform.export' => $exportSubscriptions,
      'config.transform.import' => $importSubscriptions,
    ];
  }

  /**
   * React to the export transformation.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The transformation event.
   */
  public function exportDefaultPriority(StorageTransformEvent $event) {
    foreach ($this->getDefaultPrioritySplitConfigs() as $split) {
      $this->manager->exportTransform($split->get('id'), $event);
    }
  }

  /**
   * React to the import transformation.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The transformation event.
   */
  public function importDefaultPriority(StorageTransformEvent $event) {
    $splits = array_reverse($this->getDefaultPrioritySplitConfigs($event->getStorage()));
    foreach ($splits as $split) {
      $this->manager->importTransform($split->get('id'), $event);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __call($name, $arguments) {
    // Runtime defined methods with the split in its name.
    if (substr($name, 0, strlen('_exportExplicit_')) === '_exportExplicit_') {
      $this->manager->exportTransform(substr($name, strlen('_exportExplicit_')), $arguments[0]);
      return;
    }

    if (substr($name, 0, strlen('_importExplicit_')) === '_importExplicit_') {
      $this->manager->importTransform(substr($name, strlen('_importExplicit_')), $arguments[0]);
      return;
    }

    throw new \BadMethodCallException("No method $name");
  }

  /**
   * Get the split config that was not explicitly set with a priority.
   *
   * @return \Drupal\Core\Config\ImmutableConfig[]
   *   The default priority configs.
   */
  protected function getDefaultPrioritySplitConfigs(?StorageInterface $storage = NULL): array {
    $names = $this->manager->listAll($storage);
    $explicit = Settings::get('config_split_priorities', []);
    if (is_array($explicit)) {
      // Make sure the explicit ones have the full name.
      $explicit = array_map(function ($name) {
        if (strpos($name, 'config_split.config_split.') !== 0) {
          $name = 'config_split.config_split.' . $name;
        }
        return $name;
      }, $explicit);

      $names = array_diff($names, $explicit);
    }

    $splits = $this->manager->loadMultiple($names, $storage);
    uasort($splits, function (ImmutableConfig $a, ImmutableConfig $b) {
      return $a->get('weight') <=> $b->get('weight');
    });

    return $splits;
  }

}
