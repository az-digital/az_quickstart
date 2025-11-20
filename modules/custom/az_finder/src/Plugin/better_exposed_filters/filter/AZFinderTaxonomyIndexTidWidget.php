<?php

declare(strict_types=1);

namespace Drupal\az_finder\Plugin\better_exposed_filters\filter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\az_finder\Service\AZFinderIcons;
use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\views\ViewExecutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Finder widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "az_finder_tid_widget",
 *   label = @Translation("Quickstart Finder Term ID Widget"),
 * )
 */
class AZFinderTaxonomyIndexTidWidget extends FilterWidgetBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The override settings.
   *
   * @var array
   */
  protected static $overrides = [];

  /**
   * Constructs a new AzFinderWidget object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\az_finder\Service\AZFinderIcons $az_finder_icons
   *   The AZFinderIcons service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Request $request,
    ConfigFactoryInterface $config_factory,
    protected RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AZFinderIcons $azFinderIcons,
    protected LoggerInterface $logger,
  ) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $containter->get('request_stack')->getCurrentRequest(),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('az_finder.icons'),
      $container->get('logger.channel.az_finder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'default_states' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    if (!$this->view instanceof ViewExecutable) {
      return;
    }
    // Attach contextual links to the block render array.
    $form['#contextual_links'] = [
      'az_finder' => [
        'route_parameters' => [],
      ],
    ];
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $filter_id = $filter->options['expose']['identifier'];
    $field_id = $this->getFieldId($filter);
    $identifier = $filter_id;
    $form['#attached']['library'][] = 'az_finder/taxonomy-index-tid-widget';
    $exposed_label = $filter->options['expose']['label'];
    $exposed_description = $filter->options['expose']['description'];

    if ($filter->isAGroup()) {
      $identifier = $filter->options['group_info']['identifier'];
      $exposed_label = $filter->options['group_info']['label'];
      $exposed_description = $filter->options['group_info']['description'];
    }

    // Add possible field wrapper to validate for "between" operator.
    $element_wrapper = $field_id . '_wrapper';

    $filter_elements = [
      $identifier,
      $element_wrapper,
      $filter->options['expose']['operator_id'],
    ];

    // Iterate over all exposed filter elements.
    foreach ($filter_elements as $element) {
      // Sanity check to make sure the element exists.
      if (empty($form[$element])) {
        continue;
      }

      // "Between" operator fields to validate for.
      $fields = ['min', 'max'];
      $wrapper_array = [];
      // Check if the element is part of a wrapper.
      if ($element === $element_wrapper) {
        $wrapper_array = $form[$element];
        // Determine if wrapper element has min or max fields or if collapsible,
        // if so then update type.
        if (array_intersect($fields, array_keys($wrapper_array[$field_id]))) {
          $form[$element] = [
            '#type' => 'container',
            $element => $wrapper_array,
          ];
        }
      }
      else {
        // Determine if element has min or max child fields, if so then update
        // type.
        if (array_intersect($fields, array_keys($form[$field_id]))) {
          $form[$element] = [
            '#type' => 'container',
            $element => $wrapper_array,
          ];
        }
      }

      $form[$element]['#title'] = $exposed_label;
      $form[$element]['#description'] = $exposed_description;

      // Finally, add some metadata to the form element.
      $this->addContext($form[$element]);
    }

    if (!empty($form[$field_id])) {
      $this->setFormOptions($form, $field_id);
      $svg_icons = $this->azFinderIcons->generateSvgIcons();
      foreach ($svg_icons as $key => $icon) {
        $form['#attached']['drupalSettings']['azFinder']['icons'][$key] = $this->renderer->renderInIsolation($icon);
      }
      $form[$field_id]['#type'] = !empty($form[$field_id]['#multiple']) ? 'checkboxes' : 'radios';
    }

  }

  /**
   * Returns the field ID for a views filter.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   A views filter plugin object.
   *
   * @return string
   *   The field ID.
   */
  protected function getFieldId($filter): string {
    return $filter->options['is_grouped'] ? $filter->options['group_info']['identifier'] : $filter->options['expose']['identifier'];
  }

  /**
   * Sets the form options for the filter.
   *
   * @param array $form
   *   The form array.
   * @param string $field_id
   *   The field ID.
   *
   * @return array
   *   The form array with the options set.
   */
  protected function setFormOptions(array &$form, $field_id): array {
    $form[$field_id]['#options'] = !empty($form[$field_id]['#options']) ? BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']) : $form[$field_id]['#options'];
    $form[$field_id]['#hierarchy'] = !empty($this->handler->options['hierarchy']);
    $form[$field_id]['#theme'] = 'az_finder_widget';
    $form[$field_id]['#type'] = 'checkboxes';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['help'] = ['#markup' => $this->t('This widget allows you to use the Finder widget for hierarchical taxonomy terms.')];
    unset($form['advanced']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []): bool {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    return $filter instanceof TaxonomyIndexTid;
  }

  /**
   * Preprocesses variables for the az-finder-widget template.
   *
   * @param array &$variables
   *   An associative array containing the element being processed (passed by
   *   reference).
   */
  public function preprocessAzFinderTaxonomyIndexTidWidget(array &$variables): void {
    $element = $variables['element'];
    $variables += [
      'wrapper_attributes' => new Attribute(),
      'children' => Element::children($element),
      'attributes' => ['name' => $element['#name']],
    ];
    $variables['is_nested'] = TRUE;
    $variables['depth'] = [];
    $element = $variables['element'];

    // Retrieve view_id and display_id from the element's #context.
    $view_id = $element['#context']['#view_id'];
    $display_id = $element['#context']['#display_id'];

    // Load the view entity and get the display options.
    $view_storage = $this->entityTypeManager->getStorage('view');
    $view = $view_storage->load($view_id);
    if ($view) {
      $display_options = $view->get('display')[$display_id]['display_options'] ?? [];
      // Check if 'filters' is set in display-specific options.
      if (empty($display_options['filters']) && isset($view->get('display')['default']['display_options']['filters'])) {
        $default_filters = $view->get('display')['default']['display_options']['filters'];
        $display_options['filters'] = $default_filters;
      }
    }
    else {
      $this->logger->error('Unable to load view: @view_id', ['@view_id' => $view_id]);
      return;
    }

    // Get the handler options for taxonomy reference fields.
    $vid = NULL;
    if (isset($display_options['filters'])) {
      foreach ($display_options['filters'] as $filter) {
        if ($filter['plugin_id'] === 'taxonomy_index_tid') {
          $vid = $filter['vid'];
          break;
        }
      }
    }
    if (!$vid) {
      $this->logger->error('Unable to find vocabulary ID (vid) in handler options.');
      return;
    }

    // Load override settings.
    $overrides = $this->getOverrideConfigurations($view_id, $display_id);
    $state_overrides = [];
    // Create a flat array of the overrides by term id.
    foreach ($overrides as $vid => $override) {
      $state_overrides += $override['state_overrides'] ?? [];
    }
    $variables['overrides'] = $state_overrides;
    // Load global default settings.
    $global_settings = $this->configFactory->get('az_finder.settings');
    $global_default_state = $global_settings->get('tid_widget.default_state') ?? '';
    foreach ($variables['children'] as $child) {
      if ($child === 'All') {
        // Special handling for "All" option.
        $variables['depth'][$child] = 0;
        continue;
      }

      $entity_type = 'taxonomy_term';
      $entity_id = is_numeric($child) ? $child : str_replace('tid:', '', $child);
      $state = $state_overrides[$entity_id] ?? $global_default_state;
      $variables['element'][$child]['#state'] = $state;
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $children = method_exists($entity_storage, 'loadChildren') ? $entity_storage->loadChildren($entity_id) : [];
      $original_title = $element[$child]['#title'];
      if (empty($original_title)) {
        continue;
      }
      $cleaned_title = ltrim($original_title, '-');
      $list_title = [
        '#type' => 'html_tag',
      ];

      // Determine if the child has sub-elements (actual children).
      // Calculate depth based on hyphens in the title as a proxy for hierarchy.
      $depth = strlen($original_title) - strlen($cleaned_title);
      $list_title['#value'] = $cleaned_title;

      // Decide which icon to use based on depth and state.
      $icons = $this->azFinderIcons->generateSvgIcons();
      $level_0_collapse_icon = $icons['level_0_collapse'];
      $level_0_expand_icon = $icons['level_0_expand'];
      $level_1_collapse_icon = $icons['level_1_collapse'];
      $level_1_expand_icon = $icons['level_1_expand'];

      if ($depth === 0) {
        // Use level 0 icons.
        $collapse_icon = $level_0_collapse_icon;
        $expand_icon = $level_0_expand_icon;
      }
      else {
        // Use level 1 icons.
        $collapse_icon = $level_1_collapse_icon;
        $expand_icon = $level_1_expand_icon;
      }

      // Select the icon based on the state if it is expand or collapse.
      if ($state === 'expand') {
        // Use collapse icon when expanded.
        $icon = $collapse_icon;
      }
      elseif ($state === 'collapse') {
        // Use expand icon when collapsed.
        $icon = $expand_icon;
      }
      else {
        // Do not set an icon for other states.
        $icon = NULL;
      }

      $variables['depth'][$child] = $depth;
      $list_title['#value'] = $cleaned_title;
      $variables['element'][$child]['#title'] = $list_title['#value'];
      if (!empty($children)) {
        $list_title_link = [
          '#state' => $state,
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#attributes' => [
            'class' => [],
          ],
        ];

        $collapse_id = 'collapse-az-finder-' . $entity_id;
        $list_title_link['#attributes']['data-bs-toggle'] = 'collapse';
        $list_title_link['#attributes']['href'] = '#' . $collapse_id;
        $list_title_link['#attributes']['class'][] = 'd-block';
        $list_title_link['#attributes']['role'] = 'button';
        if ($state === 'expand') {
          $list_title_link['#attributes']['aria-expanded'] = 'true';
        }
        else {
          $list_title_link['#attributes']['aria-expanded'] = 'false';
          $list_title_link['#attributes']['class'][] = 'collapsed';
        }
        $list_title_link['#attributes']['aria-controls'] = $collapse_id;
        $list_title_link['#attributes']['data-collapse-id'] = $collapse_id;
        $list_title_link['#attributes']['class'][] = 'collapser';
        $list_title_link['#attributes']['class'][] = 'level-' . $depth;
        $list_title_link['#attributes']['class'][] = 'text-decoration-none';
        if ($icon !== NULL) {
          $list_title['icon'] = $icon;
        }
        if ($depth === 0) {
          $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-0';
          $list_title['#tag'] = 'h3';
          $list_title['#attributes']['class'][] = 'text-azurite';
          $list_title['#attributes']['class'][] = 'my-0';
          $list_title['#attributes']['class'][] = 'd-flex';
          $list_title['#attributes']['class'][] = 'align-items-center';
        }
        else {
          $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-1';
          $list_title['#tag'] = "h" . ($depth + 3);
          $list_title['#attributes']['class'][] = 'text-body';
          $list_title['#attributes']['class'][] = 'fs-6';
          $list_title['#attributes']['class'][] = 'd-flex';
          $list_title['#attributes']['class'][] = 'flex-row-reverse';
          $list_title['#attributes']['class'][] = 'justify-content-end';
          $list_title['#attributes']['class'][] = 'align-items-center';
          $list_title['#attributes']['class'][] = 'mt-0';
        }
        $list_title_link['value'] = $list_title;
        // Apply the modified list title to the element.
        $variables['element'][$child] = $list_title_link;
      }
    }
  }

  /**
   * Calculate the depth of the option.
   *
   * @param mixed $option
   *   The option to calculate the depth for.
   *
   * @return int
   *   The depth of the option.
   */
  protected function calculateDepth($option): int {
    // Initialize depth.
    $depth = 0;
    // Ensure $option is a string before processing.
    $label = is_object($option) ? (property_exists($option, 'label') ? $option->label : '') : $option;
    // Use a loop or string function to count leading hyphens in the label.
    while (isset($label[$depth]) && $label[$depth] === '-') {
      $depth++;
    }

    return $depth;
  }

  /**
   * Determines the accessible title for the action based on depth.
   *
   * @param string $action
   *   Action type ('expand' or 'collapse').
   * @param int $depth
   *   Depth of the item, affecting the text.
   *
   * @return string|null
   *   Accessible title for the specified action, or NULL if not found.
   */
  protected function getAccessibleActionTitle($action, $depth): ?string {
    // Validate action and depth are within expected range/values.
    if (!in_array($action, ['expand', 'collapse']) || !in_array($depth, [0, 1])) {
      return NULL;
    }

    // Directly construct and return the title.
    // Adjusting depth to match level naming convention.
    $level = $depth + 1;
    return ucfirst($action) . " level $level";
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $default_states = [];
    $values = $form_state->getValue('default_states');
    foreach ($values as $tid => $state) {
      $default_states[$tid] = $state;
    }

    $this->configuration['default_states'] = $default_states;
  }

  /**
   * Get override configurations.
   */
  public function getOverrideConfigurations($view_id, $display_id) {
    $config_key = "$view_id.$display_id";
    if (!isset(self::$overrides[$config_key])) {
      $config_name = "az_finder.tid_widget.$view_id.$display_id";
      $config = $this->configFactory->getEditable($config_name) ?? NULL;
      $overrides = [];
      if ($config) {
        $vocabularies = $config->get('vocabularies') ?? [];
        foreach ($vocabularies as $vocabulary_id => $vocabulary) {
          $terms = $vocabulary['terms'];
          foreach ($terms as $term_id => $override) {
            if (!empty($override['default_state'])) {
              $overrides[$vocabulary_id]['state_overrides'][$term_id] = $override['default_state'];
            }
          }
        }
      }
      self::$overrides[$config_key] = $overrides;
    }
    return self::$overrides[$config_key];
  }

}
