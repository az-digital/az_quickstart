<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'az_default_langcode' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "az_default_langcode"
 * )
 *
 * This plugin can be used in a migration to set the language code of the
 * destination entity. If the incoming language value is 'und', the plugin
 * will use the destination site's default langcode. Otherwise, it will use
 * the incoming language value.
 *
 * Example usage:
 *
 * @code
 * process:
 *   langcode:
 *     plugin: az_default_langcode
 *     source: source_langcode
 * @endcode
 */
class DefaultLangcode extends ProcessPluginBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a DefaultLangcode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      return $this->languageManager->getDefaultLanguage()->getId();
    }
    return $value;
  }

}
