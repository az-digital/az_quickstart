<?php

namespace Drupal\devel\Plugin\Mail;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Core\Config\Config;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a mail backend that saves emails as temporary files.
 *
 * To enable, save a variable in settings.php (or otherwise) whose value
 * can be as simple as:
 *
 * @code
 * $config['system.mail']['interface']['default'] = 'devel_mail_log';
 * @endcode
 *
 * By default, the mails are saved in 'temporary://devel-mails'. This setting
 * can be changed using 'debug_mail_directory' config setting. For example:
 * @code
 * $config['devel.settings']['debug_mail_directory'] =
 *   'temporary://my-directory';
 * @endcode
 *
 * The default filename pattern used is '%to-%subject-%datetime.mail.txt'. This
 * setting can be changed using 'debug_mail_directory' config setting.
 * For example:
 * @code
 * $config['devel.settings']['debug_mail_file_format'] =
 *   'devel-mail-%to-%subject-%datetime.mail.txt';
 * @endcode
 *
 * The following placeholders can be used in the filename pattern:
 *   - %to: the email recipient.
 *   - %subject: the email subject.
 *   - %datetime: the current datetime in 'y-m-d_his' format.
 *
 * @Mail(
 *   id = "devel_mail_log",
 *   label = @Translation("Devel Logging Mailer"),
 *   description = @Translation("Outputs the message as a file in the temporary
 *   directory.")
 * )
 */
class DevelMailLog implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * The 'devel.settings' config object.
   */
  protected Config $config;

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;

  final public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static();
    $instance->config = $container->get('config.factory')->get('devel.settings');
    $instance->fileSystem = $container->get('file_system');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message): bool {
    $directory = $this->config->get('debug_mail_directory');

    if (!$this->prepareDirectory($directory)) {
      return FALSE;
    }

    $pattern = $this->config->get('debug_mail_file_format');
    $filename = $this->replacePlaceholders($pattern, $message);
    $output = $this->composeMessage($message);

    return (bool) file_put_contents($directory . '/' . $filename, $output);
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message): array {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    // Convert any HTML to plain-text.
    $message['body'] = MailFormatHelper::htmlToText($message['body']);
    // Wrap the mail body for sending.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    return $message;
  }

  /**
   * Compose the output message.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return string
   *   The output message.
   */
  protected function composeMessage(array $message): string {
    $mimeheaders = [];
    $message['headers']['To'] = $message['to'];
    foreach ($message['headers'] as $name => $value) {
      $mimeheaders[] = $name . ': ' . iconv_mime_decode($value);
    }

    $line_endings = Settings::get('mail_line_endings', PHP_EOL);
    $output = implode($line_endings, $mimeheaders) . $line_endings;
    // 'Subject:' is a mail header and should not be translated.
    $output .= 'Subject: ' . $message['subject'] . $line_endings;
    // Blank line to separate headers from body.
    $output .= $line_endings;
    return $output . preg_replace('@\r?\n@', $line_endings, $message['body']);
  }

  /**
   * Replaces placeholders with sanitized values in a string.
   *
   * @param string $filename
   *   The string that contains the placeholders. The following placeholders
   *   are considered in the replacement:
   *   - %to: replaced by the email recipient value.
   *   - %subject: replaced by the email subject value.
   *   - %datetime: replaced by the current datetime in 'y-m-d_his' format.
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return string
   *   The formatted string.
   */
  protected function replacePlaceholders(string $filename, array $message): string {
    $tokens = [
      '%to' => $message['to'],
      '%subject' => $message['subject'],
      '%datetime' => date('y-m-d_his'),
    ];
    $filename = str_replace(array_keys($tokens), array_values($tokens), $filename);
    return preg_replace('/[^a-zA-Z0-9_\-\.@]/', '_', $filename) ?? '';
  }

  /**
   * Checks that the directory exists and is writable.
   *
   * Public directories will be protected by adding an .htaccess which
   * indicates that the directory is private.
   *
   * @param string $directory
   *   A string reference containing the name of a directory path or URI.
   *
   * @return bool
   *   TRUE if the directory exists (or was created), is writable and is
   *   protected (if it is public). FALSE otherwise.
   */
  protected function prepareDirectory(string $directory): bool {
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
      return FALSE;
    }

    if (str_starts_with($directory, 'public://')) {
      return FileSecurity::writeHtaccess($directory);
    }

    return TRUE;
  }

}
