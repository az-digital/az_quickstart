<?php

namespace Drupal\Tests\cas\Unit\Controller;

use Drupal\cas\CasPropertyBag;
use Drupal\cas\Controller\ServiceController;
use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Exception\CasValidateException;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Utility\Token;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * ServiceController unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Controller\ServiceController
 */
class ServiceControllerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $casHelper;

  /**
   * The mocked CasValidator.
   *
   * @var \Drupal\cas\Service\CasValidator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $casValidator;

  /**
   * The mocked CasUserManager.
   *
   * @var \Drupal\cas\Service\CasUserManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $casUserManager;

  /**
   * The mocked CasLogout.
   *
   * @var \Drupal\cas\Service\CasLogout|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $casLogout;

  /**
   * The mocked Url Generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockBuilder
   */
  protected $configFactory;

  /**
   * The mocked request parameter bag.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestBag;

  /**
   * The mocked query parameter bag.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $queryBag;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $requestObject;

  /**
   * The mocked messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $externalAuth;

  /**
   * The token service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $cas_validation_info = $this->createMock(CasPropertyBag::class);
    $this->casValidator = $this->createMock('\Drupal\cas\Service\CasValidator');
    $this->casValidator->method('validateTicket')->willReturn($cas_validation_info);

    $this->casUserManager = $this->createMock('\Drupal\cas\Service\CasUserManager');
    $this->casLogout = $this->createMock('\Drupal\cas\Service\CasLogout');
    $this->configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'error_handling.login_failure_page' => '/user/login',
        'error_handling.message_validation_failure' => '/user/login',
        'login_success_message' => '',
      ],
    ]);
    $this->token = $this->prophesize(Token::class);
    $this->casHelper = new CasHelper($this->configFactory, new LoggerChannelFactory(), $this->token->reveal());
    $this->urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');

    $this->requestObject = new Request();
    $request_bag = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $query_bag = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $query_bag->method('has')->willReturn(TRUE);

    $this->requestObject->query = $query_bag;
    $this->requestObject->request = $request_bag;

    $storage = $this->createMock('\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage');
    $session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
      ->setConstructorArgs([$storage])
      ->onlyMethods([])
      ->getMock();
    $session->start();

    $this->requestObject->setSession($session);

    $this->requestBag = $request_bag;
    $this->queryBag = $query_bag;

    $this->messenger = $this->createMock('\Drupal\Core\Messenger\MessengerInterface');

    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->eventDispatcher->method('dispatch')
      ->withAnyParameters()
      ->willReturnCallback(function (Event $event, string $event_name): Event {
        if ($event instanceof CasPreUserLoadRedirectEvent && $event_name === CasHelper::EVENT_PRE_USER_LOAD_REDIRECT) {
          $event->getPropertyBag()->setUsername('foobar');
        }
        return $event;
      });

    $this->externalAuth = $this->prophesize(ExternalAuthInterface::class);
  }

  /**
   * Tests a single logout request.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSingleLogout($destination) {
    $this->setupRequestParameters(
      // destination.
      $destination,
      // logoutRequest.
      TRUE,
      // ticket.
      FALSE
    );

    $this->casLogout->expects($this->once())
      ->method('handleSlo')
      ->with($this->equalTo('<foobar/>'));

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      NULL,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher,
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $response = $serviceController->handle($this->requestObject);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('', $response->getContent());
  }

  /**
   * Tests that we redirect to the homepage when no service ticket is present.
   *
   * @dataProvider parameterDataProvider
   */
  public function testMissingTicketRedirectsHome($destination) {
    $this->setupRequestParameters(
      // destination.
      $destination,
      // logoutRequest.
      FALSE,
      // ticket.
      FALSE
    );

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      NULL,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher,
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests that validation and logging in occurs when a ticket is present.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSuccessfulLogin($destination) {
    $this->setupRequestParameters(
      // destination.
      $destination,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $validation_data = new CasPropertyBag('testuser');

    $this->assertSuccessfulValidation($destination);

    // Login should be called.
    $this->casUserManager->expects($this->once())
      ->method('login');

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      NULL,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher,
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests that a user is validated and logged in with Drupal acting as proxy.
   *
   * @dataProvider parameterDataProvider
   */
  public function testSuccessfulLoginProxyEnabled($destination) {
    $this->setupRequestParameters(
      // destination.
      $destination,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->assertSuccessfulValidation($destination, TRUE);

    $validation_data = new CasPropertyBag('testuser');
    $validation_data->setPgt('testpgt');

    // Login should be called.
    $this->casUserManager->expects($this->once())->method('login');

    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example-server.com',
        'server.port' => 443,
        'server.path' => '/cas',
        'proxy.initialize' => TRUE,
      ],
    ]);

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      NULL,
      $this->urlGenerator,
      $configFactory,
      $this->messenger,
      $this->eventDispatcher,
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToFrontPageOnHandle($serviceController);
  }

  /**
   * Tests for a potential validation error.
   *
   * @dataProvider parameterDataProvider
   */
  public function testTicketValidationError($destination) {
    $this->setupRequestParameters(
      // destination.
      $destination,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    // Validation should throw an exception.
    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->will($this->throwException(new CasValidateException()));

    // Login should not be called.
    $this->casUserManager->expects($this->never())
      ->method('login');

    $this->urlGenerator->method('generate')
      ->with($this->equalTo('<front>'))
      ->willReturn('/user/login');

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      NULL,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher,
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToSpecialPageOnLoginFailure($serviceController);
  }

  /**
   * Tests for a potential login error.
   *
   * @dataProvider parameterDataProvider
   */
  public function testLoginError($destination) {
    $this->setupRequestParameters(
      // destination.
      $destination,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $this->assertSuccessfulValidation($destination);

    // Login should throw an exception.
    $this->casUserManager->expects($this->once())
      ->method('login')
      ->will($this->throwException(new CasLoginException()));

    $this->urlGenerator->method('generate')
      ->with($this->equalTo('<front>'))
      ->willReturn('/user/login');

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      NULL,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher,
      $this->externalAuth->reveal()
    );
    $serviceController->setStringTranslation($this->getStringTranslationStub());

    $this->assertRedirectedToSpecialPageOnLoginFailure($serviceController);
  }

  /**
   * An event listener alters username before attempting to load user.
   *
   * @covers ::handle
   *
   * @dataProvider parameterDataProvider
   */
  public function testEventListenerChangesCasUsername($destination) {
    $this->setupRequestParameters(
      // destonation.
      $destination,
      // logoutRequest.
      FALSE,
      // ticket.
      TRUE
    );

    $expected_bag = new CasPropertyBag('foobar');

    $this->casUserManager->expects($this->once())->method('login');

    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->with($this->equalTo('ST-foobar'))
      ->will($this->returnValue($expected_bag));

    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->with('<front>')
      ->willReturn('/user/login');

    $serviceController = new ServiceController(
      $this->casHelper,
      $this->casValidator,
      $this->casUserManager,
      $this->casLogout,
      NULL,
      $this->urlGenerator,
      $this->configFactory,
      $this->messenger,
      $this->eventDispatcher,
      $this->externalAuth->reveal()
    );
    $serviceController->handle($this->requestObject);
  }

  /**
   * Asserts that user is redirected to a special page on login failure.
   */
  protected function assertRedirectedToSpecialPageOnLoginFailure($serviceController): void {
    $response = $serviceController->handle($this->requestObject);
    $this->assertTrue($response->isRedirect('/user/login'));
  }

  /**
   * Provides different query string params for tests.
   *
   * We want most test cases to behave accordingly for the matrix of
   * query string parameters that may be present on the request. This provider
   * will turn those params on or off.
   */
  public function parameterDataProvider() {
    return [
      // "destination" not set.
      [FALSE],
      // "destination" set.
      [TRUE],
    ];
  }

  /**
   * Assert user redirected to homepage when controller invoked.
   */
  private function assertRedirectedToFrontPageOnHandle($serviceController) {
    // URL Generator will generate a path to the homepage.
    $this->urlGenerator->expects($this->once())
      ->method('generate')
      ->with('<front>')
      ->will($this->returnValue('http://example.com/front'));

    $response = $serviceController->handle($this->requestObject);
    $this->assertTrue($response->isRedirect('http://example.com/front'));
  }

  /**
   * Asserts that validation is executed.
   */
  private function assertSuccessfulValidation($destination, $for_proxy = FALSE) {
    $service_params = [];
    if ($destination) {
      $service_params['destination'] = 'node/1';
    }

    $validation_data = new CasPropertyBag('testuser');
    if ($for_proxy) {
      $validation_data->setPgt('testpgt');
    }

    // Validation service should be called for that ticket.
    $this->casValidator->expects($this->once())
      ->method('validateTicket')
      ->with($this->equalTo('ST-foobar'), $this->equalTo($service_params))
      ->will($this->returnValue($validation_data));
  }

  /**
   * Mock our request and query bags for the provided parameters.
   *
   * This method accepts each possible parameter that the Sevice Controller
   * may need to deal with. Each parameter passed in should just be TRUE or
   * FALSE. If it's TRUE, we also mock the "get" method for the appropriate
   * parameter bag to return some predefined value.
   *
   * @param bool $destination
   *   If destination param should be set.
   * @param bool $logout_request
   *   If logoutRequest param should be set.
   * @param bool $ticket
   *   If ticket param should be set.
   */
  private function setupRequestParameters($destination, $logout_request, $ticket) {
    // Request params.
    $map = [
      ['logoutRequest', $logout_request],
    ];
    $this->requestBag->expects($this->any())
      ->method('has')
      ->will($this->returnValueMap($map));

    $map = [];
    if ($logout_request === TRUE) {
      $map[] = ['logoutRequest', NULL, '<foobar/>'];
    }
    if (!empty($map)) {
      $this->requestBag->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
    }

    // Query string params.
    $map = [
      ['destination', $destination],
      ['ticket', $ticket],
    ];
    $this->queryBag->expects($this->any())
      ->method('has')
      ->will($this->returnValueMap($map));

    $map = [];
    if ($destination === TRUE) {
      $map[] = ['destination', NULL, 'node/1'];
    }
    if ($ticket === TRUE) {
      $map[] = ['ticket', NULL, 'ST-foobar'];
    }
    if (!empty($map)) {
      $this->queryBag->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));
    }

    // Query string "all" method should include all params.
    $all = [];
    if ($destination) {
      $all['destination'] = 'node/1';
    }
    if ($ticket) {
      $all['ticket'] = 'ST-foobar';
    }
    $this->queryBag->method('all')
      ->will($this->returnValue($all));
  }

}
