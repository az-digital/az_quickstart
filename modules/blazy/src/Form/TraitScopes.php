<?php

namespace Drupal\blazy\Form;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazySettings;
use Drupal\blazy\Traits\PluginScopesTrait;

/**
 * A scopes Trait to declutter, and focus more on form elements.
 */
trait TraitScopes {

  use PluginScopesTrait;

  /**
   * The form scopes.
   *
   * @var array
   */
  protected $definition = [];

  /**
   * The form scopes.
   *
   * @var \Drupal\blazy\BlazySettings
   */
  protected $scopes;

  /**
   * The main module namespace, kind of group name including their sub-modules.
   *
   * Unlike classes, slick_views, etc. will be under slick namespace with this.
   *
   * @var string
   * @see https://www.php.net/manual/en/reserved.keywords.php
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  public function toScopes(array &$definition): BlazySettings {
    // Looks like unit test failed with manager methods given a Trait.
    $definition += Blazy::init();
    $blazies = $definition['blazies'];
    $namespace = $blazies->get('namespace') ?: ($definition['namespace'] ?? '');

    static::$namespace = $namespace;

    $scopes = $definition['scopes'] ?? $this->toPluginScopes();
    if (!$scopes->get('initializer')) {
      $definition['scopes'] = $scopes = $this->getScopes($definition);
      $scopes->set('initializer', get_called_class());

      // Might be called directly without calling self::buildSettingsForm(),
      // such as \Drupal\blazy\Plugin\views\field\BlazyViewsFieldPluginBase.
      // @todo remove this failsafe after sub-module migrations done.
      $this->checkScopes($scopes, $definition);
    }

    $this->scopes = $scopes;
    $this->definition = $definition;
    return $this->scopes;
  }

  /**
   * Check scopes, a failsafe till sub-modules migrated.
   *
   * Temporary re-definitions during migration after BlazyFormatterTrait
   * ::getScopedFormElements() for sensible checks.
   *
   * @todo remove most after sub-module migrations.
   */
  protected function checkScopes(&$scopes, array &$definition, $refresh = FALSE): void {
    if ($scopes->was('scoped') && !$refresh) {
      return;
    }

    $namespace = static::$namespace;
    $definition['plugin_id'] = $definition['plugin_id'] ?? 'x';
    $settings = $definition['settings'] ?? [];
    $blazies = $definition['blazies'];
    $lightboxes = $this->blazyManager->getLightboxes();
    $is_responsive = function_exists('responsive_image_get_image_dimensions');
    $plugin_id = $blazies->get('field.plugin_id') ?: $definition['plugin_id'];
    $target_type = $blazies->get('field.target_type') ?: ($definition['target_type'] ?? '');
    $entity_type = $blazies->get('field.entity_type') ?: ($definition['entity_type'] ?? '');
    $view_mode = $blazies->get('field.view_mode') ?: ($definition['view_mode'] ?? '');
    $switch = !$scopes->is('no_lightboxes') && isset($settings['media_switch']);
    $wrapper_format = NULL;
    $lb = $this->isAdminLb();

    if ($current = $this->getCurrentRequest()) {
      $wrapper_format = $current->query->get('_wrapper_format');
      if ($uri = $current->getRequestUri()) {
        $lb = strpos($uri, '/layout_builder') !== FALSE;
      }
    }

    $bools = [
      'background',
      'caches',
      'grid_required',
      'grid_simple',
      'multimedia',
      'multiple',
      'nav',
      'no_box_captions',
      'no_box_caption_custom',
      'no_grid_header',
      'no_image_style',
      'no_layouts',
      'no_lightboxes',
      'no_loading',
      'no_preload',
      'no_thumb_effects',
      'responsive_image',
      'style',
      'thumbnail_style',
      'vanilla',
      '_views',
    ];

    foreach ($bools as $bool) {
      $value = $scopes->is($bool) || !empty($definition[$bool]);
      $scopes->set('is.' . $bool, $value);
    }

    // Redefine for easy calls later due to sub-modules not migrated yet.
    // @todo remove after sub-modules migrations, and simplify all these at 3.x.
    $responsive = $is_responsive && $scopes->is('responsive_image');
    $sliders = in_array($namespace, ['slick', 'splide']);
    $by_delta = $lb && $scopes->is('multiple') &&  $namespace == 'blazy';

    $scopes->set('data.lightboxes', $lightboxes)
      ->set('is.fieldable', $target_type && $entity_type)
      ->set('is._lb', $lb)
      ->set('is.by_delta', $by_delta)
      ->set('is.lightbox', count($lightboxes) > 0)
      ->set('is.responsive_image', $responsive)
      ->set('is.slider', $scopes->is('slider') ?: $sliders)
      ->set('is.switch', $switch)
      ->set('namespace', $namespace)
      // @todo remove dups for $blazies object.
      ->set('entity.type', $entity_type)
      ->set('plugin_id', $plugin_id)
      ->set('target_type', $target_type)
      ->set('view_mode', $view_mode)
      ->set('_wrapper_format', $wrapper_format);

    $data = [
      'deprecations',
      'captions',
      'classes',
      'fullwidth',
      'images',
      'layouts',
      'libraries',
      'links',
      'optionsets',
      'overlays',
      'skins',
      'thumbnails',
      'thumbnail_effect',
      'thumb_captions',
      'titles',
    ];

    $captions = [
      'alt' => $this->t('Alt'),
      'title' => $this->t('Title'),
    ];

    foreach (['captions', 'thumb_captions'] as $key) {
      $check = $definition[$key] ?? NULL;
      if ($check == 'default') {
        $scopes->set('data.' . $key, $captions);
      }
    }

    foreach ($data as $key) {
      $value = $scopes->data($key) ?: ($definition[$key] ?? NULL);
      // Respects empty arrays so the option is visible to raise awareness.
      if (is_array($value)) {
        $scopes->set('data.' . $key, $value);
      }
    }

    // Merge deprecated settings.
    $scopes->set('data.deprecations', BlazyDefault::deprecatedSettings(), TRUE);

    $forms = [
      'grid',
      'fieldable',
      'image_style',
      'media_switch',
    ];

    foreach ($forms as $key) {
      $value = $scopes->form($key) ?: !empty($definition[$key . '_form']);
      if (is_bool($value)) {
        $scopes->set('form.' . $key, $value);
      }
    }

    // Ensures merged once.
    if (!$scopes->is('scopes_merged') && $definition['scopes']) {
      $definition['scopes'] = $definition['scopes']->merge($scopes->storage());
      $scopes->set('is.scopes_merged', TRUE);
    }

    $scopes->set('was.scoped', TRUE);
  }

  /**
   * Returns the plugin scopes.
   */
  protected function getScopes(array &$definition): BlazySettings {
    return $this->toPluginScopes($definition);
  }

}
