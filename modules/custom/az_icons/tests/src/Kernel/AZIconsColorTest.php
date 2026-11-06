<?php

namespace Drupal\Tests\az_icons\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the color setting of the Quickstart icon packs.
 *
 * The color values are the old Arizona Bootstrap color names, because those
 * are saved in content. The labels and the printed classes use the new names.
 */
#[Group('az_icons')]
#[RunTestsInSeparateProcesses]
class AZIconsColorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'az_icons',
    'system',
    'ui_icons',
    'ui_icons_font',
  ];

  /**
   * Tests that every color value has a label.
   */
  public function testColorLabels(): void {
    foreach ($this->packDefinitions() as $pack_id => $definition) {
      $color = $definition['settings']['color'];
      // Drupal drops a color from the select if it has no label.
      $this->assertSame($color['enum'], array_keys($color['meta:enum']), "Every $pack_id color has a label.");
      $this->assertSame('Rain', $color['meta:enum']['sky']);
      $this->assertSame('Cloud', $color['meta:enum']['cool-gray']);
    }
  }

  /**
   * Tests the class that the icon templates print for a saved color.
   *
   * @param string|null $color
   *   The saved color, or NULL for an icon with no saved color.
   * @param string|null $expected_class
   *   The class the icon gets, or NULL if it gets no color class.
   */
  #[DataProvider('provideColors')]
  public function testColorClass(?string $color, ?string $expected_class): void {
    foreach ($this->packDefinitions() as $pack_id => $definition) {
      foreach (['template', 'preview'] as $key) {
        $context = ['icon_id' => 'home', 'size' => 24];
        if ($color !== NULL) {
          $context['color'] = $color;
        }
        $build = [
          '#type' => 'inline_template',
          '#template' => $definition[$key],
          '#context' => $context,
        ];
        $output = (string) $this->container->get('renderer')->renderRoot($build);
        $message = "$pack_id $key with color " . var_export($color, TRUE);
        if ($expected_class === NULL) {
          $this->assertStringNotContainsString('text-', $output, $message);
        }
        else {
          $this->assertMatchesRegularExpression('/class="[^"]*\b' . preg_quote($expected_class, '/') . '[ "]/', $output, $message);
        }
      }
    }
  }

  /**
   * Provides saved colors and the class each one prints.
   */
  public static function provideColors(): array {
    return [
      'old name' => ['sky', 'text-rain'],
      'old name with a hyphen' => ['cool-gray', 'text-cloud'],
      'name that did not change' => ['azurite', 'text-azurite'],
      'new name' => ['rain', 'text-rain'],
      'inherit' => ['inherit', NULL],
      'empty' => ['', NULL],
      'no saved color' => [NULL, NULL],
    ];
  }

  /**
   * Tests that the icon dialog selects the option for a new color name.
   */
  public function testDialogColor(): void {
    $form = [
      'icon' => [
        '#default_settings' => [
          'material_symbols' => ['color' => 'rain', 'size' => 24],
        ],
      ],
    ];
    az_icons_form_ui_icons_ckeditor5_icon_dialog_alter($form, new FormState(), 'ui_icons_ckeditor5_icon_dialog');
    // The Color select has no "rain" value. "sky" is the value labeled Rain.
    $this->assertSame('sky', $form['icon']['#default_settings']['material_symbols']['color']);
    $this->assertSame(24, $form['icon']['#default_settings']['material_symbols']['size']);

    // A value that is already in the select stays as it is.
    $form['icon']['#default_settings'] = ['arizona_icons' => ['color' => 'midnight']];
    az_icons_form_ui_icons_ckeditor5_icon_dialog_alter($form, new FormState(), 'ui_icons_ckeditor5_icon_dialog');
    $this->assertSame('midnight', $form['icon']['#default_settings']['arizona_icons']['color']);
  }

  /**
   * Gets the definitions of the two Quickstart icon packs.
   *
   * @return array
   *   The icon pack definitions, keyed by icon pack ID.
   */
  protected function packDefinitions(): array {
    $manager = $this->container->get('plugin.manager.icon_pack');
    return [
      'arizona_icons' => $manager->getDefinition('arizona_icons'),
      'material_symbols' => $manager->getDefinition('material_symbols'),
    ];
  }

}
