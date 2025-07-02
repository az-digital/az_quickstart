<?php

namespace Drupal\devel_generate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel_generate\Annotation\DevelGenerate;

/**
 * Plugin type manager for DevelGenerate plugins.
 */
class DevelGeneratePluginManager extends DefaultPluginManager {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The messenger service.
   */
  protected MessengerInterface $messenger;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The translation manager.
   */
  protected TranslationInterface $stringTranslation;

  /**
   * Constructs a DevelGeneratePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    LanguageManagerInterface $language_manager,
    TranslationInterface $string_translation,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {
    parent::__construct('Plugin/DevelGenerate', $namespaces, $module_handler, NULL, DevelGenerate::class);
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
    $this->alterInfo('devel_generate_info');
    $this->setCacheBackend($cache_backend, 'devel_generate_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions(): array {
    $definitions = [];
    foreach (parent::findDefinitions() as $plugin_id => $plugin_definition) {
      $plugin_available = TRUE;
      foreach ($plugin_definition['dependencies'] as $module_name) {
        // If a plugin defines module dependencies and at least one module is
        // not installed don't make this plugin available.
        if (!$this->moduleHandler->moduleExists($module_name)) {
          $plugin_available = FALSE;
          break;
        }
      }

      if ($plugin_available) {
        $definitions[$plugin_id] = $plugin_definition;
      }
    }

    return $definitions;
  }

}
