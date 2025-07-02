<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base webform for deleting webform submission.
 */
abstract class WebformSubmissionsDeleteFormBase extends WebformDeleteFormBase {

  use WebformEntityStorageTrait;

  /**
   * Total number of submissions.
   *
   * @var int
   */
  protected $submissionTotal;

  /**
   * Default number of submission to be deleted during batch processing.
   *
   * @var int
   */
  protected $batchLimit = 1000;

  /**
   * The webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->requestHandler = $container->get('webform.request');
    [$instance->webform, $instance->sourceEntity] = $instance->requestHandler->getWebformEntities();
    return $instance;

  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clear');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getCancelUrl());
    if ($this->getSubmissionStorage()->getTotal($this->webform, $this->sourceEntity) < $this->getBatchLimit()) {
      $this->getSubmissionStorage()->deleteAll($this->webform, $this->sourceEntity);
      $this->messenger()->addStatus($this->getFinishedMessage());
    }
    else {
      $this->batchSet($this->webform, $this->sourceEntity);
    }
  }

  /**
   * Get webform or source entity label.
   *
   * @return null|string
   *   Webform or source entity label.
   */
  public function getLabel() {
    if ($this->sourceEntity) {
      return $this->sourceEntity->label();
    }
    elseif ($this->webform->label()) {
      return $this->webform->label();
    }
    else {
      return '';
    }
  }

  /**
   * Message to displayed after submissions are deleted.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Message to be displayed after delete has finished.
   */
  public function getFinishedMessage() {
    return $this->t('Webform submissions cleared.');
  }

  /* ************************************************************************ */
  // Batch API.
  /* ************************************************************************ */

  /**
   * Batch API; Initialize batch operations.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The webform's source entity.
   */
  public function batchSet(WebformInterface $webform = NULL, EntityInterface $entity = NULL) {
    $parameters = [
      $webform,
      $entity,
      $this->getSubmissionStorage()->getMaxSubmissionId($webform, $entity),
    ];
    $batch = [
      'title' => $this->t('Clear submissions'),
      'init_message' => $this->t('Clearing submission data'),
      'error_message' => $this->t('The submissions could not be cleared because an error occurred.'),
      'operations' => [
        [[$this, 'batchProcess'], $parameters],
      ],
      'finished' => [$this, 'batchFinish'],
    ];

    batch_set($batch);
  }

  /**
   * Get the number of submissions to be deleted with each batch.
   *
   * @return int
   *   Number of submissions to be deleted with each batch.
   */
  public function getBatchLimit() {
    return $this->config('webform.settings')->get('batch.default_batch_delete_size') ?: $this->batchLimit;
  }

  /**
   * Batch API callback; Delete submissions.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The webform's source entity.
   * @param int $max_sid
   *   The max submission ID to be delete.
   * @param mixed|array $context
   *   The batch current context.
   */
  public function batchProcess(WebformInterface $webform = NULL, EntityInterface $entity = NULL, $max_sid = NULL, &$context = []) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $this->getSubmissionStorage()->getTotal($webform, $entity, NULL, ['in_draft' => NULL]);
      $context['results']['webform'] = $webform;
      $context['results']['entity'] = $entity;
    }

    // Track progress.
    $context['sandbox']['progress'] += $this->getSubmissionStorage()->deleteAll($webform, $entity, $this->getBatchLimit(), $max_sid);

    $context['message'] = $this->t('Deleting @count of @total submissions…', ['@count' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    // Track finished.
    if ($context['sandbox']['progress'] !== $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch API callback; Completed deletion.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of function calls (not used in this function).
   */
  public function batchFinish($success = FALSE, array $results = [], array $operations = []) {
    if (!$success) {
      $this->messenger()->addStatus($this->t('Finished with an error.'));
    }
    else {
      $this->messenger()->addStatus($this->getFinishedMessage());
    }
  }

  /**
   * Get total number of submissions.
   *
   * @return int
   *   Total number of submissions.
   */
  protected function getSubmissionTotal() {
    if (!isset($this->submissionTotal)) {
      $this->submissionTotal = $this->getSubmissionStorage()->getTotal($this->webform, $this->sourceEntity, NULL, ['in_draft' => NULL]);
    }
    return $this->submissionTotal;
  }

}
