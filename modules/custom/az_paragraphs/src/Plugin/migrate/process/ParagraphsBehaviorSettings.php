<?php

namespace Drupal\az_paragraphs\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Behavior for paragraphs.
 *
 * Examples:
 *
 * Consider a paragraph with a behavior plugin.
 * @code
 * field_uaqs_setting_text_bg_color_processed:
 *   - plugin: extract
 *     source: field_uaqs_setting_text_bg_color
 *     index:
 *       - 0
 *       - value
 *   - plugin: static_map
 *     map:
 *       bg-transparent: transparent
 *       bg-trans-white: light
 *       bg-trans-sky: light
 *       bg-trans-arizona-blue: dark
 *       bg-trans-black: dark
 *       dark: dark
 *       light: light
 *   - plugin: default_value
 *     default_value: 'light'
 * view_mode_processed:
 *   - plugin: static_map
 *     source: view_mode
 *     map:
 *       uaqs_bg_img_content_left: 'col-md-8 col-lg-6'
 *       uaqs_bg_img_content_center: 'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3'
 *       uaqs_bg_img_content_right: 'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6'
 *   - plugin: default_value
 *     default_value: 'col-md-8 col-lg-6'
 * content_style_processed:
 *   - plugin: default_value
 *     default_value: 'column'
 * bottom_spacing_processed:
 *   - plugin: default_value
 *     default_value: 'mb-0'
 *     source: bottom_spacing
 * behavior_settings:
 *   plugin: paragraphs_behavior
 *   paragraph_behavior_plugins:
 *     az_text_media_paragraph_behavior:
 *       bg_color: '@field_uaqs_setting_text_bg_color_processed'
 *       bg_attach: '@field_uaqs_setting_bg_attach_value'
 *       position: '@view_mode_processed'
 *       full_width: '@processed_full_width'
 *       style: '@content_style_processed'
 *       az_display_settings:
 *         bottom_spacing: '@bottom_spacing_processed'
 *
 * @endcode * @MigrateProcessPlugin(
 *   id = "paragraphs_behavior"
 * )
 */
class ParagraphsBehaviorSettings extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $processPluginManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigratePluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;

    if (empty($this->configuration['paragraph_behavior_plugins'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'paragraph_behavior_plugins' is required."
      );
    }
    if (!is_array($this->configuration['paragraph_behavior_plugins'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'paragraph_behavior_plugins' should be a keyed array."
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {


    $behavior = $this->buildSettingsArray($this->configuration['paragraph_behavior_plugins'], $row);
    $value['behavior'] = serialize($behavior);
    return $value['behavior'];
  }

  /**
   * Recursive array builder that reads values in deeply nested arrays.
   */
  protected function buildSettingsArray(array &$settings, Row $row) {
    $branch = array();

    foreach ($settings as $key => $value) {
      if (is_array($value)) {
        $branch[$key] = $this->buildSettingsArray($value, $row);
      }
      else {
        $branch[$key] = $row->get($value);
      }
    }
    return $branch;
  }

}
