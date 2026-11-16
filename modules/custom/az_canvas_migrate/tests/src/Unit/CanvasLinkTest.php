<?php

namespace Drupal\Tests\az_canvas_migrate\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\az_canvas_migrate\Plugin\migrate\process\CanvasLink;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests converting stored ranking links to Canvas link inputs.
 */
#[CoversClass(CanvasLink::class)]
#[Group('az_canvas_migrate')]
class CanvasLinkTest extends UnitTestCase {

  /**
   * Tests the link input built for a stored link.
   */
  #[DataProvider('providerLinks')]
  public function testTransform(mixed $link, ?array $expected): void {
    $plugin = new CanvasLink([], 'az_canvas_link', []);
    $this->assertSame($expected, $plugin->transform($link, $this->createMock(MigrateExecutableInterface::class), new Row(), 'link_url'));
  }

  /**
   * Provides stored links and the expected link inputs.
   */
  public static function providerLinks(): array {
    $link = fn (string $uri) => ['uri' => $uri, 'options' => []];
    return [
      'null' => [NULL, NULL],
      'whitespace' => ['  ', NULL],
      'front' => ['<front>', $link('internal:/')],
      'top anchor' => ['#', $link('internal:#top')],
      'anchor' => ['#section', $link('internal:#section')],
      'query' => ['?page=2', $link('internal:?page=2')],
      'path' => ['/about', $link('internal:/about')],
      'relative path' => ['about', $link('internal:/about')],
      'trimmed' => [' /about ', $link('internal:/about')],
      'external' => ['https://www.arizona.edu', $link('https://www.arizona.edu')],
      'entity' => ['entity:node/1', $link('entity:node/1')],
      'mailto' => ['mailto:info@arizona.edu', $link('mailto:info@arizona.edu')],
    ];
  }

}
