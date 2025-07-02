<?php

declare(strict_types=1);

namespace Drupal\sophron_guesser;

use Drupal\Core\File\FileSystemInterface;
use Drupal\sophron\MimeMapManagerInterface;
use FileEye\MimeMap\MappingException;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Makes possible to guess the MIME type of a file using its extension.
 */
class SophronMimeTypeGuesser implements MimeTypeGuesserInterface {

  /**
   * Constructs a SophronMimeTypeGuesser object.
   *
   * @param \Drupal\sophron\MimeMapManagerInterface $mimeMapManager
   *   The MIME map manager service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   */
  public function __construct(
    protected readonly MimeMapManagerInterface $mimeMapManager,
    protected readonly FileSystemInterface $fileSystem,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function guessMimeType(string $path): ?string {
    $extension = '';
    $file_parts = explode('.', $this->fileSystem->basename($path));

    // Remove the first part: a full filename should not match an extension,
    // then iterate over the file parts, trying to find a match.
    // For 'my.awesome.image.jpeg', we try: 'awesome.image.jpeg', then
    // 'image.jpeg', then 'jpeg'.
    // We explicitly check for NULL because that indicates that the array is
    // empty.
    while (array_shift($file_parts) !== NULL) {
      $extension = strtolower(implode('.', $file_parts));
      $mime_map_extension = $this->mimeMapManager->getExtension($extension);
      try {
        return $mime_map_extension->getDefaultType();
      }
      catch (MappingException $e) {
        continue;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {
    return TRUE;
  }

  /**
   * Sets the mimetypes/extension mapping to use when guessing mimetype.
   *
   * This method is implemented to ensure that when this class is set to
   * override \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser in the service
   * definition, any call to this method does not fatal. Actually, for Sophron
   * this is a no-op.
   *
   * @param array|null $mapping
   *   Not relevant.
   */
  public function setMapping(?array $mapping = NULL) {
    // Do nothing.
  }

}
