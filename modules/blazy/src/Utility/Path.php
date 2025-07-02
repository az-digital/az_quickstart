<?php

namespace Drupal\blazy\Utility;

use Drupal\blazy\Blazy;
use Drupal\blazy\internals\Internals;

/**
 * Provides url, route, request, stream, or any path-related methods.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module. Please use the public method instead.
 */
class Path {

  /**
   * The AMP page.
   *
   * @var bool|null
   */
  protected static $isAmp;

  /**
   * The preview mode to disable Blazy where JS is not available, or useless.
   *
   * @var bool|null
   */
  protected static $isPreview;

  /**
   * The preview mode to disable interactive elements.
   *
   * @var bool|null
   */
  protected static $isSandboxed;

  /**
   * Retrieves the file url generator service.
   *
   * @return \Drupal\Core\File\FileUrlGenerator|null
   *   The file url generator.
   *
   * @see https://www.drupal.org/node/2940031
   */
  public static function fileUrlGenerator() {
    return Internals::service('file_url_generator');
  }

  /**
   * Retrieves the path resolver.
   *
   * @return \Drupal\Core\Extension\ExtensionPathResolver|null
   *   The path resolver.
   */
  public static function pathResolver() {
    return Internals::service('extension.path.resolver');
  }

  /**
   * Retrieves the request stack.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack|null
   *   The request stack.
   */
  public static function requestStack() {
    return Internals::service('request_stack');
  }

  /**
   * Retrieves the currently active route match object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface|null
   *   The currently active route match object.
   */
  public static function routeMatch() {
    return Internals::service('current_route_match');
  }

  /**
   * Retrieves the stream wrapper manager service.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperManager|null
   *   The stream wrapper manager.
   */
  public static function streamWrapperManager() {
    return Internals::service('stream_wrapper_manager');
  }

  /**
   * Retrieves the request.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   The request.
   *
   * @see https://github.com/symfony/symfony/blob/6.0/src/Symfony/Component/HttpFoundation/Request.php
   */
  public static function request() {
    if ($stack = self::requestStack()) {
      return $stack->getCurrentRequest();
    }
    return NULL;
  }

  /**
   * Returns the commonly used path, or just the base path.
   */
  public static function getPath($type, $name, $absolute = FALSE): ?string {
    if ($resolver = self::pathResolver()) {
      $path = $resolver->getPath($type, $name);

      return $absolute ? Internals::basePath() . $path : $path;
    }
    return '';
  }

  /**
   * Checks if Blazy is in CKEditor preview mode where no JS assets are loaded.
   */
  public static function isPreview(): bool {
    if (!isset(static::$isPreview)) {
      static::$isPreview = self::isAmp() || self::isSandboxed();
    }
    return static::$isPreview;
  }

  /**
   * Checks if Blazy is in AMP pages.
   */
  public static function isAmp(): bool {
    if (!isset(static::$isAmp)) {
      $request = self::request();
      static::$isAmp = $request && $request->query->get('amp') !== NULL;
    }
    return static::$isAmp;
  }

  /**
   * In CKEditor without JS assets, interactive elements must be sandboxed.
   */
  public static function isSandboxed(): bool {
    if (!isset(static::$isSandboxed)) {
      $check = FALSE;
      if ($router = self::routeMatch()) {
        if ($route = $router->getRouteName()) {
          $edits = ['entity_browser.', 'edit_form', 'add_form', '.preview'];
          foreach ($edits as $key) {
            if (Blazy::has($route, $key)) {
              $check = TRUE;
              break;
            }
          }
        }
      }

      static::$isSandboxed = $check;
    }
    return static::$isSandboxed;
  }

}
