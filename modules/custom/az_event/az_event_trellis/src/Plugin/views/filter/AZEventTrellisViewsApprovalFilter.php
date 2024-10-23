<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views_remote_data\Plugin\views\PropertyPluginTrait;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;

/**
 * Filter Trellis event API values according to approval status.
 */
#[ViewsFilter("az_event_trellis_views_approval_filter")]
class AZEventTrellisViewsApprovalFilter extends FilterPluginBase {

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
      '#type' => 'select',
      '#title' => $this->t('Value'),
      '#options' => [
        'approved' => $this->t('Approved'),
        'denied' => $this->t('Denied'),
      ],
      '#required' => FALSE,
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
  public function adminSummary() {
    $prop = $this->options['property_path'] ?? '';
    return '(API value ' . $prop . ') ' . $this->operator . ' ' . $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    if (!($this->query instanceof RemoteDataQuery)) {
      return;
    }
    $this->query->addWhere(
      $this->options['group'],
      $this->options['property_path'],
      $this->value,
      $this->operator
    );
  }

}
