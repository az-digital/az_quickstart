<?php

declare(strict_types=1);

namespace Drupal\Tests\media_entity_file_replace\Functional;

use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Base class for testing file replacement in functional tests.
 */
abstract class MediaEntityFileReplaceTestBase extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'field_ui',
    'media',
    'media_entity_file_replace',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMediaType('file', [
      'id' => 'document',
      'label' => 'Document',
    ]);
    $this->createMediaType('oembed:video', [
      'id' => 'remote_video',
      'label' => 'Remote Video',
    ]);
  }

  /**
   * Helper function to upload/replace file.
   *
   * @param string $uri
   *   The URI of the file to upload.
   * @param string $data
   *   The content of the file.
   * @param bool $override_original
   *   Whether we want to override the file or replace.
   */
  protected function uploadReplacementFile(string $uri, string $data, bool $override_original): void {
    file_put_contents($uri, $data);
    $page = $this->getSession()->getPage();
    $page->attachFileToField('File', \Drupal::service('file_system')->realpath($uri));
    if ($override_original) {
      $page->checkField('keep_original_filename');
    }
    else {
      $page->uncheckField('keep_original_filename');
    }
    $page->pressButton('Save');
    unlink($uri);
  }

  /**
   * Load media entity by its name.
   *
   * @param string $name
   *   The media name.
   *
   * @return \Drupal\media\MediaInterface
   *   The loaded media from storage.
   */
  protected function loadMediaEntityByName(string $name): MediaInterface {
    $mediaStorage = \Drupal::entityTypeManager()->getStorage('media');
    $mediaStorage->resetCache();
    $entities = $mediaStorage->loadByProperties(['name' => $name]);
    $this->assertNotEmpty($entities, "No media entity with name $name was found.");
    return array_pop($entities);
  }

  /**
   * Load file entity by ID.
   *
   * @param int|string $id
   *   The file ID to load.
   *
   * @return \Drupal\file\FileInterface
   *   The loaded file from storage.
   */
  protected function loadFileEntity(int|string $id): FileInterface {
    $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
    $fileStorage->resetCache();
    /** @var \Drupal\file\FileInterface $file */
    $file = $fileStorage->load($id);
    $this->assertNotNull($file, "No file entity with id $id was found.");
    return $file;
  }

}
