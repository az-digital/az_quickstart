<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views_remote_data\Plugin\views\PropertyPluginTrait;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;

/**
 * Filter plugin to filter values from the result at a property path.
 *
 * @ViewsFilter("views_remote_data_property")
 */
final class PropertyFilter extends FilterPluginBase {

  use PropertyPluginTrait;

  /**
   * {@inheritdoc}
   *
   * The string, equality, numeric, and boolean filters set this to TRUE. It
   * prevents the value from being wrapped as an array.
   */
  protected $alwaysMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions(): array {
    return [
      '=' => $this->t('Is equal to'),
      '!=' => $this->t('Is not equal to'),
    ];
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

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    assert($this->query instanceof RemoteDataQuery);
    $this->query->addWhere(
      $this->options['group'],
      $this->options['property_path'],
      $this->value,
      $this->operator
    );
  }

}
