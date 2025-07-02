<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\Tests\entity_test\Functional\Rest\EntityTestResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Verify that the JSON output from JsonApi works as intended.
 *
 * @group metatag
 */
class EntityTestMetatagTest extends EntityTestResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['metatag'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Whether the metatag field has been added to the bundle.
   *
   * @var bool
   */
  protected $addedFields = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Set some global metatags.
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory
      ->getEditable('metatag.metatag_defaults.global')
      ->set('tags.title', 'Global Title')
      ->set('tags.description', 'Global description')
      ->set('tags.keywords', 'drupal8, testing, jsonapi, metatag')
      ->save();

    // The global default canonical URL is [current-page:url] which returns the
    // endpoint URL on a REST request, so be sure to set a default canonical URL
    // for the entity_test entity type.
    MetatagDefaults::create([
      'id' => 'entity_test',
      'tags' => [
        'title' => '[entity_test:name] | [site:name]',
        'canonical_url' => '[entity_test:url]',
      ],
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Add the fields here rather than in ::setUp() because they need to be
    // created before the entity is, and this method is called from
    // parent::setUp().
    if (!$this->addedFields) {
      $this->addedFields = TRUE;

      FieldStorageConfig::create([
        'entity_type' => 'entity_test',
        'field_name' => 'field_metatag',
        'type' => 'metatag',
      ])->save();

      FieldConfig::create([
        'entity_type' => 'entity_test',
        'field_name' => 'field_metatag',
        'bundle' => 'entity_test',
      ])->save();
    }

    $entity_test = EntityTest::create([
      'name' => 'Llama',
      'type' => 'entity_test',
      'field_metatag' => [
        'value' => Json::encode([
          'description' => 'This is a description for use in Search Engines',
        ]),
      ],
    ]);
    $entity_test->setOwnerId(0);
    $entity_test->save();

    return $entity_test;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $canonical_url = base_path() . 'entity_test/' . $this->entity->id();
    return parent::getExpectedNormalizedEntity() + [
      'field_metatag' => [
        [
          'value' => Json::encode([
            'description' => 'This is a description for use in Search Engines',
          ]),
        ],
      ],
      'metatag' => [
        [
          'tag' => 'meta',
          'attributes' => [
            'name' => 'title',
            'content' => 'Llama | Drupal',
          ],
        ],
        [
          'tag' => 'meta',
          'attributes' => [
            'name' => 'description',
            'content' => 'This is a description for use in Search Engines',
          ],
        ],
        [
          'tag' => 'meta',
          'attributes' => [
            'name' => 'keywords',
            'content' => 'drupal8, testing, jsonapi, metatag',
          ],
        ],
        [
          'tag' => 'link',
          'attributes' => [
            'rel' => 'canonical',
            'href' => $canonical_url,
          ],
        ],
      ],
    ];
  }

}
