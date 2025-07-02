<?php

namespace Drupal\flag\ActionLink;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for all link types.
 *
 * Link types perform two key functions within Flag: They specify the route to
 * use when a flag link is clicked, and generate the render array to display
 * flag links.
 */
abstract class ActionLinkTypeBase extends PluginBase implements ActionLinkTypePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  use StringTranslationTrait;
  use RedirectDestinationTrait;

  /**
   * Build a new link type instance and sets the configuration.
   *
   * @param array $configuration
   *   The configuration array with which to initialize this plugin.
   * @param string $plugin_id
   *   The ID with which to initialize this plugin.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * Return a Url object for the given flag action.
   *
   * @param string $action
   *   The action, flag or unflag.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   *
   * @return \Drupal\Core\Url
   *   The Url object for this plugin's flag/unflag route.
   */
  abstract protected function getUrl($action, FlagInterface $flag, EntityInterface $entity);

  /**
   * {@inheritdoc}
   */
  public function getAsLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL) {
    // Trigger deprecation, if the $view_mode wasn't provided.:
    // @see original issue: https://www.drupal.org/project/flag/issues/3049155
    // @see change record: https://www.drupal.org/node/3458551.
    if ($view_mode === NULL) {
      $deprecation_message = 'Not providing the "$view_mode" parameter is deprecated in flag:8.x-4.0-beta4 and will throw an error from flag:8.x-4.0. See https://www.drupal.org/node/3458551.';
      @trigger_error($deprecation_message, E_USER_DEPRECATED);
    }

    $action = $this->getAction($flag, $entity);
    $url = $this->getUrl($action, $flag, $entity);
    $url->setRouteParameter('view_mode', $view_mode);
    $url->setOption('query', ['destination' => $this->getDestination()]);
    $title = $flag->getShortText($action);

    return Link::fromTextAndUrl($title, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL): array {
    $action = $this->getAction($flag, $entity);
    $access = $flag->actionAccess($action, $this->currentUser, $entity);

    // Trigger deprecation, if the $view_mode wasn't provided.:
    // @see original issue: https://www.drupal.org/project/flag/issues/3049155
    // @see change record: https://www.drupal.org/node/3458551.
    if ($view_mode === NULL) {
      $deprecation_message = 'Not providing the "$view_mode" parameter is deprecated in flag:8.x-4.0-beta4 and will throw an error from flag:8.x-4.0. See https://www.drupal.org/node/3458551.';
      @trigger_error($deprecation_message, E_USER_DEPRECATED);
    }

    if ($access->isAllowed()) {
      $url = $this->getUrl($action, $flag, $entity);
      $url->setRouteParameter('destination', $this->getDestination());
      $url->setRouteParameter('view_mode', $view_mode);
      $render = [
        '#theme' => 'flag',
        '#flag' => $flag,
        '#flaggable' => $entity,
        '#view_mode' => $view_mode,
        '#action' => $action,
        '#access' => $access->isAllowed(),
        // Use render array for title to allow limited markup in the link text.
        '#title' => ['#markup' => $flag->getShortText($action)],
        '#attributes' => [
          'title' => $flag->getLongText($action),
        ],
      ];
      // Build the URL. It is important that bubbleable metadata is explicitly
      // collected and applied to the render array, as it might be rendered on
      // its own, for example in an ajax response. Specifically, this is
      // necessary for CSRF token placeholder replacements.
      $rendered_url = $url->toString(TRUE);
      $rendered_url->applyTo($render);

      $render['#attributes']['href'] = $rendered_url->getGeneratedUrl();
    }
    else {
      $render = [];
    }

    CacheableMetadata::createFromRenderArray($render)
      ->addCacheableDependency($access)
      ->applyTo($render);

    return $render;
  }

  /**
   * Helper method to get the next flag action the user can take.
   */
  protected function getAction(FlagInterface $flag, EntityInterface $entity) {
    return $flag->isFlagged($entity) ? 'unflag' : 'flag';
  }

  /**
   * Helper method to generate a destination URL parameter.
   *
   * @return string
   *   A string containing a destination URL parameter.
   */
  protected function getDestination() {
    return $this->getRedirectDestination()->get();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Provides a form array for the action link plugin's settings form.
   *
   * Derived classes will want to override this method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Processes the action link setting form submit.
   *
   * Derived classes will want to override this method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override this.
  }

  /**
   * Validates the action link setting form.
   *
   * Derived classes will want to override this method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override this.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

}
