<?php

namespace Drupal\Tests\intelligencebank\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the intelligencebank module.
 *
 * @group intelligencebank
 */
class IntelligenceBankFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'intelligencebank_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * An administrative user to configure the test environment.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->moduleHandler = \Drupal::moduleHandler();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer intelligencebank configuration',
      'access administration pages'
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the proper options appear in the media mapping form.
   */
  public function testIntelligenceBankMediaMappingConfig() {
    $this->drupalGet('/admin/config/services/ib_dam/media');
    // Ensure media type filtering is working for IntelligenceBank image files.
    $expected_image_options = [
      '--' => '--',
      'image' => 'Image',
    ];
    $actual_image_options = $this->getOptions('media_types[image][media_type][id]');
    $this->assertEquals($actual_image_options, $expected_image_options);
    // Ensure media type filtering is working for IntelligenceBank audio files.
    $expected_audio_options = [
      '--' => '--',
      'audio' => 'Audio',
    ];
    $actual_audio_options = $this->getOptions('media_types[audio][media_type][id]');
    $this->assertEquals($actual_audio_options, $expected_audio_options);
    // Ensure media type filtering is working for IntelligenceBank video files.
    $expected_video_options = [
      '--' => '--',
      'video' => 'Video',
    ];
    $actual_video_options = $this->getOptions('media_types[video][media_type][id]');
    $this->assertEquals($actual_video_options, $expected_video_options);
    // Ensure media type filtering is working for IntelligenceBank files.
    $expected_file_options = [
      '--' => '--',
      'document' => 'Document',
    ];
    $actual_file_options = $this->getOptions('media_types[file][media_type][id]');
    $this->assertEquals($actual_file_options, $expected_file_options);
    // Ensure media type filtering is working for IntelligenceBank embeds.
    $expected_embed_options = [
      '--' => '--',
      'ib_dam_embed' => 'IntelligenceBank Embed',
    ];
    $actual_embed_options = $this->getOptions('media_types[embed][media_type][id]');
    $this->assertEquals($actual_embed_options, $expected_embed_options);

  }

}
