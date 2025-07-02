<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel\Entity;

use Drupal\linkit\Tests\ProfileCreationTrait;
use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests the Profile entity.
 *
 * @coversDefaultClass \Drupal\linkit\Entity\Profile
 *
 * @group linkit
 */
class ProfileTest extends LinkitKernelTestBase {

  use ProfileCreationTrait;

  /**
   * Test the profile description.
   *
   * @covers ::getDescription
   * @covers ::setDescription
   */
  public function testDescription() {
    $profile = $this->createProfile(['description' => 'foo']);
    $this->assertEquals('foo', $profile->getDescription());
    $profile->setDescription('bar');
    $this->assertEquals('bar', $profile->getDescription());
  }

}
