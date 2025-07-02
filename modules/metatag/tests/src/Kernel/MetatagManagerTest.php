<?php

namespace Drupal\Tests\metatag\Kernel;

use Drupal\metatag\MetatagSeparator;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the Metatag Manager class.
 *
 * @group metatag
 */
class MetatagManagerTest extends KernelTestBase {

  use MetatagSeparator;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core modules.
    'system',
    'field',
    'text',
    'user',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
    'metatag_open_graph',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->metatagManager = $this->container->get('metatag.manager');
    $this->configFactory = $this->container->get('config.factory');

    $this->installConfig([
      'system',
      'field',
      'text',
      'user',
      'metatag',
      'metatag_open_graph',
    ]);
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests default tags for user entity.
   */
  public function testDefaultTagsFromEntity() {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->create();

    $default_tags = $this->metatagManager->defaultTagsFromEntity($user);
    $expected_tags = [
      'canonical_url' => '[user:url]',
      'title' => '[user:display-name] | [site:name]',
      'description' => '[site:name]',
    ];

    $this->assertSame($expected_tags, $default_tags);
  }

  /**
   * Test the order of the meta tags as they are output.
   */
  public function testMetatagOrder() {
    $tags = $this->metatagManager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      // @todo Update this to use the metatag-logo.png file.
      'og_image_url' => 'https://www.example.com/example/foo.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'https://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);
  }

  /**
   * Tests metatags with multiple values return multiple metatags.
   */
  public function testMetatagMultiple() {
    $tags = $this->metatagManager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      'og_image_url' => 'https://www.example.com/example/foo.png,https://www.example.com/example/foo2.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'https://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'https://www.example.com/example/foo2.png',
              ],
            ],
            'og_image_url_1',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);
  }

  /**
   * Tests the default settings to make sure they load as expected.
   */
  public function testDefaultSettings() {
    $config = $this->configFactory->get('metatag.settings');
    $this->assertEquals($config->get('entity_type_groups'), []);
    $this->assertEquals($config->get('separator'), '');
    $this->assertEquals($config->get('tag_trim_method'), 'beforeValue');
    $this->assertEquals($config->get('tag_trim_maxlength'), []);
    $this->assertEquals($config->get('tag_scroll_max_height'), '');
  }

  /**
   * Tests separator configuration and handling of multiple values.
   */
  public function testSeparator() {
    // Get the initial value of the separator, which is empty.
    $value = $this->configFactory->get('metatag.settings')->get('separator');
    $expected = '';
    $this->assertEquals($expected, $value);

    // Confirm that if it's empty it falls back to ','.
    $value = $this->getSeparator();
    $expected = ',';
    $this->assertEquals($expected, $value);

    // Make sure the separator works.
    $tags = $this->metatagManager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      'og_image_url' => 'https://www.example.com/example/foo.png' . $this->getSeparator() . 'https://www.example.com/example/foo2.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'https://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'https://www.example.com/example/foo2.png',
              ],
            ],
            'og_image_url_1',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);

    // Change the value of the separator.
    $config = $this->configFactory->getEditable('metatag.settings');
    $config->set('separator', '||')->save();
    $value = $this->configFactory->get('metatag.settings')->get('separator');
    $expected = '||';
    $this->assertEquals($expected, $value);

    // Make sure Metatag Manager correctly picks up the new value.
    $value = $this->getSeparator();
    $expected = '||';
    $this->assertEquals($expected, $value);

    // Make sure the new value works.
    $tags = $this->metatagManager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      'og_image_url' => 'https://www.example.com/example/foo.png' . $this->getSeparator() . 'https://www.example.com/example/foo2.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'https://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'https://www.example.com/example/foo2.png',
              ],
            ],
            'og_image_url_1',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);

  }

}
