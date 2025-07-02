<?php

namespace Drupal\Tests\externalauth\Unit;

use Drupal\externalauth\Authmap;
use Drupal\Tests\UnitTestCase;

/**
 * Authmap unit tests.
 *
 * @ingroup externalauth
 *
 * @group externalauth
 *
 * @coversDefaultClass \Drupal\externalauth\Authmap
 */
class AuthmapTest extends UnitTestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $connection;

  /**
   * Mock statement.
   *
   * @var \Drupal\Core\Database\StatementInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $statement;

  /**
   * Mock select interface.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $select;

  /**
   * Mock delete class.
   *
   * @var \Drupal\Core\Database\Query\Delete
   */
  protected $delete;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a Mock database connection object.
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Statement object.
    $this->statement = $this->getMockBuilder('Drupal\Core\Database\StatementInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Select object and set expectations.
    $this->select = $this->getMockBuilder('Drupal\Core\Database\Query\Select')
      ->disableOriginalConstructor()
      ->getMock();

    $this->select->expects($this->any())
      ->method('fields')
      ->willReturnSelf();
    $this->select->expects($this->any())
      ->method('condition')
      ->willReturnSelf();
    $this->select->expects($this->any())
      ->method('range')
      ->willReturnSelf();
    $this->select->expects($this->any())
      ->method('orderBy')
      ->willReturnSelf();

    $this->select->expects($this->any())
      ->method('execute')
      ->willReturn($this->statement);

    $this->connection->expects($this->any())
      ->method('select')
      ->willReturn($this->select);

    // Create a Mock Delete object and set expectations.
    $this->delete = $this->getMockBuilder('Drupal\Core\Database\Query\Delete')
      ->disableOriginalConstructor()
      ->getMock();

    $this->delete->expects($this->any())
      ->method('condition')
      ->willReturnSelf();

    $this->delete->expects($this->any())
      ->method('execute')
      ->willReturn($this->statement);
  }

  /**
   * Test save() method.
   *
   * @covers ::save
   * @covers ::__construct
   */
  public function testSave() {
    $account = $this->createMock('Drupal\user\UserInterface');

    $merge = $this->getMockBuilder('Drupal\Core\Database\Query\Merge')
      ->disableOriginalConstructor()
      ->getMock();

    $merge->expects($this->any())
      ->method('keys')
      ->willReturnSelf();

    $merge->expects($this->any())
      ->method('fields')
      ->willReturnSelf();

    $merge->expects($this->any())
      ->method('execute')
      ->willReturn($this->statement);

    $this->connection->expects($this->once())
      ->method('merge')
      ->with($this->equalTo('authmap'))
      ->willReturn($merge);

    $authmap = new Authmap($this->connection);

    $authmap->save($account, "test_provider", "test_authname");
  }

  /**
   * Test get() method.
   *
   * @covers ::get
   * @covers ::__construct
   */
  public function testGet() {
    $actual_data = (object) [
      'authname' => "test_authname",
    ];
    $this->statement->expects($this->any())
      ->method('fetchObject')
      ->willReturn($actual_data);

    $authmap = new Authmap($this->connection);
    $result = $authmap->get(2, "test_provider");
    $this->assertEquals($result, "test_authname");
  }

  /**
   * Test getAuthData() method.
   *
   * @covers ::getAuthData
   * @covers ::__construct
   */
  public function testGetAuthData() {
    $actual_data = [
      'authname' => "test_authname",
      'data' => "test_data",
    ];
    $this->statement->expects($this->any())
      ->method('fetchAssoc')
      ->willReturn($actual_data);

    $authmap = new Authmap($this->connection);
    $result = $authmap->getAuthData(2, "test_provider");
    $this->assertEquals(['authname' => "test_authname", "data" => "test_data"], $result);
  }

  /**
   * Test getAll() method.
   *
   * @covers ::getAll
   * @covers ::__construct
   */
  public function testGetAll() {
    $actual_data = [
      'test_provider' => (object) [
        "authname" => "test_authname",
        "provider" => "test_provider",
      ],
      'test_provider2' => (object) [
        "authname" => "test_authname2",
        "provider" => "test_provider2",
      ],
    ];

    $this->statement->expects($this->any())
      ->method('fetchAllAssoc')
      ->willReturn($actual_data);

    $authmap = new Authmap($this->connection);
    $result = $authmap->getAll(2);
    $expected_data = [
      "test_provider" => "test_authname",
      "test_provider2" => "test_authname2",
    ];
    $this->assertEquals($expected_data, $result);
  }

  /**
   * Test getUid() method.
   *
   * @covers ::getUid
   * @covers ::__construct
   */
  public function testGetUid() {
    $actual_data = (object) [
      "uid" => 2,
    ];

    $this->statement->expects($this->any())
      ->method('fetchObject')
      ->willReturn($actual_data);

    $authmap = new Authmap($this->connection);
    $result = $authmap->getUid(2, "test_provider");
    $this->assertEquals(2, $result);
  }

  /**
   * Test delete() method.
   *
   * @covers ::delete
   * @covers ::__construct
   */
  public function testDelete() {
    $this->connection->expects($this->once())
      ->method('delete')
      ->with($this->equalTo('authmap'))
      ->willReturn($this->delete);

    $authmap = new Authmap($this->connection);
    $authmap->delete(2);
  }

  /**
   * Test delete() method, when passing in $provider.
   *
   * @covers ::delete
   * @covers ::__construct
   */
  public function testDeleteWithProvider() {
    // Create a Mock Delete object and set expectations.
    $this->delete = $this->getMockBuilder('Drupal\Core\Database\Query\Delete')
      ->disableOriginalConstructor()
      ->getMock();

    $this->delete->expects($this->exactly(2))
      ->method('condition')
      ->willReturnSelf();

    $this->delete->expects($this->any())
      ->method('execute')
      ->willReturn($this->statement);

    $this->connection->expects($this->once())
      ->method('delete')
      ->with($this->equalTo('authmap'))
      ->willReturn($this->delete);

    $authmap = new Authmap($this->connection);
    $authmap->delete(2, 'some_provider');
  }

  /**
   * Test deleteProviders() method.
   *
   * @covers ::deleteProvider
   * @covers ::__construct
   */
  public function testDeleteProviders() {
    $this->connection->expects($this->once())
      ->method('delete')
      ->with($this->equalTo('authmap'))
      ->willReturn($this->delete);

    $authmap = new Authmap($this->connection);
    $authmap->deleteProvider("test_provider");
  }

}
