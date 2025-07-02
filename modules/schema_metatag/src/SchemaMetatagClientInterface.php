<?php

namespace Drupal\schema_metatag;

/**
 * The SchemaMetatag Client Interface.
 *
 * A class to parse Schema.org data.
 *
 * @package Drupal\schema_metatag
 */
interface SchemaMetatagClientInterface {

  /**
   * Retrieve and decode data from a local schema.jsonld file.
   *
   * The data comes from https://schema.org/version/latest/schema.jsonld and is
   * stored at /data. This file can be updated periodically.
   *
   * @return array
   *   A decoded array of Schema.org data.
   *
   * @see http://schema.org/docs/developers.html
   * @see https://github.com/schemaorg/schemaorg
   * @see https://schema.org/version/latest/schema.jsonld
   * @see https://schema.org/version/latest/schemaorg-all-http.jsonld
   */
  public function getLocalFile();

  /**
   * Retrieve an array of object information from the raw data.
   *
   * The raw data is a series of objects with information about each of them.
   * Each class contains a list of its parent classes, but the data is not
   * represented hierarchically.
   *
   * @param bool $clear
   *   Whether to clear the cached array created by getObjects().
   *
   * @return array
   *   An array of objects:
   *   - object name:
   *     - object: The name of the object class.
   *     - description: The description of the object.
   *     - parents: An array of objects this object is a subclass of.
   */
  public function objectInfo($clear = FALSE);

  /**
   * Retrieve object properties.
   *
   * This data contains detailed property information for each object.
   *
   * @param bool $clear
   *   Whether to clear the cached array created by getProperties().
   *
   * @return array
   *   An array of objects and their properties:
   *   - Object class name:
   *     - property name:
   *       - property: The name of the property.
   *       - description: The description of the property.
   *       - expected_types: An array of the expected types of the property.
   */
  public function propertyInfo($clear = FALSE);

  /**
   * Reorganize the classes into a hierarchical tree.
   *
   * The raw data doesn't show the whole hierarchy, just the immediate parents.
   * The tree allows us to identify which objects are the topmost level and
   * sort out other relationships between them, or pull out a collection of
   * objects that are children of a specific parent.
   *
   * @param bool $clear
   *   Whether to clear the cached array created by getObjectTree().
   * @param bool $clear_objects
   *   Whether to clear the cached array created by getObjects().
   *
   * @return array
   *   A hierarchical array of the object names.
   */
  public function getObjectTree($clear = FALSE, $clear_objects = FALSE);

  /**
   * Get some or all of the object tree.
   *
   * Examples:
   * - Use all types from 'Organization' down:
   *     $parent = 'Organization'
   *     $depth = -1
   * - Use all 'Organization' types, but only a maximum of two levels deep:
   *     $parent = 'Organization'
   *     $depth = 2
   * - Use only the top level for 'Organization':
   *     $parent = 'Organization'
   *     $depth = 0
   * - Both 'Place' and 'Virtual Location':
   *     $parent = 'Place,VirtualLocation'
   *     $depth = -1
   *
   * @param string $parent_name
   *   The key of the desired sub-array, if any.
   * @param int $depth
   *   The desired depth to retrieve below the parent, -1 for the whole tree.
   * @param bool $clear
   *   Whether to clear the array created by getTree().
   * @param bool $clear_tree
   *   Whether to clear the array created by getObjectTree().
   * @param bool $clear_objects
   *   Whether to clear the array created by getObjects().
   *
   * @return array
   *   A hierarchical array of the object names.
   */
  public function getTree($parent_name = NULL, $depth = -1, $clear = FALSE, $clear_tree = FALSE, $clear_objects = FALSE);

  /**
   * Get some or all of the object tree.
   *
   * @param string $base_tree
   *   The entire Schema.org tree.
   * @param string $parent_name
   *   The key of the desired sub-array, if any.
   * @param int $depth
   *   The desired depth to retrieve below the parent, -1 for the whole tree.
   *
   * @return array
   *   A hierarchical array of the object names.
   */
  public function getUncachedTree($base_tree, $parent_name = NULL, $depth = -1);

  /**
   * Get an array of all parents of a given class.
   *
   * @param string $child_name
   *   The key of the desired sub-array.
   *
   * @return array
   *   An array of parent classes, ordered from the lowest to the highest in
   *   the tree hierarchy.
   */
  public function getParents($child_name);

  /**
   * Create a @type option list from a given tree section.
   *
   * Used to create a pseudo "nested" option list used for @type.
   *
   * @param string $parent_name
   *   The key of the desired sub-array, if any.
   * @param int $depth
   *   The desired depth to retrieve below the parent, -1 for the whole tree.
   *
   * @return array
   *   An option array for the given parent.
   */
  public function getOptionList($parent_name = NULL, $depth = -1);

  /**
   * Clears all data from the cache.
   *
   * To be used if the raw data file is updated.
   */
  public function clearData();

  /**
   * Sort a nested associative array.
   *
   * @param array $array
   *   The array to sort.
   */
  public function sortAssocArray(array &$array);

  /**
   * Detect if this is a Schema.org class we care about.
   *
   * @param array $item
   *   The item to examine.
   *
   * @return bool
   *   Whether or not to include it.
   */
  public function isIncludedClass(array $item);

  /**
   * Detect if this is a Schema.org property we care about.
   *
   * @param array $item
   *   The item to examine.
   *
   * @return bool
   *   Whether or not to include it.
   */
  public function isIncludedProperty(array $item);

}
