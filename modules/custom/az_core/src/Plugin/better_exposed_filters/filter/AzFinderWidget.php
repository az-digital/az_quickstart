<?php

namespace Drupal\az_core\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Element;


/**
 * Finder widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "az_finder",
 *   label = @Translation("Finder"),
 * )
 */
class AzFinderWidget extends FilterWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Indicates whether the widget is nested.
   */
  protected $isNested = TRUE;

  /**
   * Constructs a new AzFinderWidget object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'level_0_icon_size' => '24',
      'level_1_icon_size' => '18',
      'level_0_expand_color' => '#1E5288',
      'level_0_collapse_color' => '#1E5288',
      'level_1_expand_color' => '#1E5288',
      'level_1_collapse_color' => '#1E5288',
      'level_0_expand_title' => $this->t('Level 0 Expand'),
      'level_0_collapse_title' => $this->t('Level 0 Collapse'),
      'level_1_expand_title' => $this->t('Level 1 Expand'),
      'level_1_collapse_title' => $this->t('Level 1 Collapse'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Custom settings for the Finder widget.
    $form['help'] = [
      '#markup' => $this->t('This widget allows you to use the Finder widget for hierarchical taxonomy terms.'),
    ];

    // Fields for SVG colors and titles.
    $svg_settings = [
      'level_0_icon_size' => 'Level 0 Icon Size',
      'level_0_expand' => 'Level 0 Expand',
      'level_0_collapse' => 'Level 0 Collapse',
      'level_1_icon_size' => 'Level 1 Icon Size',
      'level_1_expand' => 'Level 1 Expand',
      'level_1_collapse' => 'Level 1 Collapse',
    ];

    foreach ($svg_settings as $key => $label) {
      $form[$key . '_icon_size'] = [
        '#type' => 'number',
        '#title' => $this->t('@label Icon Size', ['@label' => $label]),
        '#default_value' => $this->configuration[$key . '_icon_size'],
        '#description' => $this->t('Specify the size for the @label SVG icon.', ['@label' => $label]),
        '#min' => 0,
        '#step' => 1,
      ];
      $form[$key . '_color'] = [
        '#type' => 'color',
        '#title' => $this->t('@label Icon Color', ['@label' => $label]),
        '#default_value' => $this->configuration[$key . '_color'],
        '#description' => $this->t('Specify the fill color for the @label SVG icon.', ['@label' => $label]),
      ];

      $form[$key . '_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@label Icon Title', ['@label' => $label]),
        '#default_value' => $this->configuration[$key . '_title'],
        '#description' => $this->t('Specify the title for the @label SVG icon.', ['@label' => $label]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    $filter = $this->handler;
    $config = $this->getConfiguration();
    $field_id = $filter->options['is_grouped'] ? $filter->options['group_info']['identifier'] : $filter->options['expose']['identifier'];

    if (!empty($form[$field_id])) {

      if (!empty($form[$field_id]['#options'])) {
        $form[$field_id]['#options'] = BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']);
      }

      if (!empty($filter->options['hierarchy'])) {
        $form[$field_id]['#hierarchy'] = TRUE;
      }

      // SVG icon colors and titles.
      $form[$field_id]['#level_0_expand_color'] = $config['level_0_expand_color'];
      $form[$field_id]['#level_0_collapse_color'] = $config['level_0_collapse_color'];
      $form[$field_id]['#level_1_expand_color'] = $config['level_1_expand_color'];
      $form[$field_id]['#level_1_collapse_color'] = $config['level_1_collapse_color'];
      $form[$field_id]['#level_0_expand_title'] = $config['level_0_expand_title'];
      $form[$field_id]['#level_0_collapse_title'] = $config['level_0_collapse_title'];
      $form[$field_id]['#level_1_expand_title'] = $config['level_1_expand_title'];
      $form[$field_id]['#level_1_collapse_title'] = $config['level_1_collapse_title'];

      if (!empty($form[$field_id]['#multiple'])) {
        $form[$field_id]['#theme'] = 'az_finder_widget';
        $form[$field_id]['#type'] = 'checkboxes';
      }
      else {
        $form[$field_id]['#theme'] = 'az_finder_widget';
        $form[$field_id]['#type'] = 'radios';
      }
    }
  }


  /**
   * Preprocesses variables for the az-finder template.
   *
   * @param array &$variables
   *   An associative array containing the element being processed.
   */
  public function preprocessAzFinderWidget(array &$variables) {
    $element = $variables['element'];
    $variables['wrapper_attributes'] = new Attribute();
    $variables['children'] = Element::children($element);
    $variables['attributes']['name'] = $element['#name'];
    if (!empty($element['#hierarchy'])) {
      $this->preprocessNestedElements($variables);
    }
  }

