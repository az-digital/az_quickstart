<?php

declare(strict_types=1);

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
 * ('az_text') if the source field value is compatible with it and fallback to a
 * destination paragraph type with a more permissive text format ('az_html') if
 * not. This example also checks that the 'az_paragraphs_html' module exists on
 * the destination and defaults to the 'az_text' paragraph type if the module is
 * missing.
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
      // Attempt to parse the resultant html and convert back to canonical html
      // if successful.
      $fullDoc = new \DOMDocument();
      if (!empty($full) && @$fullDoc->loadHTML($full)) {
        $full = $fullDoc->saveXML();
      }

      // Render the text according to the format.
      // Attempt to put autoparagraphs back in after the fact, since they are
      // likely to exist in the source regardless of intent.
      $markup = trim(_filter_autop(check_markup($value, $format)));
      if (!empty($markup)) {
        // Attempt to parse the resultant html and convert back to canonical
        // html if successful.
        $markupDoc = new \DOMDocument();
        if (@$markupDoc->loadHTML($markup)) {
          $markup = $markupDoc->saveXML();
        }
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
