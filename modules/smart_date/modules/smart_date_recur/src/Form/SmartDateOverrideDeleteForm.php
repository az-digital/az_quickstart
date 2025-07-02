<?php

namespace Drupal\smart_date_recur\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smart_date_recur\Controller\Instances;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deletion confirmation form for Smart Date Overrides.
 */
class SmartDateOverrideDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Definition of form entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a deletion confirmation form for smart date overrides.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, ClassResolverInterface $classResolver, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->classResolver = $classResolver;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('class_resolver'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this
      ->t('Are you sure you want to revert this instance to its default values?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $rrule = $this->entity->rrule->getString();
    return new Url('smart_date_recur.instances', ['rrule' => (int) $rrule]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this
      ->t('Revert to Default');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Delete override entity, if it exists.
    $this->entity
      ->delete();
    $rrid = $this->entity->rrule->getString();
    /** @var \Drupal\smart_date_recur\Entity\SmartDateRule $rrule */
    $rrule = $this->entityTypeManager->getStorage('smart_date_rule')->load($rrid);
    /** @var \Drupal\smart_date_recur\Controller\Instances $instanceController */
    $instanceController = $this->classResolver->getInstanceFromDefinition(Instances::class);
    // Force refresh of parent entity.
    $instanceController->applyChanges($rrule);
    // Output message about operation performed.
    $this->messenger()->addMessage($this->t('The instance has been reverted to default.'));
    $form_state
      ->setRedirectUrl($this
        ->getCancelUrl());
  }

}
