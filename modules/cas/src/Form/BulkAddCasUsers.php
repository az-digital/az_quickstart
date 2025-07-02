<?php

namespace Drupal\cas\Form;

use Drupal\cas\Exception\CasLoginException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\user\RoleInterface;

/**
 * Class BulkAddCasUsers.
 *
 * A form for bulk registering CAS users.
 */
class BulkAddCasUsers extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_add_cas_users';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Use this form to pre-register one or more users, allowing them to log in using CAS.'),
      '#suffix' => '</p>',
    ];
    $form['cas_usernames'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CAS username(s)'),
      '#required' => TRUE,
      '#default_value' => '',
      '#description' => $this->t('Enter one username per line.'),
    ];

    $form['email_hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email address'),
      '#description' => $this->t("The email domain name used to combine with the username to form the user's email address. If your user's email address is usually provided via a CAS attribute, that will not work here because CAS attributes are not available."),
      '#field_prefix' => $this->t('username@'),
      '#required' => TRUE,
      '#default_value' => $this->config('cas.settings')->get('user_accounts.email_hostname'),
    ];

    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Role(s)'),
      '#options' => $roles,
      '#description' => $this->t('Optionally assign one or more roles to each user. Note that if you have CAS configured to assign roles during automatic registration on login, those will be ignored.'),
    ];
    $form['roles'][RoleInterface::AUTHENTICATED_ID] = [
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['extra_info'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t("Note that because CAS attributes are only available when a user authenticates with CAS, any role or field assignment based on attributes will not be available."),
      '#suffix' => '</p>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create new accounts'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roles = array_filter($form_state->getValue('roles'));
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $roles = array_keys($roles);

    $cas_usernames = trim($form_state->getValue('cas_usernames'));
    $cas_usernames = preg_split('/[\n\r|\r|\n]+/', $cas_usernames);

    $email_hostname = trim($form_state->getValue('email_hostname'));

    $operations = [];
    foreach ($cas_usernames as $cas_username) {
      $cas_username = trim($cas_username);
      if (!empty($cas_username)) {
        $operations[] = [
          '\Drupal\cas\Form\BulkAddCasUsers::userAdd',
          [$cas_username, $roles, $email_hostname],
        ];
      }
    }

    $batch = [
      'title' => $this->t('Creating CAS users...'),
      'operations' => $operations,
      'finished' => '\Drupal\cas\Form\BulkAddCasUsers::userAddFinished',
      'progress_message' => $this->t('Processed @current out of @total.'),
    ];

    batch_set($batch);
  }

  /**
   * Perform a single CAS user creation batch operation.
   *
   * Callback for batch_set().
   *
   * @param string $cas_username
   *   The CAS username, which will also become the Drupal username.
   * @param array $roles
   *   An array of roles to assign to the user.
   * @param string $email_hostname
   *   The hostname to combine with the username to create the email address.
   * @param array $context
   *   The batch context array, passed by reference.
   */
  public static function userAdd($cas_username, array $roles, $email_hostname, array &$context) {
    $cas_user_manager = \Drupal::service('cas.user_manager');

    // Back out of an account already has this CAS username.
    $existing_uid = $cas_user_manager->getUidForCasUsername($cas_username);
    if ($existing_uid) {
      $context['results']['messages']['already_exists'][] = $cas_username;
      return;
    }

    $user_properties = [
      'roles' => $roles,
      'mail' => $cas_username . '@' . $email_hostname,
    ];

    try {
      /** @var \Drupal\user\UserInterface $user */
      $user = $cas_user_manager->register($cas_username, $cas_username, $user_properties);
      $context['results']['messages']['created'][] = $user->toLink()->toString();
    }
    catch (CasLoginException $e) {
      \Drupal::logger('cas')->error('CasLoginException when registering user with name %name: %e', [
        '%name' => $cas_username,
        '%e' => $e->getMessage(),
      ]);
      $context['results']['messages']['errors'][] = $cas_username;
      return;
    }
  }

  /**
   * Complete CAS user creation batch process.
   *
   * Callback for batch_set().
   *
   * Consolidates message output.
   */
  public static function userAddFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      if (!empty($results['messages']['errors'])) {
        $messenger->addError(t('An error was encountered creating accounts for the following users (check logs for more details): %usernames', [
          '%usernames' => implode(', ', $results['messages']['errors']),
        ]));
      }
      if (!empty($results['messages']['already_exists'])) {
        $messenger->addError(t('The following accounts were not registered because existing accounts are already using the usernames: %usernames', [
          '%usernames' => implode(', ', $results['messages']['already_exists']),
        ]));
      }
      if (!empty($results['messages']['created'])) {
        $userLinks = Markup::create(implode(', ', $results['messages']['created']));
        $messenger->addStatus(t('Successfully created accounts for the following usernames: %usernames', [
          '%usernames' => $userLinks,
        ]));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addError(t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]));
    }
  }

}
