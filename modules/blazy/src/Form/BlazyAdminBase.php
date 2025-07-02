<?php

namespace Drupal\blazy\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Render\Element;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base for blazy admin integration to have re-usable methods in one place.
 *
 * @see \Drupal\gridstack\Form\GridStackAdmin
 * @see \Drupal\mason\Form\MasonAdmin
 * @see \Drupal\slick\Form\SlickAdmin
 * @see \Drupal\blazy\Form\BlazyAdminFormatterBase
 */
abstract class BlazyAdminBase implements BlazyAdminInterface {

  use TraitAdminBase;

  /**
   * A state that represents the responsive image style is disabled.
   */
  const STATE_RESPONSIVE_IMAGE_STYLE_DISABLED = 0;

  /**
   * A state that represents the media switch lightbox is enabled.
   */
  const STATE_LIGHTBOX_ENABLED = 1;

  /**
   * A state that represents the media switch iframe is enabled.
   */
  const STATE_IFRAME_ENABLED = 2;

  /**
   * A state that represents the thumbnail style is enabled.
   */
  const STATE_THUMBNAIL_STYLE_ENABLED = 3;

  /**
   * A state that represents the custom lightbox caption is enabled.
   */
  const STATE_LIGHTBOX_CUSTOM = 4;

  /**
   * A state that represents the image rendered switch is enabled.
   */
  const STATE_IMAGE_RENDERED_ENABLED = 5;

