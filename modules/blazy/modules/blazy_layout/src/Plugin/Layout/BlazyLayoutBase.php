<?php

namespace Drupal\blazy_layout\Plugin\Layout;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Render\Element;
use Drupal\blazy\Field\BlazyField;
use Drupal\blazy\Utility\Color;
use Drupal\blazy_layout\BlazyLayoutDefault as Defaults;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a BlazyLayoutBase class for Layout plugins.
 */
abstract class BlazyLayoutBase extends LayoutDefault implements BlazyLayoutInterface {

  /**
   * The blazy layout admin service.
   *
   * @var \Drupal\blazy_layout\Form\BlazyLayoutAdminInterface
   */
  protected $admin;

  /**
   * The blazy layout service.
   *
   * @var \Drupal\blazy_layout\BlazyLayoutManagerInterface
   */
  protected $manager;

  /**
   * The blazy entity service.
   *
   * @var \Drupal\blazy\BlazyEntityInterface
   */
  protected $blazyEntity;

  /**
   * The current entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * Provides factory regions.
   *
   * @var array
   */
  protected static $factoryRegions;

  /**
   * Provides instance regions.
   *
   * @var array
   */
  protected static $instanceRegions;

  /**
   * Provides CSS selectors.
   *
   * @var array
   */
  protected static $selectors;

  /**
   * Provides CSS rules.
   *
   * @var array
   */
  protected static $styles;

  /**
   * Provides region amount.
   *
   * @var int
   */
  protected static $count = 0;

