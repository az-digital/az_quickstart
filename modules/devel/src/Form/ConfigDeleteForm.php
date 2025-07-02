<?php

namespace Drupal\devel\Form;

use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit config variable form.
 */
class ConfigDeleteForm extends FormBase implements ConfirmFormInterface {

  /**
   * Logger service.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');
    $instance->logger = $container->get('logger.channel.devel');
    $instance->configFactory = $container->get('config.factory');
    $instance->requestStack = $container->get('request_stack');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_config_system_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = ''): array {
    $config = $this->configFactory->get($config_name);
    if ($config->isNew()) {
      $this->messenger->addError($this->t('Config @name does not exist in the system.', ['@name' => $config_name]));
      return $form;
    }

    $form['#title'] = $this->getQuestion();
    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = ['#markup' => $this->getDescription()];
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }

    $form['name'] = [
      '#type' => 'value',
      '#value' => $config_name,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
      '#submit' => [
        function (array &$form, FormStateInterface $form_state): void {
          $this->submitForm($form, $form_state);
        },
      ],
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->requestStack->getCurrentRequest());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config_name = $form_state->getValue('name');
    try {
      $this->configFactory->getEditable($config_name)->delete();
      $this->messenger->addStatus($this->t('Configuration variable %variable was successfully deleted.', ['%variable' => $config_name]));
      $this->logger->info('Configuration variable %variable was successfully deleted.', ['%variable' => $config_name]);

      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
      $this->logger->error('Error deleting configuration variable %variable : %error.', [
        '%variable' => $config_name,
        '%error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('devel.configs_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this configuration?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName(): string {
    return 'confirm';
  }

}
