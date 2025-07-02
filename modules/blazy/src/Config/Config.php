<?php

namespace Drupal\blazy\Config;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\blazy\internals\Internals;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides config utilities.
 */
class Config implements ConfigInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The cached data/ options.
   *
   * @var array
   */
  protected $cachedData;

  /**
   * Constructs a Libraries manager object.
   */
  public function __construct(
    $root,
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
  ) {
    $this->root          = $root;
    $this->cache         = $cache;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->routeMatch    = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('app.root'),
      $container->get('cache.default'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cache(): CacheBackendInterface {
    return $this->cache;
  }

  /**
   * {@inheritdoc}
   */
  public function configFactory(): ConfigFactoryInterface {
    return $this->configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleHandler(): ModuleHandlerInterface {
    return $this->moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function root(): string {
    return $this->root;
  }

  /**
   * {@inheritdoc}
   */
  public function routeMatch(): RouteMatchInterface {
    return $this->routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public function config($key = NULL, $group = 'blazy.settings') {
    $config  = $this->configFactory->get($group);
    $configs = $config->get();
    unset($configs['_core']);
    return empty($key) ? $configs : $config->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function configMultiple($group = 'blazy.settings'): array {
    return $this->config(NULL, $group) ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCachedData(
    $cid,
    array $data = [],
    $as_options = TRUE,
    array $info = [],
  ): array {
    $reset = $info['reset'] ?? FALSE;
    if (!isset($this->cachedData[$cid]) || $reset) {
      $cache = $this->cache->get($cid);

      if (!$reset && $cache && $data = $cache->data) {
        $this->cachedData[$cid] = $data;
      }
      else {
        $alter   = $info['alter'] ?? $cid;
        $context = $info['context'] ?? [];
        $key     = $info['key'] ?? NULL;

        // Allows empty array to trigger hook_alter.
        if (is_array($data)) {
          $this->moduleHandler->alter($alter, $data, $context);
        }

        // Only if we have data, cache them.
        if ($data && is_array($data)) {
          if (isset($data[1])) {
            $data = array_unique($data, SORT_REGULAR);
          }

          if ($as_options) {
            $data = $this->toOptions($data);
          }
          else {
            ksort($data);
          }

          $count = $key && isset($data[$key]) ? count($data[$key]) : count($data);
          $tags = Cache::buildTags($cid, ['count:' . $count]);
          $this->cache->set($cid, $data, Cache::PERMANENT, $tags);
        }

        $this->cachedData[$cid] = $data;
      }
    }
    return $this->cachedData[$cid] ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMetadata(array $build): array {
    $settings  = Internals::toHashtag($build) ?: $build;
    $blazies   = Internals::verify($settings);
    $namespace = $blazies->get('namespace', 'blazy');
    $count     = $blazies->total() ?: $blazies->get('count', count($settings));
    $max_age   = $this->config('cache.page.max_age', 'system.performance');
    $max_age   = empty($settings['cache']) ? $max_age : $settings['cache'];
    $id        = Internals::getHtmlId($namespace . $count);
    $id        = $blazies->get('css.id', $id);
    $id        = substr(md5($id), 0, 11);

    // Put them into cxahe.
    $cache             = [];
    $suffixes[]        = $count;
    $cache['tags']     = Cache::buildTags($namespace . ':' . $id, $suffixes, '.');
    $cache['contexts'] = ['languages', 'url.site'];
    $cache['max-age']  = $max_age;
    $cache['keys']     = $blazies->get('cache.metadata.keys', [$id]);

    if ($tags = $blazies->get('cache.metadata.tags', [])) {
      $cache['tags'] = Cache::mergeTags($cache['tags'], $tags);
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getIoSettings(array $attach = []): object {
    $io = [];
    $thold = $this->config('io.threshold');
    $thold = str_replace(['[', ']'], '', trim($thold ?: '0'));

    // @todo re-check, looks like the default 0 is broken sometimes.
    if ($thold == '0') {
      $thold = '0, 0.25, 0.5, 0.75, 1';
    }

    $thold = strpos($thold, ',') !== FALSE
      ? array_map('trim', explode(',', $thold)) : [$thold];
    $formatted = [];
    foreach ($thold as $value) {
      $formatted[] = strpos($value, '.') !== FALSE ? (float) $value : (int) $value;
    }

    // Respects hook_blazy_attach_alter() for more fine-grained control.
    foreach (['disconnect', 'rootMargin', 'threshold'] as $key) {
      $default = $key == 'rootMargin' ? '0px' : FALSE;
      $value = $key == 'threshold' ? $formatted : $this->config('io.' . $key);
      $io[$key] = $attach['io.' . $key] ?? ($value ?: $default);
    }

    return (object) $io;
  }

  /**
   * {@inheritdoc}
   */
  public function import(array $options): void {
    $options = $options + ['folder' => 'install'];

    [
      'module' => $module,
      'basename' => $basename,
      'folder' => $folder,
    ] = $options;

    $path = Internals::getPath('module', $module);
    $config_path = sprintf('%s/config/%s/%s.yml', $path, $folder, $basename);

    if ($data = Yaml::parseFile($config_path)) {
      $this->configFactory->getEditable($basename)
        ->setData($data)
        ->save(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toOptions(array $options): array {
    if ($options) {
      $options = array_map('\Drupal\Component\Utility\Html::escape', $options);
      uasort($options, 'strnatcasecmp');
    }
    return $options;
  }

}
