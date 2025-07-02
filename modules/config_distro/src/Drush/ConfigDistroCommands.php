<?php

declare(strict_types=1);

namespace Drupal\config_distro\Drush;

use Drupal\config_distro\Event\ConfigDistroEvents;
use Drupal\config_distro\Event\DistroStorageImportEvent;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Error;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Drush integration for the Configuration Distribution module.
 */
final class ConfigDistroCommands extends DrushCommands {

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The merged storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $distroStorage;

  /**
   * Holds the class name for ConfigCommands.
   *
   * @var string
   */
  protected $configCommands;

  /**
   * The event dispatcher to notify the system that the config was imported.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The logger.factory service object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The cache.config service object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $configCache;

  /**
   * The config.manager service object.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The lock service object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The config.typed service object.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $configTyped;

  /**
   * The module_handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module_installer service object.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The theme_handler service object.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The string_translation service object.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The extension.list.module service object.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The extension.list.theme service object.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * Constructs a new ConfigDistroCommands object.
   *
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active config storage used for comparison with the distro storage.
   * @param \Drupal\Core\Config\StorageInterface $distro_storage
   *   The merged storage containing the distribution configuration.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher used to notify other modules about the import.
   * @param \Drupal\Core\Cache\CacheBackendInterface $config_cache
   *   Holds cache.config service object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Holds logger.factory service object.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Holds config.manager service object.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Holds lock service object.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $config_typed
   *   Holds config.typed service object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Holds module_handler service object.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   Holds module_installer service object.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Holds theme_handler service object.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   Holds string_translation service object.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   Holds extension.list.module service object.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list
   *   Holds extension.list.theme service object.
   */
  public function __construct(
    StorageInterface $active_storage,
    StorageInterface $distro_storage,
    EventDispatcherInterface $event_dispatcher,
    CacheBackendInterface $config_cache,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigManagerInterface $config_manager,
    LockBackendInterface $lock,
    TypedConfigManagerInterface $config_typed,
    ModuleHandlerInterface $module_handler,
    ModuleInstallerInterface $module_installer,
    ThemeHandlerInterface $theme_handler,
    TranslationInterface $string_translation,
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list,
  ) {
    parent::__construct();
    $this->activeStorage = $active_storage;
    $this->distroStorage = $distro_storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->configCache = $config_cache;
    $this->loggerFactory = $logger_factory;
    $this->configManager = $config_manager;
    $this->lock = $lock;
    $this->configTyped = $config_typed;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->themeHandler = $theme_handler;
    $this->stringTranslation = $string_translation;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
    if (class_exists("Drush\Drupal\Commands\config\ConfigCommands")) {
      $this->configCommands = "Drush\Drupal\Commands\config\ConfigCommands";
    }
    else {
      $this->configCommands = "Drush\Commands\config\ConfigCommands";
    }
  }

  /**
   * Creates a new instance of the ConfigDistroCommands class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container providing necessary services.
   *
   * @return static
   *   An instance of the ConfigDistroCommands class with injected dependencies.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   *   If any of the required services cannot be found in the container.
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.storage'),
      $container->get('config_distro.storage.distro'),
      $container->get('event_dispatcher'),
      $container->get('cache.config'),
      $container->get('logger.factory'),
      $container->get('config.manager'),
      $container->get('lock'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('string_translation'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme')
    );
  }

  /**
   * Apply configuration updates from the distribution.
   *
   * This command compares the active configuration with the distribution
   * configuration to identify differences and propose updates. If there are
   * configuration changes to be applied, it offers a preview of the changes and
   * allows the user to confirm the import. The command uses the StorageComparer
   * to determine changes between the active and distribution configurations.
   *
   * @param array $options
   *   An associative array of options for the command. Supported options:
   *   - preview: Format for displaying the proposed changes. Possible values:
   *     - 'list': Display changes in a tabular list format (default).
   *     - 'diff': Display changes as a unified diff.
   *
   * @command config-distro-update
   * @option preview Format for displaying proposed changes. Recognized values: list, diff.
   * @usage drush config-distro-update
   *   Apply updates from the distribution.
   * @aliases cd-update
   *
   * @throws \Drush\Exceptions\UserAbortException
   *   Thrown when the user decides not to import the proposed config changes.
   */
  public function distroUpdate($options = ['preview' => 'list']) {
    /** @var \Drupal\Core\Config\StorageComparer $storage_comparer */
    $storage_comparer = new StorageComparer($this->distroStorage, $this->activeStorage);

    // This is copied from the Drush command.
    if (!$storage_comparer->createChangelist()->hasChanges()) {
      $this->logger()->notice(('There are no changes to import.'));
      return;
    }

    if ($options['preview'] == 'list') {
      $change_list = [];
      foreach ($storage_comparer->getAllCollectionNames() as $collection) {
        $change_list[$collection] = $storage_comparer->getChangelist(NULL, $collection);
      }
      $table = $this->configCommands::configChangesTable($change_list, $this->output());
      $table->render();
    }
    else {
      $diff_output = $this->configCommands::getDiff($this->activeStorage, $this->distroStorage, $this->output());
      $this->output()->writeln($diff_output);
    }

    if ($this->io()->confirm(dt('Import the listed configuration changes?'))) {
      // Import the config.
      $this->doImport($storage_comparer);

      // Dispatch an event to notify modules about the successful import.
      $this->eventDispatcher->dispatch(new DistroStorageImportEvent(), ConfigDistroEvents::IMPORT);
    }
    else {
      throw new UserAbortException();
    }
  }

  /**
   * Imports the configurations.
   *
   * Adapted from Drush\Commands\config\ConfigImportCommands.
   *
   * @param \Drupal\Core\Config\StorageComparer $storage_comparer
   *   A storage comparer instance.
   *
   * @throws \Exception
   */
  private function doImport(StorageComparer $storage_comparer): void {
    /** @var \Drupal\Core\Config\ConfigImporter $config_importer */
    $config_importer = new ConfigImporter(
      $storage_comparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->configTyped,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->stringTranslation,
      $this->moduleExtensionList,
      $this->themeExtensionList
    );
    if ($config_importer->alreadyImporting()) {
      $this->logger()->warning('Another request may be synchronizing configuration already.');
    }
    else {
      try {
        // This is the contents of \Drupal\Core\Config\ConfigImporter::import.
        // Copied here so we can log progress.
        if ($config_importer->hasUnprocessedConfigurationChanges()) {
          $sync_steps = $config_importer->initialize();
          foreach ($sync_steps as $step) {
            $context = [];
            do {
              $config_importer->doSyncStep($step, $context);
              if (isset($context['message'])) {
                $this->logger()->notice(str_replace('Synchronizing', 'Synchronized', (string) $context['message']));
              }
            } while ($context['finished'] < 1);
          }
          // Clear the cache of the active config storage.
          $this->configCache->deleteAll();
        }
        if ($config_importer->getErrors()) {
          throw new ConfigException('Errors occurred during import');
        }
        else {
          $this->logger()->success('The configuration was imported successfully.');
        }
      }
      catch (ConfigException $e) {
        // Return a negative result for UI purposes. We do not differentiate
        // between an actual synchronization error and a failed lock, because
        // concurrent synchronizations are an edge-case happening only when
        // multiple developers or site builders attempt to do it without
        // coordinating.
        $message = 'The import failed due to the following reasons:' . "\n";
        $message .= implode("\n", $config_importer->getErrors());

        $watchdog = $this->loggerFactory->get('config_distro');
        Error::logException($watchdog, $e, $message);
        throw new \Exception($message);
      }
    }
  }

}
