<?php

namespace Drupal\Tests\az_icons\Unit;

use Drupal\az_icons\Utility\AZIconColorNames;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests renaming of the Arizona Bootstrap color names saved in icons.
 */
#[Group('az_icons')]
class AZIconColorNamesTest extends UnitTestCase {

  /**
   * Tests that old color names in icons are renamed, and nothing else.
   */
  #[DataProvider('provideMarkup')]
  public function testUpdateMarkup(string $input, string $expected): void {
    $this->assertSame($expected, AZIconColorNames::updateMarkup($input));
  }

  /**
   * Tests that a broken icon tag with no > for a long way doesn't fail.
   */
  public function testLongBrokenTag(): void {
    $broken = '<drupal-icon data-icon-id="material_symbols:home" ' . str_repeat('a', 100000);
    $html = $broken . '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"sky"}\'></drupal-icon>';
    $expected = $broken . '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"rain"}\'></drupal-icon>';
    $this->assertSame($expected, AZIconColorNames::updateMarkup($html));
  }

  /**
   * Provides HTML and its expected updated value.
   */
  public static function provideMarkup(): array {
    return [
      // CKEditor 5 saves the attribute in double quotes.
      'double quotes, as CKEditor saves it' => [
        '<p><drupal-icon data-icon-id="material_symbols:person" data-icon-settings="{&quot;size&quot;:&quot;90&quot;,&quot;color&quot;:&quot;cool-gray&quot;}">&nbsp;</drupal-icon></p>',
        '<p><drupal-icon data-icon-id="material_symbols:person" data-icon-settings="{&quot;size&quot;:&quot;90&quot;,&quot;color&quot;:&quot;cloud&quot;}">&nbsp;</drupal-icon></p>',
      ],
      'single quotes' => [
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"sky"}\'></drupal-icon>',
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"rain"}\'></drupal-icon>',
      ],
      'numeric character references' => [
        '<drupal-icon data-icon-id="arizona_icons:a" data-icon-settings="{&#34;color&#34;:&#34;midnight&#34;}"></drupal-icon>',
        '<drupal-icon data-icon-id="arizona_icons:a" data-icon-settings="{&#34;color&#34;:&#34;tinta&#34;}"></drupal-icon>',
      ],
      'whitespace in the JSON' => [
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{ "color" : "oasis", "size": 24 }\'></drupal-icon>',
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{ "color" : "arroyo-blue", "size": 24 }\'></drupal-icon>',
      ],
      'settings before the icon ID' => [
        '<drupal-icon data-icon-settings=\'{"color":"bloom"}\' data-icon-id="arizona_icons:a">',
        '<drupal-icon data-icon-settings=\'{"color":"bougainvillea"}\' data-icon-id="arizona_icons:a">',
      ],
      'several icons' => [
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"leaf"}\'></drupal-icon> <drupal-icon data-icon-id="arizona_icons:a" data-icon-settings=\'{"color":"river"}\'></drupal-icon>',
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"saguaro"}\'></drupal-icon> <drupal-icon data-icon-id="arizona_icons:a" data-icon-settings=\'{"color":"shade"}\'></drupal-icon>',
      ],
      'a > in another attribute' => [
        '<drupal-icon aria-label="a > b" data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"mesa"}\'></drupal-icon>',
        '<drupal-icon aria-label="a > b" data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"brick"}\'></drupal-icon>',
      ],
      'new name' => [
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"rain"}\'></drupal-icon>',
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"rain"}\'></drupal-icon>',
      ],
      'inherit' => [
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"inherit"}\'></drupal-icon>',
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"inherit"}\'></drupal-icon>',
      ],
      'longer value that starts with an old name' => [
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"sky-blue"}\'></drupal-icon>',
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"sky-blue"}\'></drupal-icon>',
      ],
      'old name in another setting' => [
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"label":"sky"}\'></drupal-icon>',
        '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"label":"sky"}\'></drupal-icon>',
      ],
      // In a pack a site added, sky can be a valid color.
      'icon from another pack' => [
        '<drupal-icon data-icon-id="site_pack:star" data-icon-settings=\'{"color":"sky"}\'></drupal-icon>',
        '<drupal-icon data-icon-id="site_pack:star" data-icon-settings=\'{"color":"sky"}\'></drupal-icon>',
      ],
      'icon with no icon ID' => [
        '<drupal-icon data-icon-settings=\'{"color":"sky"}\'></drupal-icon>',
        '<drupal-icon data-icon-settings=\'{"color":"sky"}\'></drupal-icon>',
      ],
      'Styles dropdown class is left alone' => [
        '<p class="text-sky"><drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"sky"}\'></drupal-icon> Sky</p>',
        '<p class="text-sky"><drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"rain"}\'></drupal-icon> Sky</p>',
      ],
      'settings outside an icon tag' => [
        '<span data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"sky"}\'></span><code>{"color":"sky"}</code>',
        '<span data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"sky"}\'></span><code>{"color":"sky"}</code>',
      ],
      'no icon' => [
        '<p>A sky-colored paragraph.</p>',
        '<p>A sky-colored paragraph.</p>',
      ],
      'empty string' => ['', ''],
    ];
  }

}
