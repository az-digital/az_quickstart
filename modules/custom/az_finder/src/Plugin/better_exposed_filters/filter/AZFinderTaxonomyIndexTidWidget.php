<?php

declare(strict_types = 1);

namespace Drupal\az_finder\Plugin\better_exposed_filters\filter;

use Drupal\az_finder\AZFinderIcons;
use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The configuration for the plugin.
   *
   * @var array
   */
  protected $configuration;

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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The AZ Finder Icons service.
   *
   * @var \Drupal\az_finder\AZFinderIcons
   */
  protected $azFinderIcons;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\az_finder\AZFinderIcons $az_finder_icons
   *   The AZFinderIcons service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    AZFinderIcons $az_finder_icons,
    ConfigFactoryInterface $config_factory
  ) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->azFinderIcons = $az_finder_icons;
    $this->configFactory = $config_factory;
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
      $container->get('entity_type.manager'),
      $container->get('az_finder.icons'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'default_states' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): array {
    if (!$this->view instanceof ViewExecutable) {
      return $form;
    }
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

    $element_wrapper = $field_id . '_wrapper';

    $filter_elements = [
      $identifier,
      $element_wrapper,
      $filter->options['expose']['operator_id'],
    ];

    foreach ($filter_elements as $element) {
      if (empty($form[$element])) {
        continue;
      }

      $fields = ['min', 'max'];
      $wrapper_array = [];
      if ($element === $element_wrapper) {
        $wrapper_array = $form[$element];
        if (array_intersect($fields, array_keys($wrapper_array[$field_id]))) {
          $form[$element] = [
            '#type' => 'container',
            $element => $wrapper_array,
          ];
        }
      }
      else {
        if (array_intersect($fields, array_keys($form[$field_id]))) {
          $form[$element] = [
            '#type' => 'container',
            $element => $wrapper_array,
          ];
        }
      }

      $form[$element]['#title'] = $exposed_label;
      $form[$element]['#description'] = $exposed_description;
      $this->addContext($form[$element]);
    }

    if (!empty($form[$field_id])) {
      $this->setFormOptions($form, $field_id);
      $svg_icons = $this->azFinderIcons->generateSvgIcons();
      foreach ($svg_icons as $key => $icon) {
        $form['#attached']['drupalSettings']['azFinder']['icons'][$key] = $this->renderer->renderPlain($icon);
      }
      $form[$field_id]['#type'] = !empty($form[$field_id]['#multiple']) ? 'checkboxes' : 'radios';

      // Load override settings.
      $view_id = $this->view->storage->id();
      $display_id = $this->view->current_display;
      $form['#contextual_links']['az_finder.settings'] = [
        'route_parameters' => [
          'view' => $view_id,
          'display' => $display_id,
        ],
      ];
      $form['#contextual_links']['az_finder.contextual_links'] = [
        'route_parameters' => [
          'view' => $view_id,
          'display' => $display_id,
        ],
      ];

      $overrides = $this->getOverrideConfigurations($view_id, $display_id);

      foreach (Element::children($form[$field_id]) as $child) {
        $term_id = str_replace('tid:', '', $child);
        $default_state = $overrides[$term_id] ?? 'default';

        if ($default_state == 'collapse') {
          $form[$field_id][$child]['#attributes']['class'][] = 'accordion-close';
        }
        elseif ($default_state == 'expand') {
          $form[$field_id][$child]['#attributes']['class'][] = 'accordion-open';
        }
      }
    }

    return $form;
  }

  /**
   *
   */
  protected function getFieldId($filter): string {
    return $filter->options['is_grouped'] ? $filter->options['group_info']['identifier'] : $filter->options['expose']['identifier'];
  }

  /**
   *
   */
  protected function setFormOptions(array &$form, $field_id): array {
    $form[$field_id]['#options'] = !empty($form[$field_id]['#options']) ? BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']) : $form[$field_id]['#options'];
    $form[$field_id]['#hierarchy'] = !empty($this->handler->options['hierarchy']);
    $form[$field_id]['#theme'] = 'az_finder_widget';
    $form[$field_id]['#type'] = 'checkboxes';

    return $form;
  }

  /**
   *
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->configuration;
    $default_states = $config['default_states'] ?? [];
    $fallback_action = $config['fallback_action'] ?? 'hide';

    $parent_terms = $this->getParentTerms();

    $header = [
      $this->t('Parent Term'),
      $this->t('Default State'),
    ];

    $rows = [];

    foreach ($parent_terms as $parent_term) {
      $default_value = $default_states[$parent_term->id()] ?? 'collapsed';

      $rows[$parent_term->id()]['name'] = [
        'data' => ['#markup' => $parent_term->getName()],
      ];

      $rows[$parent_term->id()]['state'] = [
        'data' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Expanded by default'),
          '#default_value' => $default_value === 'expanded',
        ],
      ];
    }

    $form['default_states'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No parent terms found.'),
    ];

    $form['fallback_action'] = [
      '#type' => 'select',
      '#title' => $this->t('Fallback Action'),
      '#description' => $this->t('Action to take when a parent term is not found in the default states.'),
      '#options' => [
        'hide' => $this->t('Hide'),
        'expand' => $this->t('Expand'),
        'collapse' => $this->t('Collapse'),
        'disable' => $this->t('Disable'),
        'remove' => $this->t('Remove'),
      ],
      '#default_value' => $fallback_action,
    ];

    return $form;
  }

  /**
   *
   */
  protected function getParentTerms() {
    $vocabulary_id = $this->handler->options['vid'];

    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vocabulary_id);
    $query->condition('parent', 0);
    $query->accessCheck(TRUE);

    $tids = $query->execute();

    return Term::loadMultiple($tids);
  }

  /**
   *
   */
  public static function isApplicable($filter = NULL, array $filter_options = []) {
    return $filter instanceof TaxonomyIndexTid;
  }

  /**
   * Preprocesses variables for the az-finder-widget template.
   *
   * @param array &$variables
   *   An associative array containing the element being processed.
   */
  public function preprocessAzFinderTaxonomyIndexTidWidget(array &$variables) {
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

    // Retrieve view_id and display_id from the element's #context.
    $view_id = $element['#context']['#view_id'];
    $display_id = $element['#context']['#display_id'];

    // Load the view entity and get the display options.
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    $view = $view_storage->load($view_id);
    if ($view) {
      $display_options = $view->get('display')[$display_id]['display_options'] ?? [];
    }
    else {
      \Drupal::logger('az_finder')->error('Unable to load view: @view_id', ['@view_id' => $view_id]);
      return;
    }

    // Get the handler options for taxonomy reference fields.
    $vid = NULL;
    foreach ($display_options['filters'] as $filter) {
      if ($filter['plugin_id'] === 'taxonomy_index_tid') {
        $vid = $filter['vid'];
        break;
      }
    }

    if (!$vid) {
      \Drupal::logger('az_finder')->error('Unable to find vocabulary ID (vid) in handler options.');
      return;
    }

    // Load override settings.
    $overrides = $this->getOverrideConfigurations($view_id, $display_id);
    $state_overrides = $overrides[$vid]['state_overrides'] ?? [];

    // Load global default settings.
    $global_settings = $this->configFactory->get('az_finder.settings');
    $global_default_state = $global_settings->get('tid_widget.default_state') ?? 'default';

    foreach ($variables['children'] as $child) {
      if ($child === 'All') {
        $variables['depth'][$child] = 0;
        continue;
      }

      $entity_type = 'taxonomy_term';
      $entity_id = is_numeric($child) ? $child : str_replace('tid:', '', $child);
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $children = method_exists($entity_storage, 'loadChildren') ? $entity_storage->loadChildren($entity_id) : [];
      if (empty($children) && $entity_type !== 'taxonomy_term') {
        continue;
      }

      $original_title = $element[$child]['#title'];
      if (empty($original_title)) {
        continue;
      }
      $cleaned_title = ltrim($original_title, '-');
      $list_title = [
        '#type' => 'html_tag',
      ];
      $depth = strlen($original_title) - strlen($cleaned_title);
      $list_title['#value'] = $cleaned_title;

      $icons = $this->azFinderIcons->generateSvgIcons();
      $default_state = $state_overrides[$entity_id] ?? $global_default_state;
      $icon_name = $default_state === 'collapse' ? 'expand' : 'collapse';
      $icon = $icons['level_' . $depth . '_' . $icon_name];
      $variables['depth'][$child] = $depth;
      $list_title['#value'] = $cleaned_title;
      $variables['element'][$child]['#title'] = $list_title['#value'];
      // Apply override settings.
      $is_expanded = $default_state === 'collapse';


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
        $list_title_link['#attributes']['aria-expanded'] = $default_state === 'collapse' ? 'false' : 'true';
        $list_title_link['#attributes']['aria-controls'] = $collapse_id;
        $list_title_link['#attributes']['data-collapse-id'] = $collapse_id;
        $list_title_link['#attributes']['class'][] = 'collapser';
        $list_title_link['#attributes']['class'][] = 'level-' . $depth;
        $list_title_link['#attributes']['class'][] = 'text-decoration-none';
        $list_title['icon'] = $icon;
        // Apply the collapse or expand class and set the correct icon.
        if ($default_state === 'collapse') {
          $list_title_link['#attributes']['class'][] = 'accordion-close';
          $list_title_link['#attributes']['class'][] = 'collapsed';
        }
        elseif ($default_state == 'expand') {
          $list_title_link['#attributes']['class'][] = 'accordion-open';
        }
        if ($depth === 0) {
          $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-0';
          $list_title['#tag'] = 'h3';
          $list_title['#attributes']['class'][] = 'text-azurite';
          $list_title['#attributes']['class'][] = 'text-size-h5';
          $list_title['#attributes']['class'][] = 'm-0';
          $list_title['#attributes']['class'][] = 'd-flex';
          $list_title['#attributes']['class'][] = 'align-items-center';
        }
        else {
          $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-1';
          $list_title['#tag'] = "h" . ($depth + 3);
          $list_title['#attributes']['class'][] = 'text-body';
          $list_title['#attributes']['class'][] = 'text-size-h6';
          $list_title['#attributes']['class'][] = 'd-flex';
          $list_title['#attributes']['class'][] = 'flex-row-reverse';
          $list_title['#attributes']['class'][] = 'justify-content-end';
          $list_title['#attributes']['class'][] = 'align-items-center';
        }
        $list_title_link['value'] = $list_title;
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
   */
  protected function calculateDepth($option): int {
    $depth = 0;
    $optionLabel = is_object($option) ? (property_exists($option, 'label') ? $option->label : '') : $option;
    while (isset($optionLabel[$depth]) && $optionLabel[$depth] === '-') {
      $depth++;
    }

    return $depth;
  }

  /**
   *
   */
  protected function getAccessibleActionTitle($action, $depth): ?string {
    if (!in_array($action, ['expand', 'collapse']) || !in_array($depth, [0, 1])) {
      return NULL;
    }

    $level = $depth + 1;
    return ucfirst($action) . " level $level";
  }

  /**
   *
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $default_states = [];
    $values = $form_state->getValue('default_states');
    foreach ($values as $tid => $state) {
      $default_states[$tid] = $state;
    }

    $this->configuration['default_states'] = $default_states;
  }

  /**
   *
   */
  public function getOverrideConfigurations($view_id, $display_id) {
    $config_key = "$view_id.$display_id";
    if (!isset(self::$overrides[$config_key])) {
      $config_name = "az_finder.tid_widget.$view_id.$display_id";
      $config = $this->configFactory->getEditable($config_name);
      $overrides = [];
      if ($config) {
        $vocabularies = $config->get('vocabularies');
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
