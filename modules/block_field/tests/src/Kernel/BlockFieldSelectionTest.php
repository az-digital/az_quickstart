<?php

namespace Drupal\Tests\block_field\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Tests blockFieldSelectionManager plugins.
 *
 * @group block_field
 */
class BlockFieldSelectionTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'system', 'block_field', 'locale'];

  /**
   * Returns a plugin instance from BlockFieldSelectionManager.
   *
   * @param string $plugin_id
   *   A plugin id.
   * @param array $settings
   *   A configuration settings array.
   *
   * @return \Drupal\block_field\BlockFieldSelectionInterface
   *   Returns an instance of the plugin with passed settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function setUpSelectionInstance($plugin_id = 'blocks', array $settings = []) {
    return $this->container->get('plugin.manager.block_field_selection')->createInstance($plugin_id, $settings);
  }

  /**
   * Test if BlockFieldSelectionInterface plugins are serializable.
   */
  public function testIsSerializable() {
    // Create a plugin instance of 'categories'.
    $plugin = $this->setUpSelectionInstance('categories', ['categories' => ['core']]);

    $translation_service = \Drupal::service('string_translation');
    $plugin->setStringTranslation($translation_service);

    // Attempt to serialize and unserialize plugin.
    $string = serialize($plugin);
    $object = unserialize($string);

    // Confirm plugin_id is unchanged.
    $this->assertEquals('categories', $object->getPluginId());

    // Repeat steps with instance of 'blocks'.
    $plugin = $this->setUpSelectionInstance('blocks', ['plugin_ids' => ['system_powered_by_block', 'page_title_block']]);
    $plugin->setStringTranslation($translation_service);

    // Attempt to serialize and unserialize plugin.
    $string = serialize($plugin);
    $object = unserialize($string);

    // Confirm plugin_id is unchanged.
    $this->assertEquals('blocks', $object->getPluginId());
  }

}
