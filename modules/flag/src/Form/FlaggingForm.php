<?php

namespace Drupal\flag\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\flag\Plugin\ActionLink\FormEntryInterface;

/**
 * Provides the flagging form for field entry.
 */
class FlaggingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    $flagging = $this->getEntity();
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $flagging->getFlag();
    $action_link = $flag->getLinkTypePlugin();

    $create_button_text = $this->t('Create Flagging');
    if ($action_link instanceof FormEntryInterface) {
      $create_button_text = $action_link->getCreateButtonText();
    }

    if ($this->entity->isNew()) {
      $actions['submit']['#value'] = $create_button_text;
    }
    else {
      $update_button_text = $this->t('Update Flagging');
      if ($action_link instanceof FormEntryInterface) {
        $update_button_text = $action_link->getUpdateButtonText();
      }
      $actions['submit']['#value'] = $update_button_text;
    }

    // Customize the delete link.
    if (isset($actions['delete'])) {
      // @todo Why does the access call always fail?
      unset($actions['delete']['#access']);
      $delete_button_text = $this->t('Delete Flagging');
      if ($action_link instanceof FormEntryInterface) {
        $delete_button_text = $action_link->getDeleteButtonText();
      }
      $actions['delete']['#title'] = $delete_button_text;

      // Build the delete url from route. We need to build this manually
      // otherwise Drupal will try to build the flagging entity's delete-form
      // link. Since that route doesn't use the flagging ID, Drupal can't build
      // the link for us.
      /** @var \Drupal\flag\FlaggingInterface $flagging */
      $flagging = $this->entity;
      $route_params = [
        'flag' => $flagging->getFlagId(),
        'entity_id' => $flagging->getFlaggableId(),
        'destination' => $this->getRequest()->query->get('destination'),
      ];
      $url = Url::fromRoute('flag.field_entry.delete', $route_params);

      $actions['delete']['#url'] = $url;
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    return $this->entity->save();
  }

}
