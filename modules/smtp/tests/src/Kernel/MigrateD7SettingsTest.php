<?php

namespace Drupal\Tests\smtp\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * Tests migration of smtp settings.
 *
 * @group smtp
 */
class MigrateD7SettingsTest extends MigrateDrupal7TestBase {

  use RandomGeneratorTrait;

  /**
   * The migration this test is testing.
   *
   * @var string
   */
  const MIGRATION_UNDER_TEST = 'd7_smtp_settings';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['smtp'];

  /**
   * Test that we can migrate D7 SMTP settings.
   */
  public function testMigrateSettings() {
    // Generate test data.
    $allowHtml = '0';
    $clientHelo = $this->randomString();
    $clientHostname = $this->randomHostname();
    $debugging = TRUE;
    $from = $this->randomEmail();
    $fromName = $this->randomString();
    $host = $this->randomHostname();
    $hostBackup = $this->randomHostname();
    $smtpOn = TRUE;
    $password = $this->randomString();
    $port = strval(rand(1, 65535));
    $protocol = 'standard';
    $rerouteAddress = $this->randomEmail();
    $testAddress = $this->randomEmail();
    $username = $this->randomString();

    Database::getConnection('default', 'migrate')
      ->insert('system')
      ->fields(['name', 'type', 'status', 'schema_version'])
      ->values([
        'name' => 'smtp',
        'type' => 'module',
        'status' => 1,
        'schema_version' => '7000',
      ])
      ->execute();

    // Set D7 variables.
    $this->setUpD7Variable('smtp_allowhtml', $allowHtml);
    $this->setUpD7Variable('smtp_client_helo', $clientHelo);
    $this->setUpD7Variable('smtp_client_hostname', $clientHostname);
    $this->setUpD7Variable('smtp_debugging', $debugging);
    $this->setUpD7Variable('smtp_from', $from);
    $this->setUpD7Variable('smtp_fromname', $fromName);
    $this->setUpD7Variable('smtp_host', $host);
    $this->setUpD7Variable('smtp_hostbackup', $hostBackup);
    $this->setUpD7Variable('smtp_on', $smtpOn);
    $this->setUpD7Variable('smtp_password', $password);
    $this->setUpD7Variable('smtp_port', $port);
    $this->setUpD7Variable('smtp_protocol', $protocol);
    $this->setUpD7Variable('smtp_reroute_address', $rerouteAddress);
    $this->setUpD7Variable('smtp_test_address', $testAddress);
    $this->setUpD7Variable('smtp_username', $username);

    // Run the migration.
    try {
      $this->executeMigrations([self::MIGRATION_UNDER_TEST]);
    }
    catch (\Throwable $e) {
      $this->fail($e->getMessage());
    }

    // Validate the D7 variable values made it into the destination structure.
    $destConfig = $this->config('smtp.settings');
    // Validate smtp_allowhtml was transformed into a boolean.
    $this->assertSame((bool) $allowHtml, $destConfig->get('smtp_allowhtml'));
    $this->assertSame($clientHelo, $destConfig->get('smtp_client_helo'));
    $this->assertSame($clientHostname, $destConfig->get('smtp_client_hostname'));
    $this->assertSame($debugging, $destConfig->get('smtp_debugging'));
    $this->assertSame($from, $destConfig->get('smtp_from'));
    $this->assertSame($fromName, $destConfig->get('smtp_fromname'));
    $this->assertSame($host, $destConfig->get('smtp_host'));
    $this->assertSame($hostBackup, $destConfig->get('smtp_hostbackup'));
    $this->assertSame($smtpOn, $destConfig->get('smtp_on'));
    $this->assertSame($password, $destConfig->get('smtp_password'));
    $this->assertSame($port, $destConfig->get('smtp_port'));
    $this->assertSame($protocol, $destConfig->get('smtp_protocol'));
    $this->assertSame($rerouteAddress, $destConfig->get('smtp_reroute_address'));
    $this->assertSame($testAddress, $destConfig->get('smtp_test_address'));
    $this->assertSame($username, $destConfig->get('smtp_username'));

    // Validate default_value migrations.
    $this->assertTrue($destConfig->get('smtp_autotls'));
    $this->assertSame(10, $destConfig->get('smtp_timeout'));
    $this->assertSame('php_mail', $destConfig->get('prev_mail_system'));
    $this->assertFalse($destConfig->get('smtp_keepalive'));
  }

  /**
   * Generate a random email address.
   *
   * @return string
   *   A random email address at a random hostname.
   */
  protected function randomEmail() {
    return sprintf('%s@%s', $this->getRandomGenerator()->word(8), $this->randomHostname());
  }

  /**
   * Generate a random hostname.
   *
   * @return string
   *   A random hostname.
   */
  protected function randomHostname() {
    return sprintf('%s.%s.com', $this->getRandomGenerator()->word(8), $this->getRandomGenerator()->word(8));
  }

  /**
   * Set up a D7 variable to be migrated.
   *
   * @param string $name
   *   The name of the variable to be set.
   * @param mixed $value
   *   The value of the variable to be set.
   */
  protected function setUpD7Variable($name, $value) {
    $this->assertIsString($name, 'Name must be a string');

    Database::getConnection('default', 'migrate')
      ->upsert('variable')
      ->key('name')
      ->fields(['name', 'value'])
      ->values([
        'name' => $name,
        'value' => serialize($value),
      ])
      ->execute();
  }

}
