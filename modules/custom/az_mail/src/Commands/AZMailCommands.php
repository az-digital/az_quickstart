<?php

namespace Drupal\az_mail\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commandfile for az_mail.
 */
class AZMailCommands extends DrushCommands {

  /**
   * The configFactory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a AZMailCommands object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configFactory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
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
    $smtpConfig = $this->configFactory->getEditable('smtp.settings');
    $smtpPassword = $this->sesHash($secret, $region);
    $smtpConfig->set('smtp_password', $smtpPassword);
    $smtpConfig->save();
  }

  /**
   * Helper function for az_mail_ses_hash.
   */
  private function sesSign($key, $msg) {
    return hash_hmac('sha256', mb_convert_encoding($msg, 'UTF-8', mb_list_encodings()), $key, TRUE);
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

    $signature = $this->sesSign(mb_convert_encoding("AWS4" . $secret, 'UTF-8', mb_list_encodings()), $date);
    $signature = $this->sesSign($signature, $region);
    $signature = $this->sesSign($signature, $service);
    $signature = $this->sesSign($signature, $terminal);
    $signature = $this->sesSign($signature, $message);
    $signature_and_version = pack("C*", $version) . $signature;
    $smtp_password = base64_encode($signature_and_version);
    return mb_convert_encoding($smtp_password, 'ISO-8859-1', 'UTF-8');
  }

}
