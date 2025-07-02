<?php

namespace Drupal\webform_attachment\Element;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_attachment_token' element.
 *
 * @FormElement("webform_attachment_token")
 */
class WebformAttachmentToken extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#template' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission) {
    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    $template = $element['#template'] ?? '';
    $content = $token_manager->replace($template, $webform_submission);
    return (!empty($element['#trim'])) ? trim($content) : $content;
  }

}
