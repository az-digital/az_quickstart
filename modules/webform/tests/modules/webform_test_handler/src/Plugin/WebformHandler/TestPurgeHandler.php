<?php

namespace Drupal\webform_test_handler\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a handler that interacts with the submission purge APIs.
 *
 * @WebformHandler(
 *   id = "test_purge",
 *   label = @Translation("Test purging"),
 *   category = @Translation("Testing"),
 *   description = @Translation("Tests webform submission handler purge APIS."),
 * )
 */
class TestPurgeHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function prePurge(array &$webform_submissions) {
    array_shift($webform_submissions);
    \Drupal::state()->set('webform_test_purge_handler_pre', array_map(function (WebformSubmissionInterface $submission) {
      return $submission->id();
    }, $webform_submissions));
    parent::prePurge($webform_submissions);
  }

  /**
   * {@inheritdoc}
   */
  public function postPurge(array $webform_submissions) {
    \Drupal::state()->set('webform_test_purge_handler_post', array_map(function (WebformSubmissionInterface $submission) {
      return $submission->id();
    }, $webform_submissions));
    parent::postPurge($webform_submissions);
  }

}
