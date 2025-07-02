<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schema_metatag\SchemaMetatagClientInterface;
use Drupal\schema_metatag\SchemaMetatagManagerInterface;
use Drupal\schema_metatag\SchemaMetatagTestTagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Property type plugins.
 */
class PropertyTypeBase extends PluginBase implements PropertyTypeInterface, SchemaMetatagTestTagInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The schemaMetatagManager service.
   *
   * @var \Drupal\schema_metatag\SchemaMetatagManagerInterface
   */
  protected $schemaMetatagManager;

  /**
   * The SchemaMetatagClient service.
   *
   * @var \Drupal\schema_metatag\SchemaMetatagClientInterface
   */
  protected $schemaMetatagClient;

  /**
   * The propertyTypeManager service.
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
    $instance->setSchemaMetatagManager($container->get('schema_metatag.schema_metatag_manager'));
    $instance->setSchemaMetatagClient($container->get('schema_metatag.schema_metatag_client'));
    $instance->setPropertyTypeManager($container->get('plugin.manager.schema_property_type'));
    return $instance;
  }

  /**
   * Sets schemaMetatagManager service.
   *
   * @param \Drupal\schema_metatag\SchemaMetatagManagerInterface $schemaMetatagManager
   *   The Schema Metatag Manager service.
   */
  public function setSchemaMetatagManager(SchemaMetatagManagerInterface $schemaMetatagManager) {
    $this->schemaMetatagManager = $schemaMetatagManager;
  }

  /**
   * Sets SchemaMetatagClient service.
   *
   * @param \Drupal\schema_metatag\SchemaMetatagClientInterface $schemaMetatagClient
   *   The Schema.org client.
   */
  public function setSchemaMetatagClient(SchemaMetatagClientInterface $schemaMetatagClient) {
    $this->schemaMetatagClient = $schemaMetatagClient;
  }

  /**
   * Sets PropertyTypeManager service.
   *
   * @param \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManager $propertyTypeManager
   *   The property type manager.
   */
  public function setPropertyTypeManager(PropertyTypeManager $propertyTypeManager) {
    $this->propertyTypeManager = $propertyTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function schemaMetatagManager() {
    return $this->schemaMetatagManager;
  }

  /**
   * {@inheritdoc}
   */
  public function schemaMetatagClient() {
    return $this->schemaMetatagClient;
  }

  /**
   * {@inheritdoc}
   */
  public function getTreeParent() {
    return !empty($this->pluginDefinition['tree_parent']) ? $this->pluginDefinition['tree_parent'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTreeDepth() {
    return !empty($this->pluginDefinition['tree_depth']) ? $this->pluginDefinition['tree_depth'] : -1;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyType() {
    return $this->pluginDefinition['property_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubProperties() {
    return !empty($this->pluginDefinition['sub_properties']) ? $this->pluginDefinition['sub_properties'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function propertyInfo($property_type, $with_parents = TRUE) {
    $all_properties = $this->schemaMetatagClient()->getProperties();
    $properties = [];
    $parents = [$property_type];
    if ($with_parents) {
      $parents += $this->schemaMetatagClient()->getParents($property_type);
    }
    foreach ($parents as $type) {
      $properties = array_merge($properties, $all_properties[$type]);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree($parent, $depth) {
    $tree = [];
    foreach ((array) $parent as $item) {
      $tree = array_merge($this->schemaMetatagClient->getTree($item, $depth), $tree);
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionList($parent, $depth) {
    $list = [];
    foreach ((array) $parent as $item) {
      $list = array_merge($this->schemaMetatagClient->getOptionList($item, $depth), $list);
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $input_values) {

    $default_values = $this->schemaMetatagManager()->defaultInputValues();
    $input_values = array_merge($default_values, $input_values);

    // If no tree values were supplied, default to the values of the property
    // type plugin.
    if (empty($input_values['tree_parent'])) {
      $input_values['tree_parent'] = $this->getTreeParent();
      $input_values['tree_depth'] = $this->getTreeDepth();
    }

    // The properties and property types to generate for this form.
    $sub_properties = $this->getSubProperties();

    if (!empty($sub_properties)) {

      $form['#type'] = 'fieldset';
      $form['#title'] = $input_values['title'];
      $form['#description'] = $input_values['description'];
      $form['#tree'] = TRUE;

      // For each sub property, generate a form element for the sub property
      // by invoking an instance of that child property type.
      foreach ($sub_properties as $sub_property_name => $values) {
        $sub_property_type = $values['id'];
        $child_property = $this->getChildPropertyType($sub_property_type);
        $sub_property_value = is_array($input_values['value']) && array_key_exists($sub_property_name, $input_values['value']) ? $input_values['value'][$sub_property_name] : NULL;

        $sub_input_values['title'] = $values['label'];
        $sub_input_values['description'] = $values['description'];
        $sub_input_values['value'] = $sub_property_value;

        $sub_input_values['visibility_selector'] = $input_values['visibility_selector'];
        if (!empty($values['tree_parent'])) {
          $sub_input_values['visibility_selector'] .= "[$sub_property_name]";
        }

        // Pass parent tree values when empty, otherwise give each sub property
        // its own tree values.
        $sub_input_values['tree_parent'] = empty($values['tree_parent']) ? $input_values['tree_parent'] : $values['tree_parent'];
        $sub_input_values['tree_depth'] = empty($values['tree_depth']) ? $input_values['tree_depth'] : $values['tree_depth'];

        // Generate the sub property form element.
        $form[$sub_property_name] = $child_property->form($sub_input_values);

        if ($sub_property_name != '@type') {
          // Add #states to hide this whole section if @type is empty.
          $form[$sub_property_name]['#states'] = $this->getVisibility($input_values);
        }
        else {
          // Add a pivot element to the top of multiple value forms.
          if (!empty($input_values['multiple'])) {
            $value = is_array($input_values['value']) && array_key_exists('pivot', $input_values['value']) ? $input_values['value']['pivot'] : 0;
            $form['pivot'] = $this->pivotForm($value);
            $form['pivot']['#states'] = $this->getVisibility($input_values);
          }
        }
        // Add pivot field to sub properties.
        if (isset($form[$sub_property_name]['@type']) && isset($form['pivot'])) {
          $value_sub_property = $input_values['value'][$sub_property_name]['pivot'] ?? 0;
          $pivot_form = $this->pivotForm($value_sub_property);
          $pivot_form['#states'] = $this->getVisibility($sub_input_values);
          // Move the pivot right after @type field.
          $index = array_search('@type', array_keys($form[$sub_property_name]), TRUE);
          if ($index === FALSE) {
            $form[$sub_property_name]['pivot'] = $pivot_form;
          }
          else {
            $count = $index + 1;
            $form[$sub_property_name] = array_merge(
              array_slice($form[$sub_property_name], 0, $count),
              ['pivot' => $pivot_form],
              array_slice($form[$sub_property_name], $count)
            );
          }
        }

      }
    }
    // For basic property types, those with no sub-properties, generate
    // a simple form element.
    else {
      $form = $this->formElement($input_values);
    }

    $form['#element_validate'] = [[get_class($this), 'validateProperty']];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(array $input_values) {

    $value = $input_values['value'];

    $form['#type'] = 'textfield';
    $form['#title'] = $input_values['title'];
    $form['#description'] = $input_values['description'];
    $form['#default_value'] = !empty($value) ? $value : '';
    $form['#maxlength'] = 255;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function pivotForm($value) {

    $form = [
      '#type' => 'select',
      '#title' => 'Pivot',
      '#default_value' => $value,
      '#empty_option' => ' - ' . $this->t('None') . ' - ',
      '#empty_value' => '',
      '#options' => [
        1 => $this->t('Pivot'),
      ],
      '#description' => $this->t('Combine and pivot multiple values to display them as multiple objects.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility(array $input_values) {
    $selector = ':input[name="' . $input_values['visibility_selector'] . '[@type]"]';
    $visibility = ['invisible' => [$selector => ['value' => '']]];
    $selector2 = $this->schemaMetatagManager()->altSelector($selector);
    $visibility2 = ['invisible' => [$selector2 => ['value' => '']]];
    $visibility['invisible'] = [
      $visibility['invisible'],
      $visibility2['invisible'],
    ];
    return $visibility;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateProperty(array &$element, FormStateInterface $form_state) {
    // Extend as needed to validate property types.
  }

  /**
   * {@inheritdoc}
   */
  public function getChildPropertyType($plugin_id) {
    return $this->propertyTypeManager->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function outputValue($input_value) {
    return $input_value;
  }

  /**
   * {@inheritdoc}
   */
  public function testValue($original_type = '') {
    if (empty($this->getSubProperties())) {
      return self::testDefaultValue(2, ' ');
    }
    else {
      $items = [];
      foreach ($this->getSubProperties() as $property_name => $values) {
        $plugin = $this->getChildPropertyType($values['id']);
        if ($property_name == '@type') {
          $items[$property_name] = $plugin->testValue($original_type);
        }
        else {
          $type = !empty($values['tree_parent']) ? $values['tree_parent'] : $plugin->getTreeParent();
          $test_type = is_array($type) ? array_shift($type) : $type;
          $items[$property_name] = $plugin->testValue($test_type);
        }
      }
      return $items;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processedTestValue($items) {
    if (empty($this->getSubProperties())) {
      return $this->processTestExplodeValue($items);
    }
    else {
      foreach ($this->getSubProperties() as $property_name => $values) {
        $items[$property_name] = $this->getChildPropertyType($values['id'])->processedTestValue($items[$property_name]);
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function processTestExplodeValue($items) {
    if (!is_array($items)) {
      $items = $this->schemaMetatagManager()->explode($items);
      // Clean out any empty values that might have been added by explode().
      if (is_array($items)) {
        array_filter($items);
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function testDefaultValue($count = NULL, $delimiter = NULL) {
    $items = [];
    $min = 1;
    $max = $count ?? 2;
    $delimiter = $delimiter ?? ' ';
    for ($i = $min; $i <= $max; $i++) {
      $items[] = $this->schemaMetatagManager()->randomMachineName();
    }
    return implode($delimiter, $items);
  }

}
