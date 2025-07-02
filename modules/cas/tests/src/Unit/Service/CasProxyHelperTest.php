<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\Service\CasProxyHelper;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * CasHelper unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasProxyHelper
 */
class CasProxyHelperTest extends UnitTestCase {

  /**
   * The mocked session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The mocked CAS helper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $casHelper;

  /**
   * The mocked database connection object.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->session = new Session(new MockArraySessionStorage());
    $this->session->start();
    $this->casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');
    $this->database = $this->createMock('\Drupal\Core\Database\Connection');
  }

  /**
   * Test proxy authentication to a service.
   *
   * @covers ::proxyAuthenticate
   * @covers ::getServerProxyURL
   * @covers ::parseProxyTicket
   *
   * @dataProvider proxyAuthenticateDataProvider
   */
  public function testProxyAuthenticate($target_service, $cookie_domain, $already_proxied) {
    // Set up the fake pgt in the session.
    $this->session->set('cas_pgt', $this->randomMachineName(24));

    // Set up properties so the http client callback knows about them.
    $cookie_value = $this->randomMachineName(24);

    if ($already_proxied) {
      // Set up the fake session data.
      $session_cas_proxy_helper[$target_service][] = [
        'Name' => 'SESSION',
        'Value' => $cookie_value,
        'Domain' => $cookie_domain,
      ];
      $this->session->set('cas_proxy_helper', $session_cas_proxy_helper);

      $httpClient = new Client();
      $configFactory = $this->getConfigFactoryStub([
        'cas.settings' => [
          'server.hostname' => 'example-server.com',
          'server.port' => 443,
          'server.path' => '/cas',
        ],
      ]);
      $casProxyHelper = new CasProxyHelper($httpClient, $this->casHelper, $this->session, $configFactory, $this->database);

      $jar = $casProxyHelper->proxyAuthenticate($target_service);
      $cookie_array = $jar->toArray();
      $this->assertEquals('SESSION', $cookie_array[0]['Name']);
      $this->assertEquals($cookie_value, $cookie_array[0]['Value']);
      $this->assertEquals($cookie_domain, $cookie_array[0]['Domain']);
    }
    else {

      $proxy_ticket = $this->randomMachineName(24);
      $xml_response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
           <cas:proxySuccess>
             <cas:proxyTicket>PT-$proxy_ticket</cas:proxyTicket>
            </cas:proxySuccess>
         </cas:serviceResponse>";
      $mock = new MockHandler([
        new Response(200, [], $xml_response),
        new Response(200, [
          'Content-type' => 'text/html',
          'Set-Cookie' => 'SESSION=' . $cookie_value,
        ]),
      ]);
      $handler = HandlerStack::create($mock);
      $httpClient = new Client(['handler' => $handler]);

      $configFactory = $this->getConfigFactoryStub([
        'cas.settings' => [
          'server.hostname' => 'example-server.com',
          'server.port' => 443,
          'server.path' => '/cas',
          'proxy.initialize' => TRUE,
        ],
      ]);
      $casProxyHelper = new CasProxyHelper($httpClient, $this->casHelper, $this->session, $configFactory, $this->database);

      $jar = $casProxyHelper->proxyAuthenticate($target_service);
      $this->assertEquals('SESSION', $this->session->get('cas_proxy_helper')[$target_service][0]['Name']);
      $this->assertEquals($cookie_value, $this->session->get('cas_proxy_helper')[$target_service][0]['Value']);
      $this->assertEquals($cookie_domain, $this->session->get('cas_proxy_helper')[$target_service][0]['Domain']);
      $cookie_array = $jar->toArray();
      $this->assertEquals('SESSION', $cookie_array[0]['Name']);
      $this->assertEquals($cookie_value, $cookie_array[0]['Value']);
      $this->assertEquals($cookie_domain, $cookie_array[0]['Domain']);
    }
  }

  /**
   * Provides parameters and return value for testProxyAuthenticate.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasProxyHelperTest::testProxyAuthenticate
   */
  public function proxyAuthenticateDataProvider() {
    /* There are two scenarios that return successfully that we test here.
     * First, proxying a new service that was not previously proxied. Second,
     * a second request for a service that has already been proxied.
     */
    return [
      ['https://example.com', 'example.com', FALSE],
      ['https://example.com', 'example.com', TRUE],
    ];
  }

