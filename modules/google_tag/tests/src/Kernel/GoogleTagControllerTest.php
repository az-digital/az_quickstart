<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel;

use Drupal\google_tag\GoogleTagController;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\google_tag\GoogleTagController
 * @group google_tag
 */
final class GoogleTagControllerTest extends GoogleTagTestCase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Burn uid 1.
    $this->createUser();
  }

  /**
   * Tests the ::containerListingAccess method.
   *
   * @param array $permissions
   *   The permissions.
   * @param bool $use_collection
   *   To enable use_collection.
   * @param bool $expected_access
   *   The expected access.
   *
   * @dataProvider accessData
   */
  public function testContainerListingAccess(array $permissions, bool $use_collection, bool $expected_access): void {
    $user = $this->createUser($permissions);
    self::assertNotEquals(1, (int) $user->id());
    $this->config('google_tag.settings')->set('use_collection', $use_collection)->save();

    $sut = $this->container
      ->get('class_resolver')
      ->getInstanceFromDefinition(GoogleTagController::class);
    self::assertInstanceOf(GoogleTagController::class, $sut);

    self::assertEquals($expected_access, $sut->containerListingAccess($user)->isAllowed());
  }

  /**
   * Tests the ::addContainerAccess method.
   *
   * @param array $permissions
   *   The permissions.
   * @param bool $use_collection
   *   To enable use_collection.
   * @param bool $expected_access
   *   The expected access.
   *
   * @dataProvider accessData
   */
  public function testAddContainerAccess(array $permissions, bool $use_collection, bool $expected_access): void {
    $user = $this->createUser($permissions);
    self::assertNotEquals(1, (int) $user->id());
    $this->config('google_tag.settings')->set('use_collection', $use_collection)->save();

    $sut = $this->container
      ->get('class_resolver')
      ->getInstanceFromDefinition(GoogleTagController::class);
    self::assertInstanceOf(GoogleTagController::class, $sut);

    self::assertEquals($expected_access, $sut->addContainerAccess($user)->isAllowed());
  }

  /**
   * Access data provider.
   */
  public static function accessData(): array {
    return [
      'has permission, no collection' => [
        ['administer google_tag_container'],
        FALSE,
        FALSE,
      ],
      'has permission, with collection' => [
        ['administer google_tag_container'],
        TRUE,
        TRUE,
      ],
      'without permission, no collection' => [
        [],
        FALSE,
        FALSE,
      ],
      'without permission, with collection' => [
        [],
        TRUE,
        FALSE,
      ],
    ];
  }

}
