<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views_remote_data\Plugin\views\PropertyPluginTrait;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;

/**
 * Views argument for remote data.
 *
 * @ViewsArgument("views_remote_data_property")
 */
final class PropertyArgument extends ArgumentPluginBase {

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
  public function query($group_by = FALSE): void {
    assert($this->query instanceof RemoteDataQuery);
    $this->query->addWhere(
      '0',
      $this->options['property_path'],
      $this->argument,
      '='
    );
  }

}
