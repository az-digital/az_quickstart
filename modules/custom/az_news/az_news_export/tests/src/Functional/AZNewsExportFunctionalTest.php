<?php

namespace Drupal\Tests\az_news_export\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileExists;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;

/**
 * Validates the AZ News export endpoint output.
 *
 * @group az_news_export
 */
class AZNewsExportFunctionalTest extends QuickstartFunctionalTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string[]
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $defaultTheme = 'az_barrio';

  /**
   * Modules to enable for the test.
   *
   * @var string[]
   */
  protected static $modules = [
    'az_news_export',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
  }

  /**
   * Ensures the export endpoint returns serialized field data.
   */
  public function testAzNewsExportJson(): void {
    $tag = Term::create([
      'vid' => 'az_news_tags',
      'name' => 'Functional Test Tag',
    ]);
    $tag->save();

    $file_repository = $this->container->get('file.repository');
    $file = $file_repository->writeData(
      file_get_contents($this->root . '/core/misc/druplicon.png'),
      'public://functional-export-image.png',
      FileExists::Replace,
    );
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'az_image',
      'name' => 'Functional Export Media',
      'field_media_az_image' => [
        'target_id' => $file->id(),
        'alt' => 'Functional alt text',
      ],
    ]);
    $media->save();

    $node = Node::create([
      'type' => 'az_news',
      'title' => 'Functional Export Story',
      'status' => 1,
      'field_az_news_tags' => [
        ['target_id' => $tag->id()],
      ],
      'field_az_media_thumbnail_image' => [
        ['target_id' => $media->id()],
      ],
      'field_az_media_image' => [
        ['target_id' => $media->id()],
      ],
    ]);
    $node->save();

    $empty_media_node = Node::create([
      'type' => 'az_news',
      'title' => 'Functional Export Story Without Media',
      'status' => 1,
      'field_az_news_tags' => [
        ['target_id' => $tag->id()],
      ],
    ]);
    $empty_media_node->save();

    $this->drupalGet('az_quickstart/export/az_news/v1/az_news.json');
    $this->assertSession()->statusCodeEquals(200);
    $response = Json::decode($this->getSession()->getPage()->getContent());

    $this->assertIsArray($response);
    $this->assertNotEmpty($response);

    $match = NULL;
    $empty_match = NULL;
    foreach ($response as $row) {
      if (!empty($row['uuid']) && $row['uuid'] === $node->uuid()) {
        $match = $row;
      }
      if (!empty($row['uuid']) && $row['uuid'] === $empty_media_node->uuid()) {
        $empty_match = $row;
      }
    }

    $this->assertNotNull($match, 'Created node found in export.');
    $this->assertNotNull($empty_match, 'Created node without media found in export.');
    $this->assertArrayHasKey('field_az_news_tags', $match);
    $this->assertSame(['Functional Test Tag'], $match['field_az_news_tags']);

    $this->assertArrayHasKey('field_az_media_thumbnail_image', $match);
    $thumbnail_data = $match['field_az_media_thumbnail_image'];
    $this->assertIsArray($thumbnail_data);
    $this->assertArrayHasKey('fid', $thumbnail_data);
    $this->assertArrayHasKey('original', $thumbnail_data);
    $this->assertArrayHasKey('thumbnail', $thumbnail_data);
    $this->assertArrayHasKey('thumbnail_small', $thumbnail_data);
    $this->assertSame('Functional alt text', $thumbnail_data['alt']);
    $this->assertArrayNotHasKey(0, $thumbnail_data, 'Thumbnail image data is an object, not a list.');

    $this->assertArrayHasKey('field_az_media_image', $match);
    $image_data = $match['field_az_media_image'];
    $this->assertIsArray($image_data);
    $this->assertArrayHasKey('fid', $image_data);
    $this->assertArrayHasKey('original', $image_data);

    $this->assertArrayHasKey('field_az_media_thumbnail_image', $empty_match);
    $this->assertSame([], $empty_match['field_az_media_thumbnail_image'], 'Empty media thumbnail field serializes to an empty object.');
    $this->assertArrayHasKey('field_az_media_image', $empty_match);
    $this->assertSame([], $empty_match['field_az_media_image'], 'Empty media image field serializes to an empty object.');
  }

}
