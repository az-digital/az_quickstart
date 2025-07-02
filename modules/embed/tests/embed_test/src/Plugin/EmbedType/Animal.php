<?php

namespace Drupal\embed_test\Plugin\EmbedType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\embed\EmbedType\EmbedTypeBase;

/**
 * Animal test embed type.
 *
 * @EmbedType(
 *   id = "embed_test_animal",
 *   label = @Translation("Animals"),
 * )
 */
class Animal extends EmbedTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();

    $form['animal_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Animal type'),
      '#options' => [
        'vertebrates' => $this->t('Vertebrates (with backbone)'),
        'invertebrates' => $this->t('Invertebrates (without backbone)'),
      ],
      '#default_value' => $this->getConfigurationValue('animal_type'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$form_object, 'updateTypeSettings'],
        'effect' => 'fade',
      ],
    ];

    if ($this->getConfigurationValue('animal_type') === 'vertebrates') {
      $form['allowed_vertebrates'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Limit allowed vertebrates'),
        '#options' => [
          'amphibians' => $this->t('Amphibians'),
          'birds' => $this->t('Birds'),
          'fish' => $this->t('Fish'),
          'mammals' => $this->t('Mammals'),
          'reptiles' => $this->t('Reptiles'),
        ],
        '#default_value' => $this->getConfigurationValue('allowed_vertebrates', []),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return '/animal.png';
  }

}
