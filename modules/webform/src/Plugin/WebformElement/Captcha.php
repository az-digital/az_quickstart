<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'captcha' element.
 *
 * @WebformElement(
 *   id = "captcha",
 *   default_key = "captcha",
 *   api = "https://www.drupal.org/project/captcha",
 *   label = @Translation("CAPTCHA"),
 *   description = @Translation("Provides a form element that determines whether the user is human."),
 *   category = @Translation("Advanced elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "captcha",
 *   }
 * )
 */
class Captcha extends WebformElementBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Captcha settings.
      'captcha_type' => 'default',
      'captcha_admin_mode' => FALSE,
      'captcha_title' => '',
      'captcha_description' => '',
      // Flexbox.
      'flex' => 1,
      // Conditional logic.
    ];
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Hide and solve the element if the user is assigned 'skip CAPTCHA'
    // and '#captcha_admin_mode' is not enabled.
    $is_admin = $this->currentUser->hasPermission('skip CAPTCHA');
    if ($is_admin && empty($element['#captcha_admin_mode'])) {
      $element['#access'] = FALSE;
      $element['#captcha_admin_mode'] = TRUE;
    }

    // Hide and solve the element if the user is exempt by IP address.
    $is_exempt = function_exists('captcha_whitelist_ip_whitelisted')
      && captcha_whitelist_ip_whitelisted();
    if ($is_exempt) {
      $element['#access'] = FALSE;
      $element['#captcha_admin_mode'] = TRUE;
    }

    // Always enable admin mode for test.
    $is_test = (strpos($this->routeMatch->getRouteName(), '.webform.test_form') !== FALSE) ? TRUE : FALSE;
    if ($is_test) {
      $element['#captcha_admin_mode'] = TRUE;
    }

    // Add default CAPTCHA description if required.
    // @see captcha_form_alter()
    if (empty($element['#description']) && \Drupal::config('captcha.settings')->get('add_captcha_description')) {
      $this->moduleHandler->loadInclude('captcha', 'inc');
      $element['#description'] = _captcha_get_description();
    }

    parent::prepare($element, $webform_submission);

    $element['#after_build'][] = [get_class($this), 'afterBuildCaptcha'];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    $element = parent::preview() + [
      '#captcha_admin_mode' => TRUE,
      // Define empty form id to prevent fatal error when preview is
      // rendered via Ajax.
      // @see \Drupal\captcha\Element\Captcha::processCaptchaElement
      '#captcha_info' => ['form_id' => ''],
    ];
    if ($this->moduleHandler->moduleExists('image_captcha')) {
      $element['#captcha_type'] = 'image_captcha/Image';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    // Remove all captcha related keys from the webform submission's data.
    $key = $element['#webform_key'];
    $data = $webform_submission->getData();
    unset($data[$key]);
    // @see \Drupal\captcha\Element\Captcha
    $sub_keys = ['sid', 'token', 'response'];
    foreach ($sub_keys as $sub_key) {
      unset($data[$key . '_' . $sub_key]);
    }
    $webform_submission->setData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Issue #3090624: Call to undefined function trying to add CAPTCHA
    // element to form.
    // @see _captcha_available_challenge_types();
    // @see \Drupal\captcha\Service\CaptchaService::getAvailableChallengeTypes
    $captcha_types = [];
    $captcha_types['default'] = $this->t('Default challenge type');
    // Use ModuleHandler::invokeAllWith() here because we want to build an
    // array with custom keys and values.
    $this->moduleHandler->invokeAllWith('captcha', function (callable $hook, string $module) use (&$captcha_types) {
      $result = $hook('list');
      if (is_array($result)) {
        foreach ($result as $type) {
          $captcha_types["$module/$type"] = $this->t('@type (from module @module)', [
            '@type' => $type,
            '@module' => $module,
          ]);
        }
      }
    });

    $form['captcha'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CAPTCHA settings'),
    ];
    $form['captcha']['message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Note that the CAPTCHA module disables page caching of pages that include a CAPTCHA challenge.'),
      '#message_close' => TRUE,
      '#message_storage' => WebformMessageElement::STORAGE_SESSION,
      '#access' => TRUE,
    ];
    $form['captcha']['captcha_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Challenge type'),
      '#required' => TRUE,
      '#options' => $captcha_types,
    ];
    // Custom title and description.
    $form['captcha']['captcha_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [[':input[name="properties[captcha_type]"]' => ['value' => 'recaptcha/reCAPTCHA']]],
      ],
    ];
    $form['captcha']['captcha_container']['captcha_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Question title'),
    ];
    $form['captcha']['captcha_container']['captcha_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Question description'),
    ];
    // Admin mode.
    $form['captcha']['captcha_admin_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Admin mode'),
      '#description' => $this->t('Presolve the CAPTCHA and always shows it. This is useful for debugging and preview CAPTCHA integration.'),
      '#return_value' => TRUE,
    ];
    return $form;
  }

  /**
   * After build handler for CAPTCHA elements.
   */
  public static function afterBuildCaptcha(array $element, FormStateInterface $form_state) {
    // Make sure that the CAPTCHA response supports #title.
    if (isset($element['captcha_widgets'])
      && isset($element['captcha_widgets']['captcha_response'])
      && isset($element['captcha_widgets']['captcha_response']['#title'])) {
      if (!empty($element['#captcha_title'])) {
        $element['captcha_widgets']['captcha_response']['#title'] = $element['#captcha_title'];
      }
      if (!empty($element['#captcha_description'])) {
        $element['captcha_widgets']['captcha_response']['#description'] = $element['#captcha_description'];
      }
    }
    return $element;
  }

}
