<?php

namespace Drupal\Tests\ctools_entity_mask\Functional;

use Drupal\entity_mask_test\Entity\BlockContent;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;

/**
 * Class DisplayTest.
 *
 * @group ctools_entity_mask
 */
class DisplayTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'ctools_entity_mask',
    'entity_mask_test',
    'field',
    'field_ui',
    'file',
    'image',
    'link',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($account);
  }

  /**
   * Tests that the form display for a masked entity replicates its source.
   */
  public function testFormDisplay() {
    $assert = $this->assertSession();

    $this->drupalGet('/fake-block/add/basic');
    $assert->statusCodeEquals(200);
    $assert->fieldExists('Body');
    $assert->fieldExists('Link');
    $assert->fieldExists('Image');
  }

  /**
   * Tests that the view display for a masked entity replicates its source.
   */
  public function testViewDisplay() {
    // Generate a random image for the image field, since that can potentially
    // be tricky.
    $image_uri = uniqid('public://') . '.png';
    $image_uri = $this->getRandomGenerator()->image($image_uri, '100x100', '200x200');
    $image = File::create(['uri' => $image_uri]);
    $image->save();

    $body = 'Qui animated corpse, cricket bat max brucks terribilem incessu zomby.';
    $link = 'https://www.drupal.org/project/ctools';

    $block = BlockContent::create([
      'type' => 'basic',
      'body' => $body,
      'field_link' => $link,
      'field_image' => $image,
    ]);
    $block->save();

    // Ensure that the entity is intact after serialization and deserialization,
    // since that may prove to be a common storage mechanism for mask entities.
    $block = serialize($block);
    $block = unserialize($block);

    $this->assertSame($body, $block->body->value);
    $this->assertSame($link, $block->field_link->uri);
    $this->assertSame($image_uri, $block->field_image->entity->getFileUri());

    $build = $this->container->get('entity_type.manager')
      ->getViewBuilder('fake_block_content')
      ->view($block);

    // If the fields are not in the renderable array, something has gone awry.
    $this->assertArrayHasKey('body', $build);
    $this->assertArrayHasKey('field_link', $build);
    $this->assertArrayHasKey('field_image', $build);

    // Render the block and check the output too, just to be sure.
    $rendered = $this->container->get('renderer')->renderRoot($build);
    $rendered = (string) $rendered;

    // @todo Use assertStringContainsString() when we rely exclusively on
    // PHPUnit 8.
    $this->assertNotFalse(strpos($rendered, $block->body->value));
    $this->assertNotFalse(strpos($rendered, $block->field_link->uri));

    $image_url = $block->field_image->entity->getFileUri();
    $image_url = $this->container->get('file_url_generator')->generateAbsoluteString($image_url);
    $image_url = $this->container->get('file_url_generator')->transformRelative($image_url);
    // @todo Use assertStringContainsString() when we rely exclusively on
    // PHPUnit 8.
    $this->assertNotFalse(strpos($rendered, $image_url));
  }

}
