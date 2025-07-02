<?php

namespace Drupal\webform\Plugin;

/**
 * Defines the interface for webform elements can provide file access.
 */
interface WebformElementFileDownloadAccessInterface {

  /**
   * Control access to webform file.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return mixed
   *   Returns NULL if the file is not attached to a webform submission.
   *   Returns -1 if the user does not have permission to access a webform.
   *   Returns an associative array of headers.
   *
   * @see hook_file_download()
   * @see webform_file_download()
   */
  public static function accessFileDownload($uri);

}
