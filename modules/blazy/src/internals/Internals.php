<?php

namespace Drupal\blazy\internals;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\blazy\BlazySettings;
use Drupal\blazy\Media\BlazyFile;
use Drupal\blazy\Theme\Grid;
use Drupal\blazy\Utility\Markdown;
use Drupal\blazy\Utility\Path;

/**
 * Provides internal kitchen-sink non-reusable blazy utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Internals extends Content {

  /**
   * The data URI text.
   */
  const DATA_TEXT = 'data:text/plain;base64,';

  /**
   * The blazy HTML ID.
   *
   * @var int|null
   */
  protected static $blazyId;

  /**
   * Alias for FileExists::Replace for easy D10.3 removal.
   */
  public static function fileExistsReplace() {
    if (class_exists(FileExists::class)) {
      return FileExists::Replace;
    }
    // @todo remove when min D10.3.
    // @phpstan-ignore-next-line
    return FileSystemInterface::EXISTS_REPLACE;
  }

  /**
   * Alias for base_path() for easy removal.
   *
   * @todo replace base_path() if any replacement by D11.
   */
  public static function basePath(): ?string {
    return \base_path() ?: '';
  }

  /**
   * Returns TRUE if the link has empty title, or just plain URL or text.
   */
  public static function emptyOrPlainTextLink(array $link): bool {
    $empty = FALSE;
    if ($title = $link['#title'] ?? NULL) {
      // @todo php 8: str_starts_with($title, '/');
      $length = strlen('/');
      $empty = substr($title, 0, $length) === '/' || strpos($title, 'http') !== FALSE;
    }

    if ($empty ||
      isset($link['#plain_text']) ||
      isset($link['#context']['value'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns the URI elements of the entity.
   */
  public static function entityUrl($entity) {
    $url = NULL;
    if ($entity instanceof EntityInterface) {
      // Deals with UndefinedLinkTemplateException such as paragraphs type.
      // @see #2596385, or fetch the host entity.
      if (!$entity->isNew()) {
        try {
          $rel = $entity->getEntityType()
            ->hasLinkTemplate('revision') ? 'revision' : 'canonical';
          $url = $entity->toUrl($rel);
        }
        catch (\Exception $ignore) {
          // Do nothing.
        }
      }
    }
    return $url;
  }

  /**
   * Returns the trusted HTML ID of a single instance.
   */
  public static function getHtmlId($namespace = 'blazy', $id = ''): string {
    if (!isset(static::$blazyId)) {
      static::$blazyId = 0;
    }

    // Do not use dynamic Html::getUniqueId, otherwise broken AJAX.
    $id = empty($id) ? ($namespace . '-' . ++static::$blazyId) : $id;
    return Html::getId($id);
  }

  /**
   * Alias for Path::getPath().
   */
  public static function getPath($type, $name, $absolute = FALSE): ?string {
    return Path::getPath($type, $name, $absolute);
  }

  /**
   * Checks if it is an SVG.
   */
  public static function isSvg($uri): bool {
    return BlazyFile::isSvg($uri);
  }

  /**
   * Returns markdown.
   */
  public static function markdown($string, $help = TRUE, $sanitize = TRUE): string {
    return Markdown::parse($string, $help, $sanitize);
  }

  /**
   * Returns a wrapper to pass tests, or DI where adding params is troublesome.
   */
  public static function service($service) {
    return \Drupal::hasService($service) ? \Drupal::service($service) : NULL;
  }

  /**
   * Alias for Settings::init().
   */
  public static function settings(array $data = []): BlazySettings {
    return static::init($data);
  }

  /**
   * Alias for Grid::toNativeGrid().
   */
  public static function toNativeGrid(array &$settings): void {
    Grid::toNativeGrid($settings);
  }

  /**
   * Returns a entity object by a property.
   *
   * @todo remove for BlazyInterface::loadByProperty().
   */
  public static function loadByProperty($property, $value, $type, $manager = NULL): ?object {
    $manager = $manager ?: self::service('blazy.manager');
    return $manager ? $manager->loadByProperty($property, $value, $type) : NULL;
  }

  /**
   * Returns a entity object by a UUID.
   *
   * @todo remove for BlazyInterface::loadByUuid().
   */
  public static function loadByUuid($uuid, $type, $manager = NULL): ?object {
    $manager = $manager ?: self::service('blazy.manager');
    return $manager ? $manager->loadByUuid($uuid, $type) : NULL;
  }

  /**
   * Returns the app root.
   *
   * @todo remove after usage checks.
   */
  public static function root($container) {
    return $container->getParameter('app.root');
  }

}
