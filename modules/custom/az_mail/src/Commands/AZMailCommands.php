<?php

namespace Drupal\az_mail\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Drush commandfile for az_mail.
 */
class AZMailCommands extends DrushCommands {

  /**
   * The config for the smtp module.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a AZMailCommands object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->getEditable('smtp.settings');
  }

  /**
   * Sets the SMTP password for use with AWS SES.
   *
   * @param string $region
   *   region The AWS region in use.
   * @param string $secret
   *   The IAM secret key to convert and store.
   *
   * @command az_mail:ses-smtp-secret
   * @aliases ses-secret
   * @options region The AWS region in use.
   * @options secret The IAM secret key to convert and store.
   * @usage az_mail:ses-smtp-secret <AWS_REGION> <AWS_SECRET_ACCESS_KEY>
   * Converts a regular AWS IAM secret key to an SMTP password for SES.
   */
  public function setSmtpPassword($region, $secret) {
    $smtp_password = $this->sesHash($secret, $region);
    $this->config->set('smtp_password', $smtp_password);
    $this->config->save();
  }

  /**
   * Helper function for az_mail_ses_hash.
   */
  private function sesSign($key, $msg) {
    //phpcs:ignore Security.BadFunctions.CryptoFunctions.WarnCryptoFunc
    return hash_hmac('sha256', utf8_encode($msg), $key, TRUE);
  }

  /**
   * Derive the SMTP password from the AWS IAM secret key.
   */
  private function sesHash($secret, $region) {
    // Values that are required to calculate the signature. These values should
    // never change.
    $date = "11111111";
    $service = "ses";
    $message = "SendRawEmail";
    $terminal = "aws4_request";
    $version = 0x04;

    $signature = $this->sesSign(utf8_encode("AWS4" . $secret), $date);
    $signature = $this->sesSign($signature, $region);
    $signature = $this->sesSign($signature, $service);
    $signature = $this->sesSign($signature, $terminal);
    $signature = $this->sesSign($signature, $message);
    $signature_and_version = pack("C*", $version) . $signature;
    //phpcs:ignore Security.BadFunctions.CryptoFunctions.WarnCryptoFunc
    $smtp_password = base64_encode($signature_and_version);
    return utf8_decode($smtp_password);
  }

}
