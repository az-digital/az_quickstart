<?php

declare(strict_types=1);

namespace Drupal\Tests\embed\Functional;

use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for all embed tests.
 */
abstract class EmbedTestBase extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'embed',
    'embed_test',
    'editor',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Filtered HTML text format and enable entity_embed filter.
    $format = FilterFormat::create([
      'format' => 'embed_test',
      'name' => 'Embed format',
      'filters' => [],
    ]);
    $format->save();

    // Create a user with required permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer embed buttons',
      'use text format embed_test',
    ]);

    // Create a user with required permissions.
    $this->webUser = $this->drupalCreateUser([
      'use text format embed_test',
    ]);

    // Set up some standard blocks for the testing theme (Classy).
    // @see https://www.drupal.org/node/507488?page=1#comment-10291517
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @param string $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript',
   *   'php', 'sql', 'text'.
   * @param int $size
   *   (optional) File size in bytes to match. Defaults to NULL, which will not
   *   filter the returned list by size.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @see \Drupal\Tests\TestFileCreationTrait::getTestFiles()
   */
  protected function getTestFile($type, $size = NULL) {
    // Get a file to upload.
    $file = current($this->getTestFiles($type, $size));

    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $file->filesize = filesize($file->uri);

    $file = File::create((array) $file);
    $file->save();
    return $file;
  }

}
