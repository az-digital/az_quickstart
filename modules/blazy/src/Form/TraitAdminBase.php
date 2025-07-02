<?php

namespace Drupal\blazy\Form;

use Drupal\blazy\Theme\Admin;
use Drupal\blazy\Utility\Path;

/**
 * A blazy admin Trait to declutter, and focus more on form elements.
 */
trait TraitAdminBase {

  use TraitScopes;
  use TraitDescriptions;
  use TraitAdminOptions;

  /**
   * The typed config manager service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * {@inheritdoc}
   */
  public function getEntityDisplayRepository() {
    return $this->entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypedConfig() {
    return $this->typedConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdminCss(): bool {
    $admin_css = $this->blazyManager->config('admin_css', 'blazy.settings') ?: FALSE;
    // Disable the admin css in the off canvas menu, to avoid conflicts with
    // the active frontend theme.
    $uris = $this->getUri();
    if ($admin_css && $uri = $uris['uri']) {
      $wrapper_format = $uris['wrapper_format'] ?? '';

      if ($wrapper_format === "drupal_dialog.off_canvas"
        || strpos($uri, '/views/nojs') !== FALSE
        || strpos($uri, '/layout_builder/') !== FALSE) {
        $admin_css = FALSE;
      }
    }
    return $admin_css;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdminLb(): bool {
    $uris = $this->getUri();
    return strpos($uris['uri'], '/layout_builder/') !== FALSE;
  }

  /**
   * Provides tabs menu.
   */
  public function tabify(array &$form, $form_id, $region): void {
    Admin::tabify($form, $form_id, $region);
  }

  /**
   * {@inheritdoc}
   */
  public function themeDescription(array &$form, array $parents = []): void {
    Admin::themeDescription($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function toOptions(array $data): array {
    return $this->blazyManager->toOptions($data);
  }

  /**
   * Returns the current request object.
   */
  protected function getCurrentRequest() {
    if ($request = Path::requestStack()) {
      return $request->getCurrentRequest();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array &$form, array $definition): void {
  }

  /**
   * {@inheritdoc}
   */
  public function fieldableForm(array &$form, array $definition): void {
  }

  /**
   * {@inheritdoc}
   */
  public function imageStyleForm(array &$form, array $definition): void {
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $definition): array {
    return [];
  }

  /**
   * Returns form opening classes.
   */
  protected function getOpeningClasses($scopes): array {
    $namespace = $scopes->get('namespace', 'blazy');
    $classes = [];

    $items = ['blazy', 'slick', $namespace, 'half'];

    if ($scopes->is('_views')) {
      $items[] = 'views';
    }
    if ($scopes->is('vanilla')) {
      $items[] = 'vanilla';
    }
    if ($scopes->is('grid_required')) {
      $items[] = 'grid-required';
    }
    if ($plugin_id = $scopes->get('plugin_id')) {
      $items[] = 'plugin-' . str_replace('_', '-', $plugin_id);
    }
    if ($field_type = $scopes->get('field.type')) {
      $items[] = str_replace('_', '-', $field_type);
    }

    foreach ($items as $class) {
      $classes[] = 'form--' . $class;
    }

    $classes[] = 'b-tooltip';
    $classes[] = 'b-tooltip--lg';

    return $classes;
  }

  /**
   * Returns the admin URI.
   */
  protected function getUri(): array {
    $uri = $wrapper_format = '';
    if ($current = $this->getCurrentRequest()) {
      $uri = $current->getRequestUri();
      $wrapper_format = $current->query->get('_wrapper_format');
    }
    return ['uri' => $uri, 'wrapper_format' => $wrapper_format];
  }

  /**
   * Initialize the grid.
   */
  protected function initGrid($total, $classes): array {
    $options = [
      'count'   => $total,
      'classes' => $classes,
    ];

    $grids   = $this->blazyManager->initGrid($options);
    $attrs   = $grids['attributes'];
    $classes = implode(' ', $attrs['class']);

    return [
      'classes'  => $classes,
      'settings' => $grids['settings'],
    ];
  }

  /**
   * Returns the supported multi-breakpoint grids.
   */
  protected function isMultiBreakpoint(array $definition): bool {
    $settings = $definition['settings'] ?? [];
    if ($style = $settings['style'] ?? '') {
      return in_array($style, ['flexbox', 'nativegrid']);
    }
    return FALSE;
  }

}
