<?php

namespace Drupal\Tests\az_mail\Functional;

use Drupal\az_mail\Commands\AZMailCommands;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Main test class !
 */
class MailDrushTest extends UnitTestCase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $defaultTheme = 'seven';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'az_mail',
    'az_core',
  ];

  /**
   * Main test function.
   */
  public function testDrush() {

    // Example secret key for testing.
    $secret = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY';

    $region = 'us-west-2';

    $config_map = [
      'smtp.settings' => [
        'smtp_on' => TRUE,
        'smtp_host' => 'email-smtp.us-west-2.amazonaws.com',
        'smtp_hostbackup' => '',
        'smtp_port' => '587',
        'smtp_protocol' => 'tls',
        'smtp_autotls' => TRUE,
        'smtp_timeout' => 30,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from' => '',
        'smtp_fromname' => '',
        'smtp_client_hostname' => '',
        'smtp_client_helo' => '',
        'smtp_allowhtml' => '0',
        'smtp_test_address' => '',
        'smtp_debugging' => FALSE,
        'smtp_keepalive' => FALSE,
      ],
    ];

    $config = $this->getConfigFactoryStub($config_map);

    // Instantiate AZMailCommands object.
    $com = new AZMailCommands($config);

    $this->container = new ContainerBuilder();

    $this->container->set('config.factory', $config);

    \Drupal::setContainer($this->container);

    // Call to drush command to update password.
    $com->setSmtpPassword($region, $secret);

    $password = \Drupal::config('smtp.settings')->get('smtp_password');

    // Assert the password equals the expected example hashed pass.
    $this->assertEquals($password, 'BF2PynzbSCAjX08zhZZnP/kW+T9P5zs/1Er0pi5vTEmd');

  }

}
