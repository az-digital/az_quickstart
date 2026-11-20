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
 * still saved with an old name, such as sky, so the templates and the icon
 * dialog map an old name to its new name.
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
   * Tests the class that the icon templates print for a saved color.
   */
  public function testColorClass(): void {
    // Each saved color, and the class it prints. NULL is no color class.
    $colors = [
      'rain' => 'text-rain',
      'cloud' => 'text-cloud',
      'azurite' => 'text-azurite',
      'sky' => 'text-rain',
      'cool-gray' => 'text-cloud',
      'inherit' => NULL,
      '' => NULL,
    ];
    foreach ($this->packDefinitions() as $pack_id => $definition) {
      foreach (['template', 'preview'] as $key) {
        foreach ($colors as $color => $expected_class) {
          $output = $this->renderTemplate($definition[$key], ['color' => $color]);
          $message = "$pack_id $key with color '$color'";
          if ($expected_class === NULL) {
            $this->assertStringNotContainsString('text-', $output, $message);
          }
          else {
            $this->assertMatchesRegularExpression('/class="[^"]*\b' . preg_quote($expected_class, '/') . '[ "]/', $output, $message);
          }
        }
        // An icon with no saved color.
        $this->assertStringNotContainsString('text-', $this->renderTemplate($definition[$key], []), "$pack_id $key with no color");
      }
    }
  }

  /**
   * Renders an icon pack template.
   *
   * @param string $template
   *   The Twig template from the icon pack definition.
   * @param array $settings
   *   The icon settings to render it with.
   *
   * @return string
   *   The rendered markup.
   */
  protected function renderTemplate(string $template, array $settings): string {
    $build = [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => ['icon_id' => 'home', 'size' => 24] + $settings,
    ];
    return (string) $this->container->get('renderer')->renderRoot($build);
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
