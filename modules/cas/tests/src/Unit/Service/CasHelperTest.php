<?php

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\cas\Service\CasHelper;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

/**
 * CasHelper unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasHelper
 */
class CasHelperTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * The mocked Url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The mocked log channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerChannel;

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

    $this->loggerFactory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactory');
    $this->loggerChannel = $this->createMock('\Drupal\Core\Logger\LoggerChannel');
    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->with('cas')
      ->will($this->returnValue($this->loggerChannel));
    $this->token = $this->prophesize(Token::class);
    $this->token->replace('Use <a href="[cas:login-url]">CAS login</a>')
      ->willReturn('Use <a href="/caslogin">CAS login</a>');
    $this->token->replace('<script>alert("Hacked!");</script>')
      ->willReturn('<script>alert("Hacked!");</script>');
  }

  /**
   * Test the logging capability.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLogWhenDebugTurnedOn() {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => TRUE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    // The actual logger should be called twice.
    $this->loggerChannel->expects($this->exactly(2))
      ->method('log');

    $cas_helper->log(LogLevel::DEBUG, 'This is a debug log');
    $cas_helper->log(LogLevel::ERROR, 'This is an error log');
  }

  /**
   * Test our log wrapper when debug logging is off.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLogWhenDebugTurnedOff() {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => FALSE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    // The actual logger should only called once, when we log an error.
    $this->loggerChannel->expects($this->once())
      ->method('log');

    $cas_helper->log(LogLevel::DEBUG, 'This is a debug log');
    $cas_helper->log(LogLevel::ERROR, 'This is an error log');
  }

  /**
   * @covers ::handleReturnToParameter
   * @group legacy
   */
  public function testHandleReturnToParameter() {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => FALSE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, new LoggerChannelFactory(), $this->token->reveal());

    $request = new Request(['returnto' => 'node/1']);

    $this->assertFalse($request->query->has('destination'));
    $this->assertSame('node/1', $request->query->get('returnto'));

    $this->expectDeprecation("Using the 'returnto' query parameter in order to redirect to a destination after login is deprecated in cas:2.0.0 and removed from cas:3.0.0. Use 'destination' query parameter instead. See https://www.drupal.org/node/3231208");
    $cas_helper->handleReturnToParameter($request);

    // Check that the 'returnto' has been copied to 'destination'.
    $this->assertSame('node/1', $request->query->get('destination'));
    $this->assertSame('node/1', $request->query->get('returnto'));

    // Check that 'returnto' still takes precedence over 'destination' in order
    // to ensure backwards compatibility.
    $request = new Request(['destination' => 'node/2', 'returnto' => 'node/1']);
    $this->expectDeprecation("Using the 'returnto' query parameter in order to redirect to a destination after login is deprecated in cas:2.0.0 and removed from cas:3.0.0. Use 'destination' query parameter instead. See https://www.drupal.org/node/3231208");
    $cas_helper->handleReturnToParameter($request);
    $this->assertSame('node/1', $request->query->get('destination'));
    $this->assertSame('node/1', $request->query->get('returnto'));
  }

  /**
   * Tests the message generator.
   *
   * @covers ::getMessage
   */
  public function testGetMessage() {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'arbitrary_message' => 'Use <a href="[cas:login-url]">CAS login</a>',
        'messages' => [
          'empty_message' => '',
          'do_not_trust_user_input' => '<script>alert("Hacked!");</script>',
        ],
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    $message = $cas_helper->getMessage('arbitrary_message');
    $this->assertInstanceOf(FormattableMarkup::class, $message);
    $this->assertEquals('Use <a href="/caslogin">CAS login</a>', $message);

    // Empty message.
    $message = $cas_helper->getMessage('messages.empty_message');
    $this->assertSame('', $message);

    // Check hacker entered message.
    $message = $cas_helper->getMessage('messages.do_not_trust_user_input');
    // Check that the dangerous tags were stripped-out.
    $this->assertEquals('alert("Hacked!");', $message);
  }

}
