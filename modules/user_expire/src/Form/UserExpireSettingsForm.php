<?php

namespace Drupal\user_expire\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User expire admin settings form.
 */
class UserExpireSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a \Drupal\user_expire\Form\UserExpireSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_expire_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_expire.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#type' => 'container',
      '#markup' => $this->t('Set global user expiration settings on this page. Individual user expirations can be set via their user account edit form.'),
    ];
    $form['help_definitions'] = [
      '#type' => 'fieldset',
      '#markup' => $this->t('<dl>
        <dt><strong>Inactivity</strong>:</dt>
        <dd>The amount of time a user has not logged into the site. After this time, the account may be expired.</dd>
        <dt><strong>Expiration</strong>:</dt>
        <dd>When a user account is blocked on a specific date, or after the specified amount of time due to inactivity. Blocked users are unable to log in or interact with the site.</dd>
      </dl>'
      ),

    ];
    // Reference table for seconds conversion.
    $form['time_reference_table'] = [
      '#type' => 'details',
      '#title' => $this->t('Time reference table'),
      '#description' => $this->t('Use this table as a reference for setting time durations in seconds.'),
      '#open' => FALSE,
    ];

    $form['time_reference_table']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Time Increment'),
        $this->t('Seconds'),
      ],
      '#rows' => [
        [$this->t('1 Hour'), $this->t('3600')],
        [$this->t('1 Day'), $this->t('86400')],
        [$this->t('2 Days'), $this->t('172800')],
        [$this->t('1 Week'), $this->t('604800')],
        [$this->t('1 Month (30 Days)'), $this->t('2592000')],
        [$this->t('90 Days'), $this->t('7776000')],
        [$this->t('1 Year (365 Days)'), $this->t('31536000')],
      ],
    ];

    // Get the rules and the roles.
    $config = $this->config('user_expire.settings');
    $rules = $config->get('user_expire_roles') ?: [];
    $user_roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $roles = [];

    foreach ($user_roles as $rid => $role) {
      $roles[$role->id()] = $role->get('label');
    }

    // Save the current roles for use in submit handler.
    $form['current_roles'] = [
      '#type' => 'value',
      '#value' => $roles,
    ];

    // Now show boxes for each role.
    $form['user_expire_roles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account expiration settings by role'),
      '#description' => $this->t('Set inactivity expiration times for each user role. Enter 0 to disable expiration for a role. Enter 7776000 for 90 days.'),
    ];

    foreach ($roles as $rid => $role) {
      if ($rid === RoleInterface::ANONYMOUS_ID) {
        continue;
      }

      $form['user_expire_roles']['user_expire_' . $rid] = [
        '#type' => 'number',
        '#title' => $this->t('Seconds of inactivity before expiring %role users', ['%role' => $role]),
        '#default_value' => $rules[$rid] ?? 0,
        '#min' => 0,
      ];
    }
    // Account expiration warning settings.
    $form['warnings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account expiration warning settings'),
    ];

    // Enable or disable account expiration warnings.
    $form['warnings']['send_expiration_warnings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send account expiration warning emails'),
      '#default_value' => $config->get('send_expiration_warnings') ?? TRUE,
      '#description' => $this->t('If enabled, account expiration warning emails will be sent to users, starting at the configured offset time before account expiration.'),
    ];

    $form['warnings']['extra_settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the additional settings when expiration warnings are disabled.
        'invisible' => [
          'input[name="send_expiration_warnings"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['warnings']['extra_settings']['frequency'] = [
      '#type' => 'number',
      '#title' => $this->t('Notification frequency time (in seconds)'),
      '#default_value' => $config->get('frequency') ?: 172800,
      '#description' => $this->t('Specify how often (in seconds) warning emails should be sent. For example, 86400 = 1 day.'),
      '#min' => 0,
    ];

    $form['warnings']['extra_settings']['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Warning offset time (in seconds)'),
      '#default_value' => $config->get('offset') ?: 604800,
      '#description' => $this->t('Specify how far in advance (in seconds) to start sending warnings before account expiration. For example, 604800 = 7 days.'),
      '#min' => 0,
    ];

    // Account expiration warning email template.
    $form['warnings']['extra_settings']['mail'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account expiration warning message template'),
    ];

    $form['warnings']['extra_settings']['mail']['expiration_warning_mail_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('expiration_warning_mail.subject') ?? '',
      '#description' => $this->t('Subject line for account expiration warning email messages.'),
      '#maxlength' => 180,
    ];

    $form['warnings']['extra_settings']['mail']['expiration_warning_mail_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('expiration_warning_mail.body') ?? '',
      '#description' => $this->t('Body for account expiration warning email messages.'),
      '#rows' => 15,
    ];

    $form['warnings']['extra_settings']['mail']['help'] = [
      '#markup' => $this->t('Available token variables for use in the email are: [site:name], [site:url], [site:mail], [user:display-name], [user:account-name], [user:mail], [site:login-url], [site:url-brief], [user:edit-url], [user:one-time-login-url], [user:cancel-url]'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!ctype_digit($form_state->getValue('frequency'))) {
      $form_state->setErrorByName('frequency', $this->t('Frequency time must be an integer.'));
    }

    if (!ctype_digit($form_state->getValue('offset'))) {
      $form_state->setErrorByName('offset', $this->t('Warning offset time must be an integer.'));
    }

    foreach ($form_state->getValue('current_roles') as $rid => $role) {
      if ($rid === RoleInterface::ANONYMOUS_ID) {
        continue;
      }

      if (!ctype_digit($form_state->getValue('user_expire_' . $rid))) {
        $form_state->setErrorByName('user_expire_' . $rid, $this->t('Inactivity period must be an integer.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $config = $this->config('user_expire.settings');

    if (!empty($form_state->getValue('frequency'))) {
      $config->set('frequency', (int) $form_state->getValue('frequency'));
    }

    if (!empty($form_state->getValue('offset'))) {
      $config->set('offset', (int) $form_state->getValue('offset'));
    }

    // Insert the rows that were inserted.
    $rules = $config->get('user_expire_roles') ?: [];
    foreach ($form_state->getValue('current_roles') as $rid => $role) {
      // Only save non-zero values.
      if (!is_null($form_state->getValue('user_expire_' . $rid))) {
        $rules[$rid] = (int) $form_state->getValue('user_expire_' . $rid);
      }
    }

    $config->set('user_expire_roles', $rules);

    // The notification email.
    $config->set('send_expiration_warnings', $form_state->getValue('send_expiration_warnings'));

    $config->set('expiration_warning_mail.subject', $form_state->getValue('expiration_warning_mail_subject'));
    $config->set('expiration_warning_mail.body', $form_state->getValue('expiration_warning_mail_body'));

    $config->save();
  }

}
