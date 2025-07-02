<?php

namespace Drupal\field_group\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\field_group\FieldgroupUi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for removing a fieldgroup from a bundle.
 */
class FieldGroupDeleteForm extends ConfirmFormBase {

  /**
   * The fieldgroup to delete.
   *
   * @var object
   */
  protected $fieldGroup;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * FieldGroupDeleteForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   */
  public function __construct(MessengerInterface $messenger, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->messenger = $messenger;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_group_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_group_name = NULL, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $mode = $this->getRequest()->attributes->get('form_mode_name');
    }
    else {
      $mode = $this->getRequest()->attributes->get('view_mode_name');
    }

    if (empty($mode)) {
      $mode = 'default';
    }

    $this->fieldGroup = field_group_load_field_group($field_group_name, $entity_type_id, $bundle, $context, $mode);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    $bundle_label = $bundles[$this->fieldGroup->entity_type][$this->fieldGroup->bundle]['label'];

    field_group_delete_field_group($this->fieldGroup);

    $this->messenger->addMessage($this->t('The group %group has been deleted from the %type content type.', [
      '%group' => $this->fieldGroup->label,
      '%type' => $bundle_label,
    ]));

    // Redirect.
    $form_state->setRedirectUrl($this->getCancelUrl());

  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the group %group?', ['%group' => $this->fieldGroup->label]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return FieldgroupUi::getFieldUiRoute($this->fieldGroup);
  }

}
