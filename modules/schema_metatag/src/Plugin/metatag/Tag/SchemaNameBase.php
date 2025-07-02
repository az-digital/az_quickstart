<?php

namespace Drupal\schema_metatag\Plugin\metatag\Tag;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;
use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManager;
use Drupal\schema_metatag\SchemaMetatagManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * All Schema.org tags should extend this class.
 */
class SchemaNameBase extends MetaNameBase implements ContainerFactoryPluginInterface {

  /**
   * Config factory.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The SchemaMetatagManager service.
   *
   * @var \Drupal\schema_metatag\SchemaMetatagManager
   */
  protected $schemaMetatagManager;

  /**
   * The PropertyTypeManager service.
   *
   * @var \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManager
   */
  protected $propertyTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->setConfigFactory($container->get('config.factory'));
    $instance->setSchemaMetatagManager($container->get('schema_metatag.schema_metatag_manager'));
    $instance->setPropertyTypeManager($container->get('plugin.manager.schema_property_type'));
    return $instance;
  }

  /**
   * Sets ConfigFactoryInterface service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory service.
   */
  public function setConfigFactory(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Sets schemaMetatagManager service.
   *
   * @param \Drupal\schema_metatag\SchemaMetatagManager $schemaMetatagManager
   *   The Schema Metatag Manager service.
   */
  public function setSchemaMetatagManager(SchemaMetatagManager $schemaMetatagManager) {
    $this->schemaMetatagManager = $schemaMetatagManager;
  }

  /**
   * Sets schemaMetatagManager service.
   *
   * @param \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManager $propertyTypeManager
   *   The PropertyTypeManager service.
   */
  public function setPropertyTypeManager(PropertyTypeManager $propertyTypeManager) {
    $this->propertyTypeManager = $propertyTypeManager;
  }

  /**
   * Return the SchemaMetatagManager service.
   *
   * @return \Drupal\schema_metatag\SchemaMetatagManager
   *   The Schema Metatag Manager service.
   */
  protected function schemaMetatagManager() {
    return $this->schemaMetatagManager;
  }

  /**
   * Return the PropertyTypeManager service.
   *
   * @return \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManager
   *   The PropertyTypeManager service.
   */
  protected function propertyTypeManager() {
    return $this->propertyTypeManager;
  }

  /**
   * The #states base visibility selector for this element.
   */
  protected function visibilitySelector() {
    return $this->getPluginId();
  }

  /**
   * Generate a form element for this meta tag.
   *
   * This method should be overridden in classes that extend this base by
   * creating a form element using the property type manager.
   *
   * @param array $element
   *   The existing form element to attach to.
   *
   * @return array
   *   The completed form element.
   *
   * @see \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase::form()
   */
  public function form(array $element = []): array {
    $property_type = !empty($this->pluginDefinition['property_type']) ? $this->pluginDefinition['property_type'] : 'text';
    $tree_parent = !empty($this->pluginDefinition['tree_parent']) ? $this->pluginDefinition['tree_parent'] : '';
    $tree_depth = !empty($this->pluginDefinition['tree_depth']) ? $this->pluginDefinition['tree_depth'] : -1;

    $input_values = $this->getInputValues();
    if (!empty($tree_parent)) {
      $input_values['tree_parent'] = $tree_parent;
      $input_values['tree_depth'] = $tree_depth;
    }

    $form = $this->propertyTypeManager()
      ->createInstance($property_type)
      ->form($input_values);

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function output(): array {
    $value = $this->schemaMetatagManager()->unserialize($this->value());

    // If this is a complex array of values, process the array.
    if (is_array($value)) {

      // Clean out empty values.
      $value = $this->schemaMetatagManager()->arrayTrim($value);
    }

    if (empty($value)) {
      return [];
    }
    // If this is a complex array of value, process the array.
    elseif (is_array($value)) {

      // If the item is an array of values,
      // walk the array and process the values.
      array_walk_recursive($value, [$this, 'processItem']);

      // Recursively pivot each branch of the array.
      $value = $this->pivotItem($value);

    }
    // Process a simple string.
    else {
      $this->processItem($value);
    }
    $output = [
      '#tag' => 'meta',
      '#attributes' => [
        'name' => $this->name,
        'content' => $this->outputValue($value),
        'group' => $this->group,
        'schema_metatag' => TRUE,
      ],
    ];

    return $output;
  }

  /**
   * Transform input value to its display output.
   *
   * Types that need to transform the output to something different than the
   * stored value should extend this method and do the transformation here.
   *
   * @param mixed $input_value
   *   Input value, could be either a string or array. This will be the
   *   value after token replacement.
   *
   * @return mixed
   *   Return the (possibly expanded) value which will be rendered in JSON-LD.
   */
  public function outputValue($input_value) {
    $property_type = !empty($this->pluginDefinition['property_type']) ? $this->pluginDefinition['property_type'] : 'text';

    return $this->propertyTypeManager()
      ->createInstance($property_type)
      ->outputValue($input_value);
  }

  /**
   * Metatag expects a string value, so serialize any array of values.
   */
  public function setValue($value): void {
    $this->value = (string) $this->schemaMetatagManager()->serialize($value);
  }

  /**
   * Get default values used to create a form element.
   *
   * @return array
   *   An array of values.
   *
   * @see Drupal\schema_metatag\SchemaMetatagManager::defaultInputValues();
   */
  public function getInputValues() {
    $value = $this->schemaMetatagManager()->unserialize($this->value());
    $default_values = $this->schemaMetatagManager()->defaultInputValues();
    $input_values = [
      'title' => $this->label(),
      'description' => $this->description(),
      'value' => $value,
      'visibility_selector' => $this->visibilitySelector(),
      'multiple' => $this->multiple(),
    ];
    return array_merge($default_values, $input_values);
  }

  /**
   * Function to pivot the nested items.
   *
   * @param array $array
   *   List of items.
   *
   * @return array
   *   An array of values.
   */
  public function pivotItem(array $array) {
    // See if any nested items need to be pivoted.
    // If pivot is set to 0, it would have been removed as an empty value.
    if (array_key_exists('pivot', $array)) {
      unset($array['pivot']);
      $array = $this->schemaMetatagManager()->pivot($array);
    }
    foreach ($array as &$value) {
      if (is_array($value)) {
        $value = $this->pivotItem($value);
      }
    }
    return $array;
  }

  /**
   * Nested elements that cannot be exploded.
   *
   * @return array
   *   Array of keys that might contain commas, or otherwise cannot be exploded.
   */
  protected function neverExplode() {
    return [
      'name',
      'streetAddress',
      'reviewBody',
      'recipeInstructions',
    ];
  }

  /**
   * Function to process items.
   */
  protected function processItem(&$value, $key = 0) {
    if ($key === 0) {
      $explode = $this->multiple();
    }
    elseif ($this->schemaMetatagManager->hasSeparator()) {
      $explode = TRUE;
    }
    else {
      $explode = !in_array($key, $this->neverExplode());
    }

    // Parse out the image URL, if needed.
    $value = $this->parseImageUrlValue($value, $explode);

    // Convert value to plain text.
    $value = PlainTextOutput::renderFromHtml($value);

    // Trim resulting plain text value.
    $value = trim($value);

    // If tag must be secure, convert all http:// to https://.
    if ($this->secure() && strpos($value, 'http://') !== FALSE) {
      $value = str_replace('http://', 'https://', $value);
    }
    if ($explode) {
      $value = $this->schemaMetatagManager()->explode($value, $this->schemaMetatagManager->getSeparator());
      // Clean out any empty values that might have been added by explode().
      if (is_array($value)) {
        $value = array_values(array_filter($value));
      }
    }
  }

  /**
   * Parse the image url out of image markup.
   *
   * A copy of the base method of the same name, but where $value is passed
   * in instead of assumed to be $this->value().
   */
  protected function parseImageUrlValue($value, $explode) {
    // If this contains embedded image tags, extract the image URLs.
    if ($this->type() === 'image') {
      // Get configuration.
      $separator = $this->schemaMetatagManager->getSeparator();

      // If image tag src is relative (starts with /), convert to an absolute
      // link.
      global $base_root;
      if (strpos($value, '<img src="/') !== FALSE) {
        $value = str_replace('<img src="/', '<img src="' . $base_root . '/', $value);
      }

      if (strip_tags($value) != $value) {
        if ($explode) {
          $values = explode($separator, $value);
        }
        else {
          $values = [$value];
        }

        // Check through the value(s) to see if there are any image tags.
        foreach ($values as $key => $val) {
          $matches = [];
          preg_match('/src="([^"]*)"/', $val, $matches);
          if (!empty($matches[1])) {
            $values[$key] = $matches[1];
          }
        }
        $value = implode(',', $values);

        // Remove any HTML tags that might remain.
        $value = strip_tags($value);
      }
    }

    return $value;
  }

}
