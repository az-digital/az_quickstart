<?php

namespace Drupal\paragraphs;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\paragraphs\Attribute\ParagraphsConversion;

/**
 * Plugin type manager for paragraphs type conversion plugins.
 *
 * @ingroup paragraphs_conversion
 */
class ParagraphsConversionManager extends DefaultPluginManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ParagraphsConversionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/paragraphs/Conversion', $namespaces, $module_handler, ParagraphsConversionInterface::class, ParagraphsConversion::class, 'Drupal\paragraphs\Annotation\ParagraphsConversion');
    $this->setCacheBackend($cache_backend, 'paragraphs_conversion_plugins');
    $this->alterInfo('paragraphs_conversion_info');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function findDefinitions() {
    $definitions = parent::findDefinitions();
    uasort($definitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $definitions;
  }

  /**
   * Gets the applicable conversion plugins.
   *
   * Loop over the plugin definitions, check the applicability of each one of
   * them and return the array of the applicable plugins.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param array $allowed_types
   *   (optional) The parent fields allowed paragraph types.
   *
   * @return array
   *   The applicable conversion plugins.
   */
  public function getApplicableDefinitions(ParagraphInterface $paragraph, ?array $allowed_types = NULL) {
    $definitions = $this->getDefinitions();
    $applicable_plugins = [];
    foreach ($definitions as $key => $definition) {
      /** @var \Drupal\paragraphs\ParagraphsConversionInterface $plugin */
      $plugin = $this->createInstance($key);
      if ($plugin && $this->isApplicable($plugin, $paragraph, $allowed_types)) {
        $applicable_plugins[$key] = $definition;
      }
    }
    return $applicable_plugins;
  }

  /**
   * Returns whether the given plugin is applicable for the conversion.
   *
   * @param \Drupal\paragraphs\ParagraphsConversionInterface $plugin
   *   The conversion plugin.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param array|null $allowed_types
   *   (optional) The list of allowed types.
   *
   * @return bool
   *   TRUE if the plugin is applicable. Otherwise, FALSE.
   */
  protected function isApplicable(ParagraphsConversionInterface $plugin, ParagraphInterface $paragraph, ?array $allowed_types = NULL) {
    if (!$plugin->supports($paragraph, $allowed_types)) {
      return FALSE;
    }

    $target_types = $plugin->getPluginDefinition()['target_types'];
    if (empty($target_types)) {
      return TRUE;
    }

    // Check create access of the target paragraph type.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($paragraph->getEntityTypeId());

    // Loop over the target types and check that the user has create
    // access to all of them.
    foreach ($target_types as $target_type) {
      if (!$access_control_handler->createAccess($target_type)) {
        return FALSE;
      }

      // In case there are allowed types check the target type.
      if (is_array($allowed_types) && !isset($allowed_types[$target_type])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks if the paragraph supports conversion.
   *
   * Loop over the plugin definitions and in the first supported plugin return
   * TRUE.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param array $allowed_types
   *   (optional) The parent fields allowed paragraph types.
   *
   * @return bool
   *   Whether the current paragraph supports conversion.
   */
  public function supportsConversion(ParagraphInterface $paragraph, ?array $allowed_types = NULL) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $key => $definition) {
      /** @var \Drupal\paragraphs\ParagraphsConversionInterface $plugin */
      $plugin = $this->createInstance($key);
      if ($plugin && $this->isApplicable($plugin, $paragraph, $allowed_types)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Applies default values to a converted paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $original_paragraph
   *   The original paragraph.
   * @param \Drupal\paragraphs\ParagraphInterface $converted_paragraph
   *   The converted paragraph.
   */
  public function applyDefaultValues(ParagraphInterface $original_paragraph, ParagraphInterface $converted_paragraph) {
    // Converted paragraph has to follow the language of the original paragraph.
    $converted_paragraph->set($original_paragraph->getEntityType()->getKey('langcode'), $original_paragraph->language()->getId());

    // Converted paragraph should reuse the behavior settings when possible.
    foreach ($original_paragraph->getAllBehaviorSettings() as $plugin_id => $settings) {
      if ($converted_paragraph->getParagraphType()->hasEnabledBehaviorPlugin($plugin_id)) {
        // Having the same plugin enabled is not enough. Some behavior plugins
        // provide configurability options. Compare their behavior plugin
        // configuration and in case they match, reuse the behavior settings.
        $original_plugin_configuration = $original_paragraph->getParagraphType()->getBehaviorPlugin($plugin_id)->getConfiguration();
        $converted_plugin_configuration = $converted_paragraph->getParagraphType()->getBehaviorPlugin($plugin_id)->getConfiguration();
        if ($original_plugin_configuration == $converted_plugin_configuration) {
          $converted_paragraph->setBehaviorSettings($plugin_id, $settings);
        }
      }
    }
  }

  /**
   * A helper that adds given translation values and sets the source language.
   *
   * @param \Drupal\Core\TypedData\TranslatableInterface $entity
   *   The translatable entity.
   * @param string $lang_code
   *   The language code.
   * @param array $values
   *   An array of translation values.
   */
  public function addTranslation(TranslatableInterface $entity, $lang_code, array $values) {
    // Add a new translation to the translatable object.
    $entity->addTranslation($lang_code, $values);
    // Set the source language for this translation.
    $translation = $entity->getTranslation($lang_code);
    $content_translation_manager = \Drupal::service('content_translation.manager');
    $content_translation_manager->getTranslationMetadata($translation)->setSource($entity->language()->getId());
  }

}
