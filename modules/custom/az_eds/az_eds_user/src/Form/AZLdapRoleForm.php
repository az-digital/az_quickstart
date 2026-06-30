<?php

declare(strict_types=1);

namespace Drupal\az_eds_user\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\az_eds_user\Entity\AZLdapRole;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Quickstart LDAP Role Mapping form.
 */
final class AZLdapRoleForm extends EntityForm {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);
    /** @var \Drupal\az_eds_user\AZLdapRoleInterface $entity */
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [AZLdapRole::class, 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];

    // Generate list of roles.
    $role_options = [];
    $storage = $this->entityTypeManager->getStorage('user_role');
    $ids = $storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->execute();
    $roles = $storage->loadMultiple($ids);
    $unmanaged_roles = [
      RoleInterface::ANONYMOUS_ID,
    ];
    foreach ($roles as $role) {
      $role_id = $role->id();
      if (in_array($role_id, $unmanaged_roles)) {
        continue;
      }
      $role_options[$role_id] = $role->label();
    }

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#required' => TRUE,
      '#default_value' => $entity->get('role'),
      '#description' => $this->t('This mapping will provision users of this role.'),
      '#options' => $role_options,
    ];

    // Generate list of queries.
    $query_options = [];
    $storage = $this->entityTypeManager->getStorage('ldap_query_entity');
    $ids = $storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->execute();
    $queries = $storage->loadMultiple($ids);
    foreach ($queries as $query) {
      $query_options[$query->id()] = $query->label();
    }

    $form['query'] = [
      '#type' => 'select',
      '#title' => $this->t('LDAP Query used to map this role'),
      '#required' => TRUE,
      '#default_value' => $entity->get('query'),
      '#description' => $this->t('At login time, this LDAP query will be executed to determine if a user receives the role.'),
      '#options' => $query_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new LDAP role mapping %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated LDAP role mapping %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
