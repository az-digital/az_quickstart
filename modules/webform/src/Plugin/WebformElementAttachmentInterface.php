<?php

namespace Drupal\webform\Plugin;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the interface for webform elements can provide email attachments.
 */
interface WebformElementAttachmentInterface {

  /**
   * Get files as email attachments.
   *
   * This is also used to export attachments.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An array containing email attachments which include an attachments
   *   'filename', 'filemime', 'filepath', and 'filecontent'.
   *
   * @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessageAttachments
   * @see \Drupal\mimemail\Utility\MimeMailFormatHelper::mimeMailHtmlBody
   * @see \Drupal\smtp\Plugin\Mail\SMTPMailSystem::mail
   * @see \Drupal\swiftmailer\Plugin\Mail\SwiftMailer::attachAsMimeMail
   */
  public function getEmailAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Get files as export attachments.
   *
   * This is also used to export attachments.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An array containing email attachments which include an attachments
   *   'filename', 'filemime', 'filepath', and 'filecontent'.
   *
   * @see \Drupal\webform\WebformSubmissionExporter::writeRecords
   */
  public function getExportAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Determine if the element type has export attachments.
   *
   * @return bool
   *   TRUE if the element type has export attachments.
   */
  public function hasExportAttachments();

  /**
   * Get attachment export batch limit.
   *
   * @return int|null
   *   Batch limit or NULL if the batch limit should not be overidden.
   */
  public function getExportAttachmentsBatchLimit();

}
