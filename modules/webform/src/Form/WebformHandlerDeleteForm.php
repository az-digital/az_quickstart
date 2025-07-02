<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Form for deleting a webform handler.
 */
class WebformHandlerDeleteForm extends WebformDeleteFormBase {

  /**
   * The webform containing the webform handler to be deleted.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform handler to be deleted.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerInterface
   */
  protected $webformHandler;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->isDialog()) {
      $t_args = [
        '@title' => $this->webformHandler->label(),
      ];
      return $this->t("Delete the '@title' handler?", $t_args);
    }
    else {
      $t_args = [
        '%webform' => $this->webform->label(),
        '%title' => $this->webformHandler->label(),
      ];
      return $this->t('Delete the %title handler from the %webform webform?', $t_args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    $t_args = ['%title' => $this->webformHandler->label()];
    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to delete the %title handler?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove this handler'),
          $this->t('Cancel all pending actions'),
        ],
      ],
    ];
  }

  /* ************************************************************************ */
  // Form methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->webform->toUrl('handlers');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_handler_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_handler = NULL) {
    $this->webform = $webform;
    $this->webformHandler = $this->webform->getHandler($webform_handler);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->webform->deleteWebformHandler($this->webformHandler);
    $this->messenger()->addStatus($this->t('The webform handler %name has been deleted.', ['%name' => $this->webformHandler->label()]));
    $form_state->setRedirectUrl($this->webform->toUrl('handlers'));
  }

}
