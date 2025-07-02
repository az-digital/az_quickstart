<?php

namespace Drupal\config_update_ui\Form;

use Drupal\config_update\ConfigListInterface;
use Drupal\config_update\ConfigRevertInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a confirmation form for deleting configuration.
 */
class ConfigDeleteConfirmForm extends ConfirmFormBase {

  /**
   * The type of config being deleted.
   *
   * @var string
   */
  protected $type;

  /**
   * The name of the config item being deleted, without the prefix.
   *
   * @var string
   */
  protected $name;

  /**
   * The config lister.
   *
   * @var \Drupal\config_update\ConfigListInterface
   */
  protected $configList;

  /**
   * The config reverter.
   *
   * @var \Drupal\config_update\ConfigRevertInterface
   */
  protected $configRevert;

  /**
   * Constructs a ConfigDeleteConfirmForm object.
   *
   * @param \Drupal\config_update\ConfigListInterface $config_list
   *   The config lister.
   * @param \Drupal\config_update\ConfigRevertInterface $config_update
   *   The config reverter.
   */
  public function __construct(ConfigListInterface $config_list, ConfigRevertInterface $config_update) {
    $this->configList = $config_list;
    $this->configRevert = $config_update;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_update.config_list'),
      $container->get('config_update.config_update')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->type == 'system.simple') {
      $type_label = $this->t('Simple configuration');
    }
    else {
      $definition = $this->configList->getType($this->type);
      if (!$definition) {
        // Make a 404 error if the type doesn't exist.
        throw new NotFoundHttpException();
      }
      $type_label = $definition->get('label');
    }

    // To delete, the configuration item must exist in active storage. Check
    // that and make a 404 error if not.
    $active = $this->configRevert->getFromActive($this->type, $this->name);
    if (!$active) {
      throw new NotFoundHttpException();
    }

    return $this->t('Are you sure you want to delete the %type config %item?',
      [
        '%type' => $type_label,
        '%item' => $this->name,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('config_update_ui.report');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone. Manually deleting configuration from this page can cause problems on your site due to missing dependencies, and should only be done if there is no other way to delete a problematic piece of configuration.');
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
  public function buildForm(array $form, FormStateInterface $form_state, $config_type = NULL, $config_name = NULL) {
    $this->type = $config_type;
    $this->name = $config_name;

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configRevert->delete($this->type, $this->name);

    $this->messenger()->addMessage($this->t('The configuration %item has been deleted.', ['%item' => $this->name]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
