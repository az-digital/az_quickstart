<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for property type plugins.
 */
interface PropertyTypeInterface extends PluginInspectionInterface {

  /**
   * The Schema Metatag Manager service.
   *
   * @return \\Drupal\schema_metatag\schemaMetatagManager
   *   The Schema Metatag Manager service.
   */
  public function schemaMetatagManager();

  /**
   * The Schema Metatag Client service.
   *
   * @return \Drupal\schema_metatag\SchemaMetatagClient
   *   The Schema Metatag Client service.
   */
  public function schemaMetatagClient();

  /**
   * The classes to use for the @type options of this property.
   *
   * @return array
   *   Returns an array of classes.
   */
  public function getTreeParent();

  /**
   * The depth of the class tree to use for @type options.
   *
   * @return int
   *   The depth of the class tree.
   */
  public function getTreeDepth();

  /**
   * The property type.
   *
   * @return string
   *   Returns the property type that this plugin can create.
   */
  public function getPropertyType();

  /**
   * The sub-properties.
   *
   * @return array
   *   Returns a key/value array of property name and property info used
   *   in this plugin.
   */
  public function getSubProperties();

  /**
   * Get all the properties of a property type.
   *
   * @param string $property_type
   *   The name of the property type.
   * @param bool $with_parents
   *   Whether or not to retrieve the properties of all the parents of this
   *   class as well as its unique properties. Each Schema.org class gets
   *   many of its properties from its parent classes, and has only a few that
   *   are unique to itself.
   *
   * @return array
   *   An array keyed by property name that contains information about each
   *   property for this class.
   */
  public function propertyInfo($property_type, $with_parents);

  /**
   * Get some or all of the object tree as options for @type.
   *
   * @param mixed $parent
   *   Null or Array of the top level Schema.org object(s) used by this
   *   class, the objects that should be displayed as options for the @type
   *   property.
   * @param int $depth
   *   Goes with the above value, the depth used for the above parent(s) to
   *   create the desired array of @type values.
   *
   * @return array
   *   A hierarchical array of the object names for this class.
   *
   * @see \Drupal\schema_metatag\SchemaMetatagClient::getTree();
   */
  public function getTree($parent, $depth);

  /**
   * Create an option list for a given tree section.
   *
   * Used to create a pseudo "nested" option list used for the @type selector.
   *
   * @param mixed $parent
   *   Null or Array of the top level Schema.org object(s) used by this
   *   class, the objects that should be displayed as options for the @type
   *   property.
   * @param int $depth
   *   Goes with the above value, the depth used for the above parent(s) to
   *   create the desired array of @type values.
   *
   * @return array
   *   An option array for the class parent.
   */
  public function getOptionList($parent, $depth);

  /**
   * Create a complete form element for this property type.
   *
   * @param array $input_values
   *   An array of values to be passed to the form creator, including:
   *   - @var string 'title'
   *       The title to use for the form element.
   *   - @var string 'description'
   *       The description to use for the form element.
   *   - @var array 'value'
   *       The current value of the form element.
   *   - @var string 'visibility_selector'
   *       The selector to use in assessing form element visibility, usually
   *       the @type element.
   *   - @var array 'tree_parent'
   *       The top level to use for @type, defaults to ''.
   *   - @var int 'tree_depth'
   *       The depth to go in the tree hierarchy, defaults to -1.
   *   - @var string 'multiple'
   *       Whether multiple values should be allowed, defaults to FALSE.
   *
   * @return array
   *   Return a form array.
   */
  public function form(array $input_values);

  /**
   * A property form element.
   *
   * This will actually be rendered only by properties that have no more
   * sub-properties.
   *
   * @param array $input_values
   *   The array of input values used by form().
   *
   * @return array
   *   A form array.
   */
  public function formElement(array $input_values);

  /**
   * Pivot form element.
   *
   * @param int $value
   *   The current value for the pivot form.
   *
   * @return array
   *   A form array.
   */
  public function pivotForm($value);

  /**
   * Construct the visibility selector for a set of values.
   *
   * @param array $input_values
   *   The array of input values used by form().
   *
   * @return array
   *   A form array suitable for populating "#states" for a form element.
   */
  public function getVisibility(array $input_values);

  /**
   * Validates the property form when submitted.
   *
   * Optional per-property type validation of form values.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateProperty(array &$element, FormStateInterface $form_state);

  /**
   * Get an instance of a child property type.
   *
   * Used to do things like get the test value for a child element on complex
   * property types that contain other types.
   *
   * @param string $plugin_id
   *   The plugin id of the child property type.
   *
   * @return \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeInterface
   *   An instance of the child property type.
   */
  public function getChildPropertyType($plugin_id);

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
  public function outputValue($input_value);

}
