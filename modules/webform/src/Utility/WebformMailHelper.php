<?php

namespace Drupal\webform\Utility;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\MailboxHeader;

/**
 * Helper class for webform mail handing.
 */
class WebformMailHelper {

  /**
   * Validate email address.
   *
   * @param string $address
   *   An email address.
   *
   * @return bool
   *   TRUE is email address is valid.
   */
  public static function validateAddress(string $address) {
    try {
      Address::create($address);
      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * Encode email address.
   *
   * @param string $address
   *   An email address.
   * @param string $name
   *   (optional) A name associated with the email address.
   *
   * @return string
   *   Encode email address with an optional name.
   */
  public static function formatAddress(string $address, string $name = '') {
    // Remove less than (<) and greater (>) than from name.
    $name = preg_replace('/[<>]/', '', $name);
    // Issue #3371600 Email name and address details issue when
    // using Mimemail formatter for languages other than English.
    // @see https://www.drupal.org/project/webform/issues/3371600
    if (\Drupal::moduleHandler()->moduleExists('mimemail')) {
      return $name ? "$name <$address>" : $address;
    }
    else {
      $mime_address = new Address($address, $name);
      $mailbox_header = new MailboxHeader('Temp', $mime_address);
      return $mailbox_header->getBodyAsString();
    }
  }

}
