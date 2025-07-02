<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for migration groups.
 *
 * @package Drupal\migrate_tools\Form
 *
 * @ingroup migrate_tools
 */
class MigrationGroupFormBase extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the migration group add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group */
    $migration_group = $this->entity;

    // Build the form.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $migration_group->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $migration_group->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$migration_group->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $migration_group->get('description'),
    ];
    $form['source_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source type'),
      '#description' => $this->t('Type of source system the group is migrating from, for example "Drupal 6" or "WordPress 4".'),
      '#maxlength' => 255,
      '#default_value' => $migration_group->get('source_type'),
    ];

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing migration group.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state): bool {
    $query = $this->entityTypeManager->getStorage('migration_group')
      ->getQuery()
      ->accessCheck(TRUE);

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    // Get the basic actions from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    $migration_group = $this->getEntity();
    $status = $migration_group->save();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addStatus($this->t('Migration group %label has been updated.', ['%label' => $migration_group->label()]));
    }
    else {
      // If we created a new entity...
      $this->messenger()->addStatus($this->t('Migration group %label has been added.', ['%label' => $migration_group->label()]));
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.migration_group.list');
  }

}
