<?php

namespace Drupal\blazy\Media;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\file\FileRepository;

/**
 * Provides File utility.
 */
interface BlazyFileInterface {

  /**
   * Returns file system service.
   */
  public function fileSystem(): FileSystemInterface;

  /**
   * Returns file repository service.
   */
  public function fileRepository(): FileRepository;

  /**
   * Returns the image factory.
   */
  public function imageFactory(): ImageFactory;

  /**
   * Constructs a new Image object.
   *
   * Normally, the toolkit set as default in the admin UI is used by the
   * factory to create new Image objects. This can be overridden through
   * \Drupal\Core\Image\ImageInterface::setToolkitId() so that any new Image
   * object created will use the new toolkit specified. Finally, a single
   * Image object can be created using a specific toolkit, regardless of the
   * current factory settings, by passing its plugin ID in the $toolkit_id
   * argument.
   *
   * @param string|null $source
   *   (optional) The path to an image file, or NULL to construct the object
   *   with no image source.
   * @param string|null $toolkit_id
   *   (optional) The ID of the image toolkit to use for this image, or NULL
   *   to use the current toolkit.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   An Image object.
   *
   * @see ImageFactory::setToolkitId()
   */
  public function image($source = NULL, $toolkit_id = NULL): ImageInterface;

  /**
   * Returns file system path from an URI.
   *
   * @param string $uri
   *   URI to convert.
   *
   * @return string
   *   The realpath.
   */
  public function realpath($uri): string;

}
