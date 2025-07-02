<?php

namespace Drupal\webform_access;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\EntityListBuilder\WebformEntityListBuilderSortLabelTrait;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of webform access group entities.
 *
 * @see \Drupal\webform\Entity\WebformOption
 */
class WebformAccessGroupListBuilder extends ConfigEntityListBuilder {

  use WebformEntityListBuilderSortLabelTrait;

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Filter form.
    $build['filter_form'] = $this->buildFilterForm();

    // Display info.
    $build['info'] = $this->buildInfo();

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;
    $build['table']['#attributes']['class'][] = 'webform-access-group-table';

    // Attachments.
    $build['#attached']['library'][] = 'webform/webform.admin';
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    return [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by keyword.'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-access-group-table',
        'data-summary' => '.webform-access-group-summary',
        'data-item-singlular' => $this->t('access group'),
        'data-item-plural' => $this->t('access groups'),
        'title' => $this->t('Enter a keyword to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    $total = $this->getStorage()->getQuery()->accessCheck(FALSE)->count()->execute();
    if (!$total) {
      return [];
    }

    return [
      '#markup' => $this->formatPlural($total, '@count access group', '@count access groups'),
      '#prefix' => '<div class="webform-access-group-summary">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Label/Description');
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['users'] = [
      'data' => $this->t('Users'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['entities'] = [
      'data' => $this->t('Nodes'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['permissions'] = [
      'data' => $this->t('Permissions'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['admins'] = [
      'data' => $this->t('Admins'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['emails'] = [
      'data' => $this->t('Emails'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface $entity */

    // Label/Description.
    $row['label'] = [
      'data' => [
        'label' => $entity->toLink($entity->label(), 'edit-form')->toRenderable() + ['#suffix' => '<br/>'],
        'description' => WebformHtmlEditor::checkMarkup($entity->get('description')),
      ],
    ];

    // Type.
    $row['type'] = $entity->getTypeLabel();

    // Users.
    $row['users'] = ['data' => static::buildUserAccounts($entity->getUserIds())];

    // Entities.
    $row['entities'] = ['data' => static::buildEntities($entity->getEntityIds())];

    // Permissions.
    $row['permissions'] = ['data' => static::buildPermissions($entity->get('permissions'))];

    // Admins.
    $row['admins'] = ['data' => static::buildUserAccounts($entity->getAdminIds())];

    // Emails.
    $row['emails'] = ['data' => static::buildEmails($entity->getEmails())];

    $row = $row + parent::buildRow($entity);

    return [
      'data' => $row,
      'class' => ['webform-form-filter-text-source'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 20,
        'url' => Url::fromRoute('entity.webform_access_group.duplicate_form', ['webform_access_group' => $entity->id()]),
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
    }

    // Changed 'Edit' button label to 'Manage' which better reflects the
    // operation.
    if (!$this->currentUser->hasPermission('administer webform')) {
      $operations['edit']['title'] = $this->t('Manage');
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = $this->storage->loadMultiple($entity_ids);

    // If the user is not a webform admin, check access to each access group.
    if (!$this->currentUser->hasPermission('administer webform')) {
      foreach ($entities as $entity_id => $entity) {
        if (!$entity->access('update', $this->currentUser)) {
          unset($entities[$entity_id]);
        }
      }
    }

    return $entities;
  }

  /* ************************************************************************ */
  // Helper methods.
  /* ************************************************************************ */

  /**
   * Build a renderable array of email addresses.
   *
   * @param array $emails
   *   The email addresses to be rendered.
   *
   * @return array
   *   A renderable array of email addresses.
   */
  public static function buildEmails(array $emails) {
    return ['#theme' => 'item_list', '#items' => $emails];
  }

  /**
   * Build a renderable array of user accounts.
   *
   * @param array $uids
   *   The user ids to be rendered.
   *
   * @return array
   *   A renderable array of user accounts.
   */
  public static function buildUserAccounts(array $uids) {
    /** @var \Drupal\user\UserInterface[] $users */
    $users = $uids ? User::loadMultiple($uids) : [];
    $items = [];
    foreach ($users as $user) {
      $items[] = $user->toLink();
    }
    return ['#theme' => 'item_list', '#items' => $items];
  }

  /**
   * Build a renderable array of entities.
   *
   * @param array $entity_references
   *   The entity references (i.e. entity_type:entity_id) to be rendered.
   *
   * @return array
   *   A renderable array of entities.
   */
  public static function buildEntities(array $entity_references) {
    $items = [];
    foreach ($entity_references as $entity_reference) {
      // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
      [$entity_type, $entity_id, $field_name, $webform_id] = explode(':', $entity_reference);
      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
      $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);
      if ($entity && $webform) {
        $items[] = [
          'source_entity' => $entity->toLink()->toRenderable(),
          'webform' => ['#prefix' => '<br/>', '#markup' => $webform->label()],
        ];
      }
    }
    return ['#theme' => 'item_list', '#items' => $items];
  }

  /**
   * Build a renderable array of permissions.
   *
   * @param array $permissions
   *   The permissions to be rendered.
   *
   * @return array
   *   A renderable array of permissions.
   */
  public static function buildPermissions(array $permissions) {
    $permissions = array_intersect_key([
      'create' => t('Create submissions'),
      'view_any' => t('View any submissions'),
      'update_any' => t('Update any submissions'),
      'delete_any' => t('Delete any submissions'),
      'purge_any' => t('Purge any submissions'),
      'view_own' => t('View own submissions'),
      'update_own' => t('Update own submissions'),
      'delete_own' => t('Delete own submissions'),
      'administer' => t('Administer submissions'),
      'test' => t('Test webform'),
    ], array_flip($permissions));
    return ['#theme' => 'item_list', '#items' => $permissions];
  }

}
