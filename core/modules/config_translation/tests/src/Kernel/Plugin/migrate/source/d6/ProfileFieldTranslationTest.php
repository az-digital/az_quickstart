<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore objectid

/**
 * Tests the i18nProfileField source plugin.
 *
 * @covers \Drupal\config_translation\Plugin\migrate\source\d6\ProfileFieldTranslation
 * @group migrate_drupal
 */
class ProfileFieldTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_translation', 'migrate_drupal', 'user'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $test = [];
    $test[0]['source_data'] = [
      'profile_fields' => [
        [
          'fid' => 2,
          'title' => 'Test',
          'name' => 'profile_test',
        ],
        [
          'fid' => 42,
          'title' => 'I love migrations',
          'name' => 'profile_love_migrations',
        ],
      ],
      'i18n_strings' => [
        [
          'lid' => 1,
          'objectid' => 'profile_test',
          'type' => 'field',
          'property' => 'explanation',
        ],
        [
          'lid' => 10,
          'objectid' => 'profile_love_migrations',
          'type' => 'field',
          'property' => 'title',
        ],
        [
          'lid' => 11,
          'objectid' => 'profile_love_migrations',
          'type' => 'field',
          'property' => 'explanation',
        ],
      ],
      'locales_target' => [
        [
          'lid' => 10,
          'translation' => "fr - I love migration.",
          'language' => 'fr',
        ],
        [
          'lid' => 11,
          'translation' => 'fr - If you check this box, you like migrations.',
          'language' => 'fr',
        ],
      ],
    ];
    $test[0]['expected_data'] = [
      [
        'property' => 'title',
        'translation' => "fr - I love migration.",
        'language' => 'fr',
        'fid' => '42',
        'name' => 'profile_love_migrations',
      ],
      [
        'property' => 'explanation',
        'translation' => 'fr - If you check this box, you like migrations.',
        'language' => 'fr',
        'fid' => '42',
        'name' => 'profile_love_migrations',
      ],
    ];
    return $test;
  }

}
