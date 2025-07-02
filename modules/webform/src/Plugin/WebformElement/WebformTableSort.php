<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformInterface;

/**
 * Provides a 'webform_table_sort' element.
 *
 * @WebformElement(
 *   id = "webform_table_sort",
 *   label = @Translation("Table sort"),
 *   description = @Translation("Provides a form element for a table of values that can be sorted."),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformTableSort extends OptionsBase {

  use WebformTableTrait;

  /**
   * {@inheritdoc}
   */
  protected $exportDelta = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();
    unset($properties['options_randomize']);
    return $properties;
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsDefaultFormat() {
    return 'ol';
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $values = array_keys($element['#options']);
    if ($options['random']) {
      shuffle($values);
    }
    return $values;
  }

}
