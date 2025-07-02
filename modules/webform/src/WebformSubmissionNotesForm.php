<?php

namespace Drupal\webform;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Form\WebformDialogFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for webform submission notes.
 */
class WebformSubmissionNotesForm extends ContentEntityForm {

  use WebformDialogFormTrait;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->requestHandler = $container->get('webform.request');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    [$webform_submission, $source_entity] = $this->requestHandler->getWebformSubmissionEntities();

    $form['navigation'] = [
      '#type' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
      '#access' => $this->isDialog() ? FALSE : TRUE,
    ];
    $form['information'] = [
      '#type' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
      '#access' => $this->isDialog() ? FALSE : TRUE,
    ];

    $form['notes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Administrative notes'),
      '#description' => $this->t('Enter notes about this submission. These notes are only visible to submission administrators.'),
      '#default_value' => $webform_submission->getNotes(),
    ];
    $form['sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Star/flag the status of this submission'),
      '#description' => $this->t('If checked, this submissions will be starred when reviewing results.'),
      '#default_value' => $webform_submission->isSticky(),
      '#return_value' => TRUE,
      '#access' => $this->isDialog() ? FALSE : TRUE,
    ];
    $form['locked'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lock this submission'),
      '#description' => $this->t('If checked, users will not be able to update this submission.'),
      '#default_value' => $webform_submission->isLocked(),
      '#return_value' => TRUE,
      '#access' => $this->isDialog() ? FALSE : TRUE,
    ];
    if ($this->currentUser()->hasPermission('administer users')) {
      $form['uid'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Submitted by'),
        '#description' => $this->t('The username of the user that submitted the webform.'),
        '#target_type' => 'user',
        '#required' => TRUE,
        '#default_value' => $webform_submission->getOwner(),
      ];
    }

    $form['#attached']['library'][] = 'webform/webform.admin';

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->messenger()->addStatus($this->t('Submission @sid notes saved.', ['@sid' => '#' . $this->entity->id()]));
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->entity->toUrl('edit-notes-form');
  }

}
