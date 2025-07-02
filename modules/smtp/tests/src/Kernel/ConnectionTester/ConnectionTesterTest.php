<?php

namespace Drupal\Tests\smtp\Kernel\ConnectionTester;

use Drupal\KernelTests\KernelTestBase;
use Drupal\smtp\ConnectionTester\ConnectionTester;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Tests SMTP connections.
 *
 * @group SMTP
 */
class ConnectionTesterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'smtp',
  ];

  /**
   * Test for hookRequirements().
   *
   * @param string $message
   *   The test message.
   * @param bool $smtp_on
   *   Mock value of whether SMTP is on or not.
   * @param bool $result
   *   Mock result of ::SmtpConnect().
   * @param string $exception
   *   The exception, if any, that the mock SmtpConnect() should throw.
   * @param array $expected
   *   The expected result; ignored if an exception is expected.
   *
   * @cover ::hookRequirements
   * @dataProvider providerHookRequirements
   */
  public function testHookRequirements(string $message, bool $smtp_on, bool $result, string $exception, array $expected) {
    $smtp_settings = \Drupal::configFactory()
      ->getEditable('smtp.settings');
    $smtp_settings->set('smtp_on', $smtp_on);
    $smtp_settings->save();

    $object = \Drupal::service('smtp.connection_tester');
    $object->setMailer($this->getMockMailer($result, $exception));

    $object->testConnection();
    $output = $object->hookRequirements('runtime');

    if ($output != $expected) {
      print_r([
        'message' => $message,
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testHookRequirements().
   */
  public static function providerHookRequirements() {
    return [
      [
        'message' => 'SMTP on, working.',
        'smtp_on' => TRUE,
        'result' => TRUE,
        'exception' => '',
        'expected' => [
          'smtp_connection' => [
            'title' => 'SMTP connection',
            'value' => 'SMTP module is enabled, turned on, and connection is valid.',
            'severity' => ConnectionTester::REQUIREMENT_OK,
          ],
        ],
      ],
      [
        'message' => 'SMTP on, result FALSE.',
        'smtp_on' => TRUE,
        'result' => FALSE,
        'exception' => '',
        'expected' => [
          'smtp_connection' => [
            'title' => 'SMTP connection',
            'value' => 'SMTP module is enabled, turned on, but SmtpConnect() returned FALSE.',
            'severity' => ConnectionTester::REQUIREMENT_ERROR,
          ],
        ],
      ],
      [
        'message' => 'SMTP on, PHPMailerException.',
        'smtp_on' => TRUE,
        'result' => FALSE,
        'exception' => PHPMailerException::class,
        'expected' => [
          'smtp_connection' => [
            'title' => 'SMTP connection',
            'value' => 'SMTP module is enabled, turned on, but SmtpConnect() threw exception EXCEPTION MESSAGE',
            'severity' => ConnectionTester::REQUIREMENT_ERROR,
          ],
        ],
      ],
      [
        'message' => 'SMTP on, Exception.',
        'smtp_on' => TRUE,
        'result' => FALSE,
        'exception' => \Exception::class,
        'expected' => [
          'smtp_connection' => [
            'title' => 'SMTP connection',
            'value' => 'SMTP module is enabled, turned on, but SmtpConnect() threw an unexpected exception',
            'severity' => ConnectionTester::REQUIREMENT_ERROR,
          ],
        ],
      ],
      [
        'message' => 'SMTP off.',
        'smtp_on' => FALSE,
        'result' => FALSE,
        'exception' => '',
        'expected' => [
          'smtp_connection' => [
            'title' => 'SMTP connection',
            'value' => 'SMTP module is enabled but turned off.',
            'severity' => ConnectionTester::REQUIREMENT_OK,
          ],
        ],
      ],
    ];
  }

  /**
   * Create a mock PHPMailer class for testing the exceptions.
   *
   * @param bool $result
   *   Expected Result.
   * @param string $exception
   *   Exception passed in.
   *
   * @return \PHPMailer\PHPMailer\PHPMailer
   *   The PHPMailer library.
   */
  private function getMockMailer($result, $exception) {

    $class = new class($result, $exception) extends PHPMailer {

      public function __construct($result, $exception) {
        $this->result = $result;
        $this->exception = $exception;
      }

      /**
       * Mock function for connection.
       */
      public function smtpConnect($options = NULL) {
        if ($this->exception) {
          $class = $this->exception;
          throw new $class('EXCEPTION MESSAGE');
        }
        return $this->result;
      }

    };
    return $class;
  }

}
