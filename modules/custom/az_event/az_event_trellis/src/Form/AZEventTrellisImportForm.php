<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis\Form;

use Drupal\az_event_trellis\Entity\AZEventTrellisImport;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trellis Event Import form.
 */
final class AZEventTrellisImportForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);
    /** @var \Drupal\az_event_trellis\Entity\AZEventTrellisImport $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [AZEventTrellisImport::class, 'load'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];

    $form['keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('keyword'),
    ];

    $form['owner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Owner'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('owner'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new Trellis event import settings %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated new Trellis event import settings %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
