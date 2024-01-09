<?php

namespace Drupal\az_publication\Plugin\views\field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to display all properties of a config collection.
 *
 * @ViewsField("config_collection_properties")
 */
class ConfigCollectionProperties extends FieldPluginBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ConfigCollectionProperties object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Fetch the configuration collection name from the result row.
    // This depends on your specific implementation and how the collection name is available in the view.
    $collectionName = $this->getCollectionName($values);

    // Load the configuration.
    $config = $this->configFactory->get($collectionName);

    // Return all properties as a render array.
    // Adjust this according to how you want to format and display the properties.
    $properties = $config->getRawData();
    return [
      '#markup' => $this->t('<pre>@properties</pre>', ['@properties' => print_r($properties, TRUE)]),
    ];
  }

  /**
   * Helper function to get the collection name from the result row.
   *
   * @param \Drupal\views\ResultRow $values
   *   The result row.
   *
   * @return string
   *   The collection name.
   */
  protected function getCollectionName(ResultRow $values) {
    // Implement logic to determine the collection name from the result row.
    // This might involve accessing a field value or property from $values.
    // Return a default or example name if not available.
    return 'example.collection.name';
  }

}