  /**
   * Test the possible exceptions from proxy authentication.
   *
   * @param bool $is_proxy
   *   expected isProxy method return value.
   * @param string $pgt_set
   *   Value for pgt_set session parameter.
   * @param string $target_service
   *   Target service.
   * @param string $response
   *   Expected response data.
   * @param string $client_exception
   *   Expected exception data.
   * @param string $exception_type
   *   Expected exception type.
   * @param string $exception_message
   *   Expected exception message.
   *
   * @covers ::proxyAuthenticate
   * @covers ::getServerProxyURL
   * @covers ::parseProxyTicket
   *
   * @dataProvider proxyAuthenticateExceptionDataProvider
   */
  public function testProxyAuthenticateException($is_proxy, $pgt_set, $target_service, $response, $client_exception, $exception_type, $exception_message) {
    if ($pgt_set) {
      // Set up the fake pgt in the session.
      $this->session->set('cas_pgt', $this->randomMachineName(24));
    }
    // Set up properties so the http client callback knows about them.
    $cookie_value = $this->randomMachineName(24);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'proxy.initialize' => $is_proxy,
      ],
    ]);

    if ($client_exception == 'server') {
      $code = 404;
    }
    else {
      $code = 200;
    }
    if ($client_exception == 'client') {
      $secondResponse = new Response(404);
    }
    else {
      $secondResponse = new Response(200, [
        'Content-type' => 'text/html',
        'Set-Cookie' => 'SESSION=' . $cookie_value,
      ]);
    }
    $mock = new MockHandler(
      [new Response($code, [], $response), $secondResponse]
    );
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $casProxyHelper = new CasProxyHelper($httpClient, $this->casHelper, $this->session, $configFactory, $this->database);
    $this->expectException($exception_type, $exception_message);
    $casProxyHelper->proxyAuthenticate($target_service);

  }

  /**
   * Provides parameters and exceptions for testProxyAuthenticateException.
   *
   * @return array
   *   Parameters and exceptions.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasProxyHelperTest::testProxyAuthenticateException
   */
  public function proxyAuthenticateExceptionDataProvider() {
    $target_service = 'https://example.com';
    $exception_type = '\Drupal\cas\Exception\CasProxyException';
    // Exception case 1: not configured as proxy.
    $params[] = [
      FALSE,
      TRUE,
      $target_service,
      '',
      FALSE,
      $exception_type,
      'Session state not sufficient for proxying.',
    ];

    // Exception case 2: session pgt not set.
    $params[] = [
      TRUE,
      FALSE,
      $target_service,
      '',
      FALSE,
      $exception_type,
      'Session state not sufficient for proxying.',
    ];

    // Exception case 3: http client exception from proxy app.
    $proxy_ticket = $this->randomMachineName(24);
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:proxySuccess>
          <cas:proxyTicket>PT-$proxy_ticket</cas:proxyTicket>
        </cas:proxySuccess>
      </cas:serviceResponse>";

    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      'client',
      $exception_type,
      '',
    ];

    // Exception case 4: http client exception from CAS Server.
    $proxy_ticket = $this->randomMachineName(24);
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:proxySuccess>
          <cas:proxyTicket>PT-$proxy_ticket</cas:proxyTicket>
        </cas:proxySuccess>
      </cas:serviceResponse>";

    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      'server',
      $exception_type,
      '',
    ];

    // Exception case 5: non-XML response from CAS server.
    $response = "<> </> </ <..";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server returned non-XML response.',
    ];

    // Exception case 6: CAS Server rejected ticket.
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:proxyFailure code=\"INVALID_REQUEST\">
           'pgt' and 'targetService' parameters are both required
         </cas:proxyFailure>
       </cas:serviceResponse>";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server rejected proxy request.',
    ];

    // Exception case 7: Neither proxyFailure nor proxySuccess specified.
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:proxy code=\"INVALID_REQUEST\">
         </cas:proxy>
       </cas:serviceResponse>";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server returned malformed response.',
    ];

    // Exception case 8: Malformed ticket.
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:proxySuccess>
        </cas:proxySuccess>
       </cas:serviceResponse>";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server provided invalid or malformed ticket.',
    ];

    return $params;
  }

}
