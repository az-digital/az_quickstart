<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Repare webform configuration form.
 */
class WebformAdminConfigRepairForm extends ConfirmFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_repair_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Repair webform configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['description'] = [
      'warning' => [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Repair and remove older Webform configuration files.') . '<br/>' .
          '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
      ],
      'title' => [
          '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Repair webform submission storage schema'),
          $this->t('Repair admin configuration'),
          $this->t('Repair webform settings'),
          $this->t('Repair webform handlers'),
          $this->t('Repair webform field storage definitions'),
          $this->t('Repair webform submission storage schema'),
          $this->t('Remove webform submission translation settings'),
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Repair configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('webform.config.advanced');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Copied from:
    // @see \Drupal\webform\Commands\WebformCliService::drush_webform_repair
    \Drupal::moduleHandler()->loadInclude('webform', 'install');

    $this->messenger()->addMessage($this->t('Repairing webform submission storage schema…'));
    _webform_update_webform_submission_storage_schema();

    $this->messenger()->addMessage($this->t('Repairing admin configuration…'));
    _webform_update_admin_settings(TRUE);

    $this->messenger()->addMessage($this->t('Repairing webform HTML editor…'));
    _webform_update_html_editor();

    $this->messenger()->addMessage($this->t('Repairing webform settings…'));
    _webform_update_webform_settings();

    $this->messenger()->addMessage($this->t('Repairing webform handlers…'));
    _webform_update_webform_handler_settings();

    $this->messenger()->addMessage($this->t('Repairing webform actions…'));
    _webform_update_actions();

    $this->messenger()->addMessage($this->t('Repairing webform field storage definitions…'));
    _webform_update_field_storage_definitions();

    $this->messenger()->addMessage($this->t('Repairing webform submission storage schema…'));
    _webform_update_webform_submission_storage_schema();

    if ($this->moduleHandler->moduleExists('webform_entity_print')) {
      $this->messenger()->addMessage($this->t('Repairing webform entity print settings…'));
      $this->moduleHandler->loadInclude('webform_entity_print', 'install');
      webform_entity_print_install();
    }

    $this->messenger()->addMessage($this->t('Removing (unneeded) webform submission translation settings…'));
    _webform_update_webform_submission_translation();

    drupal_flush_all_caches();

    $this->messenger()->addStatus($this->t('Webform configuration has been repaired.'));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
