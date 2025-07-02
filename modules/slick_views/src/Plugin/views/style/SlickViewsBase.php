<?php

namespace Drupal\slick_views\Plugin\views\style;

use Drupal\blazy\Views\BlazyStylePluginBase;
use Drupal\slick\SlickDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class common for Slick style plugins.
 */
abstract class SlickViewsBase extends BlazyStylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'slick';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'caption';

  /**
   * {@inheritdoc}
   */
  protected static $navId = 'thumb';

  /**
   * The slick service manager.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->manager = $container->get('slick.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  public function admin() {
    return \Drupal::service('slick.admin');
  }

  /**
   * {@inheritdoc}
   *
   * @todo only remove $settings['nav'] after SlickGroup is updated!
   */
  public function buildElements(array $settings, $rows) {
    $build      = [];
    $view       = $this->view;
    $blazies    = $settings['blazies'];
    $nav        = $blazies->is('nav', !empty($settings['nav']));
    $tn_caption = $settings['thumbnail_caption'] ?? NULL;

    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $slide = [];
      $sets  = $settings;

      // Ensures unique settings per item.
      $this->reset($sets);

      // Provides a potential unique thumbnail different from the main image.
      // A company logo for thumbnail, and a company profile for the stage.
      // Put it up here to modify settings given a thumbnail.
      $tn = $this->getThumbnail($sets, $row, $index, $tn_caption);
      $slide['#settings'] = $sets;

      // Use Vanilla slick if so configured, ignoring Slick markups.
      if (!empty($sets['vanilla'])) {
        $slide[static::$itemId] = $view->rowPlugin->render($row);
      }
      else {
        // Otherwise, extra works. With a working Views cache, no big deal.
        $this->buildElement($slide, $row, $index);

        // Build thumbnail navs if so configured.
        if ($nav) {
          $build[static::$navId]['items'][$index] = $tn;
        }
      }

      if (!empty($sets['class'])) {
        $classes = $this->getFieldString($row, $sets['class'], $index);
        $slide['#settings']['class'] = empty($classes[$index])
          ? [] : $classes[$index];
      }

      if (empty($slide[static::$itemId]) && !empty($sets['image'])) {
        $slide[static::$itemId] = $this->getFieldRendered($index, $sets['image'], FALSE, $row);
      }

      $build['items'][$index] = $slide;
    }

    unset($view->row_index);
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Few settings are required before being passed into manager.
   */
  protected function buildSettings() {
    $settings = parent::buildSettings();
    $blazies  = $this->manager->verifySafely($settings);

    $nav = !$settings['vanilla']
      && $settings['optionset_thumbnail']
      && isset($this->view->result[1]);

    $settings['nav'] = $nav;
    $blazies->set('is.nav', $nav);

    return $settings;
  }

  /**
   * Build the Slick settings form.
   */
  protected function buildSettingsForm(&$form, &$definition) {
    $this->admin()->buildSettingsForm($form, $definition);

    $title = '<h3 class="form__header form__title">';
    $title .= $this->t('Check Vanilla if using content/custom markups, not fields. <small>See it under <strong>Format > Show</strong> section. Otherwise slick markups apply which require some fields added below.</small>');
    $title .= '</h3>';

    $form['opening']['#markup'] .= $title;

    if (isset($form['overlay'])) {
      $form['overlay']['#description'] .= ' ' . $this->t('Be sure to CHECK "<strong>Style settings > Use field template</strong>" _only if using Slick formatter for nested sliders, otherwise keep it UNCHECKED!');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (SlickDefault::extendedSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Returns the defined scopes for the current form.
   */
  protected function getDefinedFormScopes(array $extra_fields = []) {
    // Pass the common field options relevant to this style.
    $fields = [
      'captions',
      'classes',
      'images',
      'layouts',
      'links',
      'overlays',
      'thumbnails',
      'thumb_captions',
      'titles',
    ];

    // Fetches the returned field definitions to be used to define form scopes.
    $fields = array_merge($fields, $extra_fields);
    $definition = $this->getDefinedFieldOptions($fields);

    $options = [
      'fieldable_form',
      'grid_form',
      'nav',
      'style',
      'thumb_positions',
      'vanilla',
    ];

    foreach ($options as $key) {
      $definition[$key] = TRUE;
    }

    $definition['_views'] = TRUE;

    return $definition;
  }

}
