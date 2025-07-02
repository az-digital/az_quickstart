<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\redirect\EventSubscriber\RedirectRequestSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the redirect logic.
 *
 * @group redirect
 *
 * @coversDefaultClass \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber
 */
class RedirectRequestSubscriberTest extends UnitTestCase {

  /**
   * @covers ::onKernelRequestCheckRedirect
   * @dataProvider getRedirectData
   */
  public function testRedirectLogicWithQueryRetaining($request_uri, $request_query, $redirect_uri, $redirect_query) {

    // The expected final query. This query must contain values defined
    // by the redirect entity and values from the accessed url.
    $final_query = $redirect_query + $request_query;

    $url = $this->createMock('Drupal\Core\Url');

    $url->expects($this->once())
      ->method('setAbsolute')
      ->with(TRUE)
      ->willReturn($url);

    $url->expects($this->once())
      ->method('getOption')
      ->with('query')
      ->willReturn($redirect_query);

    $url->expects($this->once())
      ->method('setOption')
      ->with('query', $final_query);

    $url->expects($this->once())
      ->method('toString')
      ->willReturn($redirect_uri);

    $redirect = $this->getRedirectStub($url);
    $event = $this->callOnKernelRequestCheckRedirect($redirect, $request_uri, $request_query, TRUE);

    $this->assertTrue($event->getResponse() instanceof RedirectResponse);
    $response = $event->getResponse();
    $this->assertEquals('/test-path', $response->getTargetUrl());
    $this->assertEquals(301, $response->getStatusCode());
    $this->assertEquals(1, $response->headers->get('X-Redirect-ID'));
  }

  /**
   * @covers ::onKernelRequestCheckRedirect
   * @dataProvider getRedirectData
   */
  public function testRedirectLogicWithoutQueryRetaining($request_uri, $request_query, $redirect_uri) {

    $url = $this->createMock('Drupal\Core\Url');

    $url->expects($this->once())
      ->method('setAbsolute')
      ->with(TRUE)
      ->willReturn($url);

    // No query retaining, so getOption should not be called.
    $url->expects($this->never())
      ->method('getOption');
    $url->expects($this->never())
      ->method('setOption');

    $url->expects($this->once())
      ->method('toString')
      ->willReturn($redirect_uri);

    $redirect = $this->getRedirectStub($url);
    $event = $this->callOnKernelRequestCheckRedirect($redirect, $request_uri, $request_query, FALSE);

    $this->assertTrue($event->getResponse() instanceof RedirectResponse);
    $response = $event->getResponse();
    $this->assertEquals($redirect_uri, $response->getTargetUrl());
    $this->assertEquals(301, $response->getStatusCode());
    $this->assertEquals(1, $response->headers->get('X-Redirect-ID'));
  }

  /**
   * Data provider for both tests.
   */
  public function getRedirectData() {
    return [
      ['non-existing', ['key' => 'val'], '/test-path', ['dummy' => 'value']],
      ['non-existing/', ['key' => 'val'], '/test-path', ['dummy' => 'value']],
      ['system/files/file.txt', [], '/test-path', []],
    ];
  }

