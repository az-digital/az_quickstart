<?php

declare(strict_types=1);

namespace Drupal\az_spam_prevention\Hook;

use Drupal\captcha\Service\CaptchaService;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\captcha\Constants\CaptchaConstants;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for az_spam_prevention.
 */
class AZAntiSpamHooks {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Captcha helper service.
   *
   * @var \Drupal\captcha\Service\CaptchaService
   */
  protected $captchaHelper;

  /**
   * The currently logged-in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new AZSpamHooks object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\captcha\Service\CaptchaService $captcha_service
   *   The captcha helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged-in user.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    // Captcha modoule doesn't autowire correctly.
    #[Autowire(service: 'captcha.helper')]
    CaptchaService $captcha_service,
    #[Autowire(service: 'current_user')]
    AccountProxyInterface $current_user,
  ) {
    $this->moduleHandler = $module_handler;
    $this->captchaHelper = $captcha_service;
    $this->currentUser = $current_user;
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Find out if we are dealing with a webform.
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof BaseFormIdInterface) {
      $base_form_id = $form_object->getBaseFormId();
      if (preg_match("/^webform_submission_.+_form$/", $base_form_id)) {
        // Bail out if the user is allowed to skip.
        if ($this->currentUser->hasPermission('skip CAPTCHA')) {
          return;
        }
        // @todo multistep forms.
        // Check existing elements for captcha element.
        $elements = $form['elements'] ?? [];
        foreach ($elements as $element) {
          if (is_array($element) && !empty($element['#type']) && ($element['#type'] === 'captcha')) {
            // Do nothing if we already have a captcha element.
            return;
          }
        }
        // Make sure captcha API functions are available.
        $this->moduleHandler->loadInclude('captcha', 'inc', 'captcha');
        // Build CAPTCHA form element.
        $captcha_element = [
          '#type' => 'captcha',
          '#captcha_type' => CaptchaConstants::CAPTCHA_TYPE_DEFAULT,
        ];

        // Get placement position and insert in form.
        $captcha_placement = _captcha_get_captcha_placement($form_id, $form);
        $this->captchaHelper->insertCaptchaElement($form, $captcha_placement, $captcha_element);
      }
    }
  }

}
