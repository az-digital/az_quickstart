<?php

namespace Drupal\webform\Element;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines an interface for webform computed element.
 */
interface WebformComputedInterface {

  /**
   * Denotes HTML.
   *
   * @var string
   */
  const MODE_HTML = 'html';

  /**
   * Denotes plain text.
   *
   * @var string
   */
  const MODE_TEXT = 'text';

  /**
   * Denotes markup whose content type should be detected.
   *
   * @var string
   */
  const MODE_AUTO = 'auto';

  /**
   * Compute value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return string
   *   The computed value.
   */
  public static function computeValue(array $element, WebformSubmissionInterface $webform_submission);

}
