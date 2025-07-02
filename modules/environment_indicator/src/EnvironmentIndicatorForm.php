<?php

namespace Drupal\environment_indicator;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form field specifications.
 */
class EnvironmentIndicatorForm extends EntityForm {

  /**
   * This actually builds your form.
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\environment_indicator\Entity\EnvironmentIndicator $environment_switcher */
    $environment_switcher = $this->getEntity();

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $environment_switcher->label(),
    ];
    $form['machine'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'source' => ['name'],
        'exists' => 'environment_indicator_load',
      ],
      '#default_value' => $environment_switcher->id(),
      '#disabled' => !empty($environment_switcher->machine),
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('The hostname you want to switch to.'),
      '#default_value' => $environment_switcher->getUrl(),
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $environment_switcher->getWeight(),
    ];

    $form['bg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#description' => $this->t('Background color for the indicator. Ex: #0D0D0D.'),
      '#default_value' => $environment_switcher->getBgColor() ?: '#0D0D0D',
    ];
    $form['fg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#description' => $this->t('Color for the indicator. Ex: #D0D0D0.'),
      '#default_value' => $environment_switcher->getFgColor() ?: '#D0D0D0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Save your config entity.
   *
   * There will eventually be default code to rely on here, but it doesn't exist
   * yet.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $environment = $this->getEntity();
    $environment->save();
    $this->messenger()->addMessage($this->t('Saved the %label environment.', [
      '%label' => $environment->label(),
    ]));

    $form_state->setRedirect('entity.environment_indicator.collection');
  }

}
