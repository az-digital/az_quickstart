<?php

declare(strict_types=1);

namespace Drupal\config_ignore\EventSubscriber;

use Drupal\config_ignore\ConfigIgnoreConfig;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Makes the import/export aware of ignored configs.
 */
class ConfigIgnoreEventSubscriber implements EventSubscriberInterface, CacheTagsInvalidatorInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The sync config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;

  /**
   * Statically cached ignored config patterns from hooks.
   *
   * @var \Drupal\config_ignore\ConfigIgnoreConfig|null
   */
  protected $hookList = NULL;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config active storage.
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The sync config storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StorageInterface $config_storage, StorageInterface $sync_storage) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->activeStorage = $config_storage;
    $this->syncStorage = $sync_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    // Invalidate static cache if config changes.
    if (in_array('config:config_ignore.settings', $tags, TRUE)) {
      $this->hookList = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
    return [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform', Settings::get('config_ignore_import_priority', -100)],
      ConfigEvents::STORAGE_TRANSFORM_EXPORT => ['onExportTransform', Settings::get('config_ignore_export_priority', -100)],
    ];
  }

  /**
   * Acts when the storage is transformed for import.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    if (!Settings::get('config_ignore_deactivate')) {
      $this->transformStorage($event->getStorage(), $this->activeStorage, 'import');
    }
  }

  /**
   * Acts when the storage is transformed for export.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    if (!Settings::get('config_ignore_deactivate')) {
      $this->transformStorage($event->getStorage(), $this->syncStorage, 'export');
    }
  }

  /**
   * Makes the import or export storages aware about ignored configs.
   *
   * @param \Drupal\Core\Config\StorageInterface $transformation_storage
   *   The import or the export storage.
   * @param \Drupal\Core\Config\StorageInterface $destination_storage
   *   The active storage on import. The sync storage on export.
   * @param string $direction
   *   The direction of the transformation.
   */
  protected function transformStorage(StorageInterface $transformation_storage, StorageInterface $destination_storage, string $direction) {
    $collection_names = array_unique(array_merge($transformation_storage->getAllCollectionNames(), $destination_storage->getAllCollectionNames()));
    array_unshift($collection_names, StorageInterface::DEFAULT_COLLECTION);

    // Get the config ignore settings form the transformation storage.
    $transformation_storage = $transformation_storage->createCollection(StorageInterface::DEFAULT_COLLECTION);
    if (empty($transformation_storage->listAll())) {
      // Skip if the transformation storage is empty in the default collection.
      return;
    }
    if ($transformation_storage->exists('config_ignore.settings')) {
      try {
        // This can be used to hook into config ignore via an event subscriber
        // which alters the data prior to the config ignore subscriber.
        $settings = $transformation_storage->read('config_ignore.settings');
        $ignoreConfig = new ConfigIgnoreConfig($settings['mode'] ?? 'simple', $settings['ignored_config_entities'] ?? []);
      }
      catch (\InvalidArgumentException $exception) {
        // We should probably log this exception.
      }
    }
    if (!isset($ignoreConfig)) {
      $ignoreConfig = ConfigIgnoreConfig::fromConfig($this->configFactory->get('config_ignore.settings'));
    }
    $ignoreConfig->mergeWith($this->getHookList());
    // @todo decide if we really need to invoke an alter hook here.
    $this->moduleHandler->alter('config_ignore_ignored', $ignoreConfig);

    // Loop over all collections.
    foreach ($collection_names as $collection_name) {
      $destination_storage = $destination_storage->createCollection($collection_name);
      $transformation_storage = $transformation_storage->createCollection($collection_name);

      // Treat all configuration from the destination.
      foreach ($destination_storage->listAll() as $config_name) {

        $operation = 'update';
        if (!$transformation_storage->exists($config_name)) {
          // If the transformation storage doesn't have it, it would be removed.
          $operation = 'delete';
        }

        $ignore_create = $ignoreConfig->isIgnored($collection_name, $config_name, $direction, 'create');
        $ignored = $ignoreConfig->isIgnored($collection_name, $config_name, $direction, $operation);
        if ($ignored === FALSE) {
          $other_ignore = $ignoreConfig->isIgnored($collection_name, $config_name, $direction, $operation === 'update' ? 'delete' : 'update');
          if (!is_array($ignore_create) && !is_array($other_ignore)) {
            // This is not the config you are looking for.
            continue;
          }
        }

        $destination_data = $destination_storage->read($config_name);
        if ($ignored === TRUE) {
          // Ignored means the transformation is set to the destination.
          $transformation_storage->write($config_name, $destination_data);
          continue;
        }

        $transformation_data = $transformation_storage->read($config_name);
        if ($operation === 'delete' || $transformation_data === FALSE) {
          // The config doesn't exist in the transformation storage but only a
          // key is ignored, we skip writing anything to the transformation
          // storage. Keys in the deletion case are only useful when updating
          // the config and removing keys.
          // Both conditions should be equivalent, but we guard against the
          // storage having a bug.
          continue;
        }

        // Now we are treating the case where we are updating config but keys
        // are ignored. So we prepare all the keys.
        $ignore_deleted = $ignoreConfig->isIgnored($collection_name, $config_name, $direction, 'delete');
        $parts = [
          'create' => is_array($ignore_create) ? $ignore_create : [],
          'update' => is_array($ignored) ? $ignored : [],
          'delete' => is_array($ignore_deleted) ? $ignore_deleted : [],
        ];

        self::ignoreParts($transformation_data, $destination_data, $parts);
        $transformation_storage->write($config_name, $transformation_data);
      }

      // Config only in the transformation storage is or would be new.
      $new_config = array_diff($transformation_storage->listAll(), $destination_storage->listAll());
      foreach ($new_config as $config_name) {
        $ignored = $ignoreConfig->isIgnored($collection_name, $config_name, $direction, 'create');
        if ($ignored === TRUE) {
          $transformation_storage->delete($config_name);
          continue;
        }
        if (is_array($ignored)) {
          // Only some keys are ignored.
          $transformation_data = $transformation_storage->read($config_name);
          $parts = [
            'create' => $ignored,
            'update' => [],
            'delete' => [],
          ];
          self::ignoreParts($transformation_data, [], $parts);
          $transformation_storage->write($config_name, $transformation_data);
        }

      }
    }
  }

  /**
   * Recursively ignore parts of a config array.
   *
   * @param array $transformation
   *   The transformation data, changed by reference.
   * @param array $destination
   *   The destination data.
   * @param array $parts
   *   The configuration keys.
   * @param string $path
   *   The path into the array.
   */
  protected static function ignoreParts(array &$transformation, array $destination, array $parts, string $path = ''): void {
    // In order to not recurse into branches that are not needed we set the
    // configuration for those to exclude everything.
    $deleteParts = [
      'delete' => $parts['delete'],
      'create' => ['~*'],
      'update' => ['~*'],
    ];
    $createParts = [
      'create' => $parts['create'],
      'delete' => ['~*'],
      'update' => ['~*'],
    ];

    // Items about to be deleted.
    $delete = array_diff(array_keys($destination), array_keys($transformation));
    foreach ($delete as $key) {
      $match = self::matchWildcardPart($path . '.' . $key, $parts['delete'] ?? []);
      if ($match === FALSE) {
        continue;
      }
      if ($match === TRUE) {
        $transformation[$key] = $destination[$key];
        continue;
      }
      foreach ($parts['delete'] as $pattern) {
        if (self::wildcardMatchStart($pattern, $path, $key) && is_array($destination[$key])) {
          // Dive into the array, but don't do the other operations.
          $new = [];
          self::ignoreParts($new, $destination[$key], $deleteParts, $path . '.' . $key);
          if ($new) {
            $transformation[$key] = $new;
          }
          continue 2;
        }
      }
    }

    // Items about to be created.
    $create = array_diff(array_keys($transformation), array_keys($destination));
    foreach ($create as $key) {
      $match = self::matchWildcardPart($path . '.' . $key, $parts['create'] ?? []);
      if ($match === FALSE) {
        continue;
      }
      if ($match === TRUE) {
        unset($transformation[$key]);
        continue;
      }
      foreach ($parts['create'] as $pattern) {
        if (self::wildcardMatchStart($pattern, $path, $key) && is_array($transformation[$key])) {
          // Dive into the array, but don't do the other operations.
          self::ignoreParts($transformation[$key], [], $createParts, $path . '.' . $key);
          continue 2;
        }
      }

    }

    // Items updated.
    $update = array_intersect(array_keys($transformation), array_keys($destination));
    foreach ($update as $key) {
      $match = self::matchWildcardPart($path . '.' . $key, $parts['update'] ?? []);
      if ($match === FALSE) {
        continue;
      }
      if ($match === TRUE) {
        $transformation[$key] = $destination[$key];
        continue;
      }
      $recursed = [];
      foreach ($parts['update'] as $pattern) {
        if (!in_array($key, $recursed) && self::wildcardMatchStart($pattern, $path, $key) && is_array($transformation[$key]) && is_array($destination[$key])) {
          self::ignoreParts($transformation[$key], $destination[$key], $parts, $path . '.' . $key);
          $recursed[] = $key;
        }
      }
      // Check the other ones too. So that the nested elements can be ignored.
      $recursed = [];
      foreach ($parts['create'] as $pattern) {
        if (!in_array($key, $recursed) && self::wildcardMatchStart($pattern, $path, $key) && is_array($transformation[$key]) && is_array($destination[$key])) {
          self::ignoreParts($transformation[$key], $destination[$key], $createParts, $path . '.' . $key);
          $recursed[] = $key;
        }
      }
      $recursed = [];
      foreach ($parts['delete'] as $pattern) {
        if (!in_array($key, $recursed) && self::wildcardMatchStart($pattern, $path, $key) && is_array($transformation[$key]) && is_array($destination[$key])) {
          self::ignoreParts($transformation[$key], $destination[$key], $deleteParts, $path . '.' . $key);
          $recursed[] = $key;
        }
      }
    }

  }

  /**
   * Check if a path matches a list of keys.
   *
   * @param string $path
   *   The config path to check.
   * @param string[] $list
   *   The list of patterns.
   *
   * @return bool|null
   *   Whether it matches.
   */
  protected static function matchWildcardPart($path, array $list) {
    $path = ltrim($path, '.');
    foreach ($list as $item) {
      // Exclusion patterns are sorted first.
      if (/* str_starts_with($item, '~') */ 0 === strncmp($item, '~', \strlen('~')) /* */ && self::wildcardMatchKey(substr($item, 1), $path)) {
        return FALSE;
      }
      if (self::wildcardMatchKey($item, $path)) {
        return TRUE;
      }
    }
    return NULL;
  }

  /**
   * Check if the start of the pattern matches.
   *
   * @param string $pattern
   *   The pattern.
   * @param string $path
   *   The first part of the key.
   * @param mixed $last
   *   The last part of the key.
   *
   * @return bool
   *   Wether the pattern matches.
   */
  protected static function wildcardMatchStart(string $pattern, string $path, $last): bool {
    $parts = explode('.', ltrim($path . '.' . $last, '.'));
    $patternParts = explode('.', ltrim($pattern, '.~'));
    $currentPattern = '';
    $visited = '';
    // Go through the parts and check if they match.
    foreach ($parts as $i => $part) {
      if (!isset($patternParts[$i])) {
        return FALSE;
      }
      $currentPattern = ltrim($visited . '.' . $patternParts[$i], '.');
      $visited = ltrim($visited . '.' . $part, '.');
    }

    return self::wildcardMatchKey($currentPattern, $visited);
  }

  /**
   * Check a key for wild cards.
   *
   * @param string $pattern
   *   The pattern to match.
   * @param string $string
   *   The string to check.
   *
   * @return bool
   *   Whether the pattern matches.
   */
  protected static function wildcardMatchKey(string $pattern, string $string): bool {
    $pattern = '/^' . preg_quote($pattern, '/') . '$/';
    $pattern = str_replace('\*', '.*', $pattern);
    return (bool) preg_match($pattern, $string);
  }

  /**
   * Get the list provided by hooks.
   *
   * @return \Drupal\config_ignore\ConfigIgnoreConfig
   *   The config set by the alter hook.
   */
  protected function getHookList(): ConfigIgnoreConfig {
    if (isset($this->hookList)) {
      return $this->hookList;
    }

    $ignored_configs_patterns = [];
    $this->moduleHandler->alterDeprecated('Use hook_config_ignore_ignored_alter instead', 'config_ignore_settings', $ignored_configs_patterns);
    $ignoreConfig = new ConfigIgnoreConfig('simple', $ignored_configs_patterns);
    $this->hookList = $ignoreConfig;

    return $this->hookList;
  }

}
