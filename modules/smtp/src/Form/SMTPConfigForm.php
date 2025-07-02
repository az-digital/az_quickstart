<?php

namespace Drupal\smtp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the SMTP admin settings form.
 */
class SMTPConfigForm extends ConfigFormBase {

  /**
   * Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Email Validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');
    $instance->emailValidator = $container->get('email.validator');
    $instance->currentUser = $container->get('current_user');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smtp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('smtp.settings');

    // Don't overwrite the default if MailSystem module is enabled.
    $mailsystem_enabled = $this->moduleHandler->moduleExists('mailsystem');

    if ($config->get('smtp_on')) {
      $this->messenger->addMessage($this->t('SMTP module is active.'));
      if ($mailsystem_enabled) {
        $this->messenger->addWarning($this->t('SMTP module will use the mailsystem module upon config save.'));
      }
    }
    elseif ($mailsystem_enabled) {
      $this->messenger->addMessage($this->t('SMTP module is managed by <a href=":mailsystem">the mail system module</a>', [':mailsystem' => Url::fromRoute('mailsystem.settings')->toString()]));
    }
    else {
      $this->messenger->addMessage($this->t('SMTP module is INACTIVE.'));
    }
    // Add Debugging warning.
    if ($config->get('smtp_debugging')) {
      $this->messenger->addWarning($this->t('SMTP debugging is on, ensure it is <a href="#edit-smtp-debugging">disabled</a> before using in production.'));
    }

    $this->messenger->addMessage($this->t('Disabled fields are overridden in site-specific configuration file.'), 'warning');

    if ($mailsystem_enabled) {
      $form['onoff']['smtp_on']['#type'] = 'value';
      $form['onoff']['smtp_on']['#value'] = 'mailsystem';
    }
    else {
      $form['onoff'] = [
        '#type'  => 'details',
        '#title' => $this->t('Install options'),
        '#open' => TRUE,
      ];
      $form['onoff']['smtp_on'] = [
        '#type' => 'radios',
        '#title' => $this->t('Set SMTP as the default mailsystem'),
        '#default_value' => $config->get('smtp_on') ? 'on' : 'off',
        '#options' => ['on' => $this->t('On'), 'off' => $this->t('Off')],
        '#description' => $this->t('When on, all mail is passed through the SMTP module.'),
        '#disabled' => $this->isOverridden('smtp_on'),
      ];

      // Force Disabling if PHPmailer doesn't exist.
      if (!class_exists(PHPMailer::class)) {
        $form['onoff']['smtp_on']['#disabled'] = TRUE;
        $form['onoff']['smtp_on']['#default_value'] = 'off';
        $form['onoff']['smtp_on']['#description'] = $this->t('<strong>SMTP cannot be enabled because the PHPMailer library is missing.</strong>');
      }
    }

    $form['server'] = [
      '#type'  => 'details',
      '#title' => $this->t('SMTP server settings'),
      '#open' => TRUE,
    ];
    $form['server']['smtp_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP server'),
      '#default_value' => $config->get('smtp_host'),
      '#description' => $this->t('The address of your outgoing SMTP server.'),
      '#disabled' => $this->isOverridden('smtp_host'),
    ];
    $form['server']['smtp_hostbackup'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMTP backup server'),
      '#default_value' => $config->get('smtp_hostbackup'),
      '#description' => $this->t("The address of your outgoing SMTP backup server. If the primary server can't be found this one will be tried. This is optional."),
      '#disabled' => $this->isOverridden('smtp_hostbackup'),
    ];
    $form['server']['smtp_port'] = [
      '#type' => 'number',
      '#title' => $this->t('SMTP port'),
      '#size' => 6,
      '#maxlength' => 6,
      '#default_value' => $config->get('smtp_port'),
      '#description' => $this->t('The default SMTP port is 25, if that is being blocked try 80. Gmail uses 465. See :url for more information on configuring for use with Gmail.',
        [':url' => 'http://gmail.google.com/support/bin/answer.py?answer=13287']),
      '#disabled' => $this->isOverridden('smtp_port'),
    ];

    // Only display the option if openssl is installed.
    if (function_exists('openssl_open')) {
      $encryption_options = [
        'standard' => $this->t('No'),
        'ssl' => $this->t('Use SSL'),
        'tls' => $this->t('Use TLS'),
      ];
      $encryption_description = $this->t('This allows connection to an SMTP server that requires SSL encryption such as Gmail.');
    }
    // If openssl is not installed, use normal protocol.
    else {
      $config->set('smtp_protocol', 'standard');
      $encryption_options = ['standard' => $this->t('No')];
      $encryption_description = $this->t('Your PHP installation does not have SSL enabled. See the :url page on php.net for more information. Gmail requires SSL.',
        [':url' => 'http://php.net/openssl']);
    }

    $form['server']['smtp_protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Use encrypted protocol'),
      '#default_value' => $config->get('smtp_protocol'),
      '#options' => $encryption_options,
      '#description' => $encryption_description,
      '#disabled' => $this->isOverridden('smtp_protocol'),
    ];

    $form['server']['smtp_autotls'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enable TLS encryption automatically'),
      '#default_value' => $config->get('smtp_autotls') ? 'on' : 'off',
      '#options' => ['on' => $this->t('On'), 'off' => $this->t('Off')],
      '#description' => $this->t('Whether to enable TLS encryption automatically if a server supports it, even if the protocol is not set to "tls".'),
      '#disabled' => $this->isOverridden('smtp_autotls'),
    ];

    $form['server']['smtp_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#size' => 6,
      '#maxlength' => 6,
      '#default_value' => $config->get('smtp_timeout'),
      '#description' => $this->t('Amount of seconds for the SMTP commands to timeout.'),
      '#disabled' => $this->isOverridden('smtp_timeout'),
    ];

    $form['auth'] = [
      '#type' => 'details',
      '#title' => $this->t('SMTP Authentication'),
      '#description' => $this->t('Leave blank if your SMTP server does not require authentication.'),
      '#open' => TRUE,
    ];
    $form['auth']['smtp_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('smtp_username'),
      '#description' => $this->t('SMTP Username.'),
      '#disabled' => $this->isOverridden('smtp_username'),
    ];
    $form['auth']['smtp_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('smtp_password'),
      '#description' => $this->t("SMTP password. If you have already entered your password before, you should leave this field blank, unless you want to change the stored password. Please note that this password will be stored as plain-text inside Drupal's core configuration variables."),
      '#disabled' => $this->isOverridden('smtp_password'),
    ];

    $form['email_options'] = [
      '#type'  => 'details',
      '#title' => $this->t('E-mail options'),
      '#open' => TRUE,
    ];
    $form['email_options']['smtp_from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail from address'),
      '#default_value' => $config->get('smtp_from'),
      '#description' => $this->t('The e-mail address that all e-mails will be from.'),
      '#disabled' => $this->isOverridden('smtp_from'),
    ];
    $form['email_options']['smtp_fromname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail from name'),
      '#default_value' => $config->get('smtp_fromname'),
      '#description' => $this->t('The name that all e-mails will be from. If left blank will use a default of: @name . Some providers (such as Office365) may ignore this field. For more information, please check SMTP module documentation and your email provider documentation.',
          ['@name' => $this->configFactory->get('system.site')->get('name')]),
      '#disabled' => $this->isOverridden('smtp_fromname'),
    ];
    $form['email_options']['smtp_allowhtml'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow to send e-mails formatted as HTML'),
      '#default_value' => $config->get('smtp_allowhtml'),
      '#description' => $this->t('Checking this box will allow HTML formatted e-mails to be sent with the SMTP protocol.'),
      '#disabled' => $this->isOverridden('smtp_allowhtml'),
    ];

    $form['client'] = [
      '#type'  => 'details',
      '#title' => $this->t('SMTP client settings'),
      '#open' => TRUE,
    ];
    $form['client']['smtp_client_hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $config->get('smtp_client_hostname'),
      '#description' => $this->t('The hostname to use in the Message-Id and Received headers, and as the default HELO string. Leave blank for using %server_name.',
        ['%server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost.localdomain']),
      '#disabled' => $this->isOverridden('smtp_client_hostname'),
    ];
    $form['client']['smtp_client_helo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HELO'),
      '#default_value' => $config->get('smtp_client_helo'),
      '#description' => $this->t('The SMTP HELO/EHLO of the message. Defaults to hostname (see above).'),
      '#disabled' => $this->isOverridden('smtp_client_helo'),
    ];

    $form['email_test'] = [
      '#type' => 'details',
      '#title' => $this->t('Send test e-mail'),
      '#open' => TRUE,
    ];
    $form['email_test']['smtp_test_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail address to send a test e-mail to'),
      '#default_value' => '',
      '#description' => $this->t('Type in an address to have a test e-mail sent there.'),
    ];
    $form['email_test']['smtp_reroute_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail address to reroute all emails to'),
      '#default_value' => $config->get('smtp_reroute_address'),
      '#description' => $this->t('All emails sent by the site will be rerouted to this email address; use with caution.'),
    ];
    $form['smtp_debugging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#default_value' => $config->get('smtp_debugging'),
      '#description' => $this->t('Checking this box will print SMTP messages from the server for every e-mail that is sent.
      <br /><strong>Warning!</strong> Debugging interrupts the request and will cause AJAX, Batch, and other operations to fail. Use in test environments only.'),
      '#disabled' => $this->isOverridden('smtp_debugging'),
    ];
    $form['smtp_debug_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Debug level'),
      '#options' => [
        1 => $this->t('Debug client'),
        2 => $this->t('Debug server'),
        3 => $this->t('Debug connection'),
        4 => $this->t('Debug lowlevel'),
      ],
      '#default_value' => $config->get('smtp_debug_level'),
      '#description' => $this->t('Choose the appropriate log level.'),
      '#disabled' => $this->isOverridden('smtp_debug_level'),
      '#states' => [
        'visible' => [
          ':input[name="smtp_debugging"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['server']['smtp_keepalive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Turn on the SMTP keep alive feature'),
      '#default_value' => $config->get('smtp_keepalive'),
      '#description' => $this->t('Enabling this option will keep the SMTP connection open instead of it being openned and then closed for each mail'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * @param string $name
   *   SMTP settings key.
   *
   * @return bool
   *   Boolean.
   */
  protected function isOverridden($name) {
    $original = $this->configFactory->getEditable('smtp.settings')->get($name);
    $current = $this->configFactory->get('smtp.settings')->get($name);
    return $original != $current;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['smtp_on'] !== 'off' && $values['smtp_host'] == '') {
      $form_state->setErrorByName('smtp_host', $this->t('You must enter an SMTP server address.'));
    }

