<?php

namespace Drupal\az_marketing_cloud\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\Links;
use Drupal\views\ResultRow;

/**
 * Renders links as a drop button.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("az_dropbutton")]
class AzDropbutton extends Links {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['dropbutton_type'] = [
      'default' => 'small',
      'extrasmall' => 'extrasmall',
    ];
    $options['click_action'] = [
      'default' => '',
      'js-click2copy' => 'js-click2copy',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['dropbutton_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Dropbutton type'),
      '#description' => $this->t('Dropbutton type.'),
      '#default_value' => $this->options['dropbutton_type'],
      '#options' => [
        'small' => 'Small',
        'extrasmall' => 'Extra small',
      ],
    ];
    $form['click_action'] = [
      '#type' => 'select',
      '#title' => $this->t('Click action'),
      '#description' => $this->t('Determine how links should behave.'),
      '#default_value' => $this->options['click_action'],
      '#options' => [
        'none' => 'None',
        'js-click2copy' => 'Click to copy to clipboard via JS',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $links = $this->getLinks() ?? [];
    $click_action = $this->options['click_action'] ?? '';
    $dropbutton_type = $this->options['dropbutton_type'] ?? '';
    $dropbutton = [
      '#type' => 'dropbutton',
      '#links' => [],
    ];
    if (!empty($dropbutton_type)) {
      $dropbutton['#dropbutton_type'] = $dropbutton_type;
    }
    if (!empty($links)) {
      $dropbutton['#links'] = $links;
    }
    if ($this->options['click_action'] === 'js-click2copy') {
      $dropbutton['#attributes']['class'][] = $click_action;
      $dropbutton['#attached']['library'][] = 'az_marketing_cloud/admin';
    }

    return $dropbutton;
  }

}
