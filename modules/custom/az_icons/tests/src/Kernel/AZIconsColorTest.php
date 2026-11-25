<?php

namespace Drupal\Tests\az_icons\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\az_core\Utility\AZBootstrapColorNames;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the color setting of the Quickstart icon packs.
 *
 * The color values are the new Arizona Bootstrap color names. Some icons are
 * still saved with an old name, such as sky, so the icon dialog selects the
 * new name.
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
   * Tests that every color value is a new name, and has a label.
   */
  public function testColorValues(): void {
    foreach ($this->packDefinitions() as $pack_id => $definition) {
      $color = $definition['settings']['color'];
      $this->assertSame([], array_intersect($color['enum'], array_keys(AZBootstrapColorNames::DEPRECATED_COLOR_NAMES)), "No $pack_id color is an old name.");
      // Drupal drops a color from the select if it has no label.
      $this->assertSame($color['enum'], array_keys($color['meta:enum']), "Every $pack_id color has a label.");
      $this->assertSame('Rain', $color['meta:enum']['rain']);
      $this->assertSame('Cloud', $color['meta:enum']['cloud']);
    }
  }

  /**
   * Tests that the icon dialog selects the new name for an old color name.
   */
  public function testDialogColor(): void {
    $form = [
      'icon' => [
        '#default_settings' => [
          'material_symbols' => ['color' => 'sky', 'size' => 24],
        ],
      ],
    ];
    az_icons_form_ui_icons_ckeditor5_icon_dialog_alter($form, new FormState(), 'ui_icons_ckeditor5_icon_dialog');
    // The Color select has no "sky" value. "rain" is the value labeled Rain.
    $this->assertSame('rain', $form['icon']['#default_settings']['material_symbols']['color']);
    $this->assertSame(24, $form['icon']['#default_settings']['material_symbols']['size']);

    // A value that is already in the select stays as it is.
    $form['icon']['#default_settings'] = ['arizona_icons' => ['color' => 'tinta']];
    az_icons_form_ui_icons_ckeditor5_icon_dialog_alter($form, new FormState(), 'ui_icons_ckeditor5_icon_dialog');
    $this->assertSame('tinta', $form['icon']['#default_settings']['arizona_icons']['color']);
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
