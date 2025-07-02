<?php

namespace Drupal\ctools_views\Plugin\Display;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block as CoreBlock;
use Drupal\views\Plugin\views\HandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Block display plugin.
 *
 * Allows for greater control over Views block settings.
 */
class Block extends CoreBlock {

  /**
   * The views filter plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $filterManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /**
     * @var \Drupal\ctools_views\Plugin\Display\Block
     */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->filterManager = $container->get('plugin.manager.views.filter');
    $instance->request = $container->get('request_stack')->getCurrentRequest();

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);
    $settings['exposed'] = [];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    $filtered_allow = array_filter($this->getOption('allow'));
    $filter_options = [
      'items_per_page' => $this->t('Items per page'),
      'offset' => $this->t('Pager offset'),
      'pager' => $this->t('Pager type'),
      'hide_fields' => $this->t('Hide fields'),
      'sort_fields' => $this->t('Reorder fields'),
      'configure_filters' => $this->t('Configure filters'),
      'disable_filters' => $this->t('Disable filters'),
      'configure_sorts' => $this->t('Configure sorts'),
    ];
    $filter_intersect = array_intersect_key($filter_options, $filtered_allow);

    $options['allow'] = [
      'category' => 'block',
      'title' => $this->t('Allow settings'),
      'value' => empty($filtered_allow) ? $this->t('None') : implode(', ', $filter_intersect),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['allow']['#options']['offset'] = $this->t('Pager offset');
    $form['allow']['#options']['pager'] = $this->t('Pager type');
    $form['allow']['#options']['hide_fields'] = $this->t('Hide fields');
    $form['allow']['#options']['sort_fields'] = $this->t('Reorder fields');
    $form['allow']['#options']['configure_filters'] = $this->t('Configure filters');
    $form['allow']['#options']['disable_filters'] = $this->t('Disable filters');
    $form['allow']['#options']['configure_sorts'] = $this->t('Configure sorts');

    $defaults = [];
    if (!empty($form['allow']['#default_value'])) {
      $defaults = array_filter($form['allow']['#default_value']);
      if (!empty($defaults['items_per_page'])) {
        $defaults['items_per_page'] = 'items_per_page';
      }
    }

    $form['allow']['#default_value'] = $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $allow_settings = array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();

    // Modify "Items per page" block settings form.
    if (!empty($allow_settings['items_per_page'])) {
      // Items per page.
      $form['override']['items_per_page']['#type'] = 'number';
      $form['override']['items_per_page']['#min'] = 0;
      unset($form['override']['items_per_page']['#options']);
    }

    // Provide "Pager offset" block settings form.
    if (!empty($allow_settings['offset'])) {
      $form['override']['pager_offset'] = [
        '#type' => 'number',
        '#title' => $this->t('Pager offset'),
        '#default_value' => $block_configuration['pager_offset'] ?? 0,
        '#description' => $this->t('For example, set this to 3 and the first 3 items will not be displayed.'),
      ];
    }

    // Provide "Pager type" block settings form.
    if (!empty($allow_settings['pager'])) {
      $pager_options = [
        'view' => $this->t('Inherit from view'),
        'some' => $this->t('Display a specified number of items'),
        'none' => $this->t('Display all items'),
      ];
      $form['override']['pager'] = [
        '#type' => 'radios',
        '#title' => $this->t('Pager'),
        '#options' => $pager_options,
        '#default_value' => $block_configuration['pager'] ?? 'view',
      ];
    }

    // Provide "Hide fields" / "Reorder fields" block settings form.
    if (!empty($allow_settings['hide_fields']) || !empty($allow_settings['sort_fields'])) {
      // Set up the configuration table for hiding / sorting fields.
      $fields = $this->getHandlers('field');
      $header = [];
      if (!empty($allow_settings['hide_fields'])) {
        $header['hide'] = $this->t('Hide');
      }
      $header['label'] = $this->t('Label');
      if (!empty($allow_settings['sort_fields'])) {
        $header['weight'] = $this->t('Weight');
      }
      $form['override']['order_fields'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => [],
      ];
      if (!empty($allow_settings['sort_fields'])) {
        $form['override']['order_fields']['#tabledrag'] = [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'field-weight',
          ],
        ];
        $form['override']['order_fields']['#attributes'] = ['id' => 'order-fields'];
      }

      // Sort available field plugins by their currently configured weight.
      $sorted_fields = [];
      if (!empty($allow_settings['sort_fields']) && isset($block_configuration['fields'])) {
        uasort($block_configuration['fields'], '\Drupal\ctools_views\Plugin\Display\Block::sortFieldsByWeight');
        foreach (array_keys($block_configuration['fields']) as $field_name) {
          if (!empty($fields[$field_name])) {
            $sorted_fields[$field_name] = $fields[$field_name];
            unset($fields[$field_name]);
          }
        }
        if (!empty($fields)) {
          foreach ($fields as $field_name => $field_info) {
            $sorted_fields[$field_name] = $field_info;
          }
        }
      }
      else {
        $sorted_fields = $fields;
      }

      // Add each field to the configuration table.
      foreach ($sorted_fields as $field_name => $plugin) {
        $field_label = $plugin->adminLabel();
        if (!empty($plugin->options['label'])) {
          $field_label .= ' (' . $plugin->options['label'] . ')';
        }
        if (!empty($allow_settings['sort_fields'])) {
          $form['override']['order_fields'][$field_name]['#attributes']['class'][] = 'draggable';
        }
        $form['override']['order_fields'][$field_name]['#weight'] = !empty($block_configuration['fields'][$field_name]['weight']) ? $block_configuration['fields'][$field_name]['weight'] : 0;
        if (!empty($allow_settings['hide_fields'])) {
          $form['override']['order_fields'][$field_name]['hide'] = [
            '#type' => 'checkbox',
            '#default_value' => !empty($block_configuration['fields'][$field_name]['hide']) ? $block_configuration['fields'][$field_name]['hide'] : 0,
          ];
        }
        $form['override']['order_fields'][$field_name]['label'] = [
          '#markup' => $field_label,
        ];
        if (!empty($allow_settings['sort_fields'])) {
          $form['override']['order_fields'][$field_name]['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $field_label]),
            '#title_display' => 'invisible',
            '#delta' => 50,
            '#default_value' => !empty($block_configuration['fields'][$field_name]['weight']) ? $block_configuration['fields'][$field_name]['weight'] : 0,
            '#attributes' => ['class' => ['field-weight']],
          ];
        }
      }
    }

    // Provide "Configure filters" form elements.
    if (!empty($allow_settings['configure_filters'])) {
      $view_exposed_input = $block_configuration['exposed'];
      foreach ($block_configuration['exposed'] as $inner_input) {
        $view_exposed_input += $inner_input;
      }
      $this->view->setExposedInput($view_exposed_input);
      $exposed_form_state = new FormState();
      $exposed_form_state->setValidationEnforced();
      $exposed_form_state->set('view', $this->view);
      $exposed_form_state->set('display', $this->view->current_display);

      $exposed_form_state->setUserInput($this->view->getExposedInput());

      // Let form plugins know this is for exposed widgets.
      $exposed_form_state->set('exposed', TRUE);
      $exposed_form = [];
      $exposed_form['#info'] = [];

      // Initialize filter and sort handlers so that the exposed form alter
      // method works as expected.
      $this->view->filter = $this->getHandlers('filter');
      $this->view->sort = $this->getHandlers('sort');

      $form['exposed'] = [
        '#tree' => TRUE,
        '#title' => $this->t('Exposed filter values'),
        '#description' => $this->t('If a value is set for an exposed filter, it will be removed from the block display.'),
        '#type' => 'details',
        '#open' => TRUE,
      ];

      // Go through each handler and let it generate its exposed widget.
      /** @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handler */
      foreach ($this->view->getDisplay()->getHandlers('filter') as $id => $handler) {
        // If the current handler is exposed...
        if ($handler->canExpose() && $handler->isExposed()) {
          $filter_key = "filter-$id";
          // Create a panel for the exposed handler.
          $form['exposed'][$filter_key] = [
            '#type' => 'item',
            '#id' => Html::getUniqueId('views-exposed-pane'),
          ];

          $info = $handler->exposedInfo();

          // @todo This can result in double titles for group filters.
          if (!empty($info['label'])) {
            $form['exposed'][$filter_key]['#title'] = $info['label'];
          }

          // If the current filter has a value saved in block configuration...
          if (isset($block_configuration['exposed'][$filter_key])) {
            $identifier = $handler->options['expose']['identifier'];
            $this->mapConfigToHandler($handler, $block_configuration['exposed'][$filter_key]);
          }

          // Grouped exposed filters have their own forms. Instead of rendering
          // the standard exposed form, a new Select or Radio form field is
          // rendered with the available groups. When a user chooses an option
          // the selected value is split into the operator and value that the
          // item represents.
          if ($handler->isAGroup()) {
            $handler->groupForm($form['exposed'][$filter_key], $exposed_form_state);
            $id = $handler->options['group_info']['identifier'];
          }
          else {
            $handler->buildExposedForm($form['exposed'][$filter_key], $exposed_form_state);

            $form_field_present = isset($form['exposed'][$filter_key][$id]);
            $block_config_present = isset($block_configuration['exposed'][$filter_key]);

            $form_field_type = $form_field_present ? $form['exposed'][$filter_key][$id]['#type'] : FALSE;
            $filter_plugin_id = $block_config_present ? $block_configuration['exposed'][$filter_key]['plugin_id'] : FALSE;
            if ($form_field_present && $block_config_present) {

              if ($form_field_type == 'select') {
                // Single-value select elements get their default value set to
                // 'All' in buildExposedForm(), when that option is added, so set
                // thir defaults manually.
                $form['exposed'][$filter_key][$id]['#default_value'] = $block_configuration['exposed'][$filter_key]['value'] ?? NULL;
              }

              else if ($form_field_type =='entity_autocomplete' && $filter_plugin_id == 'taxonomy_index_tid') {
                // Entity reference autocomplete fields need their values
                // converted back to a string for the textfield input.
                $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($block_configuration['exposed'][$filter_key]['value']);
                $form['exposed'][$filter_key][$id]['#default_value'] = EntityAutocomplete::getEntityLabels($terms);
              }
            }
          }

          $form['exposed'][$filter_key]['exposed'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Expose filter value to user'),
            '#description' => $this->t('Expose this filter value to visitors? If so, the value set here will be the default.'),
            '#default_value' => $block_configuration['exposed'][$filter_key]['exposed'] ?? FALSE,
          ];

          $handler_use_operator = !empty($handler->options['expose']['use_operator']);

          // If ''use_operator' is tru on the handler, let the admin decide to
          // expose it to the user.
          if ($handler_use_operator) {
            $form['exposed'][$filter_key]['use_operator'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Expose filter operator to user'),
              '#description' => $this->t("Expose this filter's operator to visitors? If so, the operator set here will be the default."),
              '#default_value' => $block_configuration['exposed'][$filter_key]['expose']['use_operator'] ?? $handler_use_operator,
              '#states' => [
                // Hide the operator form element until the value is exposed.
                'invisible' => [
                  ':input[name="settings[exposed][' . $filter_key . '][exposed]"]' => ['checked' => FALSE],
                ],
              ],
            ];
          }

          if ($info) {
            $exposed_form['#info'][$filter_key] = $info;
          }
        }
      }

      // If there are no exposed filters, then we don't need the parent element.
      if (!count(Element::children($form['exposed']))) {
        unset($form['exposed']);
      }
    }

    if (!empty($allow_settings['disable_filters'])) {
      $filters = $this->getHandlers('filter');
      // Add a settings form for each exposed filter to configure or hide it.
      foreach ($filters as $filter_name => $plugin) {
        if ($plugin->isExposed() && $exposed_info = $plugin->exposedInfo()) {
          // Render "Disable filters" settings form.
          if (!empty($allow_settings['disable_filters'])) {
            $form['override']['filters'][$filter_name]['disable'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Disable filter: @handler', ['@handler' => $plugin->options['expose']['label']]),
              '#default_value' => !empty($block_configuration['filter'][$filter_name]['disable']) ? $block_configuration['filter'][$filter_name]['disable'] : 0,
            ];
          }
        }
      }
    }

    // Provide "Configure sorts" block settings form.
    if (!empty($allow_settings['configure_sorts'])) {
      $sorts = $this->getHandlers('sort');
      $options = [
        'ASC' => $this->t('Sort ascending'),
        'DESC' => $this->t('Sort descending'),
      ];
      foreach ($sorts as $sort_name => $plugin) {
        $form['override']['sort'][$sort_name] = [
          '#type' => 'details',
          '#title' => $plugin->adminLabel(),
        ];
        $form['override']['sort'][$sort_name]['plugin'] = [
          '#type' => 'value',
          '#value' => $plugin,
        ];
        $form['override']['sort'][$sort_name]['order'] = [
          '#title' => $this->t('Order'),
          '#type' => 'radios',
          '#options' => $options,
          '#default_value' => $plugin->options['order'],
        ];

        // Set default values for sorts for this block.
        if (!empty($block_configuration["sort"][$sort_name])) {
          $form['override']['sort'][$sort_name]['order']['#default_value'] = $block_configuration["sort"][$sort_name];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate(ViewsBlock $block, array $form, FormStateInterface $form_state) {
    // Checkout validateOptionsForm on filters before saving this.
    if ($form_state->hasValue('exposed')) {
      $handlers = $this->view->getDisplay()->getHandlers('filter');
      $values = $form_state->getValue('exposed');

      foreach ($handlers as $key => $handler) {

        if ($handler->isExposed()) {
          $identifier = $handler->options['expose']['identifier'];
          $handler_form_state = new FormState();
          $handler_form_state->setValues($values);
          $handler->validateExposed($form, $handler_form_state);

          /*
           * Select list doesn't change validated_exposed_input value
           * when "All" is selected.
           */
          // @todo Determine whether this is actually necessary or not.
          if (isset($handler->options['type']) && $handler->options['type'] === 'select') {
            if ($values['filter-' . $key][$identifier] === 'All') {
              $handler->validated_exposed_input = NULL;
            }
          }
          foreach ($handler_form_state->getErrors() as $name => $message) {
            $form_state->setErrorByName($name, $message);
          }
          // Overwrite the value with its validated counterpart, if one exists.
          if (property_exists($handler, 'validated_exposed_input')) {
            $form_state->setValue(['exposed', $key], [$identifier => $handler->validated_exposed_input]);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    // Set default value for items_per_page if left blank.
    if (empty($form_state->getValue(['override', 'items_per_page']))) {
      $form_state->setValue(['override', 'items_per_page'], "none");
    }

    parent::blockSubmit($block, $form, $form_state);
    $configuration = $block->getConfiguration();
    $allow_settings = array_filter($this->getOption('allow'));

    // Save "Pager type" settings to block configuration.
    if (!empty($allow_settings['pager'])) {
      if ($pager = $form_state->getValue(['override', 'pager'])) {
        $configuration['pager'] = $pager;
      }
    }

    // Save "Pager offset" settings to block configuration.
    if (!empty($allow_settings['offset'])) {
      $configuration['pager_offset'] = $form_state->getValue([
        'override',
        'pager_offset',
      ]);
    }

    // Save "Hide fields" / "Reorder fields" settings to block configuration.
    if (!empty($allow_settings['hide_fields']) || !empty($allow_settings['sort_fields'])) {
      if ($fields = array_filter($form_state->getValue([
        'override',
        'order_fields',
      ]))) {
        uasort($fields, '\Drupal\ctools_views\Plugin\Display\Block::sortFieldsByWeight');
        $configuration['fields'] = $fields;
      }
    }

    // Save "Configure filters" / "Disable filters" settings to block
    // configuration.
    if (!empty($allow_settings['configure_filters'])) {
      // Store the validated and raw exposed filters.
      $handlers = $this->view->getDisplay()->getHandlers('filter');

      // Map form values back to views_field type data.
      $values = $form_state->getValue('exposed');
      foreach ($handlers as $key => $handler) {
        if ($handler->canExpose() && $handler->isExposed()) {
          $identifier = $handler->options['expose']['identifier'];
          $config_key = "filter-{$key}";

          if (isset($values[$config_key])) {
            // Save values from generated form/values array, which may or may
            // not use a wrapper.
            $wrapper = !empty($values[$config_key][$identifier . '_wrapper']) ? $identifier . '_wrapper' : FALSE;
            if ($wrapper) {
              $configuration['exposed'][$config_key] = $this->extractExposedValues(
                $handler,
                $form['settings']['exposed'][$config_key][$wrapper],
                $values[$config_key][$wrapper]
              );
            }
            else {
              $configuration['exposed'][$config_key] = $this->extractExposedValues(
                $handler,
                $form['settings']['exposed'][$config_key],
                $values[$config_key]
              );
            }

            $configuration['exposed'][$config_key]['plugin_id'] = $handler->getPluginId();
            $configuration['exposed'][$config_key]['exposed'] = $values[$config_key]['exposed'];
            if ($values[$config_key]['exposed'] && isset($values[$config_key]['use_operator'])) {
              $configuration['exposed'][$config_key]['expose']['use_operator'] = $values[$config_key]['use_operator'];
            }
            else {
              $configuration['exposed'][$config_key]['expose']['use_operator'] = FALSE;
            }
          }
        }
      }
    }
    unset($configuration['filter']);
    if (!empty($allow_settings['disable_filters'])) {
      if ($filters = $form_state->getValue(['override', 'filters'])) {
        foreach ($filters as $filter_name => $filter) {
          $disable = $filter['disable'];
          if ($disable) {
            $configuration['filter'][$filter_name]['disable'] = $disable;
          }
        }
      }
    }

    // Save "Configure sorts" settings to block configuration.
    if (!empty($allow_settings['configure_sorts'])) {
      $sorts = $form_state->getValue(['override', 'sort']);
      foreach ($sorts as $sort_name => $sort) {
        $plugin = $sort['plugin'];
        // Check if we want to override the default sort order.
        if ($plugin->options['order'] != $sort['order']) {
          $configuration['sort'][$sort_name] = $sort['order'];
        }
      }
    }

    $block->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);

    $allow_settings = array_filter($this->getOption('allow'));
    $config = $block->getConfiguration();
    [, $display_id] = explode('-', $block->getDerivativeId(), 2);

    // Change pager offset settings based on block configuration.
    if (!empty($allow_settings['offset']) && isset($config['pager_offset'])) {
      $this->view->setOffset($config['pager_offset']);
    }

    // Change pager style settings based on block configuration.
    if (!empty($allow_settings['pager'])) {
      $pager = $this->view->display_handler->getOption('pager');
      if (!empty($config['pager']) && $config['pager'] != 'view') {
        $pager['type'] = $config['pager'];
      }
      $this->view->display_handler->setOption('pager', $pager);
    }

    // Change fields output based on block configuration.
    if (!empty($allow_settings['hide_fields']) || !empty($allow_settings['sort_fields'])) {
      if (!empty($config['fields']) && $this->view->getStyle()->usesFields()) {
        $fields = $this->view->getHandlers('field');
        uasort($config['fields'], '\Drupal\ctools_views\Plugin\Display\Block::sortFieldsByWeight');
        $iterate_fields = !empty($allow_settings['sort_fields']) ? $config['fields'] : $fields;
        foreach (array_keys($iterate_fields) as $field_name) {
          // Remove each field in sequence and re-add them to sort
          // appropriately or hide if disabled.
          $this->view->removeHandler($display_id, 'field', $field_name);
          if (empty($allow_settings['hide_fields']) || (!empty($allow_settings['hide_fields']) && empty($config['fields'][$field_name]['hide']))) {
            $this->view->addHandler($display_id, 'field', $fields[$field_name]['table'], $fields[$field_name]['field'], $fields[$field_name], $field_name);
          }
        }
      }
    }

    // Change filters output based on block configuration.
    if (!empty($allow_settings['disable_filters'])) {
      $filters = $this->view->getHandlers('filter', $display_id);
      foreach ($filters as $filter_name => $filter) {
        // If we allow disabled filters and this filter is disabled, disable it
        // and continue.
        if (!empty($allow_settings['disable_filters']) && !empty($config["filter"][$filter_name]['disable'])) {
          $this->view->removeHandler($display_id, 'filter', $filter_name);
          // We don't want to needlessly set filter options later.
          unset($config['exposed']['filter-' . $filter_name]);
        }
      }
    }

    // Set an exposed filter value and remove it from the display if set in the
    // block configuration.
    if (!empty($allow_settings['configure_filters'])) {
      // Fetch the current view's exposed filter input from the client request.
      $exposed = $this->view->getExposedInput();
      $denylisted_keys = [
        'form_id',
      ];
      // Filter the exposed filter input to contain only expected keys.
      $exposed = array_filter($exposed, function ($key) use ($denylisted_keys) {
        // Filter out certain keys not related to exposed filters that are
        // known to cause issues.
        return !in_array($key, $denylisted_keys, TRUE);
      }, ARRAY_FILTER_USE_KEY);

      // Loop over the exposed filter settings in the block configuration.
      foreach ($config['exposed'] as $key => $value) {
        // Load the handler related to the exposed filter.
        [$handler_type, $handler_name] = explode('-', $key, 2);
        $handler = $this->view->getDisplay()->getHandler($handler_type, $handler_name);

        // Set exposed filter input directly where they were entered in the
        // block configuration. Otherwise only set them if they haven't been set
        // already.
        if ($handler) {
          if ($handler->isAGroup()) {
            $this->mapConfigToHandler($handler, $value);
          }
          else {
            // Access values with the identifier.
            $identifier = $handler->options['expose']['identifier'];

            // If the value is set from exposed form input, keep it.
            if (!isset($exposed[$identifier])) {
              // Entity reference autocomplete fields need their values
              // submitted either as an array of entities or of arrays in the
              // form ['target_id' => $id].
              if (isset($config['exposed'][$key]['type']) && $config['exposed'][$key]['type'] == 'entity_autocomplete') {
                $exposed[$identifier] = array_map(function ($item) {
                  return ['target_id' => $item];
                }, $value['value']);
              }

              // If a value with a nested 'value' key is present, un-nest it.
              elseif (isset($value['value']['value'])) {
                $exposed[$identifier] = $value['value']['value'];
              }
              else {
                $exposed[$identifier] = $value['value'] ?? NULL;
              }
            }

            // If the operator is set from exposed form input, keep it.
            if ($handler->options['expose']['use_operator']) {
              $operator_id = $handler->options['expose']['operator_id'];
              if (!isset($exposed[$operator_id])) {
                $exposed[$operator_id] = $value['operator'];
              }
            }
          }

          // If the filter is exposed, set a variable to pass that through.
          if (isset($config['exposed'][$key]['exposed']) && $config['exposed'][$key]['exposed']) {
            $handler->options['value_exposed_to_user'] = TRUE;

            // If the operator is exposed, set a variable to pass that through.
            if ($config['exposed'][$key]['expose']['use_operator']) {
              $handler->options['operator_exposed_to_user'] = TRUE;
            }
          }
        }
      }

      // Set the updated exposed filter input array on the View.
      $this->view->setExposedInput($exposed);
    }

    // Change sorts based on block configuration.
    if (!empty($allow_settings['configure_sorts'])) {
      $sorts = $this->view->getHandlers('sort', $display_id);
      foreach ($sorts as $sort_name => $sort) {
        if (!empty($config["sort"][$sort_name])) {
          $sort['order'] = $config["sort"][$sort_name];
          $this->view->setHandler($display_id, 'sort', $sort_name, $sort);
        }
      }
    }
  }

  /**
   * Filter options value.
   */
  protected function getFilterOptionsValue(array $filter, array $config) {
    $plugin_definition = $this->filterManager->getDefinition($config['type']);
    if (is_subclass_of($plugin_definition['class'], '\Drupal\views\Plugin\views\filter\InOperator')) {
      return array_values($config['value']);
    }
    return $config['value'][$filter['expose']['identifier']];
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    $filters = $this->getHandlers('filter');
    foreach ($filters as $filter) {
      if ($filter->isExposed() && !empty($filter->exposedInfo())) {
        return TRUE;
      }
    }

    return parent::usesExposed();
  }

  /**
   * Exposed widgets.
   *
   * Exposed widgets typically only work with ajax in Drupal core, however
   * #2605218 totally breaks the rest of the functionality in this display and
   * in Core's Block display as well, so we allow non-ajax block views to use
   * exposed filters and manually set the #action to the current request uri.
   */
  public function elementPreRender(array $element) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $element['#view'];

    // Exposed widgets typically only work with Ajax in core, but #2605218
    // breaks the rest of the functionality in this display and in the core
    // Block display as well. We allow non-Ajax block views to use exposed
    // filters by manually setting the #action to the current request URI.
    if (!empty($view->exposed_widgets['#action']) && !$view->ajaxEnabled()) {
      $view->exposed_widgets['#action'] = $this->request->getRequestUri();
    }

    // Allow the parent pre-render function to set the #exposed array on the
    // element. This allows us to bypass hiding widgets if the array is emptied.
    $element = parent::elementPreRender($element);

    // Loop over the filters on the current View looking for exposed filters
    // whose values have been derived from block configuration.
    if (!empty($element['#exposed'])) {
      $allow_settings = array_filter($this->getOption('allow'));
      if (!empty($allow_settings['configure_filters'])) {
        foreach ($view->getDisplay()->getHandlers('filter') as $id => $handler) {
          /** @var \Drupal\views\Plugin\views\Filter\FilterPluginBase $handler */
          // If the current handler meets the conditions, hide its exposed
          // widget.
          if ($handler->canExpose() && $handler->isExposed()) {

            $value_exposed = $handler->options['value_exposed_to_user'] ?? FALSE;
            $operator_exposed = $handler->options['operator_exposed_to_user'] ?? FALSE;

            if ($handler->isAGroup()) {
              $identifier = $handler->options['group_info']['identifier'];
              $operator_id = FALSE;
            }
            else {
              $identifier = $handler->options['expose']['identifier'] ?? FALSE;
              $operator_id = $handler->options['expose']['use_operator'] ? $handler->options['expose']['operator_id'] : FALSE;
            }

            // If a wrapper is being used, store its key for later use.
            $wrapper = !empty($element['#exposed'][$identifier . '_wrapper']) ? $identifier . '_wrapper' : FALSE;

            if ($wrapper) {
              $element['#exposed'][$wrapper][$identifier]['#access'] = $value_exposed;
              if ($operator_id) {
                $element['#exposed'][$wrapper][$operator_id]['#access'] = $operator_exposed;
              }
              if (!$value_exposed && !$operator_exposed) {
                $element['#exposed'][$wrapper]['#access'] = FALSE;
              }
            }
            else {
              $element['#exposed'][$identifier]['#access'] = $value_exposed;
              if ($operator_id) {
                $element['#exposed'][$operator_id]['#access'] = $operator_exposed;
              }
            }
          }
        }

        // If there are no accessible child elements in the #exposed array other
        // than the actions, reset it to an empty array.
        if (Element::getVisibleChildren($element['#exposed']) == ['actions']) {
          $element['#exposed'] = [];
        }
      }
    }

    return $element;
  }

  /**
   * Sort field config array by weight.
   *
   * @param int $a
   *   The field a.
   * @param int $b
   *   The field b.
   *
   * @return int
   *   Return the more weight
   */
  public static function sortFieldsByWeight($a, $b) {
    $a_weight = $a['weight'] ?? 0;
    $b_weight = $b['weight'] ?? 0;
    if ($a_weight == $b_weight) {
      return 0;
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Converts form input values to filter handler values.
   */
  protected function mapConfigToHandler(HandlerBase $handler, $input_value) {
    // Convert to the form expected by $handler methods.
    if ($handler->isAGroup()) {
      $identifier = $handler->options['group_info']['identifier'];
      $is_multiple = $handler->multipleExposedInput();
      $value_key = $is_multiple ? 'default_group_multiple' : 'default_group';
      $v = $input_value['group_info'][$value_key] ?? NULL;
      $value = [$identifier => $v];

      $handler->group_info = $value[$identifier];
      $handler->options['group_info'][$value_key] = $handler->group_info;
    }
    else {
      $identifier = $handler->options['expose']['identifier'];
      $use_operator = !empty($handler->options['expose']['use_operator']);

      // The value passed to the handler may need defaults that are not
      // passed with the input values, so we have to attempt to merge the
      // expected values on the plugin before overwriting them.
      if (isset($input_value['value']) && is_array($input_value['value']) && is_array($handler->options['value'])) {
        $value = [$identifier => $input_value['value'] + $handler->options['value']];
      }
      elseif (isset($input_value['value']) && is_string($input_value['value']) && is_array($handler->options['value'])) {
        $value = [$identifier => ['value' => $input_value['value']] + $handler->options['value']];
      }
      else {
        $value = [$identifier => $input_value['value'] ?? NULL];
      }

      if ($use_operator) {
        $operator_id = $handler->options['expose']['operator_id'];
        $value[$operator_id] = $input_value['operator'];
      }

      $handler->value = $value[$identifier];
      $handler->options['value'] = $handler->value;
      if ($use_operator) {
        $handler->operator = $value[$operator_id];
        $handler->options['operator'] = $handler->operator;
      }
    }
  }

  /**
   * Extract values/operators from a given exposed form/values array element.
   */
  protected function extractExposedValues($handler, $form_element, $values_element) {
    $configuration = [];
    $identifier = $handler->options['expose']['identifier'];

    // If this is an entity_autocomplete field, we need to pull out
    // the target_id for each value in order to match our schema.
    $element_type = $form_element[$identifier]['#type'] ?? FALSE;
    if ($element_type == 'entity_autocomplete') {
      $configuration['type'] = $element_type;
      $configuration['value'] = [];
      if (isset($values_element[$identifier]) && is_array($values_element[$identifier])) {
        $configuration['value'] = array_map(function ($item) {
          return $item['target_id'];
        }, $values_element[$identifier]);
      }
    }

    // Grouped filters store values at a different key,
    // which we match, to match the handler's schema.
    elseif ($handler->isAGroup()) {
      $is_multiple = $handler->multipleExposedInput();
      $value_key = $is_multiple ? 'default_group_multiple' : 'default_group';
      if ($is_multiple) {
        $configuration['group_info'][$value_key] = array_filter($values_element[$identifier]);
      }
      else {
        $configuration['group_info'][$value_key] = $values_element[$identifier];
      }
    }

    // Single value select list will provide a string value, but we
    // should save it as an array if the view does.
    /* elseif (is_array($handler->value)) { */
    elseif (isset($handler->options['type'])
      && $handler->options['type'] === 'select'
      || $handler->options['plugin_id'] == 'list_field') {
      if (!is_array($values_element[$identifier])) {
        $value = $values_element[$identifier];
        // Values of 'All' should save as an empty array, like in views.
        if (!$handler->options['expose']['required'] && $value == 'All') {
          $configuration['value'] = [];
        }
        else {
          $configuration['value'] = [$value => $value];
        }
      }
      else {
        $configuration['value'] = $values_element[$identifier];
      }
    }
    else {
      $configuration['value'] = $values_element[$identifier];
    }

    // Save operator, if exposed and not grouped.
    if ($handler->options['expose']['use_operator'] && !$handler->isAGroup()) {
      $operator_id = $handler->options['expose']['operator_id'];
      $configuration['operator'] = $values_element[$operator_id];
    }

    return $configuration;
  }

  /**
   * Checks an exposed filter value array to see if it is non-empty and not All.
   *
   * @todo rename this function and document it more; it doesn't test validity.
   */
  protected function validValue($value) {
    $filter = $this->valueFilter($value);
    $not_all = $value != 'All';
    $not_empty_or_zero = (!empty($value) || (is_numeric($value) && (int) $value === 0));
    return ($filter && $not_all && $not_empty_or_zero);
  }

  /**
   * Filter a potential array of values to see if any are non-0 string lengths.
   */
  protected function valueFilter($value) {
    if (is_array($value)) {
      foreach ($value as $element) {
        // If any element returns non-0, we know all we need to.
        if ($test = $this->valueFilter($element)) {
          return $test;
        }
      }
    }
    else {
      return strlen($value);
    }
  }

}

