<?php

namespace Drupal\schema_metatag;

/**
 * Interface SchemaMetatagTestTagInterface.
 *
 * Methods that provide test values for SchemaNameBase and its derivatives.
 *
 * @package Drupal\schema_metatag
 */
interface SchemaMetatagTestTagInterface {

  /**
   * Provide a test input value for the property that will validate.
   *
   * Tags like @type that contain values other than simple strings, for
   * instance a list of allowed options, should extend this method and return
   * a valid value.
   *
   * @param mixed $type
   *   The default to use for the @type element, if any.
   *
   * @return mixed
   *   Return the test value, either a string or array, depending on the
   *   property.
   */
  public function testValue($type = '');

  /**
   * Provide a test output value for the input value.
   *
   * Tags that return values in a different format than the input, like
   * values that are exploded, should extend this method and return
   * a valid value.
   *
   * @param mixed $items
   *   The input value, either a string or an array.
   *
   * @return mixed
   *   Return the correct output value.
   */
  public function processedTestValue($items);

  /**
   * Explode a test value.
   *
   * For test values, emulates the extra processing a multiple value would get.
   *
   * @param mixed $items
   *   The input value, either a string or an array.
   *
   * @return mixed
   *   Return the correct output value.
   */
  public function processTestExplodeValue($items);

  /**
   * Provide a random test value.
   *
   * A helper function to create a random test value. Use the delimiter to
   * create comma-separated values, or a few "words" separated by spaces.
   *
   * @param int $count
   *   Number of "words".
   * @param int $delimiter
   *   Delimiter used to connect "words".
   *
   * @return mixed
   *   Return the test value, either a string or array, depending on the
   *   property.
   */
  public function testDefaultValue($count = NULL, $delimiter = NULL);

}
