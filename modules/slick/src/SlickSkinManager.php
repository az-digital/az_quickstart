<?php

namespace Drupal\slick;

// @todo use Drupal\blazy\Skin\SkinManagerBase;
use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\slick\Entity\Slick;

/**
 * Provides Slick skin manager.
 *
 * @todo extends SkinManagerBase
 */
class SlickSkinManager extends DefaultPluginManager implements SlickSkinManagerInterface, MapperInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Static cache for the skin definition.
   *
   * @var array
   */
  protected $skinDefinition;

  /**
   * Static cache for the skins by group.
   *
   * @var array
   */
  protected $skinsByGroup;

  /**
   * The library info definition.
   *
   * @var array
   */
  protected $libraryInfoBuild;

  /**
   * The easing library path.
   *
   * @var string
   */
  protected $easingPath;

  /**
   * The slick library path.
   *
   * @var string
   */
  protected $slickPath;

  /**
   * The breaking change: Slick 1.8.1+, or Accessible Slick.
   *
   * @var bool
   */
  protected $isBreaking;

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'slick';

  /**
   * {@inheritdoc}
   */
  protected static $path = 'Plugin/slick';

  /**
   * {@inheritdoc}
   */
  protected static $interface = 'Drupal\slick\SlickSkinPluginInterface';

  /**
   * {@inheritdoc}
   */
  protected static $annotation = 'Drupal\slick\Annotation\SlickSkin';

  /**
   * {@inheritdoc}
   */
  protected static $key = 'slick_skin';

  /**
   * The skin methods.
   *
   * @var array
   */
  protected static $methods = ['skins', 'arrows', 'dots'];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    $root,
    ConfigFactoryInterface $config,
  ) {
    // parent::__construct(
    // 'Plugin/slick',
    // $namespaces,
    // $module_handler,
    // SlickSkinPluginInterface::class,
    // 'Drupal\slick\Annotation\SlickSkin'
    // );.
    parent::__construct(static::$path, $namespaces, $module_handler, static::$interface, static::$annotation);

    $this->root = $root;
    $this->config = $config;

    $this->alterInfo('slick_skin_info');
    $this->setCacheBackend($cache_backend, 'slick_skin_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getCache() {
    return $this->cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function root() {
    return $this->root;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$load, array $attach, $blazies = NULL): void {
    $blazies = $blazies ?: $attach['blazies'] ?? NULL;
    if ($blazies && !$blazies->is('unlazy')) {
      $load['library'][] = 'blazy/loading';
    }

    // Load optional easing library.
    if ($this->getEasingPath()) {
      $load['library'][] = 'slick/slick.easing';
    }

    if (!empty($attach['_vanilla'])) {
      $load['library'][] = 'slick/vanilla';
    }

    // Allows Slick initializer to be disabled by a special flag _unload.
    if (empty($attach['_unload'])) {
      $load['library'][] = 'slick/slick.load';
    }
    else {
      if ($this->config('slick_css')) {
        $load['library'][] = 'slick/slick.css';
      }
    }

    foreach (['colorbox', 'mousewheel'] as $component) {
      if (!empty($attach[$component])) {
        $load['library'][] = 'slick/slick.' . $component;
      }
    }

    if (!empty($attach['skin'])) {
      $this->attachSkin($load, $attach, $blazies);
    }

    // Attach default JS settings to allow responsive displays have a lookup,
    // excluding wasted/trouble options, e.g.: PHP string vs JS object.
    $excludes = explode(' ', 'mobileFirst appendArrows appendDots asNavFor prevArrow nextArrow respondTo pauseIcon playIcon');
    $excludes = array_combine($excludes, $excludes);
    $load['drupalSettings']['slick'] = array_diff_key(Slick::defaultSettings(), $excludes);
  }

  /**
   * Provides skins only if required.
   */
  public function attachSkin(array &$load, array $attach, $blazies = NULL): void {
    if ($this->config('slick_css')) {
      $load['library'][] = 'slick/slick.css';
    }

    if ($this->config('module_css', 'slick.settings')) {
      $load['library'][] = 'slick/slick.theme';
    }

    if (!empty($attach['thumbnail_effect'])) {
      $load['library'][] = 'slick/slick.thumbnail.' . $attach['thumbnail_effect'];
    }

    if (!empty($attach['down_arrow'])) {
      $load['library'][] = 'slick/slick.arrow.down';
    }

    foreach ($this->getConstantSkins() as $group) {
      $skin = $group == 'main' ? $attach['skin'] : ($attach['skin_' . $group] ?? '');
      if (!empty($skin)) {
        $skins = $this->getSkinsByGroup($group);
        $provider = $skins[$skin]['provider'] ?? 'slick';
        $load['library'][] = 'slick/' . $provider . '.' . $group . '.' . $skin;
      }
    }
  }

  /**
   * Returns slick config shortcut.
   */
  public function config($key = '', $settings = 'slick.settings') {
    return $this->config->get($settings)->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstantSkins(): array {
    return [
      'browser',
      'lightbox',
      'overlay',
      'main',
      'thumbnail',
      'arrows',
      'dots',
      'widget',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEasingPath(): ?string {
    if (!$this->easingPath) {
      $path = NULL;
      if ($manager = self::service('slick.manager')) {
        $easings = ['easing', 'jquery.easing'];

        if ($check = $manager->getLibrariesPath($easings)) {
          $path = $check . '/jquery.easing.min.js';
          // Composer via bower-asset puts the library within `js` directory.
          if (!is_file($this->root . '/' . $path)) {
            $path = $check . '/js/jquery.easing.min.js';
          }
        }
      }

      $this->easingPath = $path;
    }
    return $this->easingPath;
  }

  /**
   * {@inheritdoc}
   */
  public function load($plugin_id): SlickSkinPluginInterface {
    return $this->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(): array {
    $skins = [];
    foreach ($this->getDefinitions() as $definition) {
      array_push($skins, $this->createInstance($definition['id']));
    }
    return $skins;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkins(): array {
    if (!$this->skinDefinition) {
      $cid = 'slick_skins_data';
      $cache = $this->cacheBackend->get($cid);

      if ($cache && $data = $cache->data) {
        $this->skinDefinition = $data;
      }
      else {
        $methods = static::$methods;
        $skins = $items = [];
        foreach ($this->loadMultiple() as $skin) {
          foreach ($methods as $method) {
            $items[$method] = $skin->{$method}();
          }
          $skins = NestedArray::mergeDeep($skins, $items);
        }

        $count = isset($items['skins']) ? count($items['skins']) : count($items);
        $tags = Cache::buildTags($cid, ['count:' . $count]);
        $this->cacheBackend->set($cid, $skins, Cache::PERMANENT, $tags);

        $this->skinDefinition = $skins;
      }
    }
    return $this->skinDefinition ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSkinsByGroup($group = '', $option = FALSE): array {
    if (!isset($this->skinsByGroup[$group])) {
      $skins         = $groups = $ungroups = [];
      $nav_skins     = in_array($group, ['arrows', 'dots']);
      $defined_skins = $nav_skins ? $this->getSkins()[$group] : $this->getSkins()['skins'];

      foreach ($defined_skins as $skin => $properties) {
        $name = $properties['name'] ?? 'x';
        $item = $option ? Html::escape($name) : $properties;
        if (!empty($group)) {
          if (isset($properties['group'])) {
            if ($properties['group'] != $group) {
              continue;
            }
            $groups[$skin] = $item;
          }
          elseif (!$nav_skins) {
            $ungroups[$skin] = $item;
          }
        }
        $skins[$skin] = $item;
      }
      $this->skinsByGroup[$group] = $group ? array_merge($ungroups, $groups) : $skins;
    }
    return $this->skinsByGroup[$group];
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfoBuild(): array {
    if (!$this->libraryInfoBuild) {
      $this->libraryInfoBuild = $this->getSkinLibraries();
    }
    return $this->libraryInfoBuild;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlickPath(): ?string {
    if (!$this->slickPath) {
      if ($manager = self::service('slick.manager')) {
        if ($this->config('library') == 'accessible-slick') {
          $libs = ['accessible360--accessible-slick', 'accessible-slick'];
          $this->slickPath = $manager->getLibrariesPath($libs);
        }
        else {
          $libs = ['slick-carousel', 'slick'];
          $this->slickPath = $manager->getLibrariesPath($libs);
        }
      }
    }
    return $this->slickPath;
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfoAlter(&$libraries, $extension): void {
    if ($path = $this->getSlickPath()) {
      if ($this->config('library') == 'accessible-slick') {
        $libraries['accessible-slick']['js'] = ['/' . $path . '/slick/slick.min.js' => ['weight' => -3]];
        $libraries['accessible-slick']['css']['base'] = ['/' . $path . '/slick/slick.min.css' => []];
        $libraries['slick.css']['css']['theme'] = ['/' . $path . '/slick/accessible-slick-theme.min.css' => ['weight' => -2]];

        $libraries_to_alter = [
          'slick.load',
          'slick.colorbox',
          'vanilla',
        ];

        foreach ($libraries_to_alter as $library_name) {
          $key = array_search('slick/slick', $libraries[$library_name]['dependencies']);
          $libraries[$library_name]['dependencies'][$key] = 'slick/accessible-slick';
        }
      }
      else {
        $libraries['slick']['js'] = ['/' . $path . '/slick/slick.min.js' => ['weight' => -3]];
        $libraries['slick']['css']['base'] = ['/' . $path . '/slick/slick.css' => []];
        $libraries['slick.css']['css']['theme'] = ['/' . $path . '/slick/slick-theme.css' => ['weight' => -2]];
      }
    }

    if ($path = $this->getEasingPath()) {
      $libraries['slick.easing']['js'] = ['/' . $path => ['weight' => -4]];
    }

    if ($manager = self::service('slick.manager')) {
      $libs = ['mousewheel', 'jquery-mousewheel', 'jquery.mousewheel'];
      if ($mousewheel = $manager->getLibrariesPath($libs)) {
        $path = $mousewheel . '/jquery.mousewheel.min.js';
        // Has no .min for jquery.mousewheel 3.1.9, jquery-mousewheel 3.1.13.
        if (!is_file($this->root . '/' . $path)) {
          $path = $mousewheel . '/jquery.mousewheel.js';
        }
        $libraries['slick.mousewheel']['js'] = ['/' . $path => ['weight' => -4]];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isBreaking(): bool {
    if (!isset($this->isBreaking)) {
      $this->isBreaking = $this->config('library') == 'accessible-slick';
    }
    return $this->isBreaking;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSkinLibraries(): array {
    $libraries = [];
    if ($this->config('library') == 'accessible-slick') {
      $libraries['slick.css'] = [
        'dependencies' => ['slick/accessible-slick'],
        'css' => [
          'theme' => ['/libraries/accessible-slick/slick/accessible-slick-theme.min.css' => ['weight' => -2]],
        ],
      ];
    }
    else {
      $libraries['slick.css'] = [
        'dependencies' => ['slick/slick'],
        'css' => [
          'theme' => ['/libraries/slick/slick/slick-theme.css' => ['weight' => -2]],
        ],
      ];
    }

    foreach ($this->getConstantSkins() as $group) {
      if ($skins = $this->getSkinsByGroup($group)) {
        foreach ($skins as $key => $skin) {
          $provider = $skin['provider'] ?? 'slick';
          $id = $provider . '.' . $group . '.' . $key;

          $libraries[$id]['dependencies'] = [];
          foreach (['css', 'js', 'dependencies'] as $property) {
            if (isset($skin[$property]) && is_array($skin[$property])) {
              $libraries[$id][$property] = $skin[$property];
            }
          }

          $libraries[$id]['version'] = 'VERSION';

          if ($dependencies = $this->getDependencies()) {
            $libraries[$id]['dependencies'] = array_merge(
              $libraries[$id]['dependencies'],
              $dependencies
            );
          }
        }
      }
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDependencies(): array {
    return ['blazy/dblazy'];
  }

  /**
   * Returns a wrapper to pass tests, or DI where adding params is troublesome.
   */
  private static function service($service) {
    return \Drupal::hasService($service) ? \Drupal::service($service) : NULL;
  }

}