    if ($values['smtp_on'] !== 'off' && $values['smtp_port'] == '') {
      $form_state->setErrorByName('smtp_port', $this->t('You must enter an SMTP port number.'));
    }

    if ($values['smtp_timeout'] == '' || $values['smtp_timeout'] < 1) {
      $form_state->setErrorByName('smtp_timeout', $this->t('You must enter a Timeout value greater than 0.'));
    }

    if ($values['smtp_from'] && !$this->emailValidator->isValid($values['smtp_from'])) {
      $form_state->setErrorByName('smtp_from', $this->t('The provided from e-mail address is not valid.'));
    }

    if ($values['smtp_test_address'] && !$this->emailValidator->isValid($values['smtp_test_address'])) {
      $form_state->setErrorByName('smtp_test_address', $this->t('The provided test e-mail address is not valid.'));
    }

    if ($values['smtp_reroute_address'] && !$this->emailValidator->isValid($values['smtp_reroute_address'])) {
      $form_state->setErrorByName('smtp_reroute_address', $this->t('The provided reroute e-mail address is not valid.'));
    }

    // If username is set empty, we must set both
    // username/password empty as well.
    if (empty($values['smtp_username'])) {
      $values['smtp_password'] = '';
    }

    // A little hack. When form is presented,
    // the password is not shown (Drupal way of doing).
    // So, if user submits the form without changing the password,
    // we must prevent it from being reset.
    elseif (empty($values['smtp_password'])) {
      $form_state->unsetValue('smtp_password');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('smtp.settings');
    $mail_config = $this->configFactory->getEditable('system.mail');
    $mail_system = $mail_config->get('interface.default');

    // Updating config vars.
    if (isset($values['smtp_password']) && !$this->isOverridden('smtp_password')) {
      $config->set('smtp_password', $values['smtp_password']);
    }
    if (!$this->isOverridden('smtp_on')) {
      $config->set('smtp_on', $values['smtp_on'] == 'on')->save();
    }
    if (!$this->isOverridden('smtp_autotls')) {
      $config->set('smtp_autotls', $values['smtp_autotls'] == 'on')->save();
    }
    $config_keys = [
      'smtp_host',
      'smtp_hostbackup',
      'smtp_port',
      'smtp_protocol',
      'smtp_timeout',
      'smtp_username',
      'smtp_from',
      'smtp_fromname',
      'smtp_client_hostname',
      'smtp_client_helo',
      'smtp_allowhtml',
      'smtp_test_address',
      'smtp_reroute_address',
      'smtp_debugging',
      'smtp_debug_level',
      'smtp_keepalive',
    ];
    foreach ($config_keys as $name) {
      if (!$this->isOverridden($name)) {
        $config->set($name, $values[$name])->save();
      }
    }

    // Set as default mail system if module is enabled.
    if ($config->get('smtp_on') ||
        ($this->isOverridden('smtp_on') && $values['smtp_on'] == 'on')) {
      if ($mail_system != 'SMTPMailSystem') {
        $config->set('prev_mail_system', $mail_system);
      }
      $mail_system = 'SMTPMailSystem';
      $mail_config->set('interface.default', $mail_system)->save();
    }
    else {
      $default_system_mail = 'php_mail';
      $mail_config = $this->configFactory->getEditable('system.mail');
      $default_interface = $mail_config->get('prev_mail_system') ? $mail_config->get('prev_mail_system') : $default_system_mail;
      $mail_config->set('interface.default', $default_interface)->save();
    }

    // If an address was given, send a test e-mail message.
    if ($test_address = $values['smtp_test_address']) {
      $params['subject'] = $this->t('Drupal SMTP test e-mail');
      $params['body'] = [$this->t('If you receive this message it means your site is capable of using SMTP to send e-mail.')];

      // If module is off, send the test message
      // with SMTP by temporarily overriding.
      if (!$config->get('smtp_on')) {
        $original = $mail_config->get('interface');
        $mail_system = 'SMTPMailSystem';
        $mail_config->set('interface.default', $mail_system)->save();
      }

      if ($this->mailManager->mail('smtp', 'smtp-test', $test_address, $this->currentUser->getPreferredLangcode(), $params)) {
        $this->messenger->addMessage($this->t('A test e-mail has been sent to @email via SMTP. You may want to check the log for any error messages.', ['@email' => $test_address]));
      }
      if (!$config->get('smtp_on')) {
        $mail_config->set('interface', $original)->save();
      }

    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smtp.settings',
    ];
  }

}
