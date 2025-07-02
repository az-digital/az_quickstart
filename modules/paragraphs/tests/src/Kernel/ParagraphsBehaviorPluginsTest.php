<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior plugins API.
 *
 * @group paragraphs
 */
class ParagraphsBehaviorPluginsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'paragraphs_test',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests the behavior settings API.
   */
  public function testBehaviorSettings() {
    // Create a paragraph type.
    $paragraph_type = ParagraphsType::create(array(
      'label' => 'test_text',
      'id' => 'test_text',
      'behavior_plugins' => [
        'test_text_color' => [
          'enabled' => TRUE,
        ]
      ],
    ));
    $paragraph_type->save();

    // Create a paragraph and set its feature settings.
    $paragraph = Paragraph::create([
      'type' => 'test_text',
    ]);
    $feature_settings = [
      'test_text_color' => [
        'text_color' => 'red'
      ],
    ];
    $paragraph->setAllBehaviorSettings($feature_settings);
    $paragraph->save();

    // Load the paragraph and assert its stored feature settings.
    $paragraph = Paragraph::load($paragraph->id());
    $this->assertEquals($paragraph->getAllBehaviorSettings(), $feature_settings);

    // Check the text color plugin settings summary.
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugins()->getEnabled();
    $this->assertEquals($plugin['test_text_color']->settingsSummary($paragraph)[0], ['label' => 'Text color', 'value' => 'red']);

    // Update the value of an specific plugin.
    $paragraph->setBehaviorSettings('test_text_color', ['text_color' => 'blue']);
    $paragraph->save();

    // Assert the values have been updated.
    $paragraph = Paragraph::load($paragraph->id());
    $this->assertEquals($paragraph->getBehaviorSetting('test_text_color', 'text_color'), 'blue');

    // Check the text color plugin settings summary.
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugins()->getEnabled();
    $this->assertEquals($plugin['test_text_color']->settingsSummary($paragraph)[0], ['label' => 'Text color', 'value' => 'blue']);

    // Settings another behavior settings should retain the original behaviors
    // from another plugin.
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $paragraph = Paragraph::load($paragraph->id());
    $paragraph->setBehaviorSettings('test_another_id', ['foo' => 'bar']);
    $paragraph->save();

    $paragraph = Paragraph::load($paragraph->id());
    $settings = $paragraph->getAllBehaviorSettings();
    $this->assertArrayHasKey('test_text_color', $settings);
    $this->assertArrayHasKey('test_another_id', $settings);
  }

  /**
   * Tests uninstalling a behavior plugin providing module.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testBehaviorUninstall() {
    // Create a paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
      'behavior_plugins' => [
        'test_text_color' => [
          'enabled' => TRUE,
        ],
      ],
    ]);
    $paragraph_type->save();
    $dependencies = $paragraph_type->getDependencies();
    $plugins = $paragraph_type->getBehaviorPlugins()->getInstanceIds();
    $this->assertSame(['module' => ['paragraphs_test']], $dependencies);
    $this->assertSame(['test_text_color' => 'test_text_color'], $plugins);

    // Uninstall plugin providing module.
    $this->container->get('config.manager')->uninstall('module', 'paragraphs_test');

    $paragraph_type = ParagraphsType::load('test_text');
    $this->assertNotNull($paragraph_type);
    $dependencies = $paragraph_type->getDependencies();
    $plugins = $paragraph_type->getBehaviorPlugins()->getInstanceIds();
    $this->assertSame([], $dependencies);
    $this->assertSame([], $plugins);
  }

}
