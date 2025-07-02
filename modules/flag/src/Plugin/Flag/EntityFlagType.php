<?php

namespace Drupal\flag\Plugin\Flag;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagType\FlagTypeBase;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a flag type for all entity types.
 *
 * Base entity flag handler.
 *
 * @FlagType(
 *   id = "entity",
 *   title = @Translation("Flag Type Entity"),
 *   deriver = "Drupal\flag\Plugin\Derivative\EntityFlagTypeDeriver"
 * )
 */
class EntityFlagType extends FlagTypeBase {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type defined in plugin definition.
   *
   * @var string
   */
  protected $entityType = '';

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation,
    EntityDisplayRepositoryInterface $entity_display_repository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $string_translation);
    $this->entityType = $plugin_definition['entity_type'];
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('entity_display.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $options = parent::defaultConfiguration();
    $options += [
      // Output the flag in the entity links.
      // This is empty for now and will get overridden for different
      // entities.
      // @see hook_entity_view().
      'show_in_links' => [],
      // Output the flag as individual fields.
      'show_as_field' => TRUE,
      // Add a checkbox for the flag in the entity form.
      // @see hook_field_attach_form().
      'show_on_form' => FALSE,
      'show_contextual_link' => FALSE,
      // Additional permissions to expose.
      'extra_permissions' => [],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['display']['show_as_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display link as field'),
      '#description' => $this->t('Show the flag link as a field, which can be ordered among other entity elements in the "Manage display" settings for the entity type.'),
      '#default_value' => $this->showAsField(),
    ];

    $form['display']['show_on_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display checkbox on entity edit form'),
      '#default_value' => $this->showOnForm(),
      '#weight' => 5,
    ];

    // We use FieldAPI to put the flag checkbox on the entity form, so therefore
    // require the entity to be fieldable. Since this is a potential DX
    // head scratcher for a developer wondering where this option has gone,
    // we disable it and explain why.
    $form['display']['show_contextual_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display in contextual links'),
      '#default_value' => $this->showContextualLink(),
      '#description' => $this->t("Note that not all entity types support contextual links.
        <br/>
        <strong>Warning: </strong>Due to how contextual links are cached on frontend
        we have to set max-age as 0 for entity cache if
        user has access to contextual links and to this flag. This means that
        those users will get no cache hits for render elements rendering flaggable
        entities with contextual links."),
      '#access' => $this->moduleHandler->moduleExists('contextual'),
      '#weight' => 10,
    ];

    // Add checkboxes to show flag link on each entity view mode.
    $options = [];
    $defaults = [];

    $view_modes = $this->entityDisplayRepository->getViewModes($this->entityType);

    foreach ($view_modes as $name => $view_mode) {
      $options[$name] = $this->t('Display on @name view mode', ['@name' => $view_mode['label']]);
      if ($this->showInLinks($name)) {
        $defaults[$name] = $name;
      }
    }

    $form['display']['show_in_links'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display in entity links'),
      '#description' => $this->t('Show the flag link with the other links on the entity.'),
      '#options' => $options,
      '#default_value' => $defaults,
      '#weight' => 15,
    ];

    $form['access']['extra_permissions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Expose additional permissions'),
      '#options' => $this->getExtraPermissionsOptions(),
      '#default_value' => $this->configuration['extra_permissions'],
      '#description' => $this->t("Provides permissions with finer levels of access for this flag."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['show_in_links'] = array_filter($form_state->getValue('show_in_links'));
    $this->configuration['show_as_field'] = $form_state->getValue('show_as_field');
    $this->configuration['show_on_form'] = $form_state->getValue('show_on_form');
    $this->configuration['show_contextual_link'] = $form_state->getValue('show_contextual_link');
    $this->configuration['extra_permissions'] = $form_state->getValue('extra_permissions');
  }

  /**
   * Return the show in links setting given a view mode.
   *
   * @param string $name
   *   The name of the view mode.
   *
   * @return bool
   *   TRUE if the flag should appear in the entity links for the view mode.
   */
  public function showInLinks($name) {
    if (!empty($this->configuration['show_in_links'][$name])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns the show as field setting.
   *
   * @return bool
   *   TRUE if the flag should appear as a pseudofield, FALSE otherwise.
   */
  public function showAsField() {
    return $this->configuration['show_as_field'];
  }

  /**
   * Returns the show on form setting.
   *
   * @return bool
   *   TRUE if the flag should appear on the entity form, FALSE otherwise.
   */
  public function showOnForm() {
    return $this->configuration['show_on_form'];
  }

  /**
   * Determines if the given form operation is add or edit.
   *
   * @param string $operation
   *   The form operation.
   *
   * @return bool
   *   Returns TRUE if the operation is an add edit operation.
   */
  public function isAddEditForm($operation) {
    return in_array($operation, ['default', 'edit']);
  }

  /**
   * Returns the show on contextual link setting.
   *
   * @return bool
   *   TRUE if the flag should appear in contextual links, FALSE otherwise.
   */
  public function showContextualLink() {
    return $this->configuration['show_contextual_link'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtraPermissionsOptions() {
    $options = parent::getExtraPermissionsOptions();
    if ($this->isFlaggableOwnable()) {
      $options['owner'] = $this->t("Permissions based on ownership of the flaggable item. For example, only allow users to flag items they own.");
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function actionPermissions(FlagInterface $flag) {
    $permissions = parent::actionPermissions($flag);

    // Define additional permissions.
    if ($this->hasExtraPermission('owner')) {
      $permissions += $this->getExtraPermissionsOwner($flag);
    }

    return $permissions;
  }

  /**
   * Defines permissions for the 'owner' set of additional action permissions.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag object.
   *
   * @return array
   *   An array of permissions.
   */
  protected function getExtraPermissionsOwner(FlagInterface $flag) {
    $permissions['flag ' . $flag->id() . ' own items'] = [
      'title' => $this->t('Flag %flag_title own items', [
        '%flag_title' => $flag->label(),
      ]),
    ];

    $permissions['unflag ' . $flag->id() . ' own items'] = [
      'title' => $this->t('Unflag %flag_title own items', [
        '%flag_title' => $flag->label(),
      ]),
    ];

    $permissions['flag ' . $flag->id() . ' other items'] = [
      'title' => $this->t("Flag %flag_title others' items", [
        '%flag_title' => $flag->label(),
      ]),
    ];

    $permissions['unflag ' . $flag->id() . ' other items'] = [
      'title' => $this->t("Unflag %flag_title others' items", [
        '%flag_title' => $flag->label(),
      ]),
    ];

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function actionAccess($action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable = NULL) {
    $access = parent::actionAccess($action, $flag, $account, $flaggable);

    if (($flaggable instanceof EntityOwnerInterface) && ($this->hasExtraPermission('owner'))) {
      // Own items.
      $permission = $action . ' ' . $flag->id() . ' own items';
      $own_permission_access = AccessResult::allowedIfHasPermission($account, $permission)
        ->addCacheContexts(['user']);
      $account_match_access = AccessResult::allowedIf($account->id() == $flaggable->getOwnerId());
      $own_access = $own_permission_access->andIf($account_match_access);
      $access = $access->orIf($own_access);

      // Others' items.
      $permission = $action . ' ' . $flag->id() . ' other items';
      $others_permission_access = AccessResult::allowedIfHasPermission($account, $permission)
        ->addCacheContexts(['user']);
      $account_mismatch_access = AccessResult::allowedIf($account->id() != $flaggable->getOwnerId());
      $others_access = $others_permission_access->andIf($account_mismatch_access);
      $access = $access->orIf($others_access);
    }

    return $access;
  }

  /**
   * Determines if the flaggable associated with the flag supports ownership.
   *
   * @return bool
   *   TRUE if the flaggable supports ownership.
   */
  protected function isFlaggableOwnable() {
    $entity_type_id = $this->entityType;
    // Get the entity type from the entity type manager.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    // Only if the flaggable entities can be owned.
    if ($entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      return TRUE;
    }

    return FALSE;
  }

}
