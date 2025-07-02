<?php

namespace Drupal\smart_date_recur\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smart_date_recur\Entity\SmartDateOverride;

/**
 * Provides AJAX handling of override deletion.
 */
class SmartDateOverrideDeleteAjaxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "smart_date_recur_delete_override_ajaxform";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SmartDateOverride $entity = NULL) {
    $cancelurl = new Url('smart_date_recur.instances', [
      'rrule' => (int) $entity->rrule->value,
      'modal' => TRUE,
    ]);
    $submiturl = new Url('smart_date_recur.instance.revert.ajax', [
      'entity' => $entity->id(),
      'confirm' => 1,
    ]);
    $form['#prefix'] = '<div id="manage-instances">';
    $form['#suffix'] = '</div>';
    $form['message'] = [
      '#markup' => '<p>' . $this->t('Revert this Instance?') . '</p>',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Revert'),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'use-ajax',
          'dialog-cancel',
        ],
      ],
      '#url' => $submiturl,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => [
        'class' => [
          'button',
          'use-ajax',
        ],
      ],
      '#url' => $cancelurl,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

}
