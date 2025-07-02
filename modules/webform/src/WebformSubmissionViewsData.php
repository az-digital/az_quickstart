<?php

namespace Drupal\webform;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the webform submission entity type.
 */
class WebformSubmissionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Disable hook_webform_submission_query_access_alter() to remove
    // performance issues related to loading all webforms.
    // phpcs:ignore Drupal.Commenting.InlineComment.InvalidEndChar
    // $data['webform_submission']['table']['base']['access query tag'] = 'webform_submission_access';
    $data['webform_submission']['webform_submission_bulk_form'] = [
      'title' => $this->t('Webform submission operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple submissions.'),
      'field' => [
        'id' => 'webform_submission_bulk_form',
      ],
    ];

    return $data;
  }

}
