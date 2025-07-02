<?php

namespace Drupal\entity_reference_revisions\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OrphanedCompositeEntitiesDeleteForm.
 *
 * @package Drupal\entity_reference_revisions\Form
 */
class OrphanedCompositeEntitiesDeleteForm extends FormBase {

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity reference revisions orphan purger service.
   *
   * @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger
   */
  protected $purger;

  /**
   * OrphanedCompositeEntitiesDeleteForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The EntityTypeManager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger $purger
   *   The entity reference revisions orphan purger.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, MessengerInterface $messenger, EntityReferenceRevisionsOrphanPurger $purger) {
    $this->entityTypeManager = $entity_manager;
    $this->messenger = $messenger;
    $this->purger = $purger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('entity_reference_revisions.orphan_purger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'orphaned_composite_entities_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->messenger->addWarning($this->t('The submission of the current form can cause the deletion of entities that are still used, backup all data first.'), 'warning');
    $form['description'] = [
      '#markup' => $this->t('Delete orphaned composite entities revisions that are no longer referenced. If there are no revisions left, the entity will be deleted as long as it is not used.'),
    ];
    $options = [];
    foreach ($this->purger->getCompositeEntityTypes() as $entity_type) {
      $options[$entity_type->id()] = $entity_type->getLabel();
    }
    $form['composite_entity_types'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => $this->t('Select the entity types to check for orphans'),
      '#options' => $options,
      '#default_value' => array_keys($options),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Delete orphaned composite revisions'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->purger->setBatch(array_filter($form_state->getValue('composite_entity_types')));
  }

}
