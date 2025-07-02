<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Unit;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManager;
use Drupal\Tests\UnitTestCase;
use Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Tests the route normalizer.
 *
 * @group redirect
 *
 * @coversDefaultClass \Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber
 */
class RouteNormalizerRequestSubscriberTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $kill_switch = $this->createMock('\Drupal\Core\PageCache\ResponsePolicy\KillSwitch');
    $kill_switch->expects($this->any())
      ->method('trigger')
      ->withAnyParameters()
      ->will($this->returnValue(NULL));
    $container = new ContainerBuilder();
    $container->set('page_cache_kill_switch', $kill_switch);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::onKernelRequestRedirect
   */
  public function testSkipIfFlagNotEnabled() {
    $request_uri = 'https://example.com/route-to-normalize';
    $request_query = [];

    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query));
    // We set 'route_normalizer_enabled' config to FALSE and expect to leave onKernelRequestRedirect at the beginning,
    // i.e. $this->redirectChecker->canRedirect($request) should never be called.
    $subscriber = $this->getSubscriber($request_uri, FALSE, FALSE);
    $subscriber->onKernelRequestRedirect($event);
  }

  /**
   * @covers ::onKernelRequestRedirect
   */
  public function testSkipIfSubRequest() {
    $request_uri = 'https://example.com/route-to-normalize';
    $request_query = [];

    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query), HttpKernelInterface::SUB_REQUEST);
    // We are using SUB_REQUEST as the request type and expect to leave onKernelRequestRedirect at the beginning,
    // i.e. $this->redirectChecker->canRedirect($request) should never be called.
    $subscriber = $this->getSubscriber($request_uri, TRUE, FALSE);
    $subscriber->onKernelRequestRedirect($event);
  }

  /**
   * @covers ::onKernelRequestRedirect
   */
  public function testSkipIfRequestAttribute() {
    $request_uri = 'https://example.com/route-to-normalize';
    $request_query = [];

    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query), HttpKernelInterface::MAIN_REQUEST, TRUE);
    // We set '_disable_route_normalizer' as a request attribute and expect to leave onKernelRequestRedirect at the beginning,
    // i.e. $this->redirectChecker->canRedirect($request) should never be called.
    $subscriber = $this->getSubscriber($request_uri, TRUE, FALSE);
    $subscriber->onKernelRequestRedirect($event);
  }

  /**
   * @covers ::onKernelRequestRedirect
   * @dataProvider getTestUrls
   */
  public function testOnKernelRequestRedirect($request_uri, $request_query, $expected, $expect_normalization) {
    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query));
    $subscriber = $this->getSubscriber($request_uri);
    $subscriber->onKernelRequestRedirect($event);

    if ($expect_normalization) {
      $response = $event->getResponse();
      $this->assertEquals($expected, $response->getTargetUrl());
    }
  }

  /**
   * Data provider for testOnKernelRequestRedirect().
   */
  public function getTestUrls() {
    return [
      ['https://example.com/route-to-normalize', [], 'https://example.com/route-to-normalize', FALSE],
      ['https://example.com/route-to-normalize', ['key' => 'value'], 'https://example.com/route-to-normalize?key=value', FALSE],
      ['https://example.com/index.php/', ['q' => 'node/1'], 'https://example.com/?q=node%2F1', TRUE],
      ['https://example.com/index.php/', ['q' => 'node/1', 'p' => 'a+b'], 'https://example.com/?q=node%2F1&p=a%2Bb', TRUE],
    ];
  }

  /**
   * Create a RouteNormalizerRequestSubscriber object.
   *
   * @param string $request_uri
   *   The return value for the generateFromRoute method.
   * @param bool $enabled
   *   Flag indicating if the normalizer shoud be enabled.
   * @param bool $call_expected
   *   If true, canRedirect() and other methods should be called once.
   *
   * @return \Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber
   */
  protected function getSubscriber($request_uri, $enabled = TRUE, $call_expected = TRUE) {

    $alias_manager = $this->createMock(AliasManager::class);
    $alias_manager->expects($this->any())
      ->method('setCacheKey')
      ->with('/current-path');

    $current_path = $this->createMock(CurrentPathStack::class);
    $current_path->expects($this->any())
      ->method('getPath')
      ->willReturn('/current-path');

    return new RouteNormalizerRequestSubscriber(
      $this->getUrlGeneratorStub($request_uri, $call_expected),
      $this->getPathMatcherStub($call_expected),
      $this->getConfigFactoryStub([
        'redirect.settings' => [
          'route_normalizer_enabled' => $enabled,
          'default_status_code' => 301,
        ],
      ]),
      $this->getRedirectCheckerStub($call_expected),
      $alias_manager,
      $current_path
    );
  }

  /**
   * Gets the UrlGenerator mock object.
   *
   * @param string $request_uri
   *   The return value for the generateFromRoute method.
   * @param bool $call_expected
   *   If true, we expect generateFromRoute() to be called once.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getUrlGeneratorStub($request_uri, $call_expected = TRUE) {
    $url_generator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $options = ['absolute' => TRUE];

    $expectation = $call_expected ? $this->once() : $this->never();

    $url_generator->expects($expectation)
      ->method('generateFromRoute')
      ->with('<current>', [], $options)
      ->willReturn($request_uri);
    return $url_generator;
  }

  /**
   * Gets the PathMatcher mock object.
   *
   * @param bool $call_expected
   *   If true, we expect isFrontPage() to be called once.
   *
   * @return \Drupal\Core\Path\PathMatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getPathMatcherStub($call_expected = TRUE) {
    $path_matcher = $this->createMock('\Drupal\Core\Path\PathMatcherInterface');

    $expectation = $call_expected ? $this->once() : $this->never();

    $path_matcher->expects($expectation)
      ->method('isFrontPage')
      ->withAnyParameters()
      ->willReturn(FALSE);
    return $path_matcher;
  }

  /**
   * Gets the RedirectChecker mock object.
   *
   * @param bool $call_expected
   *   If true, we expect canRedirect() to be called once.
   *
   * @return \Drupal\redirect\RedirectChecker|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getRedirectCheckerStub($call_expected = TRUE) {
    $redirect_checker = $this->createMock('\Drupal\redirect\RedirectChecker');

    $expectation = $call_expected ? $this->once() : $this->never();

    $redirect_checker->expects($expectation)
      ->method('canRedirect')
      ->withAnyParameters()
      ->willReturn(TRUE);
    return $redirect_checker;
  }

  /**
   * Returns a GET response event object.
   *
   * @param string $path_info
   *   The path of the request.
   * @param array $query_string
   *   The query string of the request.
   * @param int $request_type
   *   The request type of the request.
   * @param bool $set_request_attribute
   *   If true, the request attribute '_disable_route_normalizer' will be set.
   *
   * @return \Symfony\Component\HttpKernel\Event\RequestEvent
   */
  protected function getGetResponseEventStub($path_info, $query_string, $request_type = HttpKernelInterface::MAIN_REQUEST, $set_request_attribute = FALSE) {
    $request = Request::create($path_info . '?' . $query_string, 'GET', [], [], [], ['SCRIPT_NAME' => 'index.php', 'SCRIPT_FILENAME' => 'index.php']);

    if ($set_request_attribute === TRUE) {
      $request->attributes->add(['_disable_route_normalizer' => TRUE]);
    }

    $http_kernel = $this->createMock('\Symfony\Component\HttpKernel\HttpKernelInterface');
    return new RequestEvent($http_kernel, $request, $request_type);
  }

}
