<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\CasPropertyBag;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasValidator;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * CasValidator unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasValidator
 */
class CasValidatorTest extends UnitTestCase {

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

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

    // Mock event dispatcher to dispatch events.
    $this->eventDispatcher = $this->createMock('\Symfony\Contracts\EventDispatcher\EventDispatcherInterface');
  }

  /**
   * Dispatch an event.
   *
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   Event fired.
   * @param string $event_name
   *   Name of event fired.
   *
   * @return \Symfony\Contracts\EventDispatcher\Event
   *   The fired event.
   */
  public function dispatchEvent(Event $event, $event_name): Event {
    $this->events[$event_name] = $event;
    switch ($event_name) {
      case CasHelper::EVENT_PRE_VALIDATE:
        $event->setValidationPath("customPath");
        $event->setParameter("foo", "bar");
        break;

      case CasHelper::EVENT_POST_VALIDATE:
        $propertyBag = $event->getCasPropertyBag();
        $propertyBag->setAttribute('email', ['modified@example.com']);
    }
    return $event;
  }

  /**
   * Test validation of Cas tickets.
   *
   * @covers ::__construct
   * @covers ::validateTicket
   * @covers ::validateVersion1
   * @covers ::validateVersion2
   * @covers ::verifyProxyChain
   * @covers ::parseAllowedProxyChains
   * @covers ::parseServerProxyChain
   *
   * @dataProvider validateTicketDataProvider
   */
  public function testValidateTicket($ticket, array $service_params, $username, $response, $validationRequestUrl, $version, $ssl_verification, $is_proxy, $can_be_proxied, $proxy_chains) {
    // Setup Guzzle to return a mock response.
    $mock = new MockHandler([new Response(200, [], $response)]);
    $handler = HandlerStack::create($mock);
    $guzzleTransactions = [];
    $history = Middleware::history($guzzleTransactions);
    $handler->push($history);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.protocol' => 'https',
        'server.path' => '/cas',
        'server.version' => $version,
        'server.verify' => $ssl_verification,
        'server.cert' => 'foo',
        'proxy.initialize' => $is_proxy,
        'proxy.can_be_proxied' => $can_be_proxied,
        'proxy.proxy_chains' => $proxy_chains,
      ],
    ]);

    // Need to mock the URL generator so it returns the correct URL based
    // on the service params that will be fed into it.
    if (!empty($service_params)) {
      $params = '';
      foreach ($service_params as $key => $value) {
        $params .= '&' . $key . '=' . urlencode($value);
      }
      $params = '?' . substr($params, 1);
      $return_value = 'https://example.com/client' . $params;
    }
    else {
      $return_value = 'https://example.com/client';
    }
    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $urlGenerator->expects($this->once())
      ->method('generate')
      ->will($this->returnValue($return_value));
    $urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValue('https://example.com/casproxycallback'));

    $casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);

    $property_bag = $casValidator->validateTicket($ticket, $service_params);

    // Verify the username is what we expect after parsing the response.
    $this->assertEquals($username, $property_bag->getUsername());

    // Make sure the request made to the server to validate ticket is what
    // we expect.
    $validationTransaction = array_shift($guzzleTransactions);
    $this->assertEquals((string) $validationTransaction['request']->getUri(), $validationRequestUrl);
  }

  /**
   * Provides parameters and return values for testValidateTicket.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasValidatorTest::testValidateTicket
   */
  public function validateTicketDataProvider() {
    $testCases = [];

    // Protocol v1, no proxying.
    $username = $this->randomMachineName(8);
    $response = "yes\n$username\n";
    $testCases[] = [
      'ST-123456',
      [],
      $username,
      $response,
      'https://example-server.com/cas/validate?service=https%3A//example.com/client&ticket=ST-123456',
      '1.0',
      CasHelper::CA_CUSTOM,
      FALSE,
      FALSE,
      '',
    ];

    // Protocol v1, no proxying, extra params for service URL.
    $username = $this->randomMachineName(8);
    $response = "yes\n$username\n";
    $testCases[] = [
      'ST-123456',
      ['destination' => 'node/1'],
      $username,
      $response,
      'https://example-server.com/cas/validate?service=https%3A//example.com/client%3Fdestination%3Dnode%252F1&ticket=ST-123456',
      '1.0',
      CasHelper::CA_CUSTOM,
      FALSE,
      FALSE,
      '',
    ];

    // Protocol v2, no proxying.
    $username = $this->randomMachineName(8);
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:authenticationSuccess>
          <cas:user>$username</cas:user>
        </cas:authenticationSuccess>
       </cas:serviceResponse>";
    $testCases[] = [
      'ST-123456',
      [],
      $username,
      $response,
      'https://example-server.com/cas/serviceValidate?service=https%3A//example.com/client&ticket=ST-123456',
      '2.0',
      CasHelper::CA_NONE,
      FALSE,
      FALSE,
      '',
    ];

    // Protocol v2, initialized as a proxy.
    $username = $this->randomMachineName(8);
    $pgt_iou = $this->randomMachineName(24);
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:authenticationSuccess>
           <cas:user>$username</cas:user>
             <cas:proxyGrantingTicket>PGTIOU-$pgt_iou
           </cas:proxyGrantingTicket>
         </cas:authenticationSuccess>
       </cas:serviceResponse>";
    $testCases[] = [
      'ST-123456',
      [],
      $username,
      $response,
      'https://example-server.com/cas/serviceValidate?service=https%3A//example.com/client&ticket=ST-123456&pgtUrl=https%3A//example.com/casproxycallback',
      '2.0',
      CasHelper::CA_DEFAULT,
      TRUE,
      FALSE,
      '',
    ];

    // Protocol v2, can be proxied.
    $username = $this->randomMachineName(8);
    $proxy_chains = '/https:\/\/example\.com/ /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:authenticationSuccess>
           <cas:user>$username</cas:user>
             <cas:proxies>
               <cas:proxy>https://example.com</cas:proxy>
               <cas:proxy>https://foo.com</cas:proxy>
             </cas:proxies>
         </cas:authenticationSuccess>
       </cas:serviceResponse>";
    $testCases[] = [
      'ST-123456',
      [],
      $username,
      $response,
      'https://example-server.com/cas/proxyValidate?service=https%3A//example.com/client&ticket=ST-123456',
      '2.0',
      CasHelper::CA_DEFAULT,
      FALSE,
      TRUE,
      $proxy_chains,
    ];

    // Protocol v2, proxy in both directions.
    $username = $this->randomMachineName(8);
    $pgt_iou = $this->randomMachineName(24);
    // Use the same proxy chains as the fourth test case.
    $response = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
        <cas:authenticationSuccess>
          <cas:user>$username</cas:user>
          <cas:proxyGrantingTicket>PGTIOU-$pgt_iou</cas:proxyGrantingTicket>
          <cas:proxies>
            <cas:proxy>https://https://bar.com</cas:proxy>
          </cas:proxies>
         </cas:authenticationSuccess>
      </cas:serviceResponse>";
    $testCases[] = [
      'ST-123456',
      [],
      $username,
      $response,
      'https://example-server.com/cas/proxyValidate?service=https%3A//example.com/client&ticket=ST-123456&pgtUrl=https%3A//example.com/casproxycallback',
      '2.0',
      CasHelper::CA_DEFAULT,
      TRUE,
      TRUE,
      $proxy_chains,
    ];

    return $testCases;
  }

  /**
   * Test validation failure conditions for the correct exceptions.
   *
   * @covers ::validateTicket
   * @covers ::validateVersion1
   * @covers ::validateVersion2
   * @covers ::verifyProxyChain
   * @covers ::parseAllowedProxyChains
   * @covers ::parseServerProxyChain
   *
   * @dataProvider validateTicketExceptionDataProvider
   */
  public function testValidateTicketException($version, $response, $is_proxy, $can_be_proxied, $proxy_chains, $exception, $exception_message, $http_client_exception) {
    if ($http_client_exception) {
      $mock = new MockHandler([
        new RequestException($exception_message, new Request('GET', 'test')),
      ]);
    }
    else {
      $mock = new MockHandler([new Response(200, [], $response)]);
    }
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => $version,
        'proxy.initialize' => $is_proxy,
        'proxy.can_be_proxied' => $can_be_proxied,
        'proxy.proxy_chains' => $proxy_chains,
      ],
    ]);

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);

    $this->expectException($exception, $exception_message);
    $ticket = $this->randomMachineName(24);
    $casValidator->validateTicket($ticket, []);
  }

  /**
   * Provides parameters and return values for testValidateTicketException.
   *
   * @return array
   *   Parameters and return values.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasValidatorTest::testValidateTicketException
   */
  public function validateTicketExceptionDataProvider() {
    /* There are nine different exception messages that can occur. We test for
     * each one. Currently, they are all of type 'CasValidateException', so we
     * set that up front. If that changes in the future, we can rework this bit
     * without changing the function signature.
     */
    $exception_type = '\Drupal\cas\Exception\CasValidateException';

    /* The first exception is actually a 'recasting' of an http client
     * exception.
     */
    $params[] = [
      '2.0',
      '',
      FALSE,
      FALSE,
      '',
      $exception_type,
      'External http client exception',
      TRUE,
    ];

    /* Protocol version 1 can throw two exceptions: 'no' text is found, or
     * 'yes' text is not found (in that order).
     */
    $params[] = [
      '1.0',
      "no\n\n",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'Ticket did not pass validation.',
      FALSE,
    ];
    $params[] = [
      '1.0',
      "Foo\nBar?\n",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'Malformed response from CAS server.',
      FALSE,
    ];

    // Protocol version 2: Malformed XML.
    $params[] = [
      '2.0',
      "<> </ </> <<",
      FALSE,
      FALSE,
      '',
      $exception_type,
      'XML from CAS server is not valid.',
      FALSE,
    ];

    // Protocol version 2: Authentication failure.
    $ticket = $this->randomMachineName(24);
    $params[] = [
      '2.0',
      '<cas:serviceResponse xmlns:cas="http://example.com/cas">
      <cas:authenticationFailure code="INVALID_TICKET">
      Ticket ' . $ticket . ' not recognized
      </cas:authenticationFailure>
      </cas:serviceResponse>',
      FALSE,
      FALSE,
      '',
      $exception_type,
      "Error Code INVALID_TICKET: Ticket $ticket not recognized",
      FALSE,
    ];

    // Protocol version 2: Neither authentication failure nor authentication
    // succes found.
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authentication>
      Username
      </cas:authentication>
      </cas:serviceResponse>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "XML from CAS server is not valid.",
      FALSE,
    ];

    // Protocol version 2: No user specified in authenticationSuccess.
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      Username
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "No user found in ticket validation response.",
      FALSE,
    ];

    // Protocol version 2: Proxy chain mismatch.
    $proxy_chains = '/https:\/\/example\.com/ /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      <cas:user>username</cas:user>
      <cas:proxies>
      <cas:proxy>https://example.com</cas:proxy>
      <cas:proxy>https://bar.com</cas:proxy>
      </cas:proxies>
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      FALSE,
      TRUE,
      $proxy_chains,
      $exception_type,
      "Proxy chain did not match allowed list.",
      FALSE,
    ];

    // Protocol version 2: Proxy chain mismatch with non-regex proxy chain.
    $proxy_chains = 'https://bar.com /https:\/\/foo\.com/' . PHP_EOL . '/https:\/\/bar\.com/';
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      <cas:user>username</cas:user>
      <cas:proxies>
      <cas:proxy>https://example.com</cas:proxy>
      <cas:proxy>https://bar.com</cas:proxy>
      </cas:proxies>
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      FALSE,
      TRUE,
      $proxy_chains,
      $exception_type,
      "Proxy chain did not match allowed list.",
      FALSE,
    ];

    // Protocol version 2: No PGTIOU provided when initialized as proxy.
    $params[] = [
      '2.0',
      "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
      <cas:authenticationSuccess>
      <cas:user>username</cas:user>
      </cas:authenticationSuccess>
      </cas:serviceResponse>",
      TRUE,
      FALSE,
      '',
      $exception_type,
      "Proxy initialized, but no PGTIOU provided in response.",
      FALSE,
    ];

    // Unknown protocol version.
    $params[] = [
      'foobarbaz',
      "<text>",
      FALSE,
      FALSE,
      '',
      $exception_type,
      "Unknown CAS protocol version specified: foobarbaz",
      FALSE,
    ];

    return $params;
  }

  /**
   * Test parsing out CAS attributes from response.
   *
   * @covers ::validateVersion2
   * @covers ::parseAttributes
   */
  public function testParseAttributes() {
    $ticket = $this->randomMachineName(8);
    $service_params = [];
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
    <cas:authenticationSuccess>
    <cas:user>username</cas:user>
    <cas:attributes>
    <cas:email>foo@example.com</cas:email>
    <cas:memberof>cn=foo,o=example</cas:memberof>
    <cas:memberof>cn=bar,o=example</cas:memberof>
    </cas:attributes>
    </cas:authenticationSuccess>
    </cas:serviceResponse>";
    $mock = new MockHandler([new Response(200, [], $response)]);
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.version' => '2.0',
      ],
    ]);

    $casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);
    $expected_bag = new CasPropertyBag('username');
    $expected_bag->setAttributes([
      'email' => ['foo@example.com'],
      'memberof' => ['cn=foo,o=example', 'cn=bar,o=example'],
    ]);
    $actual_bag = $casValidator->validateTicket($ticket, $service_params);
    $this->assertEquals($expected_bag, $actual_bag);
  }

  /**
   * Tests the post validation event dispatched by the listener.
   *
   * @covers ::validateTicket
   */
  public function testPostValidateEvent() {
    // Mock up listener on dispatched event.
    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback([$this, 'dispatchEvent']);
    $this->events = [];

    $ticket = $this->randomMachineName(8);
    $service_params = [];
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
    <cas:authenticationSuccess>
    <cas:user>username</cas:user>
    <cas:attributes>
    <cas:email>foo@example.com</cas:email>
    <cas:memberof>cn=foo,o=example</cas:memberof>
    <cas:memberof>cn=bar,o=example</cas:memberof>
    </cas:attributes>
    </cas:authenticationSuccess>
    </cas:serviceResponse>";
    $mock = new MockHandler([new Response(200, [], $response)]);
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.version' => '2.0',
      ],
    ]);

    $casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);
    $expected_bag = new CasPropertyBag('username');
    $expected_bag->setAttributes([
      'email' => ['modified@example.com'],
      'memberof' => ['cn=foo,o=example', 'cn=bar,o=example'],
    ]);
    $actual_bag = $casValidator->validateTicket($ticket, $service_params);
    $this->assertEquals($expected_bag, $actual_bag);
  }

  /**
   * Tests the pre validation event dispatched by the listener.
   *
   * @covers ::validateTicket
   */
  public function testPreValidateEvent() {
    // Mock up event dispatcher so we can return a fake subscriber that
    // subscribes to the pre validate event to change the request path.
    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback([$this, 'dispatchEvent']);
    $this->events = [];

    // Setup Guzzle to return a mock response.
    $mock = new MockHandler([new Response(200, [], "yes\nfoobar\n")]);
    $handler = HandlerStack::create($mock);
    $guzzleTransactions = [];
    $history = Middleware::history($guzzleTransactions);
    $handler->push($history);
    $httpClient = new Client(['handler' => $handler]);

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.protocol' => 'https',
        'server.path' => '/cas',
        'server.version' => '1.0',
        'server.verify' => CasHelper::CA_DEFAULT,
      ],
    ]);

    $ticket = $this->randomMachineName(8);

    $casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');

    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $casValidator = new CasValidator($httpClient, $casHelper, $configFactory, $urlGenerator, $this->eventDispatcher);
    $casValidator->validateTicket($ticket);

    // The 'fake' subscriber we created alters the path on the server to
    // "customPath", test that actually occurred.
    $expected_url = "@https://example\-server\.com/cas/customPath\?service=?&ticket=" . $ticket . '&foo=bar@';
    $actual_url = (string) $guzzleTransactions[0]['request']->getUri();
    $this->assertMatchesRegularExpression($expected_url, $actual_url);
  }

}
