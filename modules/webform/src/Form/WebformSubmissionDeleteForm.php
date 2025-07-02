<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation webform for deleting a webform submission.
 */
class WebformSubmissionDeleteForm extends ContentEntityDeleteForm implements WebformDeleteFormInterface {

  use WebformDialogFormTrait;

  /**
   * The webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform submission entity.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * The webform source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    [$this->webformSubmission, $this->sourceEntity] = $this->requestHandler->getWebformSubmissionEntities();
    $this->webform = $this->webformSubmission->getWebform();

    $form['warning'] = $this->getWarning();
    $form = parent::buildForm($form, $form_state);
    $form['description'] = $this->getDescription();

    return $this->buildDialogConfirmForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Issue #2582295: Confirmation cancel links are incorrect if installed in
    // a subdirectory
    // Work-around: Remove subdirectory from destination before generating
    // actions.
    $request = $this->getRequest();
    $destination = $request->query->get('destination');
    if ($destination) {
      // Remove subdirectory from destination.
      $update_destination = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $destination);
      $request->query->set('destination', $update_destination);
      $actions = parent::actions($form, $form_state);
      $request->query->set('destination', $destination);
      return $actions;
    }
    else {
      return parent::actions($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $t_args = [
      '%label' => $this->getEntity()->label(),
    ];
    return $this->t('Delete %label?', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    $t_args = [
      '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
      '%label' => $this->getEntity()->label(),
    ];

    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to delete the %label @entity-type?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // @see \Drupal\webform\Form\WebformSubmissionDeleteMultipleForm::getDescription
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove records from the database'),
          $this->t('Delete any uploaded files'),
          $this->t('Cancel all pending actions'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmInput() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('%label has been deleted.', ['%label' => $this->webformSubmission->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->webform->access('submission_view_own') || $this->webform->access('submission_view_any')) {
      $base_route_name = (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.user.submission.delete') !== FALSE) ? 'webform.user.submissions' : 'webform.results_submissions';
      return $this->requestHandler->getUrl($this->webform, $this->sourceEntity, $base_route_name);
    }
    elseif ($this->sourceEntity && $this->sourceEntity->hasLinkTemplate('canonical') && $this->sourceEntity->access('view')) {
      return $this->sourceEntity->toUrl();
    }
    elseif ($this->webform->access('view')) {
      return $this->webform->toUrl();
    }
    else {
      return Url::fromRoute('<front>');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    // Deletion logging is handled via WebformSubmissionStorage.
    // @see \Drupal\webform\WebformSubmissionStorage::delete
  }

}
