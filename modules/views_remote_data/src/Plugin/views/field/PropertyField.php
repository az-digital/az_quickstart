<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views_remote_data\Plugin\views\PropertyPluginTrait;

/**
 * Field plugin to extract a value from the result at a property path.
 *
 * @ViewsField("views_remote_data_property")
 */
final class PropertyField extends FieldPluginBase {

  use PropertyPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Do nothing here.
    // However, the field alias needs to be set. This is used for click sorting
    // in the Table style and used by ::clickSort().
    $this->field_alias = $this->options['property_path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $property_path = explode('.', $this->options['property_path']);
    $property_name = array_shift($property_path);

    if (!isset($values->{$property_name})) {
      return NULL;
    }
    $value = $values->{$property_name};
    // Direct field, no properties.
    if (count($property_path) === 0) {
      return $value;
    }
    // Convert a generic object into an array.
    if ($value instanceof \stdClass) {
      $value = (array) $value;
    }
    // This isn't an array value, bail out. Bad property path.
    if (!is_array($value)) {
      return NULL;
    }
    return NestedArray::getValue($value, $property_path);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $this->definePropertyPathOption($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    $this->propertyPathElement($form, $this->options);
    parent::buildOptionsForm($form, $form_state);
  }

}
