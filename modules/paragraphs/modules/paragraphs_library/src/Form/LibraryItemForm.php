<?php

namespace Drupal\paragraphs_library\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for paragraph type forms.
 */
class LibraryItemForm extends ContentEntityForm {

  /**
   * @var \Drupal\paragraphs_library\LibraryItemInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // If the entity is not new, add the entity id. This will allow having more
    // than one form open when editing a library item within another.
    // To alter this form use hook_form_BASE_FORM_ID_alter.
    if ($this->entity->id()) {
      return 'paragraphs_library_item_edit_form_' . $this->entity->id();
    }
    return 'paragraphs_library_item_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $insert = $this->entity->isNew();
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.paragraphs_library_item.collection');
    if ($insert) {
      $this->messenger->addMessage($this->t('Paragraph %label has been created.', ['%label' => $this->entity->label()]));
    }
    else {
      $this->messenger->addMessage($this->t('Paragraph %label has been updated.', ['%label' => $this->entity->label()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNewRevisionDefault() {
    return TRUE;
  }

}
