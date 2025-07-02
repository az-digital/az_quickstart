<?php

namespace Drupal\Tests\google_tag\Kernel;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Response code condition plugin.
 *
 * @coversDefaultClass \Drupal\google_tag\Plugin\Condition\ResponseCode
 *
 * @group google_tag
 */
class ResponseCodeTest extends GoogleTagTestCase {

  /**
   * Response code condition.
   *
   * @var \Drupal\google_tag\Plugin\Condition\ResponseCode
   */
  protected $condition;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->condition = $this->container->get('plugin.manager.condition')->createInstance('response_code');
  }

  /**
   * Tests that response code condition works for 4xx requests.
   *
   * @covers ::evaluate
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testConditionOnInvalidRequest(): void {

    $request = Request::create('/non-existent-page');
    $this->container->get('http_kernel')->handle($request);

    $this->condition->setConfig('response_codes', "401\n403\n404");

    $this->assertTrue($this->condition->execute(), 'The response code plugin is working');

    $this->condition->setConfig('negate', TRUE);
    $this->assertFalse($this->condition->execute(), 'The response code plugin is working with negate flag too');
  }

  /**
   * Tests that response code condition works for 200 requests.
   *
   * @covers ::evaluate
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testConditionOnValidRequest(): void {
    $this->condition->setConfig('response_codes', "200");
    $valid_request = Request::create('/');
    $this->container->get('http_kernel')->handle($valid_request);
    $this->assertTrue($this->condition->execute(), 'Response code plugin is working for 200 code.');
  }

}
