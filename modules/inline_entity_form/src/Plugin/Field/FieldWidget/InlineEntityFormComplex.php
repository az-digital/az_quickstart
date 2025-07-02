<?php

namespace Drupal\inline_entity_form\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\TranslationHelper;
use Drupal\rat\v1\RenderArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Complex inline widget.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_complex",
 *   label = @Translation("Inline entity form - Complex"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions",
 *   },
 *   multiple_values = true
 * )
 */
class InlineEntityFormComplex extends InlineEntityFormBase implements ContainerFactoryPluginInterface {

  /**
   * Always keep referenced entity when the reference is removed.
   */
  const REMOVED_KEEP = 'keep';

  /**
   * Allow users to choose whether to delete an entity upon removing reference.
   */
  const REMOVED_OPTIONAL = 'optional';

  /**
   * Always delete referenced entity when the reference is removed.
   */
  const REMOVED_DELETE = 'delete';

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Selection Plugin Manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * Constructs a InlineEntityFormComplex object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The selection plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, ModuleHandlerInterface $module_handler, SelectionPluginManagerInterface $selection_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_type_bundle_info, $entity_type_manager, $entity_display_repository);
    $this->moduleHandler = $module_handler;
    $this->selectionManager = $selection_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('module_handler'),
      $container->get('plugin.manager.entity_reference_selection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults += [
      'allow_new' => TRUE,
      'allow_existing' => FALSE,
      'removed_reference' => self::REMOVED_OPTIONAL,
      'match_operator' => 'CONTAINS',
      'allow_duplicate' => FALSE,
      'multiple_use' => FALSE,
    ];

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $labels = $this->getEntityTypeLabels();
    $states_prefix = 'fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings]';
    $element['allow_new'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to add new @label.', ['@label' => $labels['plural']]),
      '#default_value' => $this->getSetting('allow_new'),
    ];
    $element['allow_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to add existing @label.', ['@label' => $labels['plural']]),
      '#default_value' => $this->getSetting('allow_existing'),
    ];
    $element['match_operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => $this->getMatchOperatorOptions(),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of nodes.'),
      '#states' => [
        'visible' => [
          ':input[name="' . $states_prefix . '[allow_existing]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['multiple_use'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to re-use existing @label multiple times.', ['@label' => $labels['plural']]),
      '#default_value' => $this->getSetting('multiple_use'),
    ];
    $element['allow_duplicate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to duplicate @label.', ['@label' => $labels['plural']]),
      '#default_value' => $this->getSetting('allow_duplicate'),
    ];

    $description = $this->t('Select whether a @child should be deleted altogether if removed as a reference here.<br />
    <em>Delete always</em> is recommended whenever each @child is exclusively managed within a single @parent without creating new revisions.<br />
    Otherwise <em>Keep always</em> is the safest.', [
      '@child' => $labels['singular'],
      '@parent' => $this->entityTypeManager->getDefinition($this->fieldDefinition->getTargetEntityTypeId())->getSingularLabel(),
    ]);
    $element['removed_reference'] = [
      '#type' => 'select',
      '#title' => $this->t('Keep or delete unreferenced @label', ['@label' => $labels['plural']]),
      '#default_value' => $this->getSetting('removed_reference'),
      '#options' => $this->getRemovedReferenceOptions(),
      '#description' => $description,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $labels = $this->getEntityTypeLabels();

    if ($this->getSetting('allow_new')) {
      $summary[] = $this->t('New @label can be added.', ['@label' => $labels['plural']]);
    }
    else {
      $summary[] = $this->t('New @label can not be created.', ['@label' => $labels['plural']]);
    }

    $match_operator_options = $this->getMatchOperatorOptions();
    if ($this->getSetting('allow_existing')) {
      $summary[] = $this->t('Existing @label can be referenced and are matched with the %operator operator.', [
        '@label' => $labels['plural'],
        '%operator' => $match_operator_options[$this->getSetting('match_operator')],
      ]);
    }
    else {
      $summary[] = $this->t('Existing @label can not be referenced.', ['@label' => $labels['plural']]);
    }

    if ($this->getSetting('multiple_use')) {
      $summary[] = $this->t('Multiple existing @label can be referenced and are matched with the %operator operator.', [
        '@label' => $labels['plural'],
        '%operator' => $match_operator_options[$this->getSetting('match_operator')],
      ]);
    }
    else {
      $summary[] = $this->t('Multiple existing @label can not be referenced.', ['@label' => $labels['plural']]);
    }

    if ($this->getSetting('allow_duplicate')) {
      $summary[] = $this->t('@label can be duplicated.', ['@label' => $labels['plural']]);
    }
    else {
      $summary[] = $this->t('@label can not be duplicated.', ['@label' => $labels['plural']]);
    }

    switch ($this->getSetting('removed_reference')) {
      case self::REMOVED_KEEP:
        $summary[] = $this->t('Always keep unreferenced @label.', ['@label' => $labels['plural']]);
        break;

      case self::REMOVED_OPTIONAL:
        $summary[] = $this->t('Let users decide whether to keep or delete unreferenced @label.', ['@label' => $labels['plural']]);
        break;

      case self::REMOVED_DELETE:
        $summary[] = $this->t('Always delete unreferenced @label.', ['@label' => $labels['plural']]);
        break;
    }

    return $summary;
  }

  /**
   * Returns the options for the match operator.
   *
   * @return array
   *   List of options.
   */
  protected function getMatchOperatorOptions() {
    return [
      'STARTS_WITH' => $this->t('Starts with'),
      'CONTAINS' => $this->t('Contains'),
    ];
  }

  /**
   * Returns the options for removing references.
   *
   * @return array
   *   List of options.
   */
  protected function getRemovedReferenceOptions() {
    return [
      self::REMOVED_KEEP => $this->t('Keep always'),
      self::REMOVED_OPTIONAL => $this->t('Let the user decide'),
      self::REMOVED_DELETE => $this->t('Delete always'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $target_type = $this->getFieldSetting('target_type');
    // Get the entity type labels for the UI strings.
    $labels = $this->getEntityTypeLabels();

    // Build a parents array for this element's values in the form.
    $parents = array_merge($element['#field_parents'], [
      $items->getName(),
      'form',
    ]);

    // Assign a unique identifier to each IEF widget.
    // Since $parents can get quite long, hashing ensures that every id has
    // a consistent and relatively short length while maintaining uniqueness.
    $this->setIefId($this->makeIefId($parents));

    // Get the langcode of the parent entity.
    $parent_langcode = $items->getEntity()->language()->getId();

    // Determine the wrapper ID for the entire element.
    $wrapper = 'inline-entity-form-' . $this->getIefId();

    $element = [
      '#type' => $this->getSetting('collapsible') ? 'details' : 'fieldset',
      '#tree' => TRUE,
      '#description' => $this->getFilteredDescription(),
      '#prefix' => '<div id="' . $wrapper . '">',
      '#suffix' => '</div>',
      '#ief_id' => $this->getIefId(),
      '#ief_root' => TRUE,
      '#translating' => $this->isTranslating($form_state),
      '#field_title' => $this->fieldDefinition->getLabel(),
      '#after_build' => [
        [get_class($this), 'removeTranslatabilityClue'],
      ],
    ] + $element;
    if ($element['#type'] == 'details') {
      // If there's user input, keep the details open. Otherwise, use settings.
      $element['#open'] = $form_state->getUserInput() ?: !$this->getSetting('collapsed');
    }

    $this->prepareFormState($form_state, $items, $element['#translating']);
    $entities = $form_state->get([
      'inline_entity_form', $this->getIefId(),
      'entities',
    ]);
    $entities_count = count($entities);

    // Determine if there are multiple existing entities
    // that could be referenced.
    $selection_settings = $this->getFieldSetting('handler_settings') ? $this->getFieldSetting('handler_settings') : [];
    $options = [
      'target_type' => $this->getFieldSetting('target_type'),
      'handler' => $this->getFieldSetting('handler'),
    ] + $selection_settings;

    // Prepare information about which operations may be available to the user.
    $settings = $this->getSettings();
    $allow_existing = $settings['allow_existing'];
    $allow_duplicate = $settings['allow_duplicate'] && $this->canAddNew();
    $allow_new = $settings['allow_new'] && $this->canAddNew();

    if (!$allow_new && $allow_existing) {
      // Only count referencable entities if existing entities are allowed
      // to be referenced otherwise we set the variable to false.
      /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
      $handler = $this->selectionManager->getInstance($options);
      $have_multiple_existing_entities = count($handler->getReferenceableEntities(NULL, 'CONTAINS', 2)) > 1;
    }
    else {
      $have_multiple_existing_entities = FALSE;
    }

    // Prepare cardinality information.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $cardinality_reached = ($cardinality > 0 && $entities_count == $cardinality);

    // Build the "Multiple value" widget.
    // @todo does this belong in #element_validate?
    $element['#element_validate'][] = [get_class($this), 'updateRowWeights'];
    // Add the required element marker & validation.
    if ($element['#required']) {
      $element['#element_validate'][] = [get_class($this), 'requiredField'];
    }

    $element['entities'] = [
      '#tree' => TRUE,
      '#theme' => 'inline_entity_form_entity_table',
      '#entity_type' => $target_type,
    ];

    // Get the fields that should be displayed in the table.
    $target_bundles = $this->getTargetBundles();
    $fields = $this->inlineFormHandler->getTableFields($target_bundles);
    $context = [
      'parent_entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
      'parent_bundle' => $this->fieldDefinition->getTargetBundle(),
      'field_name' => $this->fieldDefinition->getName(),
      'entity_type' => $target_type,
      'allowed_bundles' => $target_bundles,
    ];
    $this->moduleHandler->alter('inline_entity_form_table_fields', $fields, $context);
    $element['entities']['#table_fields'] = $fields;

    $weight_delta = max(ceil($entities_count * 1.2), 50);
    foreach ($entities as $key => $value) {
      // Data used by inline-entity-form-entity-table.html.twig.
      // @see template_preprocess_inline_entity_form_entity_table()
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $value['entity'];
      $element['entities'][$key]['#label'] = $this->inlineFormHandler->getEntityLabel($value['entity']);
      $element['entities'][$key]['#entity'] = $value['entity'];
      $element['entities'][$key]['#needs_save'] = $value['needs_save'];

      // Handle row weights.
      $element['entities'][$key]['#weight'] = $value['weight'];

      // First check to see if this entity should be displayed as a form.
      if (!empty($value['form'])) {
        $element['entities'][$key]['title'] = [];
        $element['entities'][$key]['delta'] = [
          '#type' => 'value',
          '#value' => $value['weight'],
        ];

        // Add the appropriate form.
        if (in_array($value['form'], ['edit', 'duplicate'])) {
          $element['entities'][$key]['form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['ief-form', 'ief-form-row']],
            'inline_entity_form' => $this->getInlineEntityForm(
              $value['form'],
              $entity->bundle(),
              $parent_langcode,
              $key,
              array_merge(
                $parents,
                ['inline_entity_form', 'entities', $key, 'form']
              ),
              $value['form'] == 'edit' ? $entity : $entity->createDuplicate()
            ),
          ];

          $element['entities'][$key]['form']['inline_entity_form']['#process'] = [
            [
              '\Drupal\inline_entity_form\Element\InlineEntityForm',
              'processEntityForm',
            ],
            [get_class($this), 'addIefSubmitCallbacks'],
            [get_class($this), 'buildEntityFormActions'],
          ];
        }
        elseif ($value['form'] == 'remove') {
          $element['entities'][$key]['form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['ief-form', 'ief-form-row']],
            // Used by Field API and controller methods to find the relevant
            // values in $form_state.
            '#parents' => array_merge($parents, ['entities', $key, 'form']),
            // Store the entity on the form, later modified in the controller.
            '#entity' => $entity,
            // Identifies the IEF widget to which the form belongs.
            '#ief_id' => $this->getIefId(),
            // Identifies the table row to which the form belongs.
            '#ief_row_delta' => $key,
          ];
          $this->buildRemoveForm($element['entities'][$key]['form']);
        }
      }
      else {
        $row = &$element['entities'][$key];
        $row['title'] = [];
        $row['delta'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for row @number', ['@number' => $key + 1]),
          '#title_display' => 'invisible',
          '#delta' => $weight_delta,
          '#default_value' => $value['weight'],
          '#attributes' => ['class' => ['ief-entity-delta']],
        ];
        // Add an actions container with edit and delete buttons for the entity.
        $row['actions'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['ief-entity-operations']],
        ];

        // Make sure entity_access is not checked for unsaved entities.
        $entity_id = $entity->id();
        if (empty($entity_id) || $entity->access('update')) {
          $row['actions']['ief_entity_edit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => 'ief-' . $this->getIefId() . '-entity-edit-' . $key,
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => 'inline_entity_form_get_element',
              'wrapper' => $wrapper,
            ],
            '#submit' => ['inline_entity_form_open_row_form'],
            '#ief_row_delta' => $key,
            '#ief_row_form' => 'edit',
          ];
        }

        // Add the duplicate button, if allowed.
        if ($allow_duplicate && !$cardinality_reached) {
          $row['actions']['ief_entity_duplicate'] = [
            '#type' => 'submit',
            '#value' => $this->t('Duplicate'),
            '#name' => 'ief-' . $this->getIefId() . '-entity-duplicate-' . $key,
            '#limit_validation_errors' => [array_merge($parents, ['actions'])],
            '#ajax' => [
              'callback' => 'inline_entity_form_get_element',
              'wrapper' => $wrapper,
            ],
            '#submit' => ['inline_entity_form_open_row_form'],
            '#ief_row_delta' => $key,
            '#ief_row_form' => 'duplicate',
          ];
        }

        // Determine if a reference may be removed.
        // Unless the user has permission to delete the entity, then they should
        // not be able to remove it if that will lead to its deletion.
        $may_remove_existing = $settings['removed_reference'] !== self::REMOVED_DELETE || $entity->access('delete');

        // Don't allow a user to remove the only entity if an entity is required
        // and the user cannot replace the entity if they remove it, because
        // this would put the form in an unrecoverable state.
        $can_replace_last_reference = $allow_new || ($allow_existing && $have_multiple_existing_entities);
        $reference_is_not_required = !$element['#required'] || $entities_count > 1 || $can_replace_last_reference;

        // Unsaved entities may always be removed.
        $may_remove = empty($entity_id) || ($may_remove_existing && $reference_is_not_required);

        // If an entity may be removed, show the "Remove" button.
        if ($may_remove) {
          // The default removal operation is unlink and the access check for
          // deleting happens inside the controller buildRemoveForm() method.
          $row['actions']['ief_entity_remove'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => 'ief-' . $this->getIefId() . '-entity-remove-' . $key,
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => 'inline_entity_form_get_element',
              'wrapper' => $wrapper,
            ],
            '#submit' => ['inline_entity_form_open_row_form'],
            '#ief_row_delta' => $key,
            '#ief_row_form' => 'remove',
            // It's OK to set #access when creating the whole element.
            '#access' => !$element['#translating'],
          ];
        }
      }
    }

    // When in translation, the widget only supports editing (translating)
    // already added entities, so there's no need to show the rest.
    if ($element['#translating']) {
      if (empty($entities)) {
        // There are no entities available for translation, hide the widget.
        // Safely restrict access. Entity cacheability already set.
        RenderArray::alter($element)->restrictAccess(FALSE, NULL);
      }
      return $element;
    }

    if ($cardinality > 1) {
      // Add a visual cue of cardinality count.
      $message = $this->t('You have added @entities_count out of @cardinality_count allowed @label.', [
        '@entities_count' => $entities_count,
        '@cardinality_count' => $cardinality,
        '@label' => $labels['plural'],
      ]);
      $element['cardinality_count'] = [
        '#markup' => '<div class="ief-cardinality-count">' . $message . '</div>',
      ];
    }
    // Do not return the rest of the form if cardinality count has been reached.
    if ($cardinality_reached) {
      return $element;
    }

    $create_bundles = $this->getCreateBundles();
    $create_bundles_count = count($create_bundles);
    $allow_new = $settings['allow_new'] && !empty($create_bundles);
    $hide_cancel = FALSE;
    // If the field is required and empty try to open one of the forms.
    if (empty($entities) && $this->fieldDefinition->isRequired()) {
      if ($settings['allow_existing'] && !$allow_new) {
        $form_state->set(['inline_entity_form', $this->getIefId(), 'form'], 'ief_add_existing');
        $hide_cancel = TRUE;
      }
      elseif ($create_bundles_count == 1 && $allow_new && !$settings['allow_existing']) {
        $bundle = reset($create_bundles);

        // The parent entity type and bundle must not be the same as the inline
        // entity type and bundle, to prevent recursion.
        $parent_entity_type = $this->fieldDefinition->getTargetEntityTypeId();
        $parent_bundle = $this->fieldDefinition->getTargetBundle();
        if ($parent_entity_type != $target_type || $parent_bundle != $bundle) {
          $form_state->set(['inline_entity_form', $this->getIefId(), 'form'], 'add');
          $form_state->set(
            ['inline_entity_form', $this->getIefId(), 'form settings'],
            ['bundle' => $bundle]
          );
          $hide_cancel = TRUE;
        }
      }
    }

    // If no form is open, show buttons that open one.
    $open_form = $form_state->get(
      ['inline_entity_form', $this->getIefId(), 'form']
    );

    if (empty($open_form)) {
      $element['actions'] = [
        '#attributes' => ['class' => ['container-inline']],
        '#type' => 'container',
        '#weight' => 100,
      ];

      // The user is allowed to create an entity of at least one bundle.
      if ($allow_new) {
        // Let the user select the bundle, if multiple are available.
        if ($create_bundles_count > 1) {
          $bundles = [];
          foreach ($this->entityTypeBundleInfo->getBundleInfo($target_type) as $bundle_name => $bundle_info) {
            if (in_array($bundle_name, $create_bundles)) {
              $bundles[$bundle_name] = $bundle_info['label'];
            }
          }
          asort($bundles);

          $element['actions']['bundle'] = [
            '#type' => 'select',
            '#options' => $bundles,
          ];
        }
        else {
          $element['actions']['bundle'] = [
            '#type' => 'value',
            '#value' => reset($create_bundles),
          ];
        }

        $element['actions']['ief_add'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add new @type_singular', ['@type_singular' => $labels['singular']]),
          '#name' => 'ief-' . $this->getIefId() . '-add',
          '#limit_validation_errors' => [array_merge($parents, ['actions'])],
          '#ajax' => [
            'callback' => 'inline_entity_form_get_element',
            'wrapper' => $wrapper,
          ],
          '#submit' => ['inline_entity_form_open_form'],
          '#ief_form' => 'add',
        ];
      }

      if ($settings['allow_existing']) {
        $element['actions']['ief_add_existing'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add existing @type_singular', ['@type_singular' => $labels['singular']]),
          '#name' => 'ief-' . $this->getIefId() . '-add-existing',
          '#limit_validation_errors' => [array_merge($parents, ['actions'])],
          '#ajax' => [
            'callback' => 'inline_entity_form_get_element',
            'wrapper' => $wrapper,
          ],
          '#submit' => ['inline_entity_form_open_form'],
          '#ief_form' => 'ief_add_existing',
        ];
      }
    }
    else {
      // Make a delta key bigger than all existing ones, without assuming that
      // the keys are strictly consecutive.
      $new_key = $entities ? max(array_keys($entities)) + 1 : 0;
      // There's a form open, show it.
      if ($form_state->get(['inline_entity_form', $this->getIefId(), 'form']) == 'add') {
        $element['form'] = [
          '#type' => 'fieldset',
          '#attributes' => ['class' => ['ief-form', 'ief-form-bottom']],
          'inline_entity_form' => $this->getInlineEntityForm(
            'add',
            $this->determineBundle($form_state),
            $parent_langcode,
            $new_key,
            array_merge($parents, [$new_key])
          ),
        ];
        $element['form']['inline_entity_form']['#process'] = [
          [
            '\Drupal\inline_entity_form\Element\InlineEntityForm',
            'processEntityForm',
          ],
          [get_class($this), 'addIefSubmitCallbacks'],
          [get_class($this), 'buildEntityFormActions'],
        ];
      }
      elseif ($form_state->get(['inline_entity_form', $this->getIefId(), 'form']) == 'ief_add_existing') {
        $element['form'] = [
          '#type' => 'fieldset',
          '#attributes' => ['class' => ['ief-form', 'ief-form-bottom']],
          // Identifies the IEF widget to which the form belongs.
          '#ief_id' => $this->getIefId(),
          // Used by Field API and controller methods to find the relevant
          // values in $form_state.
          '#parents' => array_merge($parents, [$new_key]),
          '#entity_type' => $target_type,
          '#ief_labels' => $this->getEntityTypeLabels(),
          '#match_operator' => $this->getSetting('match_operator'),
          '#multiple_use' => $this->getSetting('multiple_use'),
        ];

        $element['form'] += inline_entity_form_reference_form($element['form'], $form_state);
      }

      // Pre-opened forms can't be closed in order to force the user to
      // add / reference an entity.
      if ($hide_cancel) {
        $process_element = [];
        if ($open_form == 'add') {
          $process_element = &$element['form']['inline_entity_form'];
        }
        elseif ($open_form == 'ief_add_existing') {
          $process_element = &$element['form'];
        }
        $process_element['#process'][] = [get_class($this), 'hideCancel'];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      $items->filterEmptyItems();
      return;
    }
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#ief_submit_trigger'])) {
      return;
    }

    $field_name = $this->fieldDefinition->getName();
    $parents = array_merge($form['#parents'], [$field_name, 'form']);
    $ief_id = $this->makeIefId($parents);
    $this->setIefId($ief_id);
    $widget_state = &$form_state->get(['inline_entity_form', $ief_id]);
    foreach ($widget_state['entities'] as $key => $value) {
      $changed = TranslationHelper::updateEntityLangcode($value['entity'], $form_state);
      if ($changed) {
        $widget_state['entities'][$key]['entity'] = $value['entity'];
        $widget_state['entities'][$key]['needs_save'] = TRUE;
      }
    }

    $values = $widget_state['entities'];
    // If the inline entity form is still open, then its entity hasn't
    // been transferred to the IEF form state yet.
    if (empty($values) && !empty($widget_state['form'])) {
      if ($widget_state['form'] == 'add') {
        $element = NestedArray::getValue($form, [$field_name, 'widget', 'form']);
        $entity = $element['inline_entity_form']['#entity'];
        $values[] = ['entity' => $entity];
      }
      elseif ($widget_state['form'] == 'ief_add_existing') {
        $parent = NestedArray::getValue($form, [$field_name, 'widget', 'form']);
        $element = $parent['entity_id'] ?? [];
        if (!empty($element['#value'])) {
          $options = [
            'target_type' => $element['#target_type'],
            'handler' => $element['#selection_handler'],
          ] + $element['#selection_settings'];
          /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
          $handler = $this->selectionManager->getInstance($options);
          $input_values = $element['#tags'] ? Tags::explode($element['#value']) : [$element['#value']];

          foreach ($input_values as $input) {
            $match = EntityAutocomplete::extractEntityIdFromAutocompleteInput($input);
            if ($match === NULL) {
              // Try to get a match from the input string when the user didn't
              // use.
              // the autocomplete but filled in a value manually.
              $entities_by_bundle = $handler->getReferenceableEntities($input, '=');
              $entities = array_reduce($entities_by_bundle, function ($flattened, $bundle_entities) {
                return $flattened + $bundle_entities;
              }, []);
              $params = [
                '%value' => $input,
                '@value' => $input,
              ];
              if (empty($entities)) {
                $form_state->setError($element, $this->t('There are no entities matching "%value".', $params));
              }
              elseif (count($entities) > 5) {
                $params['@id'] = key($entities);
                // Error if there are more than 5 matching entities.
                $form_state->setError($element, $this->t('Many entities are called %value. Specify the one you want by appending the id in parentheses, like "@value (@id)".', $params));
              }
              elseif (count($entities) > 1) {
                // More helpful error if there are only a few matching entities.
                $multiples = [];
                foreach ($entities as $id => $name) {
                  $multiples[] = $name . ' (' . $id . ')';
                }
                $params['@id'] = $id;
                $form_state->setError($element, $this->t('Multiple entities match this reference; "%multiple". Specify the one you want by appending the id in parentheses, like "@value (@id)".', ['%multiple' => implode('", "', $multiples)] + $params));
              }
              else {
                // Take the one and only matching entity.
                $values += [
                  'target_id' => key($entities),
                ];
              }
            }
            else {
              $values += [
                'target_id' => $match,
              ];
            }
          }
        }
      }
    }
    // Sort values by weight.
    uasort($values, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    // Let the widget massage the submitted values.
    $values = $this->massageFormValues($values, $form, $form_state);
    // Assign the values and remove the empty ones.
    $items->setValue($values);
    $items->filterEmptyItems();
  }

  /**
   * Adds actions to the inline entity form.
   *
   * @param array $element
   *   Form array structure.
   */
  public static function buildEntityFormActions(array $element) {
    // Build a delta suffix that's appended to button #name keys for uniqueness.
    $delta = $element['#ief_id'];
    if ($element['#op'] == 'add') {
      $save_label = t('Create @type_singular', ['@type_singular' => $element['#ief_labels']['singular']]);
    }
    elseif ($element['#op'] == 'duplicate') {
      $save_label = t('Duplicate @type_singular', ['@type_singular' => $element['#ief_labels']['singular']]);
    }
    else {
      $delta .= '-' . $element['#ief_row_delta'];
      $save_label = t('Update @type_singular', ['@type_singular' => $element['#ief_labels']['singular']]);
    }

    // Add action submit elements.
    $element['actions'] = [
      '#type' => 'container',
      '#weight' => 100,
    ];
    $element['actions']['ief_' . $element['#op'] . '_save'] = [
      '#type' => 'submit',
      '#value' => $save_label,
      '#name' => 'ief-' . $element['#op'] . '-submit-' . $delta,
      '#limit_validation_errors' => [$element['#parents']],
      '#attributes' => ['class' => ['ief-entity-submit']],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $element['#ief_id'],
      ],
    ];
    $element['actions']['ief_' . $element['#op'] . '_cancel'] = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#name' => 'ief-' . $element['#op'] . '-cancel-' . $delta,
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $element['#ief_id'],
      ],
    ];

    // Add submit handlers depending on operation.
    if ($element['#op'] == 'add') {
      static::addSubmitCallbacks($element['actions']['ief_add_save']);
      $element['actions']['ief_add_cancel']['#submit'] = [
        [get_called_class(), 'closeChildForms'],
        [get_called_class(), 'closeForm'],
        'inline_entity_form_cleanup_form_state',
      ];
    }
    else {
      $element['actions']['ief_' . $element['#op'] . '_save']['#ief_row_delta'] = $element['#ief_row_delta'];
      $element['actions']['ief_' . $element['#op'] . '_cancel']['#ief_row_delta'] = $element['#ief_row_delta'];

      static::addSubmitCallbacks($element['actions']['ief_' . $element['#op'] . '_save']);
      $element['actions']['ief_' . $element['#op'] . '_save']['#submit'][] = [
        get_called_class(),
        'submitCloseRow',
      ];
      $element['actions']['ief_' . $element['#op'] . '_cancel']['#submit'] = [
        [get_called_class(), 'closeChildForms'],
        [get_called_class(), 'submitCloseRow'],
        'inline_entity_form_cleanup_row_form_state',
      ];
    }

    return $element;
  }

  /**
   * Hides cancel button.
   *
   * @param array $element
   *   Form array structure.
   */
  public static function hideCancel(array $element) {
    // @todo Name both buttons the same and simplify this logic.
    if (isset($element['actions']['ief_add_cancel'])) {
      // Safely restrict access.
      RenderArray::alter($element['actions']['ief_add_cancel'])->restrictAccess(FALSE, NULL);
    }
    elseif (isset($element['actions']['ief_reference_cancel'])) {
      // Safely restrict access.
      RenderArray::alter($element['actions']['ief_reference_cancel'])->restrictAccess(FALSE, NULL);
    }

    return $element;
  }

  /**
   * Builds remove form.
   *
   * @param array $form
   *   Form array structure.
   */
  protected function buildRemoveForm(array &$form) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form['#entity'];
    $entity_id = $entity->id();
    $entity_label = $this->inlineFormHandler->getEntityLabel($entity);
    $labels = $this->getEntityTypeLabels();

    if ($entity_label) {
      $message = $this->t('Are you sure you want to remove %label?', ['%label' => $entity_label]);
    }
    else {
      $message = $this->t('Are you sure you want to remove this %entity_type?', ['%entity_type' => $labels['singular']]);
    }

    $form['message'] = [
      '#theme_wrappers' => ['container'],
      '#markup' => $message,
    ];

    if (!empty($entity_id) && $this->getSetting('removed_reference') === self::REMOVED_OPTIONAL && $entity->access('delete')) {
      $form['delete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Delete this @type_singular from the system.', ['@type_singular' => $labels['singular']]),
      ];
    }

    // Build a delta suffix that's appended to button #name keys for uniqueness.
    $delta = $form['#ief_id'] . '-' . $form['#ief_row_delta'];

    // Add actions to the form.
    $form['actions'] = [
      '#type' => 'container',
      '#weight' => 100,
    ];

    $form['actions']['ief_remove_confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove'),
      '#name' => 'ief-remove-confirm-' . $delta,
      '#limit_validation_errors' => [$form['#parents']],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $form['#ief_id'],
      ],
      '#allow_existing' => $this->getSetting('allow_existing'),
      '#removed_reference' => $this->getSetting('removed_reference'),
      '#submit' => [[get_class($this), 'submitConfirmRemove']],
      '#ief_row_delta' => $form['#ief_row_delta'],
    ];

    $form['actions']['ief_remove_cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'ief-remove-cancel-' . $delta,
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => 'inline_entity_form_get_element',
        'wrapper' => 'inline-entity-form-' . $form['#ief_id'],
      ],
      '#submit' => [[get_class($this), 'submitCloseRow']],
      '#ief_row_delta' => $form['#ief_row_delta'],
    ];
  }

  /**
   * Button #submit callback: Closes a row form in the IEF widget.
   *
   * @param array $form
   *   The complete parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   *
   * @see inline_entity_form_open_row_form()
   */
  public static function submitCloseRow(array $form, FormStateInterface $form_state) {
    $element = inline_entity_form_get_element($form, $form_state);
    $ief_id = $element['#ief_id'];
    $delta = $form_state->getTriggeringElement()['#ief_row_delta'];

    $form_state->setRebuild();
    $form_state->set(['inline_entity_form', $ief_id, 'entities', $delta, 'form'], NULL);
  }

  /**
   * Remove form submit callback.
   *
   * The row is identified by #ief_row_delta stored on the triggering
   * element.
   * This isn't an #element_validate callback to avoid processing the
   * remove form when the main form is submitted.
   *
   * @param array $form
   *   The complete parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   */
  public static function submitConfirmRemove(array $form, FormStateInterface $form_state) {
    $element = inline_entity_form_get_element($form, $form_state);
    $remove_button = $form_state->getTriggeringElement();
    $delta = $remove_button['#ief_row_delta'];

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $element['entities'][$delta]['form']['#entity'];
    $entity_id = $entity->id();

    $form_values = NestedArray::getValue($form_state->getValues(), $element['entities'][$delta]['form']['#parents']);
    $form_state->setRebuild();

    $widget_state = $form_state->get(['inline_entity_form', $element['#ief_id']]);

    // The entity hasn't been saved yet, or is being deleted,
    // so remove the reference.
    unset($widget_state['entities'][$delta]);

    // If the entity has been saved, delete it if either the widget is set to
    // always delete, or the widget is set to let the user decide and the user
    // has decided to delete.
    if ($entity_id) {
      $removed_reference = $remove_button['#removed_reference'];
      if ($removed_reference === self::REMOVED_DELETE || ($removed_reference === self::REMOVED_OPTIONAL && $form_values['delete'] === 1)) {
        $widget_state['delete'][] = $entity;
      }
    }
    $form_state->set(['inline_entity_form', $element['#ief_id']], $widget_state);
  }

  /**
   * Determines bundle to be used when creating entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return string
   *   Bundle machine name.
   *
   * @todo Figure out if can be simplified.
   */
  protected function determineBundle(FormStateInterface $form_state) {
    $ief_settings = $form_state->get(['inline_entity_form', $this->getIefId()]);
    if (!empty($ief_settings['form settings']['bundle'])) {
      return $ief_settings['form settings']['bundle'];
    }
    elseif (!empty($ief_settings['bundle'])) {
      return $ief_settings['bundle'];
    }
    else {
      $target_bundles = $this->getCreateBundles();
      return reset($target_bundles);
    }
  }

  /**
   * Updates entity weights based on their weights in the widget.
   */
  public static function updateRowWeights($element, FormStateInterface $form_state, $form) {
    $ief_id = $element['#ief_id'];

    // Loop over the submitted delta values and update the weight of the
    // entities in the form state.
    foreach (Element::children($element['entities']) as $key) {
      $form_state->set(
        ['inline_entity_form', $ief_id, 'entities', $key, 'weight'],
        $element['entities'][$key]['delta']['#value']);
    }
  }

  /**
   * IEF widget #element_validate callback: Required field validation.
   */
  public static function requiredField($element, FormStateInterface $form_state, $form) {
    $ief_id = $element['#ief_id'];
    $children = $form_state->get(['inline_entity_form', $ief_id, 'entities']);
    $has_children = !empty($children);
    $form = $form_state->get(['inline_entity_form', $ief_id, 'form']);
    $form_open = !empty($form);
    // If the add new / add existing form is open, its validation / submission
    // will do the job instead (either by preventing the parent form submission
    // or by adding a new referenced entity).
    if (!$has_children && !$form_open) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $instance */
      $instance = $form_state->get(['inline_entity_form', $ief_id, 'instance']);
      $form_state->setError($element, t('@name field is required.', ['@name' => $instance->getLabel()]));
    }
  }

  /**
   * Button #submit callback: Closes a form in the IEF widget.
   *
   * @param array $form
   *   The complete parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   *
   * @see inline_entity_form_open_form()
   */
  public static function closeForm(array $form, FormStateInterface $form_state) {
    $element = inline_entity_form_get_element($form, $form_state);
    $ief_id = $element['#ief_id'];

    $form_state->setRebuild();
    $form_state->set(['inline_entity_form', $ief_id, 'form'], NULL);
  }

  /**
   * Add common submit callback functions and mark element as a IEF trigger.
   *
   * @param array $element
   *   Form array structure.
   */
  public static function addSubmitCallbacks(array &$element) {
    $element['#submit'] = [
      ['\Drupal\inline_entity_form\ElementSubmit', 'trigger'],
      [
        '\Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex',
        'closeForm',
      ],
    ];
    $element['#ief_submit_trigger'] = TRUE;
  }

  /**
   * Button #submit callback:  Closes all open child forms in the IEF widget.
   *
   * Used to ensure that forms in nested IEF widgets are properly closed
   * when a parent IEF's form gets submitted or cancelled.
   *
   * @param array $form
   *   The IEF Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   */
  public static function closeChildForms(array $form, FormStateInterface &$form_state) {
    $element = inline_entity_form_get_element($form, $form_state);
    inline_entity_form_close_all_forms($element, $form_state);
  }

}
