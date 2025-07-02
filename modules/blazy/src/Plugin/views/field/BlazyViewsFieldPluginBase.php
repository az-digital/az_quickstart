<?php

namespace Drupal\blazy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyEntityInterface;
use Drupal\blazy\BlazyManager;
use Drupal\blazy\Theme\BlazyViews;
use Drupal\blazy\Traits\PluginScopesTrait;
use Drupal\blazy\Utility\Arrays;
use Drupal\blazy\internals\Internals;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base views field plugin to render a preview of supported fields.
 */
abstract class BlazyViewsFieldPluginBase extends FieldPluginBase {

  use PluginScopesTrait;

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'content';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'captions';

  /**
   * The blazy service manager.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The blazy entity service.
   *
   * @var \Drupal\blazy\BlazyEntityInterface
   */
  protected $blazyEntity;

  /**
   * The blazy media service.
   *
   * @var \Drupal\blazy\Media\BlazyMediaInterface
   */
  protected $blazyMedia;

  /**
   * The blazy merged settings.
   *
   * @var array
   */
  public $mergedSettings = [];

  /**
   * Constructs a BlazyViewsFieldPluginBase object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    BlazyManager $blazy_manager,
    BlazyEntityInterface $blazy_entity,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyManager = $blazy_manager;
    $this->blazyEntity = $blazy_entity;
    $this->blazyMedia = $blazy_entity->blazyMedia();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('blazy.manager'),
      $container->get('blazy.entity')
    );
  }

  /**
   * Returns the blazy admin.
   */
  public function blazyAdmin() {
    return Internals::service('blazy.admin');
  }

  /**
   * Returns the blazy manager.
   *
   * @todo remove, hardly called outside the formatters.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    foreach ($this->getDefaultValues() as $key => $default) {
      $options[$key] = ['default' => $default];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $definitions = $this->getScopedFormElements();

    $form += $this->blazyAdmin()->baseForm($definitions);
    foreach ($this->getDefaultValues() as $key => $default) {
      if (isset($form[$key])) {
        $form[$key]['#default_value'] = $this->options[$key] ?? $default;
        $form[$key]['#weight'] = 0;

        if (in_array($key, ['box_style', 'box_media_style', 'media_switch'])) {
          $form[$key]['#empty_option'] = $this->t('- None -');
        }
      }
    }

    if (isset($form['view_mode'])) {
      $form['view_mode']['#description'] = $this->t('Will fallback to this view mode, else entity label.');
    }
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * Defines the default values.
   */
  protected function getDefaultValues() {
    return [
      'box_style'          => '',
      'box_media_style'    => '',
      'box_caption'        => '',
      'box_caption_custom' => '',
      'image_style'        => '',
      'media_switch'       => 'media',
      'ratio'              => 'fluid',
      'thumbnail_style'    => '',
      'view_mode'          => 'default',
    ];
  }

  /**
   * Merges the settings.
   */
  public function mergedViewsSettings(array $data = [], $entity = NULL) {
    $settings = BlazyDefault::entitySettings();
    $config   = [];
    $view     = $this->view;
    $style    = $view->style_plugin;
    $style_id = is_null($style) ? '' : $style->getPluginId();

    // Only fetch what we already asked for.
    foreach ($this->getDefaultValues() as $key => $default) {
      $settings[$key] = $config[$key] = $this->options[$key] ?? $default;
    }

    $info = [
      'embedded'  => FALSE,
      'is_field'  => TRUE,
      'is_view'   => TRUE,
      'plugin_id' => $style_id,
      'extras' => [
        'field'     => [
          'config'    => Arrays::filter($config),
          'plugin_id' => $this->getPluginId(),
        ],
      ],
    ];

    $settings = BlazyViews::settings($view, $settings, $info);
    $blazies  = $settings['blazies'];

    $blazies->set('item.id', static::$itemId)
      ->set('item.prefix', static::$itemPrefix)
      ->set('item.caption', static::$captionId)
      ->set('namespace', static::$namespace);

    // Be sure after item setup, and only not deferred.
    if (!isset($data['defer'])) {
      $this->blazyManager->preSettings($settings);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $type = $this->view->getBaseEntityType();
    return [
      'base_form' => TRUE,
      'target_type' => $type ? $type->id() : '',
      'thumbnail_style' => TRUE,
      'no_loading' => TRUE,
      'no_preload' => TRUE,
    ];
  }

  /**
   * Defines the scope for the form elements.
   *
   * Since 2.10 sub-modules can forget this, and use self::getPluginScopes().
   */
  public function getScopedFormElements() {
    $scopes   = $this->getPluginScopes();
    $scopes  += Blazy::init();
    $blazies  = $scopes['blazies'];
    $settings = $this->options;

    // Mimick field formatters for consistency.
    foreach (['target_type', 'view_mode'] as $key) {
      if (isset($scopes[$key])) {
        $blazies->set('field.' . $key, $scopes[$key]);
      }
    }
    foreach (['entity_type', 'plugin_id'] as $key) {
      if (isset($settings[$key])) {
        $blazies->set('field.' . $key, $settings[$key]);
      }
    }

    // @todo remove `$scopes +` at Blazy 3.x.
    $definitions = $scopes;
    $definitions['scopes'] = $this->toPluginScopes($scopes);
    $definitions['settings'] = $settings;
    $definitions['blazies'] = $blazies;

    return $definitions;
  }

}