  /**
   * Instantiates the subscriber and runs onKernelRequestCheckRedirect()
   *
   * @param $redirect
   *   The redirect entity.
   * @param $request_uri
   *   The URI of the request.
   * @param array $request_query
   *   The query that is supposed to come via request.
   * @param bool $retain_query
   *   Flag if to retain the query through the redirect.
   *
   * @return \Symfony\Component\HttpKernel\Event\RequestEvent
   *   THe response event.
   */
  protected function callOnKernelRequestCheckRedirect($redirect, $request_uri, $request_query, $retain_query) {

    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query));
    $request = $event->getRequest();

    $checker = $this->createMock('Drupal\redirect\RedirectChecker');
    $checker->expects($this->any())
      ->method('canRedirect')
      ->will($this->returnValue(TRUE));

    $context = $this->createMock('Symfony\Component\Routing\RequestContext');

    $inbound_path_processor = $this->createMock('Drupal\Core\PathProcessor\InboundPathProcessorInterface');
    $inbound_path_processor->expects($this->any())
      ->method('processInbound')
      ->with($request->getPathInfo(), $request)
      ->willReturnCallback(function ($path, Request $request) {
        if (strpos($path, '/system/files/') === 0 && !$request->query->has('file')) {
          // Private files paths are split by the inbound path processor and the
          // relative file path is moved to the 'file' query string parameter.
          // This is because the route system does not allow an arbitrary amount
          // of parameters.
          // @see \Drupal\system\PathProcessor\PathProcessorFiles::processInbound()
          $path = '/system/files';
        }
        return $path;
      });

    $alias_manager = $this->createMock(AliasManagerInterface::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);

    $subscriber = new RedirectRequestSubscriber(
      $this->getRedirectRepositoryStub('findMatchingRedirect', $redirect),
      $this->getLanguageManagerStub(),
      $this->getConfigFactoryStub(['redirect.settings' => ['passthrough_querystring' => $retain_query]]),
      $alias_manager,
      $module_handler,
      $entity_type_manager,
      $checker,
      $context,
      $inbound_path_processor
    );

    // Run the main redirect method.
    $subscriber->onKernelRequestCheckRedirect($event);
    return $event;
  }

  /**
   * Gets the redirect repository mock object.
   *
   * @param $method
   *   Method to mock - either load() or findMatchingRedirect().
   * @param $redirect
   *   The redirect object to be returned.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The redirect repository.
   */
  protected function getRedirectRepositoryStub($method, $redirect) {
    $repository = $this->createMock('Drupal\redirect\RedirectRepository');

    if ($method === 'findMatchingRedirect') {
      $repository->expects($this->any())
        ->method($method)
        ->willReturnCallback(function ($source_path) use ($redirect) {
          // No redirect with source path 'system/files' exists. The stored
          // redirect has 'system/files/file.txt' as source path.
          return $source_path === 'system/files' ? NULL : $redirect;
        });
    }
    else {
      $repository->expects($this->any())
        ->method($method)
        ->will($this->returnValue($redirect));
    }

    return $repository;
  }

  /**
   * Gets the redirect mock object.
   *
   * @param $url
   *   Url to be returned from getRedirectUrl
   * @param int $status_code
   *   The redirect status code.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mocked redirect object.
   */
  protected function getRedirectStub($url, $status_code = 301) {
    $redirect = $this->createMock('Drupal\redirect\Entity\Redirect');
    $redirect->expects($this->once())
      ->method('getRedirectUrl')
      ->will($this->returnValue($url));
    $redirect->expects($this->any())
      ->method('getStatusCode')
      ->will($this->returnValue($status_code));
    $redirect->expects($this->any())
      ->method('id')
      ->willReturn(1);
    $redirect->expects($this->once())
      ->method('getCacheTags')
      ->willReturn(['redirect:1']);

    return $redirect;
  }

  /**
   * Gets post response event.
   *
   * @param array $headers
   *   Headers to be set into the response.
   *
   * @return \Symfony\Component\HttpKernel\Event\TerminateEvent
   *   The post response event object.
   */
  protected function getPostResponseEvent($headers = []) {
    $http_kernel = $this->createMock('\Symfony\Component\HttpKernel\HttpKernelInterface');
    $request = $this->createMock('Symfony\Component\HttpFoundation\Request');

    $response = new Response('', 301, $headers);

    return new TerminateEvent($http_kernel, $request, $response);
  }

  /**
   * Gets response event object.
   *
   * @param $path_info
   *   The "pathinfo" (the url without querystring).
   * @param $query_string
   *   The query string.
   *
   * @return \Symfony\Component\HttpKernel\Event\RequestEvent
   */
  protected function getGetResponseEventStub($path_info, $query_string) {
    $request = Request::create($path_info . '?' . $query_string, 'GET', [], [], [], ['SCRIPT_NAME' => 'index.php']);

    $http_kernel = $this->createMock('\Symfony\Component\HttpKernel\HttpKernelInterface');
    return new RequestEvent($http_kernel, $request, HttpKernelInterface::MAIN_REQUEST);
  }

  /**
   * Gets the language manager mock object.
   *
   * @return \Drupal\language\ConfigurableLanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getLanguageManagerStub() {
    $language_manager = $this->createMock('Drupal\language\ConfigurableLanguageManagerInterface');
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(['id' => 'en'])));

    return $language_manager;
  }

}
