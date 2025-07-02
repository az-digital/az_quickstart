<?php

namespace Drupal\Tests\externalauth\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\externalauth\ExternalAuth;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * ExternalAuth unit tests.
 *
 * @ingroup externalauth
 *
 * @group externalauth
 *
 * @coversDefaultClass \Drupal\externalauth\ExternalAuth
 */
class ExternalAuthTest extends UnitTestCase {

  /**
   * The mocked entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $authmap;

  /**
   * The mocked logger instance.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a mock EntityTypeManager object.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    // Create a Mock Logger object.
    $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock EventDispatcher object.
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Authmap object.
    $this->authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Test the load() method.
   *
   * @covers ::load
   * @covers ::__construct
   */
  public function testLoad() {
    // Set up a mock for Authmap class,
    // mocking getUid() method.
    $authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->onlyMethods(['getUid'])
      ->getMock();

    $authmap->expects($this->once())
      ->method('getUid')
      ->willReturn(2);

    // Mock the User storage layer.
    $account = $this->createMock('Drupal\user\UserInterface');
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // Expect the external loading method to return a user object.
    $entity_storage->expects($this->once())
      ->method('load')
      ->willReturn($account);
    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->willReturn($entity_storage);

    $externalauth = new ExternalAuth(
      $this->entityTypeManager,
      $authmap,
      $this->logger,
      $this->eventDispatcher
    );
    $result = $externalauth->load("test_authname", "test_provider");
    $this->assertInstanceOf(UserInterface::class, $result);
  }

  /**
   * Test the login() method.
   *
   * @covers ::login
   * @covers ::__construct
   */
  public function testLogin() {
    // Set up a mock for ExternalAuth class,
    // mocking load() & userLoginFinalize() methods.
    $externalauth = $this->getMockBuilder('Drupal\externalauth\ExternalAuth')
      ->onlyMethods(['load', 'userLoginFinalize'])
      ->setConstructorArgs([
        $this->entityTypeManager,
        $this->authmap,
        $this->logger,
        $this->eventDispatcher,
      ])
      ->getMock();

    // Mock load method.
    $externalauth->expects($this->once())
      ->method('load')
      ->willReturn(FALSE);

    // Expect userLoginFinalize() to not be called.
    $externalauth->expects($this->never())
      ->method('userLoginFinalize');

    $result = $externalauth->login("test_authname", "test_provider");
    $this->assertEquals(FALSE, $result);
  }

  /**
   * Test the register() method.
   *
   * @covers ::register
   * @covers ::__construct
   *
   * @dataProvider registerDataProvider
   */
  public function testRegister($registration_data, $expected_data) {
    // Mock the returned User object.
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('enforceIsNew');
    $account->expects($this->once())
      ->method('save');
    $account->expects($this->any())
      ->method('getTimeZone')
      ->willReturn($expected_data['timezone']);

    // Mock the User storage layer to create a new user.
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // Expect the external registration to return us a user object.
    $entity_storage->expects($this->any())
      ->method('create')
      ->willReturn($account);
    $entity_storage->expects($this->any())
      ->method('loadByProperties')
      ->willReturn([]);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($entity_storage);

    // Set up a mock for Authmap class,
    // mocking getUid() method.
    $authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->onlyMethods(['save'])
      ->getMock();

    $authmap->expects($this->once())
      ->method('save');

    $dispatched_event = $this->getMockBuilder('\Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent')
      ->disableOriginalConstructor()
      ->getMock();

    $dispatched_event->expects($this->any())
      ->method('getUsername')
      ->willReturn($expected_data['username']);
    $dispatched_event->expects($this->any())
      ->method('getAuthname')
      ->willReturn($expected_data['authname']);
    $dispatched_event->expects($this->any())
      ->method('getData')
      ->willReturn($expected_data['data']);

    $this->eventDispatcher->expects($this->any())
      ->method('dispatch')
      ->willReturn($dispatched_event);

    $externalauth = new ExternalAuth(
      $this->entityTypeManager,
      $authmap,
      $this->logger,
      $this->eventDispatcher
    );
    $registered_account = $externalauth->register($registration_data['authname'], $registration_data['provider'], $registration_data['account_data'], $registration_data['authmap_data']);
    $this->assertInstanceOf(UserInterface::class, $registered_account);
    $this->assertEquals($expected_data['timezone'], $registered_account->getTimeZone());
    $this->assertEquals($expected_data['data'], $dispatched_event->getData());
  }

