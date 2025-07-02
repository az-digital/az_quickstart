<?php

namespace Drupal\flag\Plugin\ActionLink;

/**
 * Indicates the action link makes use of form entry for flagging operations.
 */
interface FormEntryInterface {

  /**
   * Returns the flag confirm form question when flagging.
   *
   * @return string
   *   A string containing the flag question to display.
   */
  public function getFlagQuestion();

  /**
   * Returns the edit flagging details form title.
   *
   * @return string
   *   A string containing the edit flagging details title to display.
   */
  public function getEditFlaggingTitle();

  /**
   * Returns the flag confirm form question when unflagging.
   *
   * @return string
   *   A string containing the unflag question to display.
   */
  public function getUnflagQuestion();

  /**
   * Returns the create button text.
   *
   * @return string
   *   The string stored in configuration.
   */
  public function getCreateButtonText();

  /**
   * Returns the delete button text.
   *
   * @return string
   *   The string stored in configuration.
   */
  public function getDeleteButtonText();

  /**
   * Returns the update button text.
   *
   * @return string
   *   The string stored in configuration.
   */
  public function getUpdateButtonText();

}