  /**
   * Provides instance ID.
   *
   * @var string
   */
  protected static $instanceId;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->admin = $container->get('blazy_layout.admin');
    $instance->manager = $container->get('blazy_layout');
    $instance->blazyEntity = $container->get('blazy.entity');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionConfig($name, $key): string {
    $config = $this->configuration['regions'][$name] ?? [];
    if ($key == 'label') {
      return $config[$key] ?? '';
    }
    return $config['settings'][$key] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRegionConfig($name, array $values): self {
    $config = $this->configuration['regions'][$name] ?? [];

    $this->configuration['regions'][$name] = $this->manager->merge($values, $config);
    return $this;
  }

  /**
   * Provides attachments and cache common for all blazy-related modules.
   */
  protected function attachments(
    array &$element,
    array $settings,
    array $attachments = [],
  ): void {
    $this->manager->setAttachments(
      $element,
      $settings,
      $attachments
    );

    $element['#attached']['library'][] = 'blazy_layout/layout';
    if ($this->inPreview) {
      $element['#attached']['library'][] = 'blazy_layout/admin';
    }
  }

  /**
   * Initialize dynamic layout regions.
   */
  protected function init() {
    $layout = clone $this->pluginDefinition;
    $settings = $this->getConfiguration();
    $factory_regions = $layout->getRegions();
    $dynamic_regions = $factory_regions;

    unset($dynamic_regions['bg']);
    $keys = array_values($dynamic_regions);
    $count = (int) $settings['count'];
    $layout_id = $this->id($settings);
    $settings['id'] = $layout_id;

    static::$factoryRegions = $factory_regions;
    static::$count = $settings['count'] = $count;
    static::$instanceId = Defaults::layoutId($layout_id);

    // Removed regions beyond the designated amount.
    foreach ($keys as $delta => $value) {
      $name = Defaults::regionId($delta);
      if ($delta < $count) {
        // Add initial settings if it is beyond default 9.
        if (!isset($settings['regions'][$name]) && isset($factory_regions[$name])) {
          $settings['regions'][$name] = $factory_regions[$name];
          $settings['regions'][$name]['settings'] = [];
        }
        continue;
      }

      unset($factory_regions[$name]);
      unset($settings['regions'][$name]);
    }

    static::$instanceRegions = $factory_regions;

    $this->setConfiguration($settings);
    $layout->setRegions($factory_regions);
    $this->pluginDefinition = $layout;

    return $layout;
  }

  /**
   * Returns settings.
   */
  protected function settings(): array {
    $settings = $this->getConfiguration();
    return $this->manager->layoutSettings($settings, static::$count);
  }

  /**
   * Modifies regions.
   */
  protected function regions(array &$output, array &$settings): void {
    // Add dummy regions to keep layout intact.
    foreach (range(1, static::$count) as $delta => $value) {
      $name = Defaults::regionId($delta);

      if ($subsets = $settings['regions'][$name]['settings'] ?? []) {
        if ($classes = $this->manager->getClasses($subsets)) {
          $settings['regions'][$name]['settings']['classes'] = $classes;
        }

        // Add empty flag for proper styling outside LB.
        if (empty($output[$name]) && !$this->inPreview) {
          $settings['regions'][$name]['settings']['empty'] = TRUE;
        }
      }

      // Always show empty regions to avoid collapsed regions at LB.
      if (!isset($output[$name]) && $this->inPreview) {
        $output[$name]['dummy']['#markup'] = ' ';
      }
    }

    if (empty($output['bg'])) {
      $settings['regions']['bg']['settings']['empty'] = TRUE;
      $output['bg']['dummy']['#markup'] = ' ';
    }

    $this->blocks($output, $settings);
  }

  /**
   * Modifies blocks.
   */
  protected function blocks(array &$output, array &$settings): void {
    $id      = static::$instanceId;
    $colors  = $settings['styles']['colors'] ?? [];
    $layouts = $settings['styles']['layouts'] ?? [];

    if ($mid = $settings['styles']['media']['id'] ?? NULL) {
      $this->media($output, $settings, $mid, 'bg');
    }

    // Move Blazy background to the beginning.
    foreach (array_keys(static::$instanceRegions) as $name) {
      $subsets = $settings;
      $styles  = $subsets['regions'][$name]['settings']['styles'] ?? [];
      $empty   = empty($output[$name]) || isset($output[$name]['dummy']);
      $is_bg   = FALSE;

      if ($name == 'bg') {
        $colorsets = $colors;
        $layoutsets = $layouts;
      }
      else {
        $colorsets = $styles['colors'] ?? [];
        $layoutsets = $styles['layouts'] ?? [];
      }

      $use_bg_color = !empty($colorsets['background_color']) || $this->inPreview;
      $use_overlay = !empty($colorsets['overlay_color']) || $this->inPreview;

      if ($use_bg_color && !isset($output[$name])) {
        $output[$name][$name . '-bg']['#markup'] = ' ';
      }

      // Place before a bailout so to be visible at frontend.
      if ($mid = $styles['media']['id'] ?? NULL) {
        $is_bg = TRUE;
        $this->media($output, $subsets, $mid, $name);
      }

      // Bail out if an empty region.
      if (!isset($output[$name])) {
        continue;
      }

      // Loop through each region contents, including backgrounds.
      foreach (Element::children($output[$name]) as $uuid) {
        $block = $output[$name][$uuid];
        $formatter = $block['content'][0]['#formatter'] ?? 'x';
        $use_bg = $block_bg = FALSE;

        if (!isset($output[$name]['#attributes'])) {
          $output[$name]['#attributes'] = [];
        }

        // Blazy formatter in a block.
        if (strpos($formatter, 'blazy') !== FALSE) {
          if ($fieldsets = $block['content'][0]['#blazy'] ?? []) {
            // Pass the layout settings, not formatter's.
            $blazies = $subsets['blazies']->reset($subsets);
            $subblazies = $fieldsets['blazies'];
            $output[$name][$uuid]['#blazy'] = $subsets;

            $blazies->set('is.preview', $this->inPreview)
              ->set('lb.region', $name);

            $keys = ['entity', 'field', 'image', 'lightbox', 'media'];
            foreach ($keys as $key) {
              if ($values = $subblazies->get($key)) {
                $blazies->set($key, $values);
              }
            }

            if (!empty($fieldsets['background'])) {
              $block_bg = $use_bg = TRUE;

              $blazies->set('use.bg', TRUE);

              // It is a theme_field() here.
              if (isset($output[$name][$uuid]['content'][0][0]['#build'])) {
                $blazy = &$output[$name][$uuid]['content'][0][0]['#build'];

                if ($use_overlay) {
                  $blazy['overlay']['blazy_layout'] = $this->overlay();
                }
              }

              $output[$name][$uuid]['#weight'] = -101;
            }
          }
        }

        $options = ['empty' => $empty, 'block_bg' => $block_bg];
        $this->texts($name, $colorsets, 'text', $options);
        $this->texts($name, $colorsets, 'heading', $options);
        $this->links($name, $colorsets, $options);
        $bgs = $this->backgrounds($name, $colorsets, 'background', $options);
        $this->backgrounds($name, $colorsets, 'overlay', $options);
        $this->layouts($name, $layoutsets, 'padding', $options);

        $use_bg = $use_bg || !empty($bgs['bg']);

        if ($use_bg || $is_bg) {
          if ($name == 'bg' && empty($settings['background'])) {
            $settings['background'] = TRUE;
          }

          $settings['regions'][$name]['settings']['background'] = TRUE;
          $this->setRegionConfig($name, [
            'settings' => [
              'background' => TRUE,
            ],
          ]);
        }
      }

      if ($this->inPreview) {
        $output[$name]['#attributes']['data-region'] = $name;
        if ($selectors = static::$selectors[$id][$name] ?? []) {
          $output[$name]['#attributes']['data-b-selector'] = Json::encode($selectors);
        }
      }
    }
  }

  /**
   * Modifies attributes.
   */
  protected function attributes(array &$output, array $settings): void {
    $id    = $selector = static::$instanceId;
    $style = $settings['style'] ?? '';
    $css   = '';

    foreach (['grid_auto_rows', 'align_items'] as $option) {
      if ($value = $settings[$option] ?? NULL) {
        if ($option == 'grid_auto_rows' && $style != 'nativegrid') {
          continue;
        }
        $key = str_replace('_', '-', $option);
        $value = trim($value);
        $css .= $key . ':' . $value . ';';
      }
    }

    if ($layouts = $settings['styles']['layouts'] ?? []) {
      if ($value = $layouts['padding'] ?? NULL) {
        $css .= 'padding:' . $value . ';';
      }
      if ($value = $layouts['max_width'] ?? NULL) {
        if (strpos($value, ':') === FALSE) {
          $css .= 'max-width:' . $value . ';';
        }
        else {
          $vals = array_map('trim', explode(' ', $value));
          $queries = '';
          foreach ($vals as $val) {
            $keys = array_map('trim', explode(':', $val));
            $queries .= '@media screen and (min-width: ' . $keys[0] . ') {ROOT {max-width: ' . $keys[1] . '}}';
          }
          static::$styles[$id]['max_width'] = $queries;
        }
      }
    }

    if ($css) {
      static::$styles[$id][$selector] = $css;
    }

    $this->manager->parseClasses($output, $settings);

    if (!isset($output['#wrapper_attributes'])) {
      $output['#wrapper_attributes'] = [];
    }

    if ($this->inPreview) {
      $output['#attributes']['id'] = $id;
    }
  }

  /**
   * Provides CSS rules.
   */
  protected function styles(array &$output, array $settings): void {
    $id  = static::$instanceId;
    $css = '';

    // Put this in the head to avoid ugly inline element styles.
    if ($rules = static::$styles[$id] ?? []) {
      $css = $this->manager->toRules($rules, $id);
      $css = preg_replace('/\s+/', ' ', $css);

      $output['#attached']['html_head'][] = [
        [
          '#tag'        => 'style',
          '#value'      => $css,
          '#weight'     => 1,
          '#attributes' => ['id' => $id . '-style'],
        ],
        $id . '-style',
      ];
    }

    if ($this->inPreview) {
      $json = Json::encode([
        'id' => $id,
        'style' => $css,
      ]);

      $output['#attributes']['data-b-layout'] = base64_encode($json);
    }
  }

  /**
   * Provides background styles.
   */
  protected function backgrounds(
    $region,
    array $colors,
    $key = 'background',
    array $options = [],
  ): array {
    $id   = static::$instanceId;
    $rule = 'color';
    $bg   = $alpha = FALSE;
    $hex  = $colors["{$key}_color"] ?? NULL;
    $css  = '';

    if ($value = $colors["{$key}_opacity"] ?? NULL) {
      if ($value != '0' && $value != '1') {
        $rule = 'opacity';
        $alpha = $value;
      }
    }

    $options['rule'] = $rule;
    $selector = $this->manager->selector($key, $region, $options);

    if ($hex) {
      $bg = TRUE;
      $color = Color::hexToRgba($hex, $alpha);
      $css = "background-color: $color;";
    }

    if ($css) {
      static::$styles[$id][$selector] = $css;
    }

    static::$selectors[$id][$region][$key] = $selector;
    return ['css' => $css, 'selector' => $selector, 'bg' => $bg];
  }

  /**
   * Provides text styles.
   */
  protected function texts(
    $region,
    array $colors,
    $key = 'heading',
    array $options = [],
  ): array {
    $id    = static::$instanceId;
    $rule  = 'color';
    $alpha = FALSE;
    $hex   = $colors["{$key}_color"] ?? NULL;
    $css   = '';

    if ($value = $colors["{$key}_opacity"] ?? NULL) {
      if ($value != '0' && $value != '1') {
        $rule = 'opacity';
        $alpha = $value;
      }
    }

    $options['rule'] = $rule;
    $selector = $this->manager->selector($key, $region, $options);

    if ($hex) {
      $color = Color::hexToRgba($hex, $alpha);
      $css = "color: $color;";
    }

    if ($css) {
      static::$styles[$id][$selector] = $css;
    }

    static::$selectors[$id][$region][$key] = $selector;
    return ['css' => $css, 'selector' => $selector];
  }

  /**
   * Provides link styles.
   */
  protected function links(
    $region,
    array $colors,
    array $options = [],
  ): array {
    $id  = static::$instanceId;
    $css = '';

    $selector = $this->manager->selector('link', $region, $options);
    static::$selectors[$id][$region]['link'] = $selector;

    if ($style = $colors["link_color"] ?? NULL) {
      $css = "color: $style;";

      static::$styles[$id][$selector] = $css;
    }

    $selector = $this->manager->selector('link_hover', $region, $options);
    static::$selectors[$id][$region]['link_hover'] = $selector;

    if ($style = $colors['link_hover_color'] ?? NULL) {
      $css = "color: $style;";

      static::$styles[$id][$selector] = $css;
    }

    return ['css' => $css, 'selector' => $selector];
  }

  /**
   * Provides layout styles.
   */
  protected function layouts(
    $region,
    array $settings,
    $key = 'padding',
    array $options = [],
  ): array {
    $id  = static::$instanceId;
    $css = '';

    $selector = $this->manager->selector('padding', $region, $options);
    if ($style = $settings[$key] ?? NULL) {
      $css = "$key: $style;";

      static::$styles[$id][$selector] = $css;
    }

    static::$selectors[$id][$region][$key] = $selector;
    return ['css' => $css, 'selector' => $selector];
  }

  /**
   * Returns the formatted media as Blazy CSS background.
   */
  protected function media(array &$output, array &$settings, $mid, $name): void {
    $data = [];
    $blazies = $settings['blazies'];
    $config = [
      'background' => TRUE,
      '_detached' => FALSE,
      'blazies' => $blazies,
    ] + Defaults::entitySettings();

    if ($mid) {
      if ($this->inPreview) {
        $blazies->set('use.ajax', TRUE);
      }

      // Pass results to \Drupal\blazy\BlazyEntity.
      if ($media = $this->manager->load($mid, 'media')) {
        if ($name == 'bg') {
          $styles = $settings['styles'] ?? [];
        }
        else {
          $styles = $settings['regions'][$name]['settings']['styles'] ?? [];
        }

        $mediasets = $styles['media'] ?? [];
        $use_overlay = !empty($styles['colors']['overlay_color']);
        $config = array_merge($config, $mediasets);
        $blazies = $config['blazies']->reset($config);

        // @todo remove after a hook update.
        if (!empty($mediasets['use_player'])) {
          $config['media_switch'] = 'media';
          unset($config['use_player']);
        }

        // Add a wrapper for lightbox to work.
        $use_container = FALSE;
        if ($switch = $config['media_switch'] ?? NULL) {
          $use_container = !in_array($switch, ['media', 'content', 'link']);
          $blazies->set('use.container', $use_container);
        }

        // Link, if so configured.
        $entity = $this->entity();
        if ($_link = $mediasets['link'] ?? NULL) {
          if ($links = $this->viewLinks($_link, $media)) {
            $blazies->set('field.values.link', $links);
          }
        }

        $data['#entity'] = $media;
        $data['#parent'] = $entity;
        $data['#delta'] = 0;
        $data['#settings'] = $config;

        if ($result = $this->blazyEntity->build($data)) {
          $uuid = $name . '-media';

          if ($use_overlay || $this->inPreview) {
            if ($use_container) {
              $result['content']['#build']['overlay']['blazy_layout'] = $this->overlay();
            }
            else {
              $result['#build']['overlay']['blazy_layout'] = $this->overlay();
            }
          }

          $output[$name][$uuid] = $result;
          $output[$name][$uuid]['#weight'] = -102;

          $settings['background'] = TRUE;
          $settings['regions'][$name]['settings']['empty'] = FALSE;
          $this->setRegionConfig($name, [
            'settings' => [
              'background' => TRUE,
            ],
          ]);
        }
      }
    }
  }

  /**
   * Returns current entity.
   */
  private function entity() {
    if (!isset($this->entity)) {
      $entity = NULL;
      if ($route = $this->manager->service('current_route_match')) {
        if ($route->getRouteObject()) {
          foreach ($route->getParameters() as $parameter) {
            if ($parameter instanceof EntityInterface) {
              $entity = $parameter;
              break;
            }
          }
        }
      }
      $this->entity = $entity;
    }
    return $this->entity;
  }

  /**
   * Returns layout id.
   */
  private function id(array $settings): string {
    // For some reason, short coalesce always fails.
    if ($id = $settings['id'] ?? NULL) {
      $layout_id = $id;
    }
    else {
      $id = Json::encode($settings);
      $layout_id = substr(md5($id), 0, 11);
    }

    return strtolower($layout_id);
  }

  /**
   * Returns links.
   */
  private function viewLinks($name, $entity): array {
    $links = [];
    if ($entity && isset($entity->{$name})) {
      $links = BlazyField::view($entity, $name, []);
      $formatter = $links['#formatter'] ?? 'x';

      // Only simplify markups for known formatters by link.module.
      if ($links && in_array($formatter, ['link'])) {
        $links = [];
        foreach ($entity->{$name} as $link) {
          $links[] = $link->view('default');
        }
      }
    }
    return $links;
  }

  /**
   * Returns overlay markup.
   */
  private function overlay(): array {
    return [
      '#theme' => 'container',
      '#attributes' => [
        'class' => ['media__overlay'],
      ],
    ];
  }

}
