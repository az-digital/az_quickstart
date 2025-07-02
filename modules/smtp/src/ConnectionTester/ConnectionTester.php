<?php

namespace Drupal\smtp\ConnectionTester;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;

/**
 * Allows testing the SMTP connection.
 */
class ConnectionTester {

  use StringTranslationTrait;

  // These constants de not seem to be available outside of the .install file
  // so we need to declare them here.
  const REQUIREMENT_OK = 0;
  const REQUIREMENT_ERROR = 2;

  /**
   * The severity of the connection issue; set during class construction.
   *
   * @var int
   */
  protected $severity;

  /**
   * Description of the connection, set during construction..
   *
   * @var string
   */
  protected $value;

  /**
   * PHP Mailer Object.
   *
   * @var \PHPMailer\PHPMailer\PHPMailer
   */
  protected $phpMailer;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The SMTP Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $smtpConfig;

  /**
   * The smtp logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The SMTP ConnectionTester constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The drupal config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The Mail manager.
   */
  public function __construct(ConfigFactory $config_factory, LoggerInterface $logger, MailManagerInterface $mail_manager) {
    $this->configFactory = $config_factory;
    $this->smtpConfig = $config_factory->get('smtp.settings');
    $this->mailManager = $mail_manager;
    $this->logger = $logger;

    if (!class_exists(PHPMailer::class)) {
      $this->logger->error('Unable to initialize PHPMailer, Class does not exist.');
      return;
    }
    $this->phpMailer = new PHPMailer(TRUE);
  }

  /**
   * Set the PHPMailer Library class.
   */
  public function setMailer(PHPMailer $mailer) {
    $this->phpMailer = $mailer;
  }

  /**
   * Test SMTP connection.
   */
  public function testConnection() {
    if (!$this->configurePhpMailer()) {
      $this->severity = self::REQUIREMENT_ERROR;
      $this->value = $this->t('Unable to initialize PHPMailer.');
      return FALSE;
    }

    $smtp_enabled = $this->smtpConfig->get('smtp_on');
    // Check to see if MailSystem is enabled and is using SMTPMailSystem.
    // @phpstan-ignore-line
    if (\Drupal::moduleHandler()->moduleExists('mailsystem')) {
      $mailsystem_defaults = (array) $this->configFactory->get('mailsystem.settings')->get('defaults');
      $smtp_enabled = in_array('SMTPMailSystem', $mailsystem_defaults);
    }

    if (!$smtp_enabled) {
      $this->severity = self::REQUIREMENT_OK;
      $this->value = $this->t('SMTP module is enabled but turned off.');
      return FALSE;
    }

    try {
      if ($this->phpMailer->smtpConnect()) {
        $this->severity = self::REQUIREMENT_OK;
        $this->value = $this->t('SMTP module is enabled, turned on, and connection is valid.');
        return TRUE;
      }
      $this->severity = self::REQUIREMENT_ERROR;
      $this->value = $this->t('SMTP module is enabled, turned on, but SmtpConnect() returned FALSE.');
      return FALSE;
    }
    catch (PHPMailerException $e) {
      $this->value = $this->t('SMTP module is enabled, turned on, but SmtpConnect() threw exception @e', [
        '@e' => $e->getMessage(),
      ]);
      $this->severity = self::REQUIREMENT_ERROR;
    }
    catch (\Exception $e) {
      $this->value = $this->t('SMTP module is enabled, turned on, but SmtpConnect() threw an unexpected exception');
      $this->severity = self::REQUIREMENT_ERROR;
    }
    return FALSE;
  }

  /**
   * Testable implementation of hook_requirements().
   */
  public function hookRequirements(string $phase) {
    $requirements = [];
    if ($phase == 'runtime') {
      $requirements['smtp_connection'] = [
        'title' => $this->t('SMTP connection')->__toString(),
        'value' => $this->value->__toString(),
        'severity' => $this->severity,
      ];
    }
    return $requirements;
  }

  /**
   * Get a PHPMailer object ready to be tested.
   *
   * @return bool
   *   True if config was set, False if phpMailer didn't exist.
   */
  protected function configurePhpMailer() {
    if ($this->phpMailer) {
      // Set debug to FALSE for the connection test; further debugging can be
      // used when sending actual mails.
      $this->phpMailer->SMTPDebug = FALSE;
      // Hardcoded Timeout for testing so the reports page doesn't stall out.
      $this->phpMailer->Timeout = 5;

      $this->phpMailer->Host = implode(';', array_filter(
        [
          $this->smtpConfig->get('smtp_host'),
          $this->smtpConfig->get('smtp_hostbackup'),
        ]
        ));

      $this->phpMailer->Port = $this->smtpConfig->get('smtp_port');
      $protocol = $this->smtpConfig->get('smtp_protocol');
      $this->phpMailer->SMTPAutoTLS = $this->smtpConfig->get('smtp_autotls');
      $this->phpMailer->SMTPSecure = in_array($protocol, ['ssl', 'tls'], TRUE) ? $protocol : '';
      if ($smtp_client_hostname = $this->smtpConfig->get('smtp_client_hostname')) {
        $this->phpMailer->Hostname = $smtp_client_hostname;
      }
      if ($helo = $this->smtpConfig->get('smtp_client_helo')) {
        $this->phpMailer->Helo = $helo;
      }
      $username = $this->smtpConfig->get('smtp_username');
      $password = $this->smtpConfig->get('smtp_password');
      if ($username && $password) {
        $this->phpMailer->SMTPAuth = TRUE;
        $this->phpMailer->Username = $username;
        $this->phpMailer->Password = $password;
      }
      return TRUE;
    }
    return FALSE;
  }

}
