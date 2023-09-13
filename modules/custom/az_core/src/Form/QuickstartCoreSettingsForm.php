<?php

namespace Drupal\az_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom AZ Quickstart settings.
 */
class QuickstartCoreSettingsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a QuickstartCoreSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilderInterface $route_builder, RouteProviderInterface $route_provider, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);

    $this->routeBuilder = $route_builder;
    $this->routeProvider = $route_provider;
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder'),
      $container->get('router.route_provider'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_core_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_core.settings', 'system.site'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //phpcs:ignore Security.BadFunctions.EasyRFI.WarnEasyRFI
    require_once \Drupal::service('extension.list.module')->getPath('az_core') . '/includes/common.inc';

    $az_core_config = $this->config('az_core.settings');
    $site_config = $this->config('system.site');

    $form['clear_cache'] = [
      '#type' => 'details',
      '#title' => t('Clear cache'),
      '#open' => TRUE,
    ];

    $form['clear_cache']['clear'] = [
      '#type' => 'submit',
      '#value' => t('Clear all caches'),
      '#submit' => ['::submitCacheClear'],
    ];

    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => t('Site name'),
      '#default_value' => $site_config->get('name'),
      '#required' => TRUE,
    ];

    $form['front_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Front page'),
      '#open' => TRUE,
    ];

    $front_page = $site_config->get('page.front') !== '/user/login' ? $this->aliasManager->getAliasByPath($site_config->get('page.front')) : '';
    $form['front_page']['site_frontpage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default front page'),
      '#default_value' => $front_page,
      '#size' => 40,
      '#description' => $this->t('Optionally, specify a relative URL to display as the front page. Leave blank to display the default front page.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];

    $form['error_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Error pages'),
      '#open' => TRUE,
    ];

    $form['error_page']['site_403'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 403 (access denied) page'),
      '#default_value' => $site_config->get('page.403'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed when the requested document is denied to the current user. Leave blank to display a generic "access denied" page.'),
    ];

    $form['error_page']['site_404'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 404 (not found) page'),
      '#default_value' => $site_config->get('page.404'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
    ];

    $form['monitoring_page'] = [
      '#type' => 'details',
      '#title' => t('Monitoring page'),
      '#open' => TRUE,
      '#access' => $this->currentUser()->hasPermission('administer site configuration'),
    ];

    $form['monitoring_page']['monitoring_page_enabled'] = [
      '#title' => t('Enable monitoring page'),
      '#type' => 'checkbox',
      '#description' => t("Provides an uncacheable page intended for use with uptime monitoring tools to check the health of the site, bypassing any edge cache layer (e.g. varnish)."),
      '#default_value' => $az_core_config->get('monitoring_page.enabled'),
    ];

    $form['monitoring_page']['monitoring_page_path'] = [
      '#title' => t('Monitoring page path'),
      '#type' => 'textfield',
      '#description' => t('Path for monitoring page.'),
      '#default_value' => $az_core_config->get('monitoring_page.path'),
      '#element_validate' => ['::monitoringPagePathValidate'],
      '#states' => [
        'visible' => [':input[name="monitoring_page_enabled"]' => ['checked' => TRUE]],
        'enabled' => [':input[name="monitoring_page_enabled"]' => ['checked' => TRUE]],
        'required' => [':input[name="monitoring_page_enabled"]' => ['checked' => TRUE]],
      ],
    ];

    $form['enterprise_attributes'] = [
      '#type' => 'details',
      '#title' => t('Enterprise attributes'),
      '#open' => FALSE,
      '#access' => $this->currentUser()->hasPermission('administer site configuration'),
    ];

    $form['enterprise_attributes']['enterprise_attributes_locked'] = [
      '#title' => t('Enterprise attributes edits prohibited'),
      '#type' => 'checkbox',
      '#description' => t("With this setting enabled, edits to the enterprise attributes taxonomy will be prohibited (recommended)."),
      '#default_value' => $az_core_config->get('enterprise_attributes.locked'),
    ];

    $form['arizona_bootstrap'] = [
      '#type' => 'details',
      '#title' => t('Arizona Bootstrap'),
      '#open' => TRUE,
      '#group' => 'arizona-bootstrap',
      '#access' => $this->currentUser()->hasPermission('administer site configuration'),
    ];
    $form['arizona_bootstrap']['settings']['az_bootstrap_source'] = [
      '#type' => 'radios',
      '#title' => t('AZ Bootstrap Source'),
      '#options' => [
        'local' => t('Use local copy of AZ Bootstrap packaged with AZ Barrio (%stableversion).', ['%stableversion' => AZ_BOOTSTRAP_STABLE_VERSION]),
        'cdn' => t('Use external copy of AZ Bootstrap hosted on the AZ Bootstrap CDN.'),
      ],
      '#default_value' => $az_core_config->get('arizona_bootstrap.source'),
      '#prefix' => t(
        'AZ Quickstart requires the <a href="@azbootstrap">AZ Bootstrap</a> front-end framework. AZ Bootstrap can either be loaded from the local copy packaged with Quickstart or from the AZ Bootstrap CDN.', [
          '@azbootstrap' => 'http://digital.arizona.edu/arizona-bootstrap',
        ]
      ),
    ];
    $form['arizona_bootstrap']['settings']['az_bootstrap_cdn'] = [
      '#type' => 'fieldset',
      '#title' => t('AZ Bootstrap CDN Settings'),
      '#states' => [
        'visible' => [
          ':input[name="az_bootstrap_source"]' => ['value' => 'cdn'],
        ],
      ],
    ];
    $form['arizona_bootstrap']['settings']['az_bootstrap_cdn']['az_bootstrap_cdn_version'] = [
      '#type' => 'radios',
      '#title' => t('AZ Bootstrap CDN version'),
      '#options' => [
        'stable' => t('Stable version: This option has undergone the most testing within the az_barrio theme. Currently: %stableversion (Recommended).', ['%stableversion' => AZ_BOOTSTRAP_STABLE_VERSION]),
        'latest-2.x' => t('Latest tagged version. The most recently tagged stable release of AZ Bootstrap. While this has not been explicitly tested on this version of az_barrio, it’s probably OK to use on production sites. Please report bugs to the AZ Digital team.'),
        '2.x' => t('Latest dev version. This is the tip of the 2.x branch of AZ Bootstrap. Please do not use on production unless you are following the AZ Bootstrap project closely. Please report bugs to the AZ Digital team.'),
      ],
      '#default_value' => $az_core_config->get('arizona_bootstrap.cdn_version'),
    ];
    $form['arizona_bootstrap']['settings']['az_bootstrap_minified'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Use minified version of AZ Bootstrap.'),
      '#default_value' => $az_core_config->get('arizona_bootstrap.minified'),

    ];

    $form['arizona_font']['settings'] = [
      '#type' => 'details',
      '#title' => t('Arizona Font Settings'),
      '#open' => TRUE,
    ];

    $form['arizona_font']['settings']['use_managed_font'] = [
      '#type' => 'checkbox',
      '#title' => t('Use the centrally-managed Typekit webfont, Proxima Nova'),
      '#default_value' => $az_core_config->get('arizona_font.use_managed_font'),
      '#description' => t(
        'If selected, a Typekit CDN <code>&lt;link&gt;</code> will be added to every page importing the @proxima_nova_docs_link CSS.', [
          '@proxima_nova_docs_link' => Link::fromTextAndUrl(
            'Arizona Digital, centrally-managed Proxima Nova font', Url::fromUri(
                'https://digital.arizona.edu/arizona-bootstrap/docs/2.0/content/font/',
                [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ]
      ),
    ];
    $form['arizona_icons']['settings'] = [
      '#type' => 'details',
      '#title' => t('Arizona Icons'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['arizona_icons']['settings']['use_material_design_sharp_icons'] = [
      '#type' => 'checkbox',
      '#title' => t('Use Material Design Sharp Icons'),
      '#description' => t(
        'If selected, a Google Fonts CDN <code>&lt;link&gt;</code> will be added to every page importing the @material_design_sharp_icons_docs_link CSS.', [
          '@material_design_sharp_icons_docs_link' => Link::fromTextAndUrl(
            'Material Design Sharp icons', Url::fromUri(
                'https://material.io/resources/icons/?style=sharp', [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ]
      ),
      '#default_value' => $az_core_config->get('arizona_icons.use_material_design_sharp_icons'),
    ];

    $form['arizona_icons']['settings']['use_az_icons'] = [
      '#type' => 'checkbox',
      '#title' => t('Use AZ Icons'),
      '#description' => t(
        'If selected, a Arizona Digital CDN <code>&lt;link&gt;</code> will be added to every page importing the @az_icons_link CSS.', [
          '@az_icons_link' => Link::fromTextAndUrl(
            'Arizona icons', Url::fromUri(
                'https://github.com/az-digital/az-icons', [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ]
      ),
      '#default_value' => $az_core_config->get('arizona_icons.use_az_icons'),
    ];

    $form['arizona_icons']['settings']['az_icons_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('AZ Icons Settings'),
      '#states' => [
        'visible' => [
          ':input[name="use_az_icons"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['arizona_icons']['settings']['az_icons_settings']['az_icons_source'] = [
      '#type' => 'radios',
      '#title' => t('Arizona Icons Source'),
      '#options' => [
        'cdn' => t(
        'Use external copy of @azicons hosted on the CDN.', [
          '@azicons' => Link::fromTextAndUrl(
            'AZ Icons', Url::fromUri(
                'https://github.com/az-digital/az-icons', [
                  'attributes' => [
                    'target' => '_blank',
                  ],
                ]
            )
          )->toString(),
        ],

        ),
        'local' => t('Use local copy of AZ Icons packaged with AZ Barrio (%stableversion).', ['%stableversion' => AZ_ICONS_STABLE_VERSION]),
      ],
      '#default_value' => $az_core_config->get('arizona_icons.az_icons_source'),

    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for empty front page path.
    if ($form_state->isValueEmpty('site_frontpage')) {
      // Set to default "user/login".
      $form_state->setValueForElement($form['front_page']['site_frontpage'], '/user/login');
    }
    else {
      // Get the normal path of the front page.
      $form_state->setValueForElement($form['front_page']['site_frontpage'], $this->aliasManager->getPathByAlias($form_state->getValue('site_frontpage')));
    }
    // Validate front page path.
    if (($value = $form_state->getValue('site_frontpage')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_frontpage')]));

    }
    if (!$this->pathValidator->isValid($form_state->getValue('site_frontpage'))) {
      $form_state->setErrorByName('site_frontpage', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_frontpage')]));
    }
    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('site_403')) {
      $form_state->setValueForElement($form['error_page']['site_403'], $this->aliasManager->getPathByAlias($form_state->getValue('site_403')));
    }
    if (!$form_state->isValueEmpty('site_404')) {
      $form_state->setValueForElement($form['error_page']['site_404'], $this->aliasManager->getPathByAlias($form_state->getValue('site_404')));
    }
    if (($value = $form_state->getValue('site_403')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_403', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_403')]));
    }
    if (($value = $form_state->getValue('site_404')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_404', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_404')]));
    }
    // Validate 403 error path.
    if (!$form_state->isValueEmpty('site_403') && !$this->pathValidator->isValid($form_state->getValue('site_403'))) {
      $form_state->setErrorByName('site_403', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_403')]));
    }
    // Validate 404 error path.
    if (!$form_state->isValueEmpty('site_404') && !$this->pathValidator->isValid($form_state->getValue('site_404'))) {
      $form_state->setErrorByName('site_404', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_404')]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Validates the monitoring page path.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function monitoringPagePathValidate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if ($form_state->getValue('monitoring_page_enabled')) {
      $submitted_value = $form_state->getValue('monitoring_page_path');
      if (empty($submitted_value)) {
        $form_state->setError($element, t('A monitoring page path must be provided.'));
      }

      $path = strtolower(trim(trim($submitted_value), " \\/"));
      if (!empty($path) && $submitted_value !== $element['#default_value']) {
        if ($this->routeProvider->getRoutesByPattern($path)->count()) {
          $form_state->setError($element, t('The path is already in use.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.site')
      ->set('name', $form_state->getValue('site_name'))
      ->set('page.front', $form_state->getValue('site_frontpage'))
      ->set('page.403', $form_state->getValue('site_403'))
      ->set('page.404', $form_state->getValue('site_404'))
      ->save();

    $this->config('az_core.settings')
      ->set('monitoring_page.enabled', $form_state->getValue('monitoring_page_enabled'))
      ->set('monitoring_page.path', $form_state->getValue('monitoring_page_path'))
      ->set('enterprise_attributes.locked', $form_state->getValue('enterprise_attributes_locked'))
      ->set('arizona_bootstrap.source', $form_state->getValue('az_bootstrap_source'))
      ->set('arizona_bootstrap.cdn_version', $form_state->getValue('az_bootstrap_cdn_version'))
      ->set('arizona_bootstrap.minified', $form_state->getValue('az_bootstrap_minified'))
      ->set('arizona_font.use_managed_font', $form_state->getValue('use_managed_font'))
      ->set('arizona_bootstrap.minified', $form_state->getValue('az_bootstrap_minified'))

      ->save();

    $this->routeBuilder->setRebuildNeeded();

    parent::submitForm($form, $form_state);
  }

  /**
   * Clears the caches.
   */
  public function submitCacheClear(array &$form, FormStateInterface $form_state) {
    drupal_flush_all_caches();
    $this->messenger()->addStatus($this->t('Caches cleared.'));
  }

}
