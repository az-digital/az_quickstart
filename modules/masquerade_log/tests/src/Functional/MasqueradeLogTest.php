<?php

declare(strict_types=1);

namespace Drupal\Tests\masquerade_log\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests logging the original user.
 *
 * @group masquerade_log
 */
class MasqueradeLogTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'masquerade_log',
    'node',
    'syslog_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The main user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $originalAccount;

  /**
   * Tests logging the original user.
   */
  public function testMasqueradeLog(): void {
    $assert = $this->assertSession();

    $this->originalAccount = $this->createUser([
      'administer content types',
      'access user profiles',
      'masquerade as any user',
    ], 'original');
    $target_account = $this->createUser([
      'administer content types',
    ]);

    $this->drupalLogin($this->originalAccount);
    $this->drupalGet('/admin/structure/types/add');

    $this->submitForm([
      'name' => 'Type 1',
      'type' => 'type1',
    ], 'Save');
    $assert->pageTextContains('The content type Type 1 has been added.');

    $this->drupalGet($target_account->toUrl());
    $this->clickLink("Masquerade as {$target_account->getAccountName()}");
    $assert->pageTextContains("You are now masquerading as {$target_account->getAccountName()}.");

    // Check the log entry.
    $this->assertLogged('node', $this->originalAccount, [
      'Added content type Type 1.',
    ]);
    $this->drupalGet('/admin/structure/types/add');

    $this->submitForm([
      'name' => 'Type 2',
      'type' => 'type2',
    ], 'Save');
    $assert->pageTextContains('The content type Type 2 has been added.');

    // Check that the original username has been added to the log message.
    $this->assertLogged('node', $target_account, [
      'Added content type Type 2.',
      "[masquerading {$this->originalAccount->getAccountName()}, uid {$this->originalAccount->id()}]",
    ]);
  }

  /**
   * Asserts that some message chunks were logged.
   *
   * We're checking both, DbLog and Syslog, for the same entry.
   *
   * @param string $type
   *   The log type.
   * @param \Drupal\user\UserInterface $account
   *   The logged user account.
   * @param string[] $messages
   *   Chunks of message to be checked.
   */
  protected function assertLogged(string $type, UserInterface $account, array $messages): void {
    // DbLog.
    $db = \Drupal::database();
    $result = $db->select('watchdog')
      ->fields('watchdog', ['message', 'variables', 'uid'])
      ->condition('type', $type)
      ->condition('uid', $account->id())
      // Check for latest entries.
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetch();

    if ($dblog_logged = !empty($result)) {
      $variables = unserialize($result->variables, ['allowed_classes' => FALSE]);
      $stored_message = strip_tags((new FormattableMarkup($result->message, $variables))->__toString());
      foreach ($messages as $message) {
        $dblog_logged = $dblog_logged && (strpos($stored_message, $message) !== FALSE);
      }
      // On DbLog check also the variables, if the logged user is masquerading.
      if ($account->id() !== $this->originalAccount->id()) {
        $dblog_logged = $dblog_logged && isset($variables['@original_uid']) && $variables['@original_uid'] === $this->originalAccount->id();
        $dblog_logged = $dblog_logged && isset($variables['@original_username']) && $variables['@original_username'] === $this->originalAccount->getAccountName();
      }
    }

    // Syslog. We're using the logger provided by syslog_test module.
    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_entries = explode(PHP_EOL, trim(file_get_contents($log_filename)));
    // Check for latest entries.
    $log_entries = array_reverse($log_entries);
    $i = 0;
    do {
      $log_entry = explode('|', $log_entries[$i++]);
    } while ($log_entry[2] !== $type && $i < count($log_entries));

    $syslog_logged = $log_entry[2] === $type && $log_entry[6] === $account->id();
    foreach ($messages as $message) {
      $syslog_logged = $syslog_logged && (strpos($log_entry[8], $message) !== FALSE);
    }

    // Both, DbLog and Syslog should log correctly.
    $this->assertTrue($dblog_logged && $syslog_logged);

    // Cleanup the log backends, preparing them for the next assertion.
    $db->truncate('watchdog');
    file_put_contents($log_filename, '');
  }

}
