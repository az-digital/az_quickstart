<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'az_default_langcode' migrate process plugin.
 *
 * This plugin can be used in a migration to set the language code of the
 * destination entity. If the incoming language value is 'und' or empty, the
 * plugin will use the destination site's default langcode. Otherwise, it will
 * use the incoming language value.
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
#[MigrateProcess('az_default_langcode')]
class DefaultLangcode extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) || $value === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      return $this->languageManager->getDefaultLanguage()->getId();
    }
    return $value;
  }

}
