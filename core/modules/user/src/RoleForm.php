<?php

namespace Drupal\user;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the role entity edit forms.
 *
 * @internal
 */
class RoleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role name'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => $this->t('The name for this role. Example: "Moderator", "Editorial board", "Site architect".'),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => [
        'exists' => ['\Drupal\user\Entity\Role', 'load'],
      ],
    ];
    $form['weight'] = [
      '#type' => 'value',
      '#value' => $entity->getWeight(),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Prevent leading and trailing spaces in role names.
    $entity->set('label', trim($entity->label()));
    $status = $entity->save();

    // Create actions but ignore the authenticated and anonymous roles.
    if (!in_array($entity->id(), [RoleInterface::AUTHENTICATED_ID, RoleInterface::ANONYMOUS_ID])) {
      $type_manager = $this->entityTypeManager->getStorage('action');
      $add_id = 'user_add_role_action.' . $entity->id();
      if (!$type_manager->load($add_id)) {
        $action = $type_manager->create([
          'id' => $add_id,
          'type' => 'user',
          'label' => $this->t('Add the @label role to the selected users', ['@label' => $entity->label()]),
          'configuration' => [
            'rid' => $entity->id(),
          ],
          'plugin' => 'user_add_role_action',
        ]);
        $action->trustData()->save();
      }
      $remove_id = 'user_remove_role_action.' . $entity->id();
      if (!$type_manager->load($remove_id)) {
        $action = $type_manager->create([
          'id' => $remove_id,
          'type' => 'user',
          'label' => $this->t('Remove the @label role from the selected users', ['@label' => $entity->label()]),
          'configuration' => [
            'rid' => $entity->id(),
          ],
          'plugin' => 'user_remove_role_action',
        ]);
        $action->trustData()->save();
      }
    }

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Role %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('user')->info('Role %label has been updated.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    else {
      $this->messenger()->addStatus($this->t('Role %label has been added.', ['%label' => $entity->label()]));
      $this->logger('user')->info('Role %label has been added.', ['%label' => $entity->label(), 'link' => $edit_link]);
    }
    $form_state->setRedirect('entity.user_role.collection');
  }

}
