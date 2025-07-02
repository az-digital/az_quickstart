<?php

declare(strict_types=1);

namespace Drupal\config_split;

use Drupal\config_split\Config\ConfigImporterTrait;
use Drupal\config_split\Config\StatusOverride;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;

/**
 * The CLI service class for interoperability.
 *
 * @internal This service is not an api and may change at any time.
 */
class ConfigSplitCliService {

  use StorageCopyTrait;
  use ConfigImporterTrait;

  /**
   * The return value indicating no changes were imported.
   */
  const NO_CHANGES = 'no_changes';

  /**
   * The return value indicating that the import is already in progress.
   */
  const ALREADY_IMPORTING = 'already_importing';

  /**
   * The return value indicating that the process is complete.
   */
  const COMPLETE = 'complete';

  /**
   * The split manager.
   *
   * @var \Drupal\config_split\ConfigSplitManager
   */
  private $manager;

  /**
   * Active Config Storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $activeStorage;

  /**
   * Sync Config Storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $syncStorage;

  /**
   * The split status override service.
   *
   * @var \Drupal\config_split\Config\StatusOverride
   */
  protected $statusOverride;

  /**
   * List of messages.
   *
   * @var array
   */
  protected $errors;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigSplitManager $manager,
    StorageInterface $activeStorage,
    StorageInterface $syncStorage,
    StatusOverride $statusOverride,
  ) {
    $this->manager = $manager;
    $this->activeStorage = $activeStorage;
    $this->syncStorage = $syncStorage;
    $this->statusOverride = $statusOverride;
    $this->errors = [];
  }

  /**
   * Handle the export interaction.
   *
   * @param string $split
   *   The split name to export.
   * @param \Symfony\Component\Console\Style\StyleInterface|object $io
   *   The io interface of the cli tool calling the method.
   * @param callable $t
   *   The translation function akin to t().
   * @param bool $confirmed
   *   Whether the export is already confirmed by the console input.
   */
  public function ioExport(string $split, $io, callable $t, bool $confirmed = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io, $t);
    if ($config === NULL) {
      return FALSE;
    }

    if (!$config->get('status')) {
      $io->warning("Inactive splits can not not be exported.");
      return FALSE;
    }

    $message = $t('Export the split config configuration?');
    if ($confirmed || $io->confirm($message)) {
      $target = $this->manager->singleExportTarget($config);
      self::replaceStorageContents($this->manager->singleExportPreview($config), $target);
      $io->success($t("Configuration successfully exported."));
    }

    return TRUE;
  }

  /**
   * Handle the import interaction.
   *
   * @param string $split
   *   The split name to import.
   * @param \Symfony\Component\Console\Style\StyleInterface|object $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   * @param bool $confirmed
   *   Whether the import is already confirmed by the console input.
   */
  public function ioImport(string $split, $io, callable $t, $confirmed = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io, $t);
    if ($config === NULL) {
      return FALSE;
    }

    $message = $t('Import the split config configuration?');
    $storage = $this->manager->singleImport($config, FALSE);

    if ($confirmed || $io->confirm($message)) {
      return $this->tryImport($storage, $io, $t);
    }
    return TRUE;
  }

  /**
   * Handle the activation interaction.
   *
   * @param string $split
   *   The split name to activate.
   * @param \Symfony\Component\Console\Style\StyleInterface|object $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   * @param bool $confirmed
   *   Whether the import is already confirmed by the console input.
   */
  public function ioActivate(string $split, $io, callable $t, $confirmed = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io, $t);
    if ($config === NULL) {
      return FALSE;
    }

    $message = $t('Activate the split config configuration?');
    $storage = $this->manager->singleActivate($config, TRUE);

    if ($confirmed || $io->confirm($message)) {
      return $this->tryImport($storage, $io, $t);
    }
    return TRUE;
  }

  /**
   * Handle the deactivation interaction.
   *
   * @param string $split
   *   The split name to deactivate.
   * @param \Drush\Style\DrushStyle|object $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   * @param bool $confirmed
   *   Whether the import is already confirmed by the console input.
   * @param bool $override
   *   Allows the deactivation via override.
   */
  public function ioDeactivate(string $split, $io, callable $t, $confirmed = FALSE, $override = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io, $t);
    if ($config === NULL) {
      return FALSE;
    }

    $message = $t('Deactivate the split config configuration?');
    $storage = $this->manager->singleDeactivate($config, FALSE, $override);

    if ($confirmed || $io->confirm($message)) {
      return $this->tryImport($storage, $io, $t);
    }
    return TRUE;
  }

  /**
   * The hook to invoke after having exported all config.
   */
  public function postExportAll() {
    // We need to make sure the split config is also written to the permanent
    // split storage.
    $this->manager->commitAll();
  }

  /**
   * Get and set status config overrides.
   *
   * @param string $name
   *   The split name to override.
   * @param string|bool $status
   *   The status to set.
   * @param \Drush\Style\DrushStyle|object $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   */
  public function statusOverride(string $name, $status, $io, callable $t) {
    if ($this->getSplitFromArgument($name, $io, $t) === NULL) {
      return FALSE;
    }
    $map = [
      NULL => 'none/default',
      TRUE => 'active',
      FALSE => 'inactive',
    ];

    $settings = $this->statusOverride->getSettingsOverride($name);
    if ($settings !== NULL) {
      $io->caution($t('The status for @name is overridden in settings.php to @status', ['@name' => $name, '@status' => $map[$settings]]));
    }

    if ($status === '') {
      $state = $this->statusOverride->getSplitOverride($name);
      $io->success($t('The status override for @name is @status', ['@name' => $name, '@status' => $map[$state]]));
      return TRUE;
    }

    switch (strtolower((string) $status)) {
      case 'active':
      case '1':
      case 'true':
        $state = TRUE;
        break;

      case 'inactive':
      case '0':
      case 'false':
        $state = FALSE;
        break;

      case 'default':
      case 'null':
      case 'none':
        $state = NULL;
        break;

      default:
        throw new \InvalidArgumentException(sprintf('The status must be one of "active", "inactive", "default" or "none". %s given', $status));
    }

    $this->statusOverride->setSplitOverride($name, $state);
    $io->success($t('The status override for @name was set to @status', ['@name' => $name, '@status' => $map[$state]]));
    return TRUE;
  }

  /**
   * Import the configuration.
   *
   * This is the quintessential config import.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The config storage to import from.
   *
   * @return string
   *   The state of importing.
   */
  private function import(StorageInterface $storage) {

    $comparer = new StorageComparer($storage, $this->activeStorage);

    if (!$comparer->createChangelist()->hasChanges()) {
      return static::NO_CHANGES;
    }

    $importer = $this->getConfigImporterFromComparer($comparer);

    if ($importer->alreadyImporting()) {
      return static::ALREADY_IMPORTING;
    }

    try {
      // Do the import with the ConfigImporter.
      $importer->import();
    }
    catch (ConfigImporterException $e) {
      // Catch and re-trow the ConfigImporterException.
      $this->errors = $importer->getErrors();
      throw $e;
    }

    return static::COMPLETE;
  }

  /**
   * Returns error messages created while running the import.
   *
   * @return array
   *   List of messages.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Get the split from the argument.
   *
   * @param string $split
   *   The split name.
   * @param object $io
   *   The io object.
   * @param callable $t
   *   The translation function.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The split config.
   *
   * @throws \InvalidArgumentException
   *   When there is no split argument.
   */
  private function getSplitFromArgument(string $split, $io, callable $t): ?ImmutableConfig {
    if (!$split) {
      throw new \InvalidArgumentException('Split can not be empty');
    }

    $config = $this->manager->getSplitConfig($split);
    if ($config === NULL) {
      // Try to get the split from the sync storage. This may not make sense
      // for all the operations.
      $config = $this->manager->getSplitConfig($split, $this->syncStorage);
      if ($config === NULL) {
        $io->error($t('There is no split with name @name', ['@name' => $split]));
      }
    }

    return $config;
  }

  /**
   * Try importing the storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to import.
   * @param object $io
   *   The io object.
   * @param callable $t
   *   The translation function.
   *
   * @return bool
   *   The success status.
   */
  private function tryImport(StorageInterface $storage, $io, callable $t): bool {
    try {
      $status = $this->import($storage);
      switch ($status) {
        case ConfigSplitCliService::COMPLETE:
          $io->success($t("Configuration successfully imported."));
          return TRUE;

        case ConfigSplitCliService::NO_CHANGES:
          $io->text($t("There are no changes to import."));
          return TRUE;

        case ConfigSplitCliService::ALREADY_IMPORTING:
          $io->error(
            $t("Another request may be synchronizing configuration already.")
          );
          return FALSE;

        default:
          $io->error($t("Something unexpected happened"));
          return FALSE;
      }
    }
    catch (ConfigImporterException $e) {
      $io->error(
        $t(
          'There have been errors importing: @errors',
          ['@errors' => strip_tags(implode("\n", $this->getErrors()))]
        )
      );
      return FALSE;
    }
  }

}
