<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the custom access control handler for the webform submission entities.
 */
class WebformSubmissionAccess {

  /**
   * Check whether a webform submissions' webform has wizard pages/cards.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWizardPagesAccess(WebformSubmissionInterface $webform_submission) {
    // Check wizard pages.
    $has_wizard_pages = ($webform_submission->getWebform()->hasWizardPages());

    // Check cards.
    if (\Drupal::moduleHandler()->moduleExists('webform_cards')) {
      /** @var \Drupal\webform_cards\WebformCardsManagerInterface $webform_cards_manager */
      $webform_cards_manager = \Drupal::service('webform_cards.manager');
      $has_cards = $webform_cards_manager->hasCards($webform_submission->getWebform());
    }
    else {
      $has_cards = FALSE;
    }

    return AccessResult::allowedIf($has_wizard_pages || $has_cards);
  }

  /**
   * Check that webform submission has (email) messages and the user can update any webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkResendAccess(WebformSubmissionInterface $webform_submission, AccountInterface $account) {
    if ($webform_submission->getWebform()->hasMessageHandler()) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