  /**
   * Provides test data for testRegister.
   *
   * @return array
   *   Parameters
   */
  public static function registerDataProvider(): array {
    return [
      // Test basic registration.
      [
        [
          'authname' => 'test_authname',
          'provider' => 'test_provider',
          'account_data' => [],
          'authmap_data' => NULL,
        ],
        [
          'username' => 'test_provider-test_authname',
          'authname' => 'test_authname',
          'timezone' => 'Europe/Brussels',
          'data' => [],
        ],
      ],
      // Test with added account data.
      [
        [
          'authname' => 'test_authname',
          'provider' => 'test_provider',
          'account_data' => ['timezone' => 'Europe/Prague'],
          'authmap_data' => NULL,
        ],
        [
          'username' => 'test_provider-test_authname',
          'authname' => 'test_authname',
          'timezone' => 'Europe/Prague',
          'data' => [],
        ],
      ],
      // Test with added authmap data.
      [
        [
          'authname' => 'test_authname',
          'provider' => 'test_provider',
          'account_data' => [],
          'authmap_data' => ['extra_property' => 'extra'],
        ],
        [
          'username' => 'test_provider-test_authname',
          'authname' => 'test_authname',
          'timezone' => 'Europe/Brussels',
          'data' => ['extra_property' => 'extra'],
        ],
      ],
    ];
  }

  /**
   * Test the loginRegister() method.
   *
   * @covers ::loginRegister
   * @covers ::__construct
   */
  public function testLoginRegister() {
    $account = $this->createMock('Drupal\user\UserInterface');

    // Set up a mock for ExternalAuth class,
    // mocking login(), register() & userLoginFinalize() methods.
    $externalauth = $this->getMockBuilder('Drupal\externalauth\ExternalAuth')
      ->onlyMethods(['login', 'register', 'userLoginFinalize'])
      ->setConstructorArgs([
        $this->entityTypeManager,
        $this->authmap,
        $this->logger,
        $this->eventDispatcher,
      ])
      ->getMock();

    // Mock ExternalAuth methods.
    $externalauth->expects($this->once())
      ->method('login')
      ->willReturn(FALSE);
    $externalauth->expects($this->once())
      ->method('register')
      ->willReturn($account);
    $externalauth->expects($this->once())
      ->method('userLoginFinalize')
      ->willReturn($account);

    $result = $externalauth->loginRegister("test_authname", "test_provider");
    $this->assertInstanceOf(UserInterface::class, $result);
  }

  /**
   * Test linking an existing account.
   */
  public function testLinkExistingAccount() {
    $account = $this->createMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('id')
      ->willReturn(5);

    // Set up a mock for Authmap class,
    // mocking get() & save() methods.
    $authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->onlyMethods(['save', 'get'])
      ->getMock();

    $authmap->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    $authmap->expects($this->once())
      ->method('save');

    $dispatched_event = $this->getMockBuilder('\Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent')
      ->disableOriginalConstructor()
      ->getMock();

    $dispatched_event->expects($this->any())
      ->method('getUsername')
      ->willReturn("Test username");
    $dispatched_event->expects($this->any())
      ->method('getAuthname')
      ->willReturn("Test authname");
    $dispatched_event->expects($this->any())
      ->method('getData')
      ->willReturn("Test data");

    $this->eventDispatcher->expects($this->any())
      ->method('dispatch')
      ->willReturn($dispatched_event);

    $externalauth = new ExternalAuth(
      $this->entityTypeManager,
      $authmap,
      $this->logger,
      $this->eventDispatcher
    );
    $externalauth->linkExistingAccount("test_authname", "test_provider", $account);
  }

}
