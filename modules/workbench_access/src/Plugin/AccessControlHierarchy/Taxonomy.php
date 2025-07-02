<?php

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a hierarchy based on a Vocabulary.
 *
 * @AccessControlHierarchy(
 *   id = "taxonomy",
 *   module = "taxonomy",
 *   entity = "taxonomy_term",
 *   label = @Translation("Taxonomy"),
 *   description = @Translation("Uses a taxonomy vocabulary as an access control hierarchy.")
 * )
 */
class Taxonomy extends AccessControlHierarchyBase {

  use StringTranslationTrait;

  /**
   * Field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new AccessControlHierarchyBase object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   User section storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Entity type bundle info.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserSectionStorageInterface $user_section_storage, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundleInfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_section_storage, $configFactory, $entityTypeManager);
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('workbench_access.user_section_storage'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    if (!isset($this->tree)) {
      $this->tree = [];
      /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $tree = [];
      foreach ($this->configuration['vocabularies'] as $vocabulary_id) {
        if ($vocabulary = Vocabulary::load($vocabulary_id)) {
          $tree[$vocabulary_id][$vocabulary_id] = [
            'label' => $vocabulary->label(),
            'depth' => 0,
            'parents' => [],
            'weight' => 0,
            'description' => $vocabulary->label(),
            'path' => $vocabulary->toUrl('overview-form')->toString(TRUE)->getGeneratedUrl(),
          ];
          // @todo It is possible that this will return a filtered set, if
          // term_access is applied to the query.
          $data = $term_storage->loadTree($vocabulary_id);
          $this->tree = $this->buildTree($vocabulary_id, $data, $tree);
        }
      }
    }
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'fields' => [],
      'vocabularies' => [],
    ];
    return $defaults + parent::defaultConfiguration();
  }

  /**
   * Traverses the taxonomy tree and builds parentage arrays.
   *
   * Note: this method is necessary to load all parents to the array.
   *
   * @param string $id
   *   The root id of the section tree.
   * @param array $data
   *   An array of menu tree or subtree data.
   * @param array &$tree
   *   The computed tree array to return.
   *
   * @return array
   *   The compiled tree data.
   */
  protected function buildTree($id, array $data, array &$tree) {
    foreach ($data as $term) {
      $tree[$id][$term->tid] = [
        'id' => $term->tid,
        'label' => $term->name,
        'depth' => $term->depth + 1,
        'parents' => $this->convertParents($term, $id),
        'weight' => $term->weight,
        'description' => $term->description__value,
        'path' => Url::fromUri('entity:taxonomy_term/' . $term->tid)->toString(TRUE)->getGeneratedUrl(),
      ];
      foreach ($tree[$id][$term->tid]['parents'] as $key) {
        if (!empty($tree[$id][$key]['parents'])) {
          $tree[$id][$term->tid]['parents'] = array_unique(array_merge($tree[$id][$key]['parents'], $tree[$id][$term->tid]['parents']));
        }
      }
    }
    return $tree;
  }

