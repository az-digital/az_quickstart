<?php

namespace Drupal\cas\Form;

use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasUserManager;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the CAS settings form.
 */
class CasSettings extends ConfigFormBase {

  /**
   * RequestPath condition that contains the paths to use for gateway.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $gatewayPaths;

  /**
   * RequestPath condition that contains the paths to used for forcedLogin.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $forcedLoginPaths;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\cas\Form\CasSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The condition plugin factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->gatewayPaths = $plugin_factory->createInstance('request_path');
    $this->forcedLoginPaths = $plugin_factory->createInstance('request_path');
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cas_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cas.settings');

    $form['server'] = [
      '#type' => 'details',
      '#title' => $this->t('CAS server'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('Enter the details of your CAS server.'),
    ];
    $form['server']['version'] = [
      '#type' => 'radios',
      '#title' => $this->t('CAS Protocol version'),
      '#options' => [
        '1.0' => $this->t('1.0'),
        '2.0' => $this->t('2.0'),
        '3.0' => $this->t('3.0 or higher'),
      ],
      '#default_value' => $config->get('server.version'),
      '#description' => $this->t('The CAS protocol version your CAS server supports. If unsure, ask your CAS server administrator.'),
    ];
    $form['server']['protocol'] = [
      '#type' => 'radios',
      '#title' => $this->t('HTTP Protocol'),
      '#options' => [
        'http' => $this->t('HTTP (non-secure)'),
        'https' => $this->t('HTTPS (secure)'),
      ],
      '#default_value' => $config->get('server.protocol'),
      '#description' => $this->t('HTTP protocol type of the CAS server. WARNING: Do not use HTTP on production environments!'),
    ];
    $form['server']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#description' => $this->t('Hostname or IP Address of the CAS server.'),
      '#size' => 30,
      '#default_value' => $config->get('server.hostname'),
    ];
    $form['server']['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#size' => 5,
      '#description' => $this->t('443 is the standard SSL port. 8443 is the standard non-root port for Tomcat.'),
      '#default_value' => $config->get('server.port'),
    ];
    $form['server']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('If the CAS server paths (like /login) are not at the root of the host, specify the base path (e.g., /cas).'),
      '#size' => 30,
      '#default_value' => $config->get('server.path'),
    ];
    $form['server']['verify'] = [
      '#type' => 'radios',
      '#title' => 'SSL Verification',
      '#description' => $this->t("Choose an appropriate option for verifying the SSL/TLS certificate of your CAS server."),
      '#options' => [
        CasHelper::CA_DEFAULT => $this->t("Verify using your web server's default certificate authority (CA) chain."),
        CasHelper::CA_NONE => $this->t('Do not verify. (Note: this should NEVER be used in production.)'),
        CasHelper::CA_CUSTOM => $this->t('Verify using a specific CA certificate. Use the field below to provide path (recommended).'),
      ],
      '#default_value' => $config->get('server.verify'),
    ];
    $form['server']['cert'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Certificate Authority PEM Certificate'),
      '#description' => $this->t('The PEM certificate of the Certificate Authority that issued the certificate on the CAS server, used only with the custom certificate option above.'),
      '#default_value' => $config->get('server.cert'),
      '#states' => [
        'visible' => [
          ':input[name="server[verify]"]' => ['value' => CasHelper::CA_CUSTOM],
        ],
      ],
    ];

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['general']['login_link_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place a link to log in via CAS on the standard /user/login form'),
      '#description' => $this->t('Note that even when enabled, the CAS module will not hide the standard Drupal login. If CAS is the primary way your users will log in, it is recommended to alter the login page in a custom module to hide the standard form.'),
      '#default_value' => $config->get('login_link_enabled'),
    ];
    $form['general']['login_link_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login link text'),
      '#default_value' => $config->get('login_link_label'),
      '#states' => [
        'visible' => [
          ':input[name="general[login_link_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['general']['login_success_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login success message'),
      '#description' => $this->t('The message to output to users upon successful login. Leave blank to output no message.'),
      '#default_value' => $config->get('login_success_message'),
    ];

    $form['user_accounts'] = [
      '#type' => 'details',
      '#title' => $this->t('User Account Handling'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['user_accounts']['prevent_normal_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent normal login for CAS users (recommended)'),
      '#description' => $this->t('Prevents any user associated with CAS from authenticating using the normal login form. If attempted, users will be presented with an error message and a link to login via CAS instead.'),
      '#default_value' => $config->get('user_accounts.prevent_normal_login'),
    ];
    $form['user_accounts']['restrict_password_management'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict password management (recommended)'),
      '#description' => $this->t('Prevents CAS users from changing their Drupal password by removing the password fields on the user profile form and disabling the "forgot password" functionality. Admins will still be able to change Drupal passwords for CAS users.'),
      '#default_value' => $config->get('user_accounts.restrict_password_management'),
    ];
    $form['user_accounts']['restrict_email_management'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict email management (recommended)'),
      '#description' => $this->t("Prevents CAS users from changing their email by disabling the email field on the user profile form. Admins will still be able to change email addresses for CAS users. Note that Drupal requires a user enter their current password before changing their email, which your users may not know. Enable the restricted password management feature above to remove this password requirement."),
      '#default_value' => $config->get('user_accounts.restrict_email_management'),
    ];
    $form['user_accounts']['auto_register'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically register users'),
      '#description' => $this->t('Enable to automatically create local Drupal accounts for first-time CAS logins. If disabled, users must be pre-registered before being allowed to log in.'),
      '#default_value' => $config->get('user_accounts.auto_register'),
    ];
    $form['user_accounts']['auto_register_follow_registration_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Follow site's account registration policy"),
      '#description' => $this->t('With auto-register on, will follow the user account registration policy. For instance, if the <a href=":url">account settings</a> "%option" is selected under "%field", the auto-created account will wait for administrator approval.', [
        '%option' => $this->t('Visitors, but administrator approval is required'),
        '%field' => $this->t('Who can register accounts?'),
        ':url' => Url::fromRoute('entity.user.admin_form')->toString(),
      ]),
      '#default_value' => $config->get('user_accounts.auto_register_follow_registration_policy'),
      '#states' => [
        'visible' => [
          ':input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (!$this->moduleHandler->moduleExists('cas_attributes')) {
      $form['user_accounts']['cas_attributes_callout'] = [
        '#prefix' => '<p class="messages messages--status">',
        '#markup' => $this->t('If your CAS server supports <a href="@attributes" target="_blank">attributes</a>, you can install the <a href="@module" target="_blank">CAS Attributes</a> module to map them to user fields and roles during login and auto-registration.', [
          '@attributes' => 'https://apereo.github.io/cas/5.1.x/protocol/CAS-Protocol-Specification.html#255-attributes-cas-30',
          '@module' => 'https://drupal.org/project/cas_attributes',
        ]),
        '#suffix' => '</p>',
      ];
    }
    $form['user_accounts']['email_assignment_strategy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Email address assignment'),
      '#description' => $this->t("Drupal requires every user to have an email address. Select how you'd like to assign an email to automatically registered users."),
      '#default_value' => $config->get('user_accounts.email_assignment_strategy'),
      '#options' => [
        CasUserManager::EMAIL_ASSIGNMENT_STANDARD => $this->t('Use the CAS username combined with a custom domain name you specify.'),
        CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE => $this->t("Use a CAS attribute that contains the user's complete email address."),
      ],
      '#states' => [
        'visible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['user_accounts']['email_hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email hostname'),
      '#description' => $this->t("The email domain name used to combine with the username to form the user's email address."),
      '#field_prefix' => $this->t('username@'),
      '#default_value' => $config->get('user_accounts.email_hostname'),
      '#states' => [
        'visible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
          'input[name="user_accounts[email_assignment_strategy]"]' => ['value' => CasUserManager::EMAIL_ASSIGNMENT_STANDARD],
        ],
      ],
    ];
    $form['user_accounts']['email_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email attribute'),
      '#description' => $this->t("The CAS attribute name (case sensitive) that contains the user's email address. If unsure, check with your CAS server administrator to see a list of attributes that are returned during login."),
      '#default_value' => $config->get('user_accounts.email_attribute'),
      '#states' => [
        'visible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => TRUE],
          'input[name="user_accounts[email_assignment_strategy]"]' => ['value' => CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE],
        ],
      ],
    ];
    $auto_assigned_roles = $config->get('user_accounts.auto_assigned_roles');
    $form['user_accounts']['auto_assigned_roles_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically assign roles on user registration'),
      '#description' => $this->t('To provide role mappings based on CAS attributes, install and configure the optional <a href="@module" target="_blank">CAS Attributes</a> module.', ['@module' => 'https://drupal.org/project/cas_attributes']),
      '#default_value' => count($auto_assigned_roles) > 0,
      '#states' => [
        'invisible' => [
          'input[name="user_accounts[auto_register]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $form['user_accounts']['auto_assigned_roles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Roles'),
      '#description' => $this->t('The selected roles will be automatically assigned to each CAS user on login. Use this to automatically give CAS users additional privileges or to identify CAS users to other modules.'),
      '#default_value' => $auto_assigned_roles,
      '#options' => $roles,
      '#states' => [
        'invisible' => [
          'input[name="user_accounts[auto_assigned_roles_enable]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['error_handling'] = [
      '#type' => 'details',
      '#title' => $this->t('Error Handling'),
      '#tree' => TRUE,
    ];
    $form['error_handling']['login_failure_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login failure page'),
      '#description' => $this->t('If CAS login fails for any reason (e.g. validation failure or some other module prevents login), redirect the user to this page. If empty, users will be redirected to the homepage or to the original page they were on when initiating a login sequence. If your site is configured to automatically log users in via CAS when accessing a restricted page, you should set this to a page that does not require authentication to view. Otherwise you will create a redirect loop for users that that experience login failures as CAS continuously attempts to log them in as it returns them to the restricted page.'),
      '#default_value' => $config->get('error_handling.login_failure_page'),
    ];
    $form['error_handling']['messages'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Error messages'),
    ];
    $form['error_handling']['messages']['help'] = [
      [
        '#markup' => $this->t('Replacement tokens can be used to customize the messages.'),
      ],
    ];
    if (!$this->moduleHandler->moduleExists('token')) {
      $form['error_handling']['messages']['help'][] = [
        '#prefix' => ' ',
        '#markup' => $this->t('Install the <a href="https://www.drupal.org/project/token">Token</a> module to see what tokens are available.'),
      ];
    }
    $form['error_handling']['messages']['message_validation_failure'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Ticket validation failure'),
      '#description' => $this->t('During the CAS authentication process, the CAS server provides Drupal with a "ticket" which is then exchanged for user details (e.g. username and other attributes). This message will be displayed if there is a problem during this process.'),
      '#default_value' => $config->get('error_handling.message_validation_failure'),
    ];
    $form['error_handling']['messages']['message_no_local_account'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Local account does not exist'),
      '#description' => $this->t('Displayed when a new user attempts to login via CAS and automatic registration is disabled.'),
      '#default_value' => $config->get('error_handling.message_no_local_account'),
    ];
    $form['error_handling']['messages']['message_subscriber_denied_reg'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Denied registration'),
      '#description' => $this->t('Displayed when some other module (like CAS Attributes) denies automatic registration of a new user.'),
      '#default_value' => $config->get('error_handling.message_subscriber_denied_reg'),
    ];
    $form['error_handling']['messages']['message_subscriber_denied_login'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Denied login'),
      '#description' => $this->t('Displayed when some other module (like CAS Attributes) denies login of a user.'),
      '#default_value' => $config->get('error_handling.message_subscriber_denied_login'),
    ];
    $form['error_handling']['messages']['message_account_blocked'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Local account is blocked'),
      '#description' => $this->t('Displayed when the Drupal user account belonging to the user logging in via CAS is blocked.'),
      '#default_value' => $config->get('error_handling.message_account_blocked'),
    ];
    $form['error_handling']['messages']['message_username_already_exists'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Local account username already exists'),
      '#description' => $this->t('Displayed when automatic registraton of new user fails because an existing Drupal user is using the same username.'),
      '#default_value' => $config->get('error_handling.message_username_already_exists'),
    ];
    $form['error_handling']['messages']['message_prevent_normal_login'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Prevent normal login error message'),
      '#description' => $this->t('Displayed when prevent normal login for CAS users is on and a CAS user tries to logon using the normal Drupal login form.'),
      '#default_value' => $config->get('error_handling.message_prevent_normal_login'),
      '#states' => [
        'disabled' => [
          ':input[name="user_accounts[prevent_normal_login]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    $form['error_handling']['messages']['message_restrict_password_management'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Restrict password management error message'),
      '#description' => $this->t('Displayed when restrict password management is on and a CAS user tries to reset their Drupal password.'),
      '#default_value' => $config->get('error_handling.message_restrict_password_management'),
      '#states' => [
        'disabled' => [
          ':input[name="user_accounts[restrict_password_management]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['error_handling']['messages']['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['cas'],
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }

    $form['gateway'] = [
      '#type' => 'details',
      '#title' => $this->t('Gateway Feature (Auto Login)'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $form['gateway']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Gateway login enabled'),
      '#default_value' => $config->get('gateway.enabled'),
      '#description' => $this->t(
        'This implements the <a href="@cas-gateway">Gateway feature</a> of the CAS Protocol. When enabled, Drupal will check if a visitor has an active CAS session, and if so, will be automatically log them into Drupal. This is done by quickly redirecting them to the CAS server to perform the active session check, and then redirecting them back to page they initially requested.<br/><br/>If enabled, all pages on your site will trigger this feature by default unless you specify specific pages below.<br/><br/><strong>WARNING:</strong> This feature may disable page caching on pages it is active on. See "Method" below.',
        ['@cas-gateway' => 'https://wiki.jasig.org/display/CAS/gateway']
      ),
    ];
    $gatewayEnabledStates = [
      'visible' => [
        'input[name="gateway[enabled]"]' => ['checked' => TRUE],
      ],
    ];
    $form['gateway']['recheck_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Recheck time'),
      '#description' => $this->t('After initially checking if the visitor has an active CAS session, this is the amount of time to wait before checking again. Every check redirects the user to the CAS server and then back to the page they were on.'),
      '#default_value' => $config->get('gateway.recheck_time'),
      '#options' => [
        -1 => $this->t('Every page request (not recommended)'),
        30 => $this->t('30 minutes'),
        60 => $this->t('1 hour'),
        120 => $this->t('2 hours'),
        180 => $this->t('3 hours'),
        360 => $this->t('6 hours'),
        540 => $this->t('9 hours'),
        720 => $this->t('12 hours'),
        1440 => $this->t('24 hours'),
      ],
      '#states' => $gatewayEnabledStates,
    ];
    $this->gatewayPaths->setConfiguration($config->get('gateway.paths'));
    $form['gateway']['paths'] = $this->gatewayPaths->buildConfigurationForm([], $form_state);
    $form['gateway']['paths']['pages']['#states'] = $gatewayEnabledStates;
    $form['gateway']['paths']['negate']['#states'] = $gatewayEnabledStates;

    $form['gateway']['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method'),
      '#default_value' => $config->get('gateway.method'),
      '#options' => [
        CasHelper::GATEWAY_SERVER_SIDE => $this->t('Server-side redirect. Faster, but disables page caching on configured paths.'),
        CasHelper::GATEWAY_CLIENT_SIDE => $this->t('Client-side redirect (using JavaScript). Slower, but works with all page caching. Not compatible with "Every page request" option above.'),
      ],
      '#description' => $this->t('Configure how the redirect to the CAS server is performed.'),
      '#states' => $gatewayEnabledStates,
    ];

    $form['forced_login'] = [
      '#type' => 'details',
      '#title' => $this->t('Forced login'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $form['forced_login']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Forced login enabled'),
      '#description' => $this->t('When enabled, anonymous users will be forced to login through CAS on the pages you specify. <strong>If enabled and no specific pages are specified below, it will trigger on every page.</strong>'),
      '#default_value' => $config->get('forced_login.enabled'),
    ];
    $forcedLoginEnabledStates = [
      'visible' => [
        'input[name="forced_login[enabled]"]' => ['checked' => TRUE],
      ],
    ];
    $this->forcedLoginPaths->setConfiguration($config->get('forced_login.paths'));
    $form['forced_login']['paths'] = $this->forcedLoginPaths->buildConfigurationForm([], $form_state);
    $form['forced_login']['paths']['pages']['#states'] = $forcedLoginEnabledStates;
    $form['forced_login']['paths']['negate']['#states'] = $forcedLoginEnabledStates;

    $form['logout'] = [
      '#type' => 'details',
      '#title' => $this->t('Log out behavior'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $form['logout']['cas_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Drupal logout triggers CAS logout'),
      '#description' => $this->t('When enabled, users that log out of your Drupal site will then be logged out of your CAS server as well. This is done by redirecting the user to the CAS logout page.'),
      '#default_value' => $config->get('logout.cas_logout'),
    ];
    $form['logout']['logout_destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log out destination'),
      '#description' => $this->t('Drupal path or URL. Enter a destination if you want the CAS Server to redirect the user after logging out of CAS.'),
      '#default_value' => $config->get('logout.logout_destination'),
    ];
    $form['logout']['enable_single_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable single log out?'),
      '#default_value' => $config->get('logout.enable_single_logout'),
      '#description' => $this->t('If enabled (and your CAS server supports it), users will be logged out of your Drupal site when they log out of your CAS server. <strong>WARNING:</strong> THIS WILL BYPASS A SECURITY HARDENING FEATURE ADDED IN DRUPAL 8, causing session IDs to be stored unhashed in the database.'),
    ];
    $form['logout']['single_logout_session_lifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max lifetime of session mapping data'),
      '#description' => $this->t("This module stores a mapping of Drupal session IDs to CAS server session IDs to support single logout. Normally this data is cleared automatically when a user is logged out, but not always. To make sure this storage doesn't grow out of control, session mapping data older than the specified amout of days is cleared during cron. This should be a length of time slightly longer than the session lifetime of your Drupal site or CAS server."),
      '#default_value' => $config->get('logout.single_logout_session_lifetime'),
      '#field_suffix' => $this->t('days'),
      '#size' => 4,
      '#states' => [
        'visible' => [
          'input[name="logout[enable_single_logout]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['proxy'] = [
      '#type' => 'details',
      '#title' => $this->t('Proxy'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t('These options relate to the proxy feature of the CAS protocol, including configuring this client as a proxy and configuring this client to accept proxied connections from other clients.'),
    ];
    $form['proxy']['initialize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Initialize this client as a proxy?'),
      '#description' => $this->t('Initializing this client as a proxy allows it to access CAS-protected resources from other clients that have been configured to accept it as a proxy.'),
      '#default_value' => $config->get('proxy.initialize'),
    ];
    $form['proxy']['can_be_proxied'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow this client to be proxied?'),
      '#description' => $this->t("Allow other CAS clients to access this site's resources via the CAS proxy protocol. You will need to configure a list of allowed proxies below."),
      '#default_value' => $config->get('proxy.can_be_proxied'),
    ];
    $form['proxy']['proxy_chains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed proxy chains'),
      '#description' => $this->t('A list of proxy chains to allow proxy connections from. Each line is a chain, and each chain is a whitespace delimited list of URLs for an allowed proxy in the chain, listed from most recent (left) to first (right). Each URL in the chain can be either a plain URL or a URL-matching regular expression (delimited only by slashes). Only if the proxy list returned by the CAS Server exactly matches a chain in this list will a proxy connection be allowed.'),
      '#default_value' => $config->get('proxy.proxy_chains'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $form['advanced']['debug_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log debug information?'),
      '#description' => $this->t('This is not meant for production sites! Enable this to log debug information about the interactions with the CAS Server to the Drupal log.'),
      '#default_value' => $config->get('advanced.debug_log'),
    ];
    $form['advanced']['connection_timeout'] = [
      '#type' => 'textfield',
      '#size' => 3,
      '#title' => $this->t('Connection timeout'),
      '#field_suffix' => $this->t('seconds'),
      '#description' => $this->t('This module makes HTTP requests to your CAS server and, if configured as a proxy, to a proxied service. This value determines the maximum amount of time to wait on those requests before canceling them.'),
      '#default_value' => $config->get('advanced.connection_timeout'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->validateConfigurationForm($form, $condition_values);

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['forced_login', 'paths']));
    $this->forcedLoginPaths->validateConfigurationForm($form, $condition_values);

    $ssl_verification_method = $form_state->getValue(['server', 'verify']);
    $cert_path = $form_state->getValue(['server', 'cert']);
    if ($ssl_verification_method == CasHelper::CA_CUSTOM && !file_exists($cert_path)) {
      $form_state->setErrorByName('server][cert', $this->t('The path you provided to the custom PEM certificate for your CAS server does not exist or is not readable. Verify this path and try again.'));
    }

    if ($form_state->getValue(['user_accounts', 'auto_register'])) {
      $follow_registration_policy = $form_state->getValue([
        'user_accounts',
        'auto_register_follow_registration_policy',
      ]);
      if ($follow_registration_policy && $this->config('user.settings')->get('register') === UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
        $form_state->setErrorByName('user_accounts][auto_register_follow_registration_policy', $this->t('Auto-registering accounts is not possible while following the account registration policy because the policy requires that new accounts to be created only by administrators. Either uncheck <em>Follow site\'s account registration policy</em> or change the policy at <a href=":url">account settings</a>.', [
          ':url' => Url::fromRoute('entity.user.admin_form')->toString(),
        ]));
      }
      $email_assignment_strategy = $form_state->getValue([
        'user_accounts',
        'email_assignment_strategy',
      ]);
      if ($email_assignment_strategy == CasUserManager::EMAIL_ASSIGNMENT_STANDARD && empty($form_state->getValue([
        'user_accounts',
        'email_hostname',
      ]))) {
        $form_state->setErrorByName('user_accounts][email_hostname', $this->t('You must provide a hostname for the auto assigned email address.'));
      }
      elseif ($email_assignment_strategy == CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE && empty($form_state->getValue([
        'user_accounts',
        'email_attribute',
      ]))) {
        $form_state->setErrorByName('user_accounts][email_attribute', $this->t('You must provide an attribute name for the auto assigned email address.'));
      }

      if ($form_state->getValue(['server', 'version']) == '1.0' && $email_assignment_strategy == CasUserManager::EMAIL_ASSIGNMENT_ATTRIBUTE) {
        $form_state->setErrorByName('user_accounts][email_assignment_strategy', $this->t("The CAS protocol version you've specified does not support attributes, so you cannot assign user emails from a CAS attribute value."));
      }
    }

    $error_page_val = $form_state->getValue([
      'error_handling',
      'login_failure_page',
    ]);
    if ($error_page_val) {
      $error_page_val = trim($error_page_val);
      if (strpos($error_page_val, '/') !== 0) {
        $form_state->setErrorByName('error_handling][login_failure_page', $this->t('Path must begin with a forward slash.'));
      }
    }

    $method = $form_state->getValue(['gateway', 'method']);
    $recheck_time = $form_state->getValue(['gateway', 'recheck_time']);
    if ($method == CasHelper::GATEWAY_CLIENT_SIDE && $recheck_time == -1) {
      $form_state->setErrorByName('gateway][method', $this->t('The "Every page request" recheck time is not compatible with the "Client-side" method.'));
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cas.settings');

    $server_data = $form_state->getValue('server');
    $config
      ->set('server.version', $server_data['version'])
      ->set('server.protocol', $server_data['protocol'])
      ->set('server.hostname', $server_data['hostname'])
      ->set('server.port', $server_data['port'])
      ->set('server.path', $server_data['path'])
      ->set('server.verify', $server_data['verify'])
      ->set('server.cert', $server_data['cert']);

    $general_data = $form_state->getValue('general');
    $config
      ->set('login_link_enabled', $general_data['login_link_enabled'])
      ->set('login_link_label', $general_data['login_link_label'])
      ->set('login_success_message', $general_data['login_success_message']);

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['gateway', 'paths']));
    $this->gatewayPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('gateway.enabled', $form_state->getValue(['gateway', 'enabled']))
      ->set('gateway.recheck_time', $form_state->getValue([
        'gateway',
        'recheck_time',
      ]))
      ->set('gateway.paths', $this->gatewayPaths->getConfiguration())
      ->set('gateway.method', $form_state->getValue(['gateway', 'method']));

    $condition_values = (new FormState())
      ->setValues($form_state->getValue(['forced_login', 'paths']));
    $this->forcedLoginPaths->submitConfigurationForm($form, $condition_values);
    $config
      ->set('forced_login.enabled', $form_state->getValue([
        'forced_login',
        'enabled',
      ]))
      ->set('forced_login.paths', $this->forcedLoginPaths->getConfiguration());

    $config
      ->set('logout.logout_destination', $form_state->getValue([
        'logout',
        'logout_destination',
      ]))
      ->set('logout.enable_single_logout', $form_state->getValue([
        'logout',
        'enable_single_logout',
      ]))
      ->set('logout.cas_logout', $form_state->getValue(['logout', 'cas_logout']))
      ->set('logout.single_logout_session_lifetime', $form_state->getValue([
        'logout',
        'single_logout_session_lifetime',
      ]));
    $config
      ->set('proxy.initialize', $form_state->getValue(['proxy', 'initialize']))
      ->set('proxy.can_be_proxied', $form_state->getValue([
        'proxy',
        'can_be_proxied',
      ]))
      ->set('proxy.proxy_chains', $form_state->getValue([
        'proxy',
        'proxy_chains',
      ]));
    $config
      ->set('user_accounts.prevent_normal_login', $form_state->getValue([
        'user_accounts',
        'prevent_normal_login',
      ]))
      ->set('user_accounts.auto_register', $form_state->getValue([
        'user_accounts',
        'auto_register',
      ]))
      ->set('user_accounts.auto_register_follow_registration_policy', $form_state->getValue([
        'user_accounts',
        'auto_register_follow_registration_policy',
      ]))
      ->set('user_accounts.email_assignment_strategy', $form_state->getValue([
        'user_accounts',
        'email_assignment_strategy',
      ]))
      ->set('user_accounts.email_hostname', $form_state->getValue([
        'user_accounts',
        'email_hostname',
      ]))
      ->set('user_accounts.email_attribute', $form_state->getValue([
        'user_accounts',
        'email_attribute',
      ]))
      ->set('user_accounts.restrict_password_management', $form_state->getValue([
        'user_accounts',
        'restrict_password_management',
      ]))
      ->set('user_accounts.restrict_email_management', $form_state->getValue([
        'user_accounts',
        'restrict_email_management',
      ]));

    $auto_assigned_roles = [];
    if ($form_state->getValue([
      'user_accounts',
      'auto_assigned_roles_enable',
    ])) {
      $auto_assigned_roles = array_keys($form_state->getValue([
        'user_accounts',
        'auto_assigned_roles',
      ]));
    }
    $config
      ->set('user_accounts.auto_assigned_roles', $auto_assigned_roles);

    $config->set('error_handling.login_failure_page', trim($form_state->getValue([
      'error_handling',
      'login_failure_page',
    ])));
    $messages = $form_state->getValue(['error_handling', 'messages']);
    $config
      ->set('error_handling.message_validation_failure', trim($messages['message_validation_failure']))
      ->set('error_handling.message_no_local_account', trim($messages['message_no_local_account']))
      ->set('error_handling.message_subscriber_denied_reg', trim($messages['message_subscriber_denied_reg']))
      ->set('error_handling.message_subscriber_denied_login', trim($messages['message_subscriber_denied_login']))
      ->set('error_handling.message_account_blocked', trim($messages['message_account_blocked']))
      ->set('error_handling.message_username_already_exists', trim($messages['message_username_already_exists']))
      ->set('error_handling.message_prevent_normal_login', trim($messages['message_prevent_normal_login']))
      ->set('error_handling.message_restrict_password_management', trim($messages['message_restrict_password_management']));

    $config
      ->set('advanced.debug_log', $form_state->getValue([
        'advanced',
        'debug_log',
      ]))
      ->set('advanced.connection_timeout', $form_state->getValue([
        'advanced',
        'connection_timeout',
      ]));

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cas.settings'];
  }

}
