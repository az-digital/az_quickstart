<?php

namespace Drupal\migrate_queue_importer\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Build the add and edit form for cron migration entity.
 */
class CronMigrationForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $migration = NULL;
    /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $entity **/
    $entity = $this->entity;

    if ($entity->migration) {
      $migration = $this->entityTypeManager->getStorage('migration')
        ->load($entity->migration);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['migration'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Migration'),
      '#required' => TRUE,
      '#default_value' => $migration instanceof EntityInterface ? $migration : '',
      '#target_type' => 'migration',
      '#selection_handler' => 'default:migration',
    ];
    $form['time'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval'),
      '#min' => 0,
      '#default_value' => $entity->time,
      '#description' => $this->t('Time in seconds.'),
    ];
    $form['update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update'),
      '#default_value' => $entity->update,
    ];
    $form['sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Synchronize'),
      '#default_value' => $entity->sync,
    ];
    $form['ignore_dependencies'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore dependencies'),
      '#default_value' => $entity->ignore_dependencies,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status) {
      $this->messenger()
        ->addMessage($this->t('Saved the %label cron migration.', [
          '%label' => $entity->label(),
        ]));
    }
    else {
      $this->messenger()
        ->addMessage($this->t('The %label cron migration was not saved.', [
          '%label' => $entity->label(),
        ]), MessengerInterface::TYPE_ERROR);
    }

    $form_state->setRedirect('entity.cron_migration.collection');

    return $status;
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('cron_migration')->getQuery()
      ->condition('id', $id)
      ->accessCheck(TRUE)
      ->execute();
    return (bool) $entity;
  }

}
