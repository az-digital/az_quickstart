<?php

namespace Drupal\az_core\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['help'] = ['#markup' => $this->t('This widget allows you to use the Finder widget for hierarchical taxonomy terms.')];
    $svg_settings = [
      'level_0_icon_size' => 'Level 0 Icon Size',
      'level_0_expand' => 'Level 0 Expand',
      'level_0_collapse' => 'Level 0 Collapse',
      'level_1_icon_size' => 'Level 1 Icon Size',
      'level_1_expand' => 'Level 1 Expand',
      'level_1_collapse' => 'Level 1 Collapse',
    ];

    foreach ($svg_settings as $key => $label) {
      $suffix = strpos($key, 'icon_size') !== FALSE ? '_icon_size' : '_color';
      $form[$key . $suffix] = [
        '#type' => strpos($key, 'icon_size') !== FALSE ? 'number' : 'color',
        '#title' => $this->t('@label', ['@label' => $label]),
        '#default_value' => $this->configuration[$key . $suffix],
        '#description' => $this->t('Specify the size for the @label.', ['@label' => $label]),
        '#min' => 0,
        '#step' => 1,
      ];

      // Titles for non-size fields.
      if (strpos($key, 'icon_size') === FALSE) {
        $form[$key . '_title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('@label Icon Title', ['@label' => $label]),
          '#default_value' => $this->configuration[$key . '_title'],
          '#description' => $this->t('Specify the title for the @label SVG icon.', ['@label' => $label]),
        ];
      }
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
      $form[$field_id]['#options'] = !empty($form[$field_id]['#options']) ? BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']) : $form[$field_id]['#options'];
      $form[$field_id]['#hierarchy'] = !empty($filter->options['hierarchy']);

      // Assigning SVG icon colors and titles directly.
      foreach (['level_0', 'level_1'] as $level) {
        foreach (['expand', 'collapse'] as $action) {
          $form[$field_id]["#{$level}_{$action}_color"] = $config["{$level}_{$action}_color"];
          $form[$field_id]["#{$level}_{$action}_title"] = $config["{$level}_{$action}_title"];
        }
      }

      $form[$field_id]['#theme'] = 'az_finder_widget';
      // Initialize an array to hold the SVG icon settings.
      $svgIcons = [];

      // Define the levels and actions for which SVG icons need to be generated.
      $levels = [0, 1];
      $actions = ['expand', 'collapse'];

      // Loop through each level and action to generate the SVG icons.
      foreach ($levels as $level) {
        foreach ($actions as $action) {
          // Generate the SVG icon for the current level and action.
          $svg_icons["level_{$level}_{$action}"] = $this->renderer->render($this->generateSvgRenderArray($level, $action));
        }
      }

      // Attach the generated SVG icons to Drupal settings.
      $form['#attached']['drupalSettings']['azFinder']['icons'] = $svg_icons;
      $form[$field_id]['#type'] = !empty($form[$field_id]['#multiple']) ? 'checkboxes' : 'radios';
    }
  }

  /**
   * Preprocesses variables for the az-finder template.
   *
   * @param array &$variables
   *   An associative array containing the element being processed.
   */
  public function preprocessAzFinderWidget(array &$variables) {
    //recreate the icons for the form element
    $level_0_collapse_icon = $this->generateSvgRenderArray(0, 'collapse');
    $level_1_collapse_icon = $this->generateSvgRenderArray(1, 'collapse');

    $element = $variables['element'];
    $variables += [
      'wrapper_attributes' => new Attribute(),
      'children' => Element::children($element),
      'attributes' => ['name' => $element['#name']],
    ];

    if (!empty($element['#hierarchy'])) {
      $variables['is_nested'] = TRUE;
    }

    $variables['is_nested'] = TRUE;
    $variables['depth'] = [];
    $element = $variables['element'];

    // Example logic to structure elements (simplified for illustration).
    foreach ($variables['children'] as $child) {

      if ($child === 'All') {
        // Special handling for "All" option.
        $variables['depth'][$child] = 0;
        continue;
      }
      // $entity_type = $child_element['#entity_type'];
      $entity_type = 'taxonomy_term';
      $entity_id = $child;
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
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

      $list_title['#value'] = $cleaned_title;
      // // Decide which icon to use based on depth.
      $collapse_icon = $depth === 0 ? $level_0_collapse_icon : $level_1_collapse_icon;
      $variables['depth'][$child] = $depth;
      if (!empty($children) && $depth >= 1) {
        $collapse_icon_html = $this->renderer->render($collapse_icon);
        $list_title['#value'] = Markup::create($collapse_icon_html . ' ' . $cleaned_title);
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

        // Apply the modified list title to the element.
        $variables['element'][$child] = $list_title_link;
      }
    }

  }

  /**
   * Generates SVG markup for icons based on depth and action.
   *
   * @param int $depth
   *   The depth of the option, affecting icon size and path.
   * @param string $action
   *   The action ('expand' or 'collapse') determining the icon.
   *
   * @return array
   *   A render array for the SVG icon.
   */
  protected function generateSvgRenderArray($depth, $action): array {
    $config = $this->getConfiguration();
    $level = $depth === 0 ? 'level_0' : 'level_1';
    $actionType = $action === 'expand' ? 'expand' : 'collapse';

    // Define default values and paths for SVG attributes based on action
    // and depth.
    $attributes = [
      'fill_color' => $config["{$level}_{$actionType}_color"] ?? '#000000',
      'size' => $depth === 0 ? '24' : '18',
      'title' => $config["{$level}_{$actionType}_title"] ?? ucfirst($action) . ' this section',
      'icon_path' => $this->getIconPath($depth, $action),
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

    // Return the rendered SVG markup.
    return $svg_render_template;
  }

  /**
   * Determines the SVG path for the icon based on depth and action.
   *
   * @param int $depth
   *   Depth of the item, affecting the icon shape.
   * @param string $action
   *   Action type ('expand' or 'collapse').
   *
   * @return string
   *   SVG path for the specified icon.
   */
  private function getIconPath($depth, $action): string {
    if ($depth === 0) {
      return $action === 'expand' ? "M16.59 8.59 12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z" : "m12 8-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14l-6-6z";
    }
    else {
      return $action === 'expand' ? "M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" : "M19 13H5v-2h14v2z";
    }
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
