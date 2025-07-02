<?php

namespace Drupal\metatag_routes\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\metatag_routes\Helper\MetatagRoutesHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating custom definitions.
 *
 * @package Drupal\metatag_routes\Form
 */
class MetatagCustomCreateForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\RouteProvider definition.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Drupal\Core\Routing\AdminContext definition.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Drupal\metatag_routes\Helper\MetatagRoutesHelperInterface definition.
   *
   * @var \Drupal\metatag_routes\Helper\MetatagRoutesHelperInterface
   */
  protected $metatagRoutesHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteProviderInterface $route_provider,
    PathValidatorInterface $path_validator,
    AdminContext $admin_context,
    MetatagRoutesHelperInterface $metatag_routes_helper,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
    $this->pathValidator = $path_validator;
    $this->adminContext = $admin_context;
    $this->metatagRoutesHelper = $metatag_routes_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('router.route_provider'),
      $container->get('path.validator'),
      $container->get('router.admin_context'),
      $container->get('metatag_routes.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_custom_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['metatag_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route / Path'),
      '#description' => $this->t('Enter the route (path) for this new configuration, starting with a leading slash.<br />Note: this must already exist as a path in Drupal.'),
      '#maxlength' => 200,
      '#required' => TRUE,
    ];

    $form['route_name'] = [
      '#type' => 'hidden',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Get the path given by the user.
    $url = trim($form_state->getValue('metatag_url'));

    // Validate the url format.
    if (strpos($url, '/') === FALSE) {
      $form_state->setErrorByName('metatag_url', $this->t('The path must begin with /'));
      return FALSE;
    }

    // Get route name from path.
    $url_object = $this->pathValidator->getUrlIfValid($url);
    if ($url_object) {
      $route_name = $url_object->getRouteName();
      $route_object = $this->routeProvider->getrouteByName($route_name);
      // Avoid administrative routes to have metatags.
      if ($this->adminContext->isAdminRoute($route_object)) {
        $form_state->setErrorByName('metatag_url',
          $this->t('The admin routes should not have metatags.'));
        return FALSE;
      }

      // Avoid including entity routes.
      $params = $url_object->getRouteParameters();
      // Exclude entities routes.
      if (preg_match('/^entity\..+\.canonical$/', $url_object->getRouteName())) {
        $form_state->setErrorByName('metatag_url',
          $this->t('The entities routes metatags must be added by fields.')
        );
        return FALSE;
      }

      if (count($params) > 0) {
        $form_state->setValue('params', $params);
      }

      // Validate that the route doesn't have metatags created already.
      if ($route_name) {
        $route_with_params = $this->metatagRoutesHelper->createMetatagRouteId($route_name, $params);
        $ids = $this->entityTypeManager
          ->getStorage('metatag_defaults')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('id', $route_with_params)
          ->execute();
      }
      if (!empty($ids)) {
        $form_state->setErrorByName('metatag_url',
          $this->t('There are already metatags created for this route.'));
        return FALSE;
      }
      $form_state->setValue('route_name', $route_name);
    }
    else {
      $form_state->setErrorByName('metatag_url', $this->t('The path does not exist as an internal Drupal route.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get values for form submission.
    $route_name = $form_state->getValue('route_name');
    $url = $form_state->getValue('metatag_url');
    $params = $form_state->getValue('params');
    $id = $this->metatagRoutesHelper->createMetatagRouteId($route_name, $params);
    if ($route_name && $url) {
      // Create the new metatag entity.
      $entity = $this->entityTypeManager->getStorage('metatag_defaults')->create([
        'id' => $id,
        'label' => $url,
      ]);
      $entity->save();
      $this->messenger()->addStatus($this->t('Created metatags for the path: @url. Internal route: @route.', [
        '@url' => $url,
        '@route' => $route_name,
      ]));

      // Redirect to metatag edit page.
      $form_state->setRedirect('entity.metatag_defaults.edit_form', [
        'metatag_defaults' => $id,
      ]);
    }
    else {
      $this->messenger()->addError($this->t('The metatags could not be created for the path: @url.', [
        '@url' => $url,
      ]));

      // Redirect to metatag edit page.
      $form_state->setRedirect('entity.metatag_defaults.collection');
    }
  }

}