/**
 * Processes nested elements for hierarchical display.
 *
 * @param array &$element
 *   The form element to process.
 */
public function preprocessNestedElements(array &$variables): void {
    $variables['is_nested'] = TRUE;
    $variables['depth'] = [];
    $element = $variables['element'];
  $default_color = '#1E5288';
  $default_title_expand = t('Expand this section');
  $default_title_collapse = t('Collapse this section');

  $level_0_expand_icon_fill_color = $element['#level_0_expand_color'] ?? $default_color;
  $level_0_collapse_icon_fill_color = $element['#level_0_collapse_color'] ?? $default_color;
  $level_1_expand_icon_fill_color = $element['#level_1_expand_color'] ?? $default_color;
  $level_1_collapse_icon_fill_color = $element['#level_1_collapse_color'] ?? $default_color;

  $level_0_expand_icon_title = $element['#level_0_expand_title'] ?? $default_title_expand;
  $level_0_collapse_icon_title = $element['#level_0_collapse_title'] ?? $default_title_collapse;
  $level_1_expand_icon_title = $element['#level_1_expand_title'] ?? $default_title_expand;
  $level_1_collapse_icon_title = $element['#level_1_collapse_title'] ?? $default_title_collapse;

  // Example logic to structure elements (simplified for illustration).
  foreach ($variables['children'] as $child) {

    if ($child === 'All') {
      // Special handling for "All" option.
      $variables['depth'][$child] = 0;
      continue;
    }
    $renderer = \Drupal::service('renderer');
    // $entity_type = $child_element['#entity_type'];
    $entity_type = 'taxonomy_term';
    $entity_id = $child;
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $children = method_exists($entity_storage, 'loadChildren') ? $entity_storage->loadChildren($entity_id) : [];
    if (empty($children) && $entity_type !== 'taxonomy_term') {
      continue;
    }

    $original_title = $element[$child]['#title'];
    $cleaned_title = ltrim($original_title, '-');
    $list_title = [
      '#type' => 'html_tag',
    ];
    // Determine if the child has sub-elements (actual children).
    // Calculate depth based on hyphens in the title as a proxy for hierarchy.
    $depth = strlen($original_title) - strlen($cleaned_title);
    $level_0_expand_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="M16.59 8.59 12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z"/></svg>',
      '#context' => [
        'title' => $level_0_expand_icon_title,
        'fill_color' => $level_0_expand_icon_fill_color,
      ],
    ];
    $level_0_collapse_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="m12 8-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14l-6-6z"/></svg>',
      '#context' => [
        'title' => $level_0_collapse_icon_title,
        'fill_color' => $level_0_collapse_icon_fill_color,
      ],
    ];
    $level_1_expand_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
      '#context' => [
        'title' => $level_1_expand_icon_title,
        'fill_color' => $level_1_expand_icon_fill_color,
      ],
    ];
    $level_1_collapse_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="M19 13H5v-2h14v2z"/></svg>',
      '#context' => [
        'title' => $level_1_collapse_icon_title,
        'fill_color' => $level_1_collapse_icon_fill_color,
      ],
    ];
    $list_title['#value'] = $cleaned_title;
    // // Decide which icon to use based on depth.
    $collapse_icon = $depth === 0 ? $level_0_collapse_icon : $level_1_collapse_icon;
    $variables['depth'][$child] = $depth;
    if (!empty($children) && $depth >= 1) {
      $icon_html = $renderer->render($collapse_icon);
      $list_title['#value'] = Markup::create($icon_html . ' ' . $cleaned_title);
    }
    else {
      $list_title['#value'] = $cleaned_title;
      $variables['element'][$child]['#title'] = $list_title['#value'];
    }

    if (!empty($children)) {

      $list_title_link = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'class' => [],
        ],
      ];

      $collapse_id = 'collapse-az-finder-' . $entity_id;
      $list_title_link['#attributes']['data-toggle'] = 'collapse';
      $list_title_link['#attributes']['href'] = '#' . $collapse_id;
      $list_title_link['#attributes']['class'][] = 'd-block';
      $list_title_link['#attributes']['role'] = 'button';
      $list_title_link['#attributes']['aria-expanded'] = 'true';
      $list_title_link['#attributes']['aria-controls'] = $collapse_id;
      $list_title_link['#attributes']['data-collapse-id'] = $collapse_id;
      $list_title_link['#attributes']['class'][] = 'collapser';
      $list_title_link['#attributes']['class'][] = 'level-' . $depth;
      $list_title_link['#attributes']['class'][] = 'text-decoration-none';
      if ($depth === 0) {
        $list_title['icon'] = $collapse_icon;
        $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-0';
        $list_title['#tag'] = 'h3';
        $list_title['#attributes']['class'][] = 'text-azurite';
        $list_title['#attributes']['class'][] = 'text-size-h5';
        $list_title['#attributes']['class'][] = 'mt-3';
        $list_title['#attributes']['class'][] = 'pt-3';
      }
      else {
        $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-1';
        $list_title['#tag'] = 'h4';
        $list_title['#attributes']['class'][] = 'text-azurite';
        $list_title['#attributes']['class'][] = 'text-size-h6';
      }

      $list_title_link['value'] = $list_title;
      $list_title_link['#attached']['drupalSettings']['azFinder']['icons'] = [
        'level_0_expand' => $renderer->render($level_0_expand_icon),
        'level_0_collapse' => $renderer->render($level_0_collapse_icon),
        'level_1_expand' => $renderer->render($level_1_expand_icon),
        'level_1_collapse' => $renderer->render($level_1_collapse_icon),
      ];

      // Apply the modified list title to the element.
      $variables['element'][$child] = $list_title_link;
    }
  }
}

  /**
   * Implementation of generateSvgMarkup.
   *
   * @param int $depth
   *   The depth of the option.
   *
   * @return string
   *   The SVG markup.
   */
  protected function generateSvgMarkup($depth): string {
    $config = $this->getConfiguration();

    // Define default values for the SVG attributes.
    $attributes = [
      'fill_color' => $config['level_' . $depth . '_expand_color'] ?? '#000000',
      'size' => $depth === 0 ? '24' : '18',
      'title' => $config['level_' . $depth . '_expand_title'] ?? 'Expand this section',
      'icon_path' => $depth === 0 ? "M16.59 8.59 12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z" : "M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z",
    ];

    // Sanitize dynamic values to prevent XSS vulnerabilities.
    foreach ($attributes as &$attribute) {
      $attribute = htmlspecialchars($attribute, ENT_QUOTES, 'UTF-8');
    }

    $svg_render_template = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="{{ size }}" height="{{ size }}" viewBox="0 0 {{ size }} {{ size }}" title="{{ title }}"><path fill="{{ fill_color }}" d="{{ icon_path }}"/></svg>',
      '#context' => $attributes,
    ];

    // Return the render array for the SVG.
    return $this->renderer->render($svg_render_template);
  }

  /**
   * Calculates depth for a given option label.
   *
   * @param mixed $option
   *   The option, which can be a string label or an object with properties.
   *
   * @return int
   *   The calculated depth.
   */
  protected function calculateDepth($option): int {
    // Initialize depth.
    $depth = 0;
    // Ensure $option is a string before processing.
    $optionLabel = is_object($option) ? (property_exists($option, 'label') ? $option->label : '') : $option;
    // Use a loop or string function to count leading hyphens in the label.
    while (isset($optionLabel[$depth]) && $optionLabel[$depth] === '-') {
      $depth++;
    }

    return $depth;
  }

}
