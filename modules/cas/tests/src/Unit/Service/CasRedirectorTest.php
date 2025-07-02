<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\CasRedirectData;
use Drupal\cas\Event\CasPreRedirectEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasRedirector;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Cas Redirector Unit Tests.
 *
 * @group cas
 *
 * @ingroup cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasRedirector
 */
class CasRedirectorTest extends UnitTestCase {

  /**
   * Mock Cas Helper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $casHelper;

  /**
   * Mock URL Generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The mocked config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockBuilder
   */
  protected $configFactory;

  /**
   * Storage for events during tests.
   *
   * @var array
   */
  protected $events;

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.protocol' => 'https',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => '2.0',
        'server.verify' => CasHelper::CA_DEFAULT,
        'server.cert' => 'foo',
        'advanced.connection_timeout' => 30,
      ],
    ]);

    $this->casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');

    $this->urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->method('generate')
      ->willReturnCallback([$this, 'getServiceUrl']);

    // Mock event dispatcher to dispatch events.
    $this->eventDispatcher = $this->createMock('\Symfony\Contracts\EventDispatcher\EventDispatcherInterface');

    // We have to mock the cache context manager which is called when we
    // add cache contexts to a cacheable metadata.
    $cache_contexts_manager = $this->createMock('Drupal\Core\Cache\Context\CacheContextsManager');
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);

    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Get a service URL.
   *
   * @param string $route
   *   The route name.
   * @param array $parameters
   *   The service URL parameters.
   *
   * @return string
   *   The constructed service URL.
   */
  public function getServiceUrl($route, array $parameters = NULL) {
    if ($parameters) {
      return 'http://example.com/casservice?' . UrlHelper::buildQuery($parameters);
    }
    else {
      return 'http://example.com/casservice';
    }
  }

  /**
   * Dispatch an event.
   *
   * @param \Drupal\cas\Event\CasPreRedirectEvent $event
   *   Event fired.
   * @param string $event_name
   *   Name of event fired.
   *
   * @return \Drupal\cas\Event\CasPreRedirectEvent
   *   The event instance.
   */
  public function dispatchEvent(CasPreRedirectEvent $event, $event_name): CasPreRedirectEvent {
    $this->events[$event_name] = $event;
    $data = $event->getCasRedirectData();
    $data->setParameter('strong_auth', 'true');
    $data->forceRedirection();
    return $event;
  }

  /**
   * Test buildRedirectResponse.
   */
  public function testBuildRedirectResponse() {
    $cas_redirector = new CasRedirector($this->casHelper, $this->eventDispatcher, $this->urlGenerator, $this->configFactory);
    $cas_data = new CasRedirectData();
    $cas_data->forceRedirection();

    $response = $cas_redirector->buildRedirectResponse($cas_data, TRUE);
    // Make sure that are url begins with the login redirection.
    $this->assertEquals('https://example-server.com/cas/login?service=http%3A//example.com/casservice', $response->getTargetUrl());
    $this->assertInstanceOf('\Drupal\core\Routing\TrustedRedirectResponse', $response);

    // Validate redirection control.
    $cas_data->preventRedirection();
    $response = $cas_redirector->buildRedirectResponse($cas_data);
    $this->assertNull($response, 'Return null for no response');

    $cas_data->forceRedirection();
    $response = $cas_redirector->buildRedirectResponse($cas_data);
    $this->assertNotNull($response, 'Found response for redirect data');

    // Make sure setting of normal parameters works.
    $cas_data->setParameter('strong_auth', 'true');
    $response = $cas_redirector->buildRedirectResponse($cas_data);
    $this->assertEquals('https://example-server.com/cas/login?strong_auth=true&service=http%3A//example.com/casservice', $response->getTargetUrl(), 'Target URL with parameters');
    $cas_data->setParameter('strong_auth', NULL);

    // Verfiy setting of gateway parameters.
    $cas_data->setServiceParameter('destination', 'node/1');
    $response = $cas_redirector->buildRedirectResponse($cas_data);
    $this->assertEquals('https://example-server.com/cas/login?service=http%3A//example.com/casservice%3Fdestination%3Dnode/1', $response->getTargetUrl(), 'Service parameters present');

    // Verify proper redirector type.
    $cas_data->setIsCacheable(TRUE);
    /** @var \Drupal\Core\Routing\TrustedRedirectResponse $response */
    $response = $cas_redirector->buildRedirectResponse($cas_data);
    $this->assertInstanceOf('\Drupal\core\Routing\TrustedRedirectResponse', $response);
    $data = $response->getCacheableMetadata();
    $tags = $data->getCacheTags();
    $this->assertContains('config:cas.settings', $tags, 'Cache Tags set');

    // Verify proper redirector type.
    $cas_data->setIsCacheable(FALSE);
    $response = $cas_redirector->buildRedirectResponse($cas_data);
    $this->assertInstanceOf('\Drupal\cas\CasRedirectResponse', $response, 'Non-cacheable response');
  }

  /**
   * Tests the events dispatched by the listener.
   *
   * @covers ::buildRedirectResponse
   */
  public function testEventsDispatched() {
    // Mock up listener on dispatched event.
    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback([$this, 'dispatchEvent']);
    $this->events = [];

    // Fire the redirection event.
    $cas_redirector = new CasRedirector($this->casHelper, $this->eventDispatcher, $this->urlGenerator, $this->configFactory);
    $cas_data = new CasRedirectData();
    $response = $cas_redirector->buildRedirectResponse($cas_data);

    // Verfiy that the event fired and the redirector was appropriately altered.
    $this->assertEquals(1, count($this->events), 'One Event was fired');
    $this->assertEquals('https://example-server.com/cas/login?strong_auth=true&service=http%3A//example.com/casservice', $response->getTargetUrl(), 'Altered parameters');
  }

}
