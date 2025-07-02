<?php

namespace Drupal\devel;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Manager class for DevelDumper.
 */
class DevelDumperManager implements DevelDumperManagerInterface {

  use StringTranslationTrait;

  /**
   * The devel config.
   */
  protected ImmutableConfig $config;

  /**
   * The current account.
   */
  protected AccountProxyInterface $account;

  /**
   * The devel dumper plugin manager.
   */
  protected DevelDumperPluginManagerInterface $dumperManager;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The messenger.
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs a DevelDumperPluginManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current account.
   * @param \Drupal\devel\DevelDumperPluginManagerInterface $dumper_manager
   *   The devel dumper plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $account,
    DevelDumperPluginManagerInterface $dumper_manager,
    EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
    TranslationInterface $string_translation,
  ) {
    $this->config = $config_factory->get('devel.settings');
    $this->account = $account;
    $this->dumperManager = $dumper_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Instances a new dumper plugin.
   *
   * @param string $plugin_id
   *   (optional) The plugin ID, defaults to NULL.
   *
   * @return \Drupal\devel\DevelDumperInterface
   *   Returns the devel dumper plugin instance.
   */
  protected function createInstance($plugin_id = NULL) {
    if (!$plugin_id || !$this->dumperManager->isPluginSupported($plugin_id)) {
      $plugin_id = $this->config->get('devel_dumper');
    }

    return $this->dumperManager->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function dump($input, $name = NULL, $plugin_id = NULL): void {
    if ($this->hasAccessToDevelInformation()) {
      $this->createInstance($plugin_id)->dump($input, $name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function export(mixed $input, ?string $name = NULL, ?string $plugin_id = NULL, bool $load_references = FALSE): MarkupInterface|string {
    if (!$this->hasAccessToDevelInformation()) {
      return '';
    }

    if ($load_references && $input instanceof EntityInterface) {
      $input = $this->entityToArrayWithReferences($input);
    }

    return $this->createInstance($plugin_id)->export($input, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function message($input, $name = NULL, $type = MessengerInterface::TYPE_STATUS, $plugin_id = NULL, $load_references = FALSE): void {
    if ($this->hasAccessToDevelInformation()) {
      $output = $this->export($input, $name, $plugin_id, $load_references);
      $this->messenger->addMessage($output, $type, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function debug($input, $name = NULL, $plugin_id = NULL) {
    $output = $this->createInstance($plugin_id)->export($input, $name) . "\n";
    // The temp directory does vary across multiple simpletest instances.
    $file = $this->config->get('debug_logfile');
    if (empty($file)) {
      $file = 'temporary://drupal_debug.txt';
    }

    if (file_put_contents($file, $output, FILE_APPEND) === FALSE && $this->hasAccessToDevelInformation()) {
      $this->messenger->addError($this->t('Devel was unable to write to %file.', ['%file' => $file]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dumpOrExport($input, $name = NULL, $export = TRUE, $plugin_id = NULL) {
    if ($this->hasAccessToDevelInformation()) {
      $dumper = $this->createInstance($plugin_id);
      if ($export) {
        return $dumper->export($input, $name);
      }

      $dumper->dump($input, $name);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function exportAsRenderable($input, $name = NULL, $plugin_id = NULL, $load_references = FALSE): array {
    if ($this->hasAccessToDevelInformation()) {
      if ($load_references && $input instanceof EntityInterface) {
        $input = $this->entityToArrayWithReferences($input);
      }

      return $this->createInstance($plugin_id)->exportAsRenderable($input, $name);
    }

    return [];
  }

  /**
   * Checks whether a user has access to devel information.
   *
   * @return bool
   *   TRUE if the user has the permission, FALSE otherwise.
   */
  protected function hasAccessToDevelInformation(): bool {
    return $this->account->hasPermission('access devel information');
  }

  /**
   * Converts the given entity to an array with referenced entities loaded.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The target entity.
   * @param int $depth
   *   Internal. Track the recursion.
   * @param array $array_path
   *   Internal. Track where we first say this entity.
   *
   * @return mixed[]
   *   An array of field names and deep values.
   */
  protected function entityToArrayWithReferences(EntityInterface $entity, int $depth = 0, array $array_path = []) {
    // Note that we've now seen this entity.
    $seen = &drupal_static(__FUNCTION__);
    $seen_key = $entity->getEntityTypeId() . '-' . $entity->id();
    if (!isset($seen[$seen_key])) {
      $seen[$seen_key] = $array_path;
    }

    $array = $entity->toArray();

    // Prevent out of memory and too deep traversing.
    if ($depth > 20) {
      return $array;
    }

    if (!$entity instanceof FieldableEntityInterface) {
      return $array;
    }

    foreach ($array as $field => &$value) {
      if (is_array($value)) {
        $fieldDefinition = $entity->getFieldDefinition($field);
        $target_type = $fieldDefinition->getSetting('target_type');
        if (!$target_type) {
          continue;
        }

        try {
          $storage = $this->entityTypeManager->getStorage($target_type);
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException) {
          continue;
        }

        foreach ($value as $delta => &$item) {
          if (is_array($item)) {
            $referenced_entity = NULL;
            if (isset($item['target_id'])) {
              $referenced_entity = $storage->load($item['target_id']);
            }
            elseif (isset($item['target_revision_id'])) {
              /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
              $referenced_entity = $storage->loadRevision($item['target_revision_id']);
            }

            $langcode = $entity->language()->getId();
            if ($referenced_entity instanceof TranslatableInterface
              && $referenced_entity->hasTranslation($langcode)) {
              $referenced_entity = $referenced_entity->getTranslation($langcode);
            }

            if (empty($referenced_entity)) {
              continue;
            }

            $seen_id = $referenced_entity->getEntityTypeId() . '-' . $referenced_entity->id();
            if (isset($seen[$seen_id])) {
              $item['message'] = 'Recursion detected.';
              $item['array_path'] = implode('.', $seen[$seen_id]);
              continue;
            }

            $item['entity'] = $this->entityToArrayWithReferences($referenced_entity, $depth++, array_merge($array_path, [$field, $delta, 'entity']));
            $item['bundle'] = $referenced_entity->bundle();
          }
        }
      }
    }

    return $array;
  }

}
