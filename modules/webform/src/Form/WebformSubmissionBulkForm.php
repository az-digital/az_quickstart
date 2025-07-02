<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the webform submission bulk form.
 */
class WebformSubmissionBulkForm extends WebformBulkFormBase {

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'webform_submission';

  /**
   * Can user delete any submission.
   *
   * @var bool
   */
  protected $submissionDeleteAny = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table = [], $submission_delete_any = FALSE) {
    // Track if the user can delete any submission.
    $this->submissionDeleteAny = $submission_delete_any;

    // Restructure the table rows so that they can be displayed as
    // table select options.
    foreach ($table['#rows'] as $key => $row) {
      $table['#rows'][$key] = $row['data'] + ['#attributes' => ['data-webform-href' => $row['data-webform-href']]];
    }

    return parent::buildForm($form, $form_state, $table);
  }

  /**
   * {@inheritdoc}
   */
  protected function getActions() {
    $actions = parent::getActions();
    // Make sure user can delete any submission.
    if (!$this->submissionDeleteAny) {
      unset($actions['webform_submission_delete_action']);
    }
    return $actions;
  }

}
