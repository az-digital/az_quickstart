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
   * Tests export with changed image field cardinality.
   */
  public function testAzNewsExportWithMultipleImages(): void {
    // Change the cardinality of the image field to unlimited.
    $field_storage = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->load('node.field_az_media_image');
    $field_storage->setCardinality(-1);
    $field_storage->save();

    // Create multiple media items.
    $file_repository = $this->container->get('file.repository');
    $media_items = [];

    for ($i = 1; $i <= 3; $i++) {
      $file = $file_repository->writeData(
        file_get_contents($this->root . '/core/misc/druplicon.png'),
        "public://functional-export-image-{$i}.png",
        FileExists::Replace,
      );
      $file->setPermanent();
      $file->save();

      $media = Media::create([
        'bundle' => 'az_image',
        'name' => "Functional Export Media {$i}",
        'field_media_az_image' => [
          'target_id' => $file->id(),
          'alt' => "Functional alt text {$i}",
        ],
      ]);
      $media->save();
      $media_items[] = $media;
    }

    // Create a news node with multiple images.
    $node = Node::create([
      'type' => 'az_news',
      'title' => 'News with Multiple Images',
      'status' => 1,
      'field_az_media_image' => [
        ['target_id' => $media_items[0]->id()],
        ['target_id' => $media_items[1]->id()],
        ['target_id' => $media_items[2]->id()],
      ],
    ]);
    $node->save();

    // Test the export endpoint.
    $this->drupalGet('az_quickstart/export/az_news/v1/az_news.json');
    $this->assertSession()->statusCodeEquals(200);
    $response = Json::decode($this->getSession()->getPage()->getContent());

    $this->assertIsArray($response);
    $this->assertNotEmpty($response);

    // Find our test node in the response.
    $match = NULL;
    foreach ($response as $row) {
      if (!empty($row['uuid']) && $row['uuid'] === $node->uuid()) {
        $match = $row;
        break;
      }
    }

    $this->assertNotNull($match, 'Created node with multiple images not found in export.');
    $this->assertArrayHasKey('field_az_media_image', $match);

    // With multiple images, should return an array of image objects.
    $image_data = $match['field_az_media_image'];
    $this->assertIsArray($image_data);
    $this->assertCount(3, $image_data, 'Expected 3 images in export but got different count.');

    // Verify each image has proper structure.
    foreach ($image_data as $i => $image_item) {
      $this->assertIsArray($image_item);
      $this->assertArrayHasKey('fid', $image_item);
      $this->assertArrayHasKey('original', $image_item);
      $this->assertArrayHasKey('alt', $image_item);
      $this->assertSame("Functional alt text " . ($i + 1), $image_item['alt']);
    }
  }

  /**
   * Tests export with single image (cardinality = 1).
   */
  public function testAzNewsExportWithSingleImage(): void {
    // Ensure the cardinality is set to 1 (default).
    $field_storage = \Drupal::entityTypeManager()
      ->getStorage('field_storage_config')
      ->load('node.field_az_media_image');
    $field_storage->setCardinality(1);
    $field_storage->save();

    // Create single media item.
    $file_repository = $this->container->get('file.repository');
    $file = $file_repository->writeData(
      file_get_contents($this->root . '/core/misc/druplicon.png'),
      'public://functional-single-image.png',
      FileExists::Replace,
    );
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'az_image',
      'name' => 'Single Image Media',
      'field_media_az_image' => [
        'target_id' => $file->id(),
        'alt' => 'Single image alt text',
      ],
    ]);
    $media->save();

    // Create a news node with single image.
    $node = Node::create([
      'type' => 'az_news',
      'title' => 'News with Single Image',
      'status' => 1,
      'field_az_media_image' => [
        ['target_id' => $media->id()],
      ],
    ]);
    $node->save();

    // Test the export endpoint.
    $this->drupalGet('az_quickstart/export/az_news/v1/az_news.json');
    $this->assertSession()->statusCodeEquals(200);
    $response = Json::decode($this->getSession()->getPage()->getContent());

    $this->assertIsArray($response);
    $this->assertNotEmpty($response);

    // Find our test node in the response.
    $match = NULL;
    foreach ($response as $row) {
      if (!empty($row['uuid']) && $row['uuid'] === $node->uuid()) {
        $match = $row;
        break;
      }
    }

    $this->assertNotNull($match, 'Created node with single image not found in export.');
    $this->assertArrayHasKey('field_az_media_image', $match);

    // With single value field, should return an object (not array).
    $image_data = $match['field_az_media_image'];
    $this->assertIsArray($image_data);
    $this->assertArrayHasKey('fid', $image_data);
    $this->assertArrayHasKey('original', $image_data);
    $this->assertArrayHasKey('alt', $image_data);
    $this->assertSame('Single image alt text', $image_data['alt']);
    // Ensure it's not a multi-value array.
    $this->assertArrayNotHasKey(0, $image_data, 'Single image exported as array instead of object.');
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
