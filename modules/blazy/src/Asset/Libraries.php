<?php

namespace Drupal\blazy\Asset;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Config\Config;
use Drupal\blazy\Media\Preloader;
use Drupal\blazy\Theme\Lightbox;
use Drupal\blazy\internals\Internals;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides libraries utilities.
 */
class Libraries extends Config implements LibrariesInterface {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $discovery;

  /**
   * The library finder service.
   *
   * @var \Drupal\Core\Asset\LibrariesDirectoryFileFinder
   */
  protected $finder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->setDiscovery($container->get('library.discovery'));
    $instance->setFinder($container->get('library.libraries_directory_file_finder'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function discovery(): LibraryDiscoveryInterface {
    return $this->discovery;
  }

  /**
   * Sets library discovery service.
   */
  public function setDiscovery(LibraryDiscoveryInterface $discovery): self {
    $this->discovery = $discovery;
    return $this;
  }

  /**
   * Sets library finder service.
   */
  public function setFinder(LibrariesDirectoryFileFinder $finder): self {
    $this->finder = $finder;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$attach): array {
    Internals::postSettings($attach);

    $load    = [];
    $blazies = $attach['blazies'];
    $unblazy = $blazies->is('unblazy', FALSE);
    $unload  = $blazies->ui('nojs.lazy', FALSE) || $blazies->is('unlazy');

    if ($blazies->is('lightbox')) {
      Lightbox::attach($load, $attach, $blazies);
    }

    // Always keep Drupal UI config to support dynamic compat features.
    $config = $this->config('blazy');
    $config['loader'] = !$unload;
    $config['unblazy'] = $unblazy;
    $config['visibleClass'] = $blazies->ui('visible_class') ?: FALSE;

    // One is enough due to various formatters negating each others.
    $compat = $blazies->get('libs.compat');

    // Only if `No JavaScript` option is disabled, or has compat.
    // Compat is a loader for Blur, BG, Video which Native doesn't support.
    if ($compat || !$unload) {
      if ($compat) {
        $config['compat'] = $compat;
      }

      // Modern sites may want to forget oldies, respect.
      if (!$unblazy) {
        $load['library'][] = 'blazy/blazy';
      }

      foreach (BlazyDefault::nojs() as $key) {
        if (empty($blazies->ui('nojs.' . $key))) {
          $lib = $key == 'lazy' ? 'load' : $key;
          $load['library'][] = 'blazy/' . $lib;
        }
      }
    }

    if ($libs = array_filter($blazies->get('libs', []))) {
      foreach (array_keys($libs) as $lib) {
        $key = str_replace('__', '.', $lib);
        $load['library'][] = 'blazy/' . $key;
      }
    }

    // @todo remove for the above once all components are set to libs.
    foreach (BlazyDefault::components() as $component) {
      $key = str_replace('.', '__', $component);
      if ($blazies->get('libs.' . $key, FALSE)) {
        $load['library'][] = 'blazy/' . $component;
      }
    }

    // Adds AJAX helper to revalidate Blazy/ IO, if using VIS, or alike.
    // @todo remove when VIS detaches behaviors properly like IO.
    if ($blazies->use('ajax', FALSE)) {
      $load['library'][] = 'blazy/bio.ajax';
      $config['useAjax'] = TRUE;
    }

    // Preload.
    if (!empty($attach['preload'])) {
      Preloader::preload($load, $attach);
    }

    // No blazy libraries are loaded when `No JavaScript`, etc. enabled.
    // And the drupalSettings should not be, either. So quiet here.
    if (isset($load['library'])) {
      $load['drupalSettings']['blazy'] = $config;
      $load['drupalSettings']['blazyIo'] = $this->getIoSettings($attach);
      $load['library'] = array_unique($load['library']);
    }
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function byName($extension, $name): array {
    return $this->discovery->getLibraryByName($extension, $name) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(array $names, $base_path = FALSE): array {
    $libraries = [];
    foreach ($this->find($names, TRUE) as $key => $path) {
      if ($path) {
        $libraries[$key] = $base_path ? Internals::basePath() . $path : $path;
      }
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getLightboxes(): array {
    $lightboxes = ['flybox'];
    if (function_exists('colorbox_theme')) {
      $lightboxes[] = 'colorbox';
    }

    // Most lightboxes are unmantained, only supports mostly used, or robust.
    $paths = [
      'mfp' => 'magnific-popup/dist/jquery.magnific-popup.min.js',
    ];

    foreach ($paths as $key => $path) {
      if (is_file($this->root . '/libraries/' . $path)) {
        $lightboxes[] = $key;
      }
    }
    return $lightboxes;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($name, $base_path = FALSE): ?string {
    $library = '';
    $names = is_array($name) ? $name : [$name];
    foreach ($this->find($names) as $path) {
      if ($path) {
        $library = $base_path ? Internals::basePath() . $path : $path;
        break;
      }
    }
    return $library;
  }

  /**
   * Retrieves libraries.
   */
  private function find(array $libraries, $keyed = FALSE): \Generator {
    foreach ($libraries as $library) {
      $result = $this->finder->find($library);
      if ($keyed) {
        yield $library => $result;
      }
      else {
        yield $result;
      }
    }
  }

}
