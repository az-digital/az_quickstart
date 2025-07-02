<?php

namespace Drupal\Tests\seckit\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Security Kit's Report CSP Violation functionality.
 *
 * @group seckit
 */
class SecKitReportCspViolationTest extends BrowserTestBase {

  /**
   * Array of modules to enable.
   *
   * @var array
   */
  protected static $modules = ['seckit', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An example CSP violation report.
   *
   * @var array
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy-Report-Only#sample_violation_report
   */
  protected $validReport = [
    'csp-report' => [
      'blocked-uri' => 'http://example.com/css/style.css',
      'disposition' => 'report',
      'document-uri' => 'http://example.com/signup.html',
      'effective-directive' => 'style-src-elem',
      'original-policy' => "default-src 'none'; style-src cdn.example.com; report-uri /report-csp-violation",
      'referrer' => '',
      'status-code' => 200,
      'violated-directive' => 'style-src-elem',
    ],
  ];

  /**
   * Test a valid report.
   */
  public function testValidReportCspViolation() {
    $database = \Drupal::database();
    $client = $this->getHttpClient();
    $url = Url::fromRoute('seckit.report');

    $response = $client->post($this->buildUrl($url), [
      'body' => json_encode($this->validReport),
      'headers' => [
        'Content-Type' => 'application/csp-report',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals(200, $response->getStatusCode());

    $last_log = $database->select('watchdog', 'w')
      ->fields('w', ['message'])
      ->condition('type', 'seckit')
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $this->assertEquals('CSP: Directive @directive violated.<br /> Blocked URI: @blocked_uri.<br /> <pre>Data: @data</pre>', $last_log, 'A message was logged for the valid CSP violation report.');
  }

  /**
   * Test an invalid report with a missing field in the json payload.
   */
  public function testInvalidReportCspViolationMissingField() {
    $database = \Drupal::database();
    $client = $this->getHttpClient();
    $url = Url::fromRoute('seckit.report');

    $report = $this->validReport;
    unset($report['csp-report']['violated-directive']);

    $response = $client->post($this->buildUrl($url), [
      'body' => json_encode($report),
      'headers' => [
        'Content-Type' => 'application/csp-report',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals(200, $response->getStatusCode());

    $last_log = $database->select('watchdog', 'w')
      ->fields('w', ['message'])
      ->condition('type', 'seckit')
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $this->assertFalse($last_log, 'No message was logged for an invalid CSP violation report.');
  }

  /**
   * Test an invalid report with the wrong content type.
   */
  public function testInvalidReportCspViolationWrongContentType() {
    $client = $this->getHttpClient();
    $url = Url::fromRoute('seckit.report');

    $response = $client->post($this->buildUrl($url), [
      'body' => json_encode($this->validReport),
      'headers' => [
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals(404, $response->getStatusCode());
  }

}
