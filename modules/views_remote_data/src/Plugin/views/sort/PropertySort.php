<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views_remote_data\Plugin\views\PropertyPluginTrait;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;

/**
 * Sort plugin to sort values from the result at a property path.
 *
 * @ViewsSort("views_remote_data_property")
 */
final class PropertySort extends SortPluginBase {

  use PropertyPluginTrait;

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
  public function query(): void {
    assert($this->query instanceof RemoteDataQuery);
    $this->query->addOrderBy(
      '',
      $this->options['property_path'],
      $this->options['order']
    );
  }

}
