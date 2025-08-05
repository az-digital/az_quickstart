<?php

namespace Drupal\az_paragraphs\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\ParagraphsBehaviorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Behavior for paragraphs.
 *
 * Available configuration keys
 * - paragraph_behavior_plugins: A keyed multidimensional array matching each
 *   enabled behavior's expected structure where the first key is a paragraph
 *   behavior plugin id.
 *
 * Examples: Consider a paragraph migration for a marquee style paragraph with
 * the following behavior settings in the destination site:
 *   - bg_color string representing a selected value key.
 *   - bg_attach string representing a selected value key.
 *   - position: string representing a selected value key.
 *   - full_width: boolean indicating that the paragraph should span the page
 *     width.
 *   - style: string representing a selected value key.
 *   - bottom_spacing: string representing a selected value key.
 *
 *  Also consider that paragraphs behavior settings are formatted as a
 *  serialized array in the `behavior_settings` field of the
 *  `paragraphs_item_field_data` table in the destination site.
 *
 *  Modified seralized array for readability.
 *  a:1:{
 * i:0;s:323:"a:1:{
 *   s:32:"az_text_media_paragraph_behavior";a:6:{
 *     s:10:"full_width";s:21:"full-width-background";
 *     s:5:"style";s:3:"box";
 *     s:8:"bg_color";s:5:"light";
 *     s:8:"position";s:41:"col-md-8 col-lg-6 offset-md-4 offset-lg-6";
 *     s:18:"text_media_spacing";s:4:"y-10";
 *     s:19:"az_display_settings";a:1:{
 *       s:14:"bottom_spacing";s:4:"mb-0";
 *     }
 *   }
 * }
 * ";}
 *
 * The `behavior_settings` field uses the `az_paragraphs_behavior_settings`
 * process plugin to build the array to be serialized, you are able to use the
 * pseudo field concept within migrate processes to get the values of the source
 * site ready for injecting into the `behavior_settings` array.
 *
 * @code
 * process:
 *   field_uaqs_setting_text_bg_color_processed:
 *     - plugin: extract
 *       source: field_uaqs_setting_text_bg_color
 *       index:
 *         - 0
 *         - value
 *     - plugin: static_map
 *       default: 'bg-transparent-white'
 *       map:
 *         bg-transparent: bg-transparent
 *         bg-trans-white: bg-transparent-white
 *         bg-trans-sky: bg-transparent-white
 *         bg-trans-arizona-blue: bg-transparent-black
 *         bg-trans-black: bg-transparent-black
 *         dark: bg-transparent-black
 *         light: bg-transparent-white
 *     - plugin: default_value
 *       default_value: 'bg-transparent-white'
 *   view_mode_processed:
 *     - plugin: static_map
 *       source: view_mode
 *       default: 'col-md-8 col-lg-6'
 *       map:
 *         uaqs_bg_img_content_left: 'col-md-8 col-lg-6'
 *         uaqs_bg_img_content_center: 'col-md-8 col-lg-6 offset-md-2 offset-lg-3'
 *         uaqs_bg_img_content_right: 'col-md-8 col-lg-6 offset-md-4 offset-lg-6'
 *     - plugin: default_value
 *       default_value: 'col-md-8 col-lg-6'
 *   content_style_processed:
 *     - plugin: default_value
 *       default_value: 'column'
 *   bottom_spacing_processed:
 *     - plugin: default_value
 *       default_value: 'mb-0'
 *       source: bottom_spacing
 *
 *   # Set the behavior_settings value.
 *   behavior_settings:
 *     plugin: az_paragraphs_behavior_settings
 *     paragraph_behavior_plugins:
 *       az_text_media_paragraph_behavior:
 *         bg_color: '@field_uaqs_setting_text_bg_color_processed'
 *         bg_attach: '@field_uaqs_setting_bg_attach_value'
 *         position: '@view_mode_processed'
 *         full_width: '@processed_full_width'
 *         style: '@content_style_processed'
 *         az_display_settings:
 *           bottom_spacing: '@bottom_spacing_processed'
 * @endcode
 */
#[MigrateProcess('az_paragraphs_behavior_settings')]
class ParagraphsBehaviorSettings extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The paragraphs behavior plugin manager.
   *
   * @var \Drupal\paragraphs\ParagraphsBehaviorManager
   */
  protected $behaviorPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ParagraphsBehaviorManager $behavior_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->behaviorPluginManager = $behavior_plugin_manager;
    if (empty($this->configuration['paragraph_behavior_plugins'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'paragraph_behavior_plugins' is required."
      );
    }
    if (!is_array($this->configuration['paragraph_behavior_plugins'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'paragraph_behavior_plugins' should be a multi-dimensional array keyed with paragraph behavior plugin ids."
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.paragraphs.behavior')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): string {
    $behaviors = array_keys($this->behaviorPluginManager->getDefinitions());
    foreach ($this->configuration['paragraph_behavior_plugins'] as $behavior_id => $settings) {
      if (!$behaviors || !in_array($behavior_id, $behaviors)) {
        throw new PluginNotFoundException($behavior_id);
      }
    }

    $behavior = $this->buildSettingsArray($this->configuration['paragraph_behavior_plugins'], $row);
    $value['behavior'] = serialize($behavior);
    return $value['behavior'];
  }

  /**
   * Recursive array builder that reads values in deeply nested arrays.
   *
   * @param array $settings
   *   The behavior settings array.
   * @param \Drupal\migrate\Row $row
   *   The current row.
   *
   * @return array
   *   The behavior settings array.
   */
  protected function buildSettingsArray(array &$settings, Row $row): array {
    $branch = [];

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