  /**
   * Constructs a BlazyAdminBase object.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\blazy\BlazyManagerInterface $blazy_manager
   *   The blazy manager service.
   */
  public function __construct(
    EntityDisplayRepositoryInterface $entity_display_repository,
    TypedConfigManagerInterface $typed_config,
    DateFormatterInterface $date_formatter,
    BlazyManagerInterface $blazy_manager,
  ) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->typedConfig             = $typed_config;
    $this->dateFormatter           = $date_formatter;
    $this->blazyManager            = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository'),
      $container->get('config.typed'),
      $container->get('date.formatter'),
      $container->get('blazy.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function openingForm(array &$form, array &$definition): void {
    $scopes = $this->toScopes($definition);

    $this->blazyManager
      ->moduleHandler()
      ->alter('blazy_form_element_definition', $definition, $scopes);

    $base_form = $this->baseForm($definition);

    // Display style: column, plain static grid, slick grid, slick carousel.
    // https://drafts.csswg.org/css-multicol
    if ($scopes->is('style')) {
      $form['style'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Display style'),
        '#enforced'     => TRUE,
        '#empty_option' => $this->t('- None -'),
        '#options'      => $this->blazyManager->getStyles(),
        '#required'     => $scopes->is('grid_required', FALSE),
        '#weight'       => -112,
        '#wrapper_attributes' => $this->getTooltipClasses(['tooltip-wide']),
      ];
    }

    if ($scopes->is('by_delta')) {
      $form['by_delta'] = [
        '#type'   => 'textfield',
        '#title'  => $this->t('By delta'),
        '#weight' => -111,
        '#wrapper_attributes' => $this->getTooltipClasses(),
      ];
    }

    // @todo remove after sub-modules calls ::baseImageForm().
    if ($scopes->is('background')) {
      $form['background'] = [
        '#type'   => 'checkbox',
        '#title'  => $this->t('Use CSS background'),
        '#weight' => -100,
      ];
    }

    if ($skins = $scopes->data('skins')) {
      $form['skin'] = [
        '#type'     => 'select',
        '#title'    => $this->t('Skin'),
        '#options'  => $this->toOptions($skins),
        '#enforced' => TRUE,
        '#weight'   => -109,
      ];
    }

    if ($layouts = $scopes->data('layouts')) {
      $form['layout'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Layout'),
        '#options' => $this->toOptions($layouts),
        '#weight'  => 2,
      ];
    }

    if ($captions = $scopes->data('captions')) {
      $form['caption'] = [
        '#type'       => 'checkboxes',
        '#title'      => $this->t('Caption fields'),
        '#options'    => $this->toOptions($captions),
        '#weight'     => 80,
        '#attributes' => ['class' => ['form-wrapper--caption']],
      ];
    }

    if ($element = $base_form['view_mode'] ?? []) {
      $form['view_mode'] = $element;
    }

    // Add descriptions, if applicable.
    foreach ($this->openingDescriptions() as $key => $description) {
      if (isset($form[$key])) {
        $form[$key]['#description'] = $description;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function gridForm(array &$form, array $definition): void {
    $scopes    = $this->toScopes($definition);
    $required  = $scopes->is('grid_required');
    $multigrid = $this->isMultiBreakpoint($definition);

    if (!$scopes->is('no_grid_header')) {
      $header  = $this->t('Group individual items as block grid?');
      $desc    = $definition['grid_header_desc'] ?? $this->gridHeaderDescription();
      $texts[] = ['#markup' => '<h3>' . $header . '</h3>'];
      $texts[] = ['#markup' => '<p>' . $desc . '</p>'];

      $form['grid_header'] = [
        '#type'       => 'container',
        'items'       => $texts,
        '#access'     => !$required,
        '#attributes' => $this->getTitleClasses(['grids']),
      ];
    }

    $form['grid'] = [
      '#type'     => 'textarea',
      '#title'    => $this->t('Grid large'),
      '#enforced' => TRUE,
      '#required' => $required,
      '#weight'   => 60,
      '#wrapper_attributes' => $this->getTooltipClasses(['full', 'tooltip-wide']),
    ];

    $form['grid_medium'] = [
      '#type'  => $multigrid ? 'textarea' : 'textfield',
      '#title' => $this->t('Grid medium'),
    ];

    $form['grid_small'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Grid small'),
    ];

    if (!$scopes->is('grid_simple')) {
      $form['visible_items'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Visible items'),
        '#options' => array_combine(range(1, 32), range(1, 32)),
      ];

      $form['preserve_keys'] = [
        '#type'   => 'checkbox',
        '#title'  => $this->t('Preserve keys'),
        '#access' => $scopes->is('grid_preserve_keys'),
      ];
    }

    $grids = [
      'grid_header',
      'grid_medium',
      'grid_small',
      'visible_items',
      'preserve_keys',
    ];

    foreach ($grids as $key) {
      if (isset($form[$key])) {
        $form[$key]['#enforced'] = TRUE;
        $form[$key]['#weight'] = $key == 'grid_header' ? 50 : 61;

        $form[$key]['#states'] = [
          'visible' => [
            '[name$="[grid]"]' => ['!value' => ''],
          ],
        ];
      }
    }

    // Add descriptions, if applicable.
    foreach ($this->gridDescriptions() as $key => $description) {
      if (isset($form[$key])) {
        $form[$key]['#description'] = $description;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function gridOnlyForm(array &$form, array &$definition): void {
    $this->openingForm($form, $definition);
    $this->gridForm($form, $definition);
    $this->finalizeForm($form, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function closingForm(array &$form, array $definition): void {
    $scopes = $this->toScopes($definition);
    $namespace = $scopes->get('namespace');
    $valid = $scopes->get('field') && $scopes->is('theme_field');
    $uri = $this->getUri()['uri'];
    $lb = strpos($uri, '/layout_builder/') !== FALSE;

    if ($namespace == 'blazy') {
      $valid = $lb || $valid;

      if ($lb) {
        $form['use_lb'] = [
          '#type' => 'hidden',
          '#value' => TRUE,
        ];
      }
    }

    if ($valid) {
      $form['use_theme_field'] = [
        '#title'       => $this->t('Use field template'),
        '#type'        => 'checkbox',
        '#description' => $this->closingDescriptions()['use_theme_field'],
        '#weight'      => -100,
      ];
    }

    $form['admin_uri'] = [
      '#type' => 'hidden',
      '#value' => $uri,
    ];

    $this->finalizeForm($form, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function baseForm(array &$definition): array {
    $scopes       = $this->toScopes($definition);
    $blazies      = $definition['blazies'];
    $form         = [];
    $no_image     = $scopes->is('no_image_style');
    $disabled     = $scopes->is('no_view_mode');
    $target_type  = $scopes->get('target_type') ?: $blazies->get('field.target_type');
    $view_mode    = $scopes->get('view_mode') ?: $blazies->get('field.view_mode');
    $is_fieldable = $target_type && $view_mode;

    $scopes->set('is.fieldable', $is_fieldable);

    if ($is_fieldable && !$disabled) {
      $form['view_mode'] = [
        '#type'     => 'select',
        '#options'  => $this->getViewModeOptions($target_type),
        '#title'    => $this->t('View mode'),
        '#weight'   => -101,
        '#enforced' => TRUE,
      ];
    }

    if ($scopes->form('image_style') || !$no_image) {
      $this->baseImageForm($form, $definition);
    }

    // Add descriptions, if applicable.
    foreach ($this->baseDescriptions() as $key => $description) {
      if (isset($form[$key])) {
        $form[$key]['#description'] = $description;
      }
    }

    $this->blazyManager->moduleHandler()->alter('blazy_base_form_element', $form, $definition, $scopes);

    return $form;
  }

  /**
   * Provides basic image options.
   */
  protected function baseImageForm(array &$form, array $definition): void {
    $scopes = $this->scopes;
    $data = $scopes->get('data');
    $multimedia = $scopes->is('multimedia');

    if (!$scopes->is('no_preload')) {
      $form['preload'] = [
        '#type'   => 'checkbox',
        '#title'  => $this->t('Preload'),
        '#weight' => -111,
        '#wrapper_attributes' => $this->getTooltipClasses(),
      ];
    }

    if (!$scopes->is('no_loading')) {
      $loadings = ['auto', 'defer', 'eager', 'unlazy'];

      // It is defined in sub-modules, not Blazy.
      if ($scopes->is('slider')) {
        $loadings[] = 'slider';
      }
      $form['loading'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Loading priority'),
        '#options'      => array_combine($loadings, $loadings),
        '#empty_option' => $this->t('lazy'),
        '#weight'       => -111,
        '#wrapper_attributes' => $this->getTooltipClasses(),
      ];
    }

    $form['image_style'] = [
      '#type'    => 'select',
      '#title'   => $this->t('Image style'),
      '#options' => $this->getEntityAsOptions('image_style'),
      '#weight'  => -106,
      '#wrapper_attributes' => $this->getTooltipClasses(),
    ];

    if ($scopes->is('responsive_image')) {
      $options = $this->getResponsiveImageOptions();
      $form['responsive_image_style'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Responsive image'),
        '#options'     => $options,
        '#access'      => count($options) > 0,
        '#weight'      => -105,
      ];
    }

    $form['background'] = [
      '#type'   => 'checkbox',
      '#title'  => $this->t('Use CSS background'),
      '#weight' => -100,
    ];

    if ($scopes->is('switch')) {
      $form['media_switch'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Media switcher'),
        '#weight'  => -99,
        '#options' => [
          'content' => $this->t('Image linked to content'),
        ],
      ];

      if (isset($data['links'])) {
        $form['media_switch']['#options']['link'] = $this->t('Image linked by Link field');
      }

      if ($scopes->is('lightbox')) {
        $this->lightboxForm($form, $definition, $scopes);
      }

      // Adds common supported entities for media integration.
      if ($multimedia) {
        $form['media_switch']['#options']['media'] = $this->t('Image to iFrame');
      }
    }

    // https://en.wikipedia.org/wiki/List_of_common_resolutions
    $ratio = array_merge(BlazyDefault::RATIO, ['fluid']);
    if (!$scopes->is('no_ratio')) {
      $form['ratio'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Aspect ratio'),
        '#options' => array_combine($ratio, $ratio),
        '#weight'  => -101,
      ];
    }

    if ($scopes->is('thumbnail_style')) {
      $form['thumbnail_style'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Thumbnail style'),
        '#options' => $this->getEntityAsOptions('image_style'),
        '#weight'  => -104,
      ];
    }

    // @todo this can also be used for local video poster image option.
    if (isset($data['images'])) {
      $classes = $this->getTitleClasses(['fields', 'hideable'], TRUE);
      $form['image'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Main stage'),
        '#options' => $this->toOptions($data['images'] ?: []),
        '#prefix'  => '<h3 class="' . $classes . '">' . $this->t('Fields') . '</h3>',
      ];
    }

    $this->linkForm($form, $definition, $scopes);

    // Add descriptions, if applicable.
    foreach ($this->baseDescriptions() as $key => $description) {
      if (isset($form[$key])) {
        $form[$key]['#description'] = $description;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mediaSwitchForm(array &$form, array $definition): void {
    $scopes    = $this->toScopes($definition);
    $base_form = $this->baseForm($definition);
    $classes   = $this->getTitleClasses(['media-switch', 'hideable'], TRUE);
    $options   = [
      'media_switch',
      'ratio',
      'box_style',
      'box_media_style',
      'box_caption',
      'box_caption_custom',
      'link',
    ];

    foreach ($options as $key) {
      if ($element = $base_form[$key] ?? []) {
        $form[$key] = $element;
        if ($key == 'media_switch') {
          $form[$key]['#prefix'] = '<h3 class="' . $classes . '">' . $this->t('Media switcher') . '</h3>';
        }
      }
    }

    $this->blazyManager->moduleHandler()->alter('blazy_media_switch_form_element', $form, $definition, $scopes);
  }

  /**
   * {@inheritdoc}
   */
  public function finalizeForm(array &$form, array $definition): void {
    $scopes    = $this->toScopes($definition);
    $settings  = $definition['settings'] ?? [];
    $admin_css = $this->isAdminCss();
    $classes   = $this->getOpeningClasses($scopes);
    $excludes  = ['details', 'fieldset', 'hidden', 'markup', 'item', 'table'];
    $selects   = ['cache', 'optionset', 'view_mode'];
    $fullwidth = $scopes->data('fullwidth', []);
    $descs     = $scopes->data('additional_descriptions', []);
    $repdescs  = $scopes->data('replaced_descriptions', []);
    $multigrid = $this->isMultiBreakpoint($definition);

    $this->blazyManager->moduleHandler()->alter('blazy_form_element', $form, $definition, $scopes);

    // Prevents non-expected overrides.
    if (isset($form['grid'], $form['grid']['#description'])) {
      $description = $form['grid']['#description'];
      $form['grid']['#description'] = $description . $this->nativeGridDescription();
    }

    // Accounts for hook_alter additions.
    $children = Element::children($form);
    $gridsets = [];
    $total    = count($children);

    if ($admin_css) {
      $grids    = $this->initGrid($total, $classes);
      $classes  = $grids['classes'];
      $gridsets = $grids['settings'];
    }
    else {
      $classes = implode(' ', $classes);
    }

    $form['opening'] = [
      '#markup' => '<div class="' . $classes . '">',
      '#weight' => -120,
    ];

    $form['closing'] = [
      '#markup' => '</div>',
      '#weight' => 120,
    ];

    // Mostly babysitters to help few things out.
    foreach ($children as $delta => $key) {
      $type = $form[$key]['#type'] ?? NULL;
      if (!$type || in_array($type, $excludes)) {
        continue;
      }

      // If no defined default values, set them from settings.
      if (!isset($form[$key]['#default_value']) && isset($settings[$key])) {
        $value = is_array($settings[$key])
          ? array_values((array) $settings[$key])
          : $settings[$key];

        if (is_string($value)) {
          if ($key == 'loading' && !$value) {
            $value = 'lazy';
          }

          if ($value) {
            $value = trim($value);
          }
        }

        $form[$key]['#default_value'] = $value;
      }

      // Trying to be nice with gazillion options.
      foreach (['attributes', 'wrapper_attributes'] as $attribute) {
        if (!isset($form[$key]["#$attribute"])) {
          $form[$key]["#$attribute"] = [];
        }
      }

      $attrs = &$form[$key]['#attributes'];
      $wrapper_attrs = &$form[$key]['#wrapper_attributes'];
      $content_attrs = [];

      if (isset($form[$key]['#description'])) {
        $attrs['class'][] = 'is-tooltip';
      }

      // Trying to be compact with gazillion options.
      if ($admin_css) {
        if ($gridsets) {
          $blazy = $gridsets['blazies']->reset($gridsets);
          $blazy->set('delta', $delta);
        }

        if ($type == 'checkbox') {
          $form[$key]['#title_display'] = 'before';
        }
        elseif ($type == 'checkboxes' && !empty($form[$key]['#options'])) {
          // Cannot set wrapper classes here since they leak to each input.
          foreach ($form[$key]['#options'] as $name => $option) {
            $form[$key][$name]['#title_display'] = 'before';
          }
        }

        $dummies['class'] = [];
        $this->blazyManager->gridItemAttributes($dummies, $content_attrs, $gridsets);
        $wrapper_attrs = $this->blazyManager->merge($wrapper_attrs, $dummies);

        $wide = in_array($key, ['grid', 'box_caption_custom'])
          || ($scopes->is('grid_required') && $key == 'style');

        if ($multigrid && $key == 'grid_medium') {
          $wide = TRUE;
        }

        if ($wide || ($fullwidth && in_array($key, $fullwidth))) {
          $wrapper_attrs['data-b-w'] = 12;
        }
      }

      $wrapper_attrs['class'][] = 'form-item--' . str_replace('_', '-', $key);

      // Select option babysitters.
      if ($type == 'select' && !in_array($key, $selects)) {
        $required = $form[$key]['#required'] ?? FALSE;
        if ($required) {
          unset($form[$key]['#empty_option']);
        }
        else {
          if (!isset($form[$key]['#empty_option'])) {
            $form[$key]['#empty_option'] = $this->t('- None -');
          }
        }
      }

      // Vanilla states babysitters.
      if ($scopes->is('vanilla') && !isset($form[$key]['#enforced'])) {
        $states['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
        if (isset($form[$key]['#states'])) {
          $form[$key]['#states']['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
        }
        else {
          $form[$key]['#states'] = $states;
        }
      }

      // To minimize CSS rules for common lightbox items.
      foreach (['style', 'media_style', 'caption'] as $k) {
        $k = 'box_' . $k;
        if (isset($form[$k]) && $k == $key) {
          $wrapper_attrs['class'][] = 'form-item--litebox';
        }
      }

      // Don't store values babysitters.
      if (!empty($form[$key]['#unset'])
        || ($form[$key]['#access'] ?? 'x') == FALSE) {
        unset($form[$key]['#default_value']);
      }

      if (in_array($key, $scopes->data('deprecations'))) {
        unset($form[$key]['#default_value']);
      }

      // Additional descriptions.
      if ($desc = $descs[$key] ?? '') {
        if (!empty($form[$key]['#description'])) {
          $placement = $desc['placement'] ?? '';
          $description = $desc['description'] ?? '';
          if ($placement == 'after') {
            $form[$key]['#description'] .= $description;
          }
          else {
            $form[$key]['#description'] = $description . ' ' . $form[$key]['#description'];
          }
        }
      }
      elseif ($desc = $repdescs[$key] ?? '') {
        $form[$key]['#description'] = $desc;
      }

      if ($this->isAdminLb() || $scopes->is('collapsible_description')) {
        $this->themeDescription($form[$key]);
      }
    }

    if ($admin_css) {
      $form['closing']['#attached']['library'][] = 'blazy/admin';

      if ($libraries = $scopes->data('libraries')) {
        foreach ($libraries as $key) {
          $form['closing']['#attached']['library'][] = $key;
        }
      }
    }

    if ($this->isAdminLb()) {
      $form['closing']['#attached']['library'][] = 'blazy/admin.lb';
    }

    $this->blazyManager->moduleHandler()->alter('blazy_complete_form_element', $form, $definition, $scopes);

    if (!$scopes->is('_views')) {
      $prefix = $form['opening']['#prefix'] ?? '';
      $form['opening']['#prefix'] = $prefix . '<br /><small>' . $this->t("<strong>Tips!</strong> Reload the page, or save first, only when changing formatters. Some form items may not be loaded after AJAX.") . '</small>';
    }
  }

  /**
   * Provides lightbox options.
   */
  protected function lightboxForm(array &$form, array $definition, $scopes): void {
    $blazies    = $definition['blazies'];
    $multimedia = $scopes->is('multimedia');
    $is_token   = $this->blazyManager->moduleExists('token');

    // Optional lightbox integration.
    if ($lightboxes = $scopes->data('lightboxes')) {
      foreach ($lightboxes as $lightbox) {
        $name = Unicode::ucwords(str_replace('_', ' ', $lightbox));
        if ($lightbox == 'mfp') {
          $name = 'Magnific Popup';
        }
        $form['media_switch']['#options'][$lightbox] = $this->t('Image to @lightbox', ['@lightbox' => $name]);
      }

      // Re-use the same image style for both lightboxes.
      $box_styles = $this->getResponsiveImageOptions()
        + $this->getEntityAsOptions('image_style');
      $form['box_style'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Lightbox image style'),
        '#options' => $box_styles,
        '#weight'  => -97,
      ];

      if ($multimedia) {
        $form['box_media_style'] = [
          '#type'    => 'select',
          '#title'   => $this->t('Lightbox video style'),
          '#options' => $this->getEntityAsOptions('image_style'),
          '#weight'  => -96,
        ];
      }

      // @todo remove check after another check.
      // Was meant for Blazy Views fields lacking of field info needed here.
      if (!$scopes->is('no_box_captions')) {
        $custom = !$scopes->is('no_box_caption_custom');
        $options = $this->getLightboxCaptionOptions();

        if (!$custom) {
          unset($options['custom']);
        }

        $form['box_caption'] = [
          '#type'    => 'select',
          '#title'   => $this->t('Lightbox caption'),
          '#options' => $options,
          '#weight'  => -95,
        ];

        if ($custom) {
          $form['box_caption_custom'] = [
            '#title'  => $this->t('Lightbox custom caption'),
            '#type'   => 'textfield',
            '#weight' => -94,
            '#states' => $this->getState(static::STATE_LIGHTBOX_CUSTOM, $scopes),
          ];

          if ($is_token) {
            $entity_type = $blazies->get('field.entity_type');
            $target_type = $blazies->get('field.target_type');
            $types = $entity_type ? [$entity_type] : [];
            $types = $target_type ? array_merge($types, [$target_type]) : $types;

            if ($types) {
              $form['box_caption_custom']['#field_suffix'] = [
                '#theme'       => 'token_tree_link',
                '#text'        => $this->t('Tokens'),
                '#token_types' => $types,
              ];
            }
          }
        }
      }

      if (!$scopes->is('box_stateless')) {
        foreach (['box_caption', 'box_style', 'box_media_style'] as $key) {
          if (isset($form[$key])) {
            $form[$key]['#states'] = $this->getState(static::STATE_LIGHTBOX_ENABLED, $scopes);
          }
        }
      }
    }
  }

  /**
   * Provides link options serving plain image, fieldable and views ui.
   */
  protected function linkForm(array &$form, array $definition, $scopes): void {
    $data = $scopes->get('data');
    $description = $this->baseDescriptions();

    if (isset($data['links'])) {
      $form['link'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Link'),
        '#options'     => $this->toOptions($data['links'] ?: []),
        '#weight'      => 9,
        '#description' => $description['link'] ?? '',
      ];
    }
  }

  /**
   * Provides SVG options.
   */
  protected function svgForm(array &$form, array $definition): void {
    foreach (BlazyDefault::svgSettings() as $key => $value) {
      $base  = str_replace('svg_', '', $key);
      $name  = str_replace('_', ' ', $base);
      $title = Unicode::ucfirst($name);
      $exist = Blazy::svgSanitizerExists();
      $desc  = $this->svgDescriptions()[$base] ?? '';

      $form[$key] = [
        '#type'        => is_bool($value) ? 'checkbox' : 'textfield',
        '#title'       => $this->t('@title', ['@title' => $title]),
        // @todo recheck '#enforced' => !$scopes->is('vanilla'),
        '#description' => $desc,
        '#weight'      => -99,
      ];

      if ($base == 'inline') {
        $classes = $this->getTitleClasses(['svg', 'hideable'], TRUE);
        $form[$key]['#disabled'] = !$exist;
        $form[$key]['#unset'] = !$exist;
        $form[$key]['#prefix'] = '<h3 class="' . $classes . '">' . $this->t('SVG') . '</h3>';
      }
      if ($base == 'fill') {
        $form[$key]['#states']['visible'][':input[name*="[svg_inline]"]'] = ['checked' => TRUE];
      }
    }
  }

}
