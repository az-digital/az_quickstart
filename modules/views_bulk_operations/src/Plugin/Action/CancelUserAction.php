<?php

namespace Drupal\views_bulk_operations\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cancel a user account.
 */
#[Action(
  id: 'vbo_cancel_user_action',
  label: new TranslatableMarkup('Cancel the selected user accounts'),
  type: 'user'
)]
class CancelUserAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * Object constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\views_bulk_operations\Plugin\Action\Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Config\ImmutableConfig $userConfig
   *   User settings config object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly AccountInterface $currentUser,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly ImmutableConfig $userConfig,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('user.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if ($account->id() === $this->currentUser->id() && (empty($this->context['list']) || \count($this->context['list']) > 1)) {
      $this->messenger()->addError($this->t('The current user account cannot be canceled in a batch operation. Select your account only or cancel it from your account page.'));
    }
    elseif (\intval($account->id()) === 1) {
      $this->messenger()->addError($this->t('The user 1 account (%label) cannot be canceled.', [
        '%label' => $account->label(),
      ]));
    }
    else {
      // Allow other modules to act.
      if ($this->configuration['user_cancel_method'] != 'user_cancel_delete') {
        $this->moduleHandler->invokeAll('user_cancel', [
          $this->configuration,
          $account,
          $this->configuration['user_cancel_method'],
        ]);
      }

      // Finish the batch and actually cancel the account.
      $batch = [
        'title' => $this->t('Cancelling user account'),
        'operations' => [
          [
            '_user_cancel',
            [
              $this->configuration,
              $account,
              $this->configuration['user_cancel_method'],
            ],
          ],
        ],
      ];

      // After cancelling account, ensure that user is logged out.
      if ($account->id() == \Drupal::currentUser()->id()) {
        // Batch API stores data in the session, so use the finished operation
        // to manipulate the current user's session id.
        $batch['finished'] = '_user_cancel_session_regenerate';
      }

      \batch_set($batch);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['user_cancel_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('When cancelling these accounts'),
    ];

    $form['user_cancel_method'] += \user_cancel_methods();

    // Allow to send the account cancellation confirmation mail.
    $form['user_cancel_confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require email confirmation to cancel account'),
      '#default_value' => FALSE,
      '#description' => $this->t('When enabled, the user must confirm the account cancellation via email.'),
    ];
    // Also allow to send account canceled notification mail, if enabled.
    $form['user_cancel_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is canceled'),
      '#default_value' => FALSE,
      '#access' => $this->userConfig->get('notify.status_canceled'),
      '#description' => $this->t('When enabled, the user will receive an email notification after the account has been canceled.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    return $object->access('delete', $account, $return_as_object);
  }

}
