<?php

namespace Drupal\webform_cards;

use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_cards\Plugin\WebformElement\WebformCard;

/**
 * Manage webform cards.
 */
class WebformCardsManager implements WebformCardsManagerInterface {

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform submission (server-side) conditions (#states) validator.
   *
   * @var \Drupal\webform\WebformSubmissionConditionsValidator
   */
  protected $conditionsValidator;

  /**
   * Constructs a WebformCardsManager object.
   *
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   */
  public function __construct(WebformElementManagerInterface $element_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator) {
    $this->elementManager = $element_manager;
    $this->conditionsValidator = $conditions_validator;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCards(WebformInterface $webform) {
    return ($this->getNumberOfCards($webform) > 0) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfCards(WebformInterface $webform) {
    $elements = $webform->getElementsDecoded();
    $count = 0;
    foreach ($elements as $element) {
      if (is_array($element)) {
        $element_plugin = $this->elementManager->getElementInstance($element);
        if ($element_plugin instanceof WebformCard) {
          $count++;
        }
      }
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPages(WebformInterface $webform, $operation = 'default') {
    $card_properties = [
      '#title' => '#title',
      '#states' => '#states',
    ];

    $pages = [];

    // Add webform cards.
    $elements = $webform->getElementsInitialized();
    if (is_array($elements) && !in_array($operation, ['edit_all', 'api'])) {
      foreach ($elements as $key => $element) {
        if (!isset($element['#type'])) {
          continue;
        }

        /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
        $element_plugin = $this->elementManager->getElementInstance($element, $webform);
        if (!($element_plugin instanceof WebformCard)) {
          continue;
        }

        // Check element access rules and only include pages that are visible
        // to the current user.
        $access_operation = (in_array($operation, ['default', 'add'])) ? 'create' : 'update';
        if ($element_plugin->checkAccessRules($access_operation, $element)) {
          $pages[$key] = array_intersect_key($element, $card_properties) + [
            '#type' => 'card',
            '#access' => TRUE,
          ];
        }
      }
    }

    // Add preview.
    if ($webform->getSetting('preview') !== DRUPAL_DISABLED) {
      $pages[WebformInterface::PAGE_PREVIEW] = [
        '#title' => $webform->getSetting('preview_label', TRUE),
        '#type' => 'page',
        '#access' => TRUE,
      ];
    }

    // Add confirmation.
    if ($webform->getSetting('wizard_confirmation')) {
      $pages[WebformInterface::PAGE_CONFIRMATION] = [
        '#title' => $webform->getSetting('wizard_confirmation_label', TRUE),
        '#type' => 'page',
        '#access' => TRUE,
      ];
    }

    return $pages;
  }

  /**
   * Update cards pages based on conditional logic (#states).
   *
   * @param array $pages
   *   An associative array of webform cards.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   An associative array of webform cards with conditional logic applied.
   *
   * @see \Drupal\webform\Entity\Webform::getPages
   */
  public function applyConditions(array $pages, WebformSubmissionInterface $webform_submission = NULL) {
    if ($webform_submission && $webform_submission->getWebform()->getSetting('wizard_progress_states')) {
      return $this->conditionsValidator->buildPages($pages, $webform_submission);
    }
    else {
      return $pages;
    }
  }

}
