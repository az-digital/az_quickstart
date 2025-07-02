<?php

namespace Drupal\workbench_access;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\Plugin\views\filter\Section;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base hierarchy class that others may extend.
 *
 * @phpstan-consistent-constructor
 */
abstract class AccessControlHierarchyBase extends PluginBase implements AccessControlHierarchyInterface, ContainerFactoryPluginInterface {

  use PluginWithFormsTrait;
  use StringTranslationTrait;

  /**
   * A configuration factory object to store configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The access tree array.
   *
   * @var array
   */
  protected $tree;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Config for module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserSectionStorageInterface $user_section_storage, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $configFactory->get('workbench_access.settings');
    $this->userSectionStorage = $user_section_storage;
    $this->entityTypeManager = $entityTypeManager;
    $this->setConfiguration($configuration);
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function entityType() {
    return $this->pluginDefinition['entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function resetTree() {
    unset($this->tree);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $plugin = NULL;
    // This cache is specific to the object being called.
    // e.g. Drupal\workbench_access\Plugin\AccessControlHierarchy\Menu.
    if (!isset($this->tree)) {
      $this->tree = $this->getTree();
    }
    foreach ($this->tree as $data) {
      if (isset($data[$id])) {
        $plugin = $data[$id];
        break;
      }
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEntityAccess(AccessSchemeInterface $scheme, EntityInterface $entity, $op, AccountInterface $account) {
    if (!$this->applies($entity->getEntityTypeId(), $entity->bundle())) {
      return AccessResult::neutral();
    }

    // Discover the field and check status.
    $entity_sections = $this->getEntityValues($entity);
    // If no value is set on the entity, ignore.
    // @todo Is this the correct logic? It is helpful for new installs.
    $deny_on_empty = $this->config->get('deny_on_empty');

    if (!$deny_on_empty && empty($entity_sections)) {
      return AccessResult::neutral();
    }
    $user_sections = $this->userSectionStorage->getUserSections($scheme, $account);
    if (empty($user_sections)) {
      return AccessResult::forbidden();
    }
    // Check the tree status of the $entity against the $user.
    // Return neutral if in tree, forbidden if not.
    if (WorkbenchAccessManager::checkTree($scheme, $entity_sections, $user_sections)) {
      return AccessResult::neutral();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function disallowedOptions(array $field) {
    $options = [];
    if (isset($field['widget']['#default_value']) && isset($field['widget']['#options'])) {
      // Default value may be an array or a string.
      if (is_array($field['widget']['#default_value'])) {
        $default_value_array = array_flip($field['widget']['#default_value']);
      }
      else {
        $default_value_array = [$field['widget']['#default_value'] => $field['widget']['#default_value']];
      }
      $options = array_diff_key($default_value_array, $field['widget']['#options']);
    }
    return array_keys($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicableFields($entity_type, $bundle) {
    // Extending classes are expected to provide their own implementation.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function submitEntity(array &$form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $access_scheme */
      foreach (\Drupal::entityTypeManager()
        ->getStorage('access_scheme')
        ->loadMultiple() as $access_scheme) {
        $scheme = $access_scheme->getAccessScheme();
        $hidden_values = $form_state->getValue('workbench_access_disallowed');
        if (!empty($hidden_values)) {
          $entity = $form_object->getEntity();
          $scheme->massageFormValues($entity, $form_state, $hidden_values);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsJoin($entity_type, $key, $alias = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function addWhere(Section $filter, array $values) {
    // The JOIN data tells us if we have multiple tables to deal with.
    $join_data = $this->getViewsJoin($filter->getEntityType(), $filter->realField);
    if (count($join_data) === 1) {
      // @phpstan-ignore-next-line
      $filter->query->addWhere($filter->options['group'], "$filter->tableAlias.$filter->realField", array_values($values), $filter->operator);
    }
    else {
      $or = new Condition('OR');
      foreach ($join_data as $data) {
        $alias = $data['table_alias'] . '.' . $data['real_field'];
        $or->condition($alias, array_values($values), $filter->operator);
      }
      // @phpstan-ignore-next-line
      $filter->query->addWhere($filter->options['group'], $or);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsData(array &$data, AccessSchemeInterface $scheme) {
    // Null op.
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(ContentEntityInterface $entity, FormStateInterface $form_state, array $hidden_values) {
    // Null op.
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Default implementation is empty.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Default implementation is empty.
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(AccessSchemeInterface $scheme, array &$form, FormStateInterface &$form_state, ContentEntityInterface $entity) {
    // Default implementation is empty.
  }

}
