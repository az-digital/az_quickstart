<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to recognize text formats and return a given response.
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
    if (!empty($value['value'])) {
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

      if (!empty($full) && !empty($markup)) {
        // Let's compare canonical markup after going back from parsed html.
        $full = $full->saveXML();
        $markup = $markup->saveXML();
        // If the HTML nodes were comparable, output should be the same.
        if ($full === $markup) {
          return $this->configuration['passed'];
        }
      }
    }

    return $this->configuration['failed'];
  }

}
