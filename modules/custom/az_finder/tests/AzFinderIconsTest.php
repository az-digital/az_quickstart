<?php

declare(strict_types=1);

namespace Drupal\Tests\az_finder\Unit;

use Drupal\az_finder\AzFinderIcons;
use PHPUnit\Framework\TestCase;

/**
 * Tests the AzFinderIcons class.
 *
 * @group az_finder
 */
class AzFinderIconsTest extends TestCase {

  /**
   * Tests the generateSvgIcons method.
   */
  public function testGenerateSvgIcons() {
    $azFinderIcons = new AzFinderIcons();
    $svgIcons = $azFinderIcons->generateSvgIcons();

    $this->assertNotEmpty($svgIcons);

    $expectedKeys = ['level_0_expand', 'level_0_collapse', 'level_1_expand', 'level_1_collapse'];
    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $svgIcons);
    }

    $this->assertIsArray($svgIcons['level_0_expand']);
    $this->assertArrayHasKey('#type', $svgIcons['level_0_expand']);
    $this->assertArrayHasKey('#template', $svgIcons['level_0_expand']);
    $this->assertArrayHasKey('#context', $svgIcons['level_0_expand']);
    $this->assertIsArray($svgIcons['level_1_expand']);
    $this->assertArrayHasKey('#type', $svgIcons['level_1_expand']);
    $this->assertArrayHasKey('#template', $svgIcons['level_1_expand']);
    $this->assertArrayHasKey('#context', $svgIcons['level_1_expand']);
    $this->assertIsArray($svgIcons['level_1_collapse']);
    $this->assertArrayHasKey('#type', $svgIcons['level_1_collapse']);
    $this->assertArrayHasKey('#template', $svgIcons['level_1_collapse']);
    $this->assertArrayHasKey('#context', $svgIcons['level_1_collapse']);
    $this->assertIsArray($svgIcons['level_0_collapse']);
    $this->assertArrayHasKey('#type', $svgIcons['level_0_collapse']);
    $this->assertArrayHasKey('#template', $svgIcons['level_0_collapse']);
    $this->assertArrayHasKey('#context', $svgIcons['level_0_collapse']);

  }

}
