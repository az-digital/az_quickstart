<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to recognize text formats with configurable response values.
 *
 * Available configuration keys
 * - format: The text format to test compatibility with.
 * - passed: The value to return if the text format compatibility test passed.
 * - failed: The value to return if the text format compatibility test failed.
 * - required_module: The name of a required module. (optional)
 * - module_missing: The value to return if the required module is missing.
 *   (optional)
 *
 * Examples:
 *
 * Consider a paragraphs migration, where you want to be able to automatically
 * use a specific destination paragraph type with a less permissive text format
 * if the source field value is compatible with it and fallback to a destination
 * paragraph type with a more permissive text format if not. This example also
 * checks that the 'az_paragraphs_html' module exists on the destination check
 * and defaults to the 'az_text' paragraph type if the module is missing.
 * @code
 * process:
 *   destination_bundle:
 *     plugin: text_format_recognizer
 *     source: field_uaqs_html
 *     format: 'az_standard'
 *     passed: 'az_text'
 *     failed: 'az_html'
 *     required_module: 'az_paragraphs_html'
 *     module_missing: 'az_text'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "text_format_recognizer"
 * )
 */
class TextFormatRecognizer extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Likely dealing with a text field.
    if (isset($value['value'])) {
      $value = $value['value'];
    }

    if (!empty($this->configuration['required_module'])) {
      // Don't proceed if the specified module is not loaded.
      if (!$this->moduleHandler->moduleExists($this->configuration['required_module'])) {
        return $this->configuration['module_missing'];
      }

    }

    if (!empty($this->configuration['format']) &&
      !empty($this->configuration['passed']) && !empty($this->configuration['failed'])) {
      $format = $this->configuration['format'];

      // Render as full html first.
      $full = trim(_filter_autop(check_markup($value, 'full_html')));
      // Attempt to parse the resultant html.
      $full = @\DOMDocument::loadHTML($full);

      // Render the text according to the format.
      // Attempt to put autoparagraphs back in after the fact, since they
      // Are likely to exist in the source regardless of intent.
      $markup = trim(_filter_autop(check_markup($value, $format)));
      // Attempt to parse the resultant html.
      $markup = @\DOMDocument::loadHTML($markup);

      // Let's convert back to canonical HTML if parsing was successful.
      if (!empty($full)) {
        $full = $full->saveXML();
      }
      if (!empty($markup)) {
        $markup = $markup->saveXML();
      }
      // Let's compare canonical markup after going back from parsed html.
      // If the HTML nodes were comparable, output should be the same.
      if ($full === $markup) {
        return $this->configuration['passed'];
      }
    }

    return $this->configuration['failed'];
  }

}