  /**
   * Coverts the 0 parent id to a string.
   *
   * @param object $term
   *   The term to modify.
   * @param string $id
   *   The root parent id string.
   */
  private function convertParents($term, $id) {
    foreach ($term->parents as $pos => $parent) {
      if ($parent === 0 || $parent === '0') {
        $term->parents[$pos] = $id;
      }
    }
    return $term->parents;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(AccessSchemeInterface $scheme, array &$form, FormStateInterface &$form_state, ContentEntityInterface $entity) {
    foreach (array_column($this->getApplicableFields($entity->getEntityTypeId(), $entity->bundle()), 'field') as $field) {
      if (!isset($form[$field])) {
        continue;
      }
      $element = &$form[$field];

      if (isset($element['widget']['#options'])) {
        foreach ($element['widget']['#options'] as $id => $data) {
          // When using a select list, options may be a nested array.
          if (is_array($data)) {
            foreach ($data as $index => $item) {
              $sections = [$index];
              if (empty(WorkbenchAccessManager::checkTree($scheme, $sections, $this->userSectionStorage->getUserSections($scheme)))) {
                unset($element['widget']['#options'][$id][$index]);
              }
            }
            // If the parent is empty, remove it.
            if (empty($element['widget']['#options'][$id])) {
              unset($element['widget']['#options'][$id]);
            }
          }
          else {
            $sections = [$id];
            if ($id !== '_none' && empty(WorkbenchAccessManager::checkTree($scheme, $sections, $this->userSectionStorage->getUserSections($scheme)))) {
              unset($element['widget']['#options'][$id]);
            }
          }
        }
      }
      // Check for autocomplete fields. In this case, we replace the selection
      // handler with our own, which likely breaks Views-based handlers, but
      // that can be handled later. We swap out the default handler for our own,
      // since we don't have another way to filter the autocomplete results.
      // @todo test this against views-based handlers.
      // @see \Drupal\workbench_access\Plugin\EntityReferenceSelection\TaxonomyHierarchySelection
      else {
        foreach ($element['widget'] as $key => $item) {
          if (is_array($item) && isset($item['target_id']['#type']) && $item['target_id']['#type'] === 'entity_autocomplete') {
            $element['widget'][$key]['target_id']['#selection_handler'] = 'workbench_access:taxonomy_term:' . $scheme->id();
            $element['widget'][$key]['target_id']['#validate_reference'] = TRUE;
            // Hide elements that cannot be edited.
            if (!empty($element['widget'][$key]['target_id']['#default_value'])) {
              $sections = [$element['widget'][$key]['target_id']['#default_value']->id()];
              if (empty(WorkbenchAccessManager::checkTree($scheme, $sections, $this->userSectionStorage->getUserSections($scheme)))) {
                unset($element['widget'][$key]);
                $id = current($sections);
                $disallowed[$id] = $id;
              }
            }
          }
        }
      }
      if (!empty($disallowed)) {
        $form['workbench_access_disallowed']['#tree'] = TRUE;
        $form['workbench_access_disallowed'][$field] = [
          $scheme->id() => [
            '#type' => 'value',
            '#value' => $disallowed,
          ],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($entity_type_id, $bundle) {
    return (bool) $this->getApplicableFields($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return [];
    }
    $values = [];
    foreach (array_column($this->getApplicableFields($entity->getEntityTypeId(), $entity->bundle()), 'field') as $field) {
      foreach ($entity->get($field)->getValue() as $item) {
        if (isset($item['target_id'])) {
          $values[] = $item['target_id'];
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicableFields($entity_type, $bundle) {
    if (is_array($this->configuration['fields'])) {
      return array_filter($this->configuration['fields'], function ($field) use ($entity_type, $bundle) {
        $field += [
          'entity_type' => NULL,
          'bundle' => NULL,
          'field' => '',
        ];
        return $field['entity_type'] === $entity_type && $field['bundle'] === $bundle;
      });
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsData(array &$data, AccessSchemeInterface $scheme) {
    foreach (array_column($this->configuration['fields'], 'entity_type') as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if (($base_table = $entity_type->getBaseTable()) && ($id = $entity_type->getKey('id'))) {
        $data[$base_table]['workbench_access_section__' . $scheme->id()] = [
          'title' => $this->t('Workbench access @name', ['@name' => $scheme->label()]),
          'help' => $this->t('The sections to which this content belongs in the @name scheme.', [
            '@name' => $scheme->label(),
          ]),
          'field' => [
            'scheme' => $scheme->id(),
            'id' => 'workbench_access_section',
          ],
          'filter' => [
            'field' => $id,
            'scheme' => $scheme->id(),
            'id' => 'workbench_access_section',
          ],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(ContentEntityInterface $entity, FormStateInterface $form_state, array $hidden_values) {
    foreach (array_column($this->getApplicableFields($entity->getEntityTypeId(), $entity->bundle()), 'field') as $field_name) {
      $values = $form_state->getValue($field_name);
      // The $hidden_values are deeply nested.
      foreach ($hidden_values as $key => $value) {
        if ($key === $field_name) {
          foreach ($value as $element) {
            foreach ($element as $item) {
              // Ensure that we do not save duplicate values. Note that this
              // cannot be a strict in_array() check thanks to form handling.
              if (empty($values[0]) || !in_array($item, array_values($values[0]))) {
                $values[]['target_id'] = $item;
              }
            }
          }
        }
      }
      $form_state->setValue($field_name, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['vocabularies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
      '#description' => $this->t('Select the vocabularies to use for access control'),
      '#default_value' => $this->configuration['vocabularies'],
      '#options' => array_map(function (VocabularyInterface $vocabulary) {
        return $vocabulary->label();
      }, $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple()),
    ];
    $entity_reference_fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    $taxonomy_fields = [];
    $validate = [];

    foreach ($entity_reference_fields as $entity_type_id => $fields) {
      foreach ($fields as $field_name => $details) {
        // Parent fields on taxonomy terms would create infinite loops. Deny.
        if ($entity_type_id === 'taxonomy_term' && $field_name === 'parent') {
          continue;
        }
        foreach ($details['bundles'] as $bundle) {
          $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          if (isset($field_definitions[$field_name]) && $field_definitions[$field_name]->getFieldStorageDefinition()->getSetting('target_type') === 'taxonomy_term') {
            $handler_settings = $field_definitions[$field_name]->getSetting('handler_settings');
            // Must refer to a proper target. Target bundles referring to
            // themselves would create an infinite loop. Deny.
            if ($entity_type_id === 'taxonomy_term' && in_array($bundle, $this->configuration['vocabularies'], TRUE)) {
              continue;
            }
            // Must have a proper target.
            if (!isset($handler_settings['target_bundles'])) {
              continue;
            }
            // At least one target must be configured for access control.
            $allowed = array_intersect($handler_settings['target_bundles'], $this->configuration['vocabularies']);
            if (empty($allowed)) {
              continue;
            }
            // Create a unique key for each option.
            $key = sprintf('%s:%s:%s', $entity_type_id, $bundle, $field_name);
            $taxonomy_fields[$key] = [
              'entity_type' => $this->entityTypeManager->getDefinition($entity_type_id)->getLabel(),
              'bundle' => $this->bundleInfo->getBundleInfo($entity_type_id)[$bundle]['label'],
              'field' => $field_definitions[$field_name]->getLabel(),
            ];
            $validate[$key] = $handler_settings['target_bundles'];
          }
        }
      }
    }
    if (!$taxonomy_fields) {
      $form['fields'] = ['#markup' => $this->t('There are no configured taxonomy fields, please create a new term reference field on a content type to continue')];
      return $form;
    }
    $default_value = array_map(function (array $field) {
      $field += [
        'entity_type' => NULL,
        'bundle' => NULL,
        'field' => '',
      ];
      return sprintf('%s:%s:%s', $field['entity_type'], $field['bundle'], $field['field']);
    }, $this->configuration['fields']);
    $form['fields'] = [
      '#type' => 'tableselect',
      '#header' => [
        'entity_type' => $this->t('Entity type'),
        'bundle' => $this->t('Bundle'),
        'field' => $this->t('Field name'),
      ],
      '#options' => $taxonomy_fields,
      '#default_value' => array_combine($default_value, $default_value),
    ];
    if ($validate) {
      $form['validate'] = ['#type' => 'value', '#value' => $validate];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValues();
    $settings['vocabularies'] = array_values(array_filter($settings['vocabularies']));
    if (!empty($settings['fields'])) {
      $settings['fields'] = array_filter($settings['fields']);
      foreach ($settings['fields'] as $field) {
        if (isset($settings['validate'][$field])) {
          $error = TRUE;
          foreach ($settings['vocabularies'] as $vocabulary) {
            if (in_array($vocabulary, $settings['validate'][$field], TRUE)) {
              $error = FALSE;
            }
          }
          if ($error) {
            $form_field = $form['fields']['#options'][$field];
            // phpcs:ignore
            [$entity_type, $bundle, $field_name] = explode(':', $field);
            $form_state->setErrorByName('scheme_settings][fields][' . $field,
              $this->t('The field %field on %type entities of type %bundle is not in the selected vocabularies.',
              [
                '%field' => $form_field['field'],
                '%type' => $entity_type,
                '%bundle' => $form_field['bundle'],
              ])
            );
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValues();
    if (empty($settings['fields'])) {
      $settings['fields'] = [];
    }
    // Saving 'validate' can cause schema errors.
    unset($settings['validate']);
    $settings['vocabularies'] = array_values(array_filter($settings['vocabularies']));
    $settings['fields'] = array_values(array_map(function ($item) {
      [$entity_type, $bundle, $field_name] = explode(':', $item);
      return [
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'field' => $field_name,
      ];
    }, array_filter($settings['fields'])));

    $this->configuration = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependent_entities = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple($this->configuration['vocabularies']);
    $dependent_entities = array_merge($dependent_entities, $this->entityTypeManager->getStorage('field_config')->loadMultiple(array_map(function (array $field) {
      $field += [
        'entity_type' => NULL,
        'bundle' => NULL,
        'field' => '',
      ];
      return sprintf('%s.%s.%s', $field['entity_type'], $field['bundle'], $field['field']);
    }, $this->configuration['fields'])));
    return array_reduce($dependent_entities, function (array $carry, ConfigEntityInterface $entity) {
      $carry[$entity->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
      return $carry;
    }, []);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $fields = array_udiff($this->configuration['fields'], array_reduce($dependencies['config'], function (array $carry, $item) {
      if (!$item instanceof FieldConfigInterface) {
        return $carry;
      }
      $carry[] = [
        'field' => $item->getName(),
        'entity_type' => $item->getTargetEntityTypeId(),
        'bundle' => $item->getTargetBundle(),
      ];
      return $carry;
    }, []), function ($array1, $array2) {
      $key1 = sprintf('%s.%s.%s', $array1['field'], $array1['entity_type'], $array1['bundle']);
      $key2 = sprintf('%s.%s.%s', $array2['field'], $array2['entity_type'], $array2['bundle']);
      if ($key1 < $key2) {
        return -1;
      }
      elseif ($key1 > $key2) {
        return 1;
      }
      else {
        return 0;
      }
    });
    $vocabularies = array_diff($this->configuration['vocabularies'], array_reduce($dependencies['config'], function (array $carry, $item) {
      if (!$item instanceof VocabularyInterface) {
        return $carry;
      }
      $carry[] = $item->id();
      return $carry;
    }, []));
    $changed = ($fields != $this->configuration['fields']) || ($vocabularies != $this->configuration['vocabularies']);
    $this->configuration['fields'] = $fields;
    $this->configuration['vocabularies'] = $vocabularies;
    return $changed;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor
   */
  public function getViewsJoin($entity_type, $key, $alias = NULL) {
    if ($entity_type === 'user') {
      $configuration['taxonomy'] = [
        'table' => 'section_association__user_id',
        'field' => 'user_id_target_id',
        'left_table' => 'users',
        'left_field' => $key,
        'operator' => '=',
        'table_alias' => 'section_association__user_id',
        'real_field' => 'entity_id',
      ];
      return $configuration;
    }
    $fields = array_column(array_filter($this->configuration['fields'], function ($field) use ($entity_type) {
      return isset($field['entity_type']) && $field['entity_type'] === $entity_type;
    }), 'field');
    $table_prefix = $entity_type;
    $field_suffix = '_target_id';
    $configuration = [];
    foreach ($fields as $field) {
      $configuration[$field] = [
        'table' => $table_prefix . '__' . $field,
        'field' => 'entity_id',
        'left_table' => $entity_type,
        'left_field' => $key,
        'operator' => '=',
        'table_alias' => $field,
        'real_field' => $field . $field_suffix,
      ];
    }
    return $configuration;
  }

}
