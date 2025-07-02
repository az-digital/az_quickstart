<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests file matcher.
 *
 * @group linkit
 */
class FileMatcherTest extends LinkitKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file_test', 'file'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    // Linkit doesn't care about the actual resource, only the entity.
    foreach (['gif', 'jpg', 'png'] as $ext) {
      $file = File::create([
        'uid' => 1,
        'filename' => 'image-test.' . $ext,
        'uri' => 'public://image-test.' . $ext,
        'filemime' => 'text/plain',
        'status' => FileInterface::STATUS_PERMANENT,
      ]);
      $file->save();
    }

    // Create user 1 who has special permissions.
    \Drupal::currentUser()->setAccount($this->createUser(['uid' => 1]));
  }

  /**
   * Tests file matcher.
   */
  public function testFileMatcherWithDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:file', []);
    $suggestions = $plugin->execute('image-test');
    $this->assertEquals(3, count($suggestions->getSuggestions()), 'Correct number of suggestions.');
  }

  /**
   * Tests file matcher with extension filer.
   */
  public function testFileMatcherWithExtensionFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:file', [
      'settings' => [
        'file_extensions' => 'png',
      ],
    ]);

    $suggestions = $plugin->execute('image-test');
    $this->assertEquals(1, count($suggestions->getSuggestions()), 'Correct number of suggestions with single file extension filter.');

    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:file', [
      'settings' => [
        'file_extensions' => 'png jpg',
      ],
    ]);

    $suggestions = $plugin->execute('image-test');
    $this->assertEquals(2, count($suggestions->getSuggestions()), 'Correct number of suggestions with multiple file extension filter.');
  }

  /**
   * Tests file matcher with tokens in the matcher metadata.
   */
  public function testTermMatcherWidthMetadataTokens() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:file', [
      'settings' => [
        'metadata' => '[file:fid] [file:field_with_no_value]',
      ],
    ]);

    $suggestionCollection = $plugin->execute('Lorem');
    /** @var \Drupal\linkit\Suggestion\EntitySuggestion[] $suggestions */
    $suggestions = $suggestionCollection->getSuggestions();

    foreach ($suggestions as $suggestion) {
      $this->assertStringNotContainsString('[file:fid]', $suggestion->getDescription(), 'Raw token "[file:fid]" is not present in the description');
      $this->assertStringNotContainsString('[file:field_with_no_value]', $suggestion->getDescription(), 'Raw token "[file:field_with_no_value]" is not present in the description');
    }
  }

}
