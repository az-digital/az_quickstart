<?php

namespace Drupal\Tests\metatag\Kernel\Migrate\d7;

use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Metatag-D7 configuration source plugin.
 *
 * @group metatag
 * @covers \Drupal\metatag\Plugin\migrate\source\d7\MetatagDefaults
 */
class MetatagDefaultsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/d7_metatag.php');

    $this->installConfig(static::$modules);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('metatag_defaults');

    // Run the Metatag defaults migration.
    $this->executeMigrations([
      'd7_metatag_defaults',
    ]);
  }

  /**
   * Test Metatag default configuration migration from Drupal 7 to 8.
   */
  public function testMetatag() {
    // The expected structure of the config items.
    $expected_configs = [
      'global' => [
        'langcode' => 'en',
        'label' => 'Global',
        'tags' => [
          'description' => 'Mango heaven!',
          'robots' => 'nofollow, noindex',
          'title' => 'I\'m in heaven!',
        ]
      ],
      'node' => [
        'langcode' => 'en',
        'label' => 'Node',
        'tags' => [
          'description' => 'The summary is: [node:field_summary]',
          'keywords' => 'mango, ',
          'robots' => 'follow, index',
          'title' => '[node:title]',
        ],
      ],
      'node__article' => [
        'langcode' => 'en',
        'label' => 'Node: Article',
        'tags' => [
          'keywords' =>'Alphonso, Angie, Julie',
          'robots' => 'nofollow, noindex',
        ],
      ],
      'taxonomy_term' => [
        'langcode' => 'en',
        'label' => 'Taxonomy Term',
        'tags' => [
          'description' => 'The summary is: [term;description]',
          'keywords' => 'mango, ',
          'robots' => 'follow, index',
          'title' => '[term:name]',
        ],
      ],
      'taxonomy_term__tags' => [
        'langcode' => 'en',
        'label' => 'Taxonomy Term: Tags',
        'tags' => [
          'keywords' => 'Alphonso, Angie, Julie',
          'robots' => 'nofollow, noindex',
        ],
      ],
      'user' => [
        'langcode' => 'en',
        'label' => 'User',
        'tags' => [
          'description' => 'The summary is: [user;name]',
          'keywords' => 'mango, ',
          'robots' => 'follow, index',
          'title' => '[user:name]',
        ],
      ],
      '404' => [
        'langcode' => 'en',
        'label' => '404 page not found',
      ],
      '404' => [
        'langcode' => 'en', 
        'label' => '404 page not found',
      ],
    ];

    foreach ($expected_configs as $config_name => $config) {
      $defaults = MetatagDefaults::load($config_name);
      $this->assertNotNull($defaults);
      // Convert the two names to strings because some of the config items are
      // numeric.
      $this->assertSame((string) $config_name, $defaults ? (string) $defaults->id() : '');

      $this->assertSame($config['langcode'], $defaults->language()->getId());
      $this->assertSame($config['label'], $defaults->label());

      // If a resultant tags value was expected, compare it against the migrated
      // value.
      if (!empty($config['tags'])) {
        foreach ($config['tags'] as $tag_name => $value) {
          $this->assertTrue($defaults->hasTag($tag_name));
          $this->assertSame($value, $defaults->getTag($tag_name));
        }
      }
    }
  }

}
