<?php

declare(strict_types=1);

namespace Drupal\honeypot;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a service to append Honeypot protection to forms.
 */
class HoneypotService implements HoneypotServiceInterface {
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The honeypot config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValue;

  /**
   * HoneypotService constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Drupal account object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module_handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal configuration object factory service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value
   *   Drupal key value factory store factory.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   KillSwitch policy object.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger.factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $timeService
   *   The datetime.time service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(
    protected AccountProxyInterface $account,
    protected ModuleHandlerInterface $moduleHandler,
    ConfigFactoryInterface $config_factory,
    KeyValueExpirableFactoryInterface $key_value,
    protected KillSwitch $killSwitch,
    protected Connection $connection,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected TimeInterface $timeService,
    TranslationInterface $string_translation,
    protected RequestStack $requestStack,
  ) {
    $this->config = $config_factory->get('honeypot.settings');
    $this->keyValue = $key_value->get('honeypot_time_restriction');
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getProtectedForms(): array {
    return array_keys(array_filter($this->config->get('form_settings')));
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeLimit(array $form_values = []): int {
    $honeypot_time_limit = $this->config->get('time_limit');

    // Only calculate time limit if honeypot_time_limit has a value > 0.
    if ($honeypot_time_limit > 0) {
      $expire_time = $this->config->get('expire');

      // Query the {honeypot_user} table to determine the number of failed
      // submissions for the current user.
      $uid = $this->account->id();
      $query = $this->connection->select('honeypot_user', 'hu')
        ->condition('uid', $uid)
        ->condition('timestamp', $this->timeService->getRequestTime() - $expire_time, '>');

      // For anonymous users, take the hostname into account.
      if ($uid === 0) {
        $hostname = $this->requestStack->getCurrentRequest()->getClientIp();
        $query->condition('hostname', $hostname);
      }
      $number = $query->countQuery()->execute()->fetchField();

      // Don't add more time than the expiration window.
      $honeypot_time_limit = (int) min($honeypot_time_limit + exp((float) $number) - 1, $expire_time);
      // @todo Only accepts two args.
      $additions = $this->moduleHandler->invokeAll('honeypot_time_limit', [
        $honeypot_time_limit,
        $form_values,
        $number,
      ]);
      if (count($additions)) {
        $honeypot_time_limit += array_sum($additions);
      }
    }

    return $honeypot_time_limit;
  }

  /**
   * {@inheritdoc}
   */
  public function addFormProtection(array &$form, FormStateInterface $form_state, array $options = []): void {
    // Allow other modules to alter the protections applied to this form.
    $this->moduleHandler->alter('honeypot_form_protections', $options, $form);

    // Don't add any protections if the user can bypass the Honeypot.
    if ($this->account->hasPermission('bypass honeypot protection')) {
      return;
    }

    // Build the honeypot element.
    if (in_array('honeypot', $options)) {
      // Get the element name (default is generic 'url').
      $honeypot_element = $this->config->get('element_name');

      // If this is a Webform form, ensure the honeypot element has
      // a unique name in the 'elements' array.
      if (!empty($form['#webform_id'])) {
        if (!empty($form['elements'])) {
          while (array_key_exists($honeypot_element, $form['elements'])) {
            $honeypot_element .= '_';
          }
        }
      }

      // Build the honeypot element.
      $honeypot_class = $honeypot_element . '-textfield';
      $form[$honeypot_element] = [
        '#theme_wrappers' => [
          0 => 'form_element',
          'container' => [
            '#id' => NULL,
            '#attributes' => [
              'class' => [$honeypot_class],
              'style' => ['display: none !important;'],
            ],
          ],
        ],
        '#type' => 'textfield',
        '#title' => $this->t('Leave this field blank'),
        '#size' => 20,
        '#weight' => 100,
        '#attributes' => ['autocomplete' => 'off'],
        '#element_validate' => [
          [$this, 'validateHoneypot'],
        ],
      ];

    }

    // Set the time restriction for this form (if it's not disabled).
    if (in_array('time_restriction', $options) && $this->config->get('time_limit') != 0) {
      // Set the current time in a hidden value to be checked later.
      $input = $form_state->getValues();
      if (empty($input['honeypot_time']) || !$this->validateHoneypotTimeIdentifier($input['honeypot_time'])) {
        $identifier = Crypt::randomBytesBase64();
        $this->keyValue->setWithExpire($identifier, $this->timeService->getCurrentTime(), 3600 * 24);
      }
      else {
        $identifier = $input['honeypot_time'];
      }
      $form['honeypot_time'] = [
        '#type' => 'hidden',
        '#title' => $this->t('Timestamp'),
        '#default_value' => $identifier,
        '#element_validate' => [
          [$this, 'validateTimeRestriction'],
        ],
        '#cache' => ['max-age' => 0],
      ];

      // Disable page caching to make sure timestamp isn't cached.
      if ($this->account->id() == 0) {
        // @todo D8 - Use DIC?
        // @see https://www.drupal.org/node/1539454
        // Should this now set 'omit_vary_cookie' instead?
        $this->killSwitch->trigger();
      }
    }

    // Allow other modules to react to addition of form protection.
    if (!empty($options)) {
      $this->moduleHandler->invokeAll('honeypot_add_form_protection', [$options, $form]);
    }
  }

  /**
   * An #element_validate callback for the honeypot field.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateHoneypot(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    // Get the honeypot field value.
    $honeypot_value = $element['#value'];

    // Make sure it's empty.
    if (!empty($honeypot_value) || $honeypot_value == '0') {
      $this->log($form_state->getValue('form_id'), 'honeypot');
      $form_state->setErrorByName('', $this->t('There was a problem with your form submission. Please refresh the page and try again.'));
    }
  }

  /**
   * An #element_validate callback for the honeypot time restriction field.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateTimeRestriction(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    if ($form_state->isProgrammed()) {
      // Don't do anything if the form was submitted programmatically.
      return;
    }

    $triggering_element = $form_state->getTriggeringElement();
    // Don't do anything if the triggering element is a preview button.
    if ($triggering_element['#value'] == (string) $this->t('Preview')) {
      return;
    }

    $form_values = $form_state->getValues();

    // Get the time value.
    $identifier = $form_state->getValue('honeypot_time', FALSE);
    if ($this->validateHoneypotTimeIdentifier($identifier)) {
      $honeypot_time = $this->keyValue->get($identifier, 0);
    }
    else {
      $honeypot_time = $identifier = FALSE;
      unset($form_values['honeypot_time']);
    }

    // Get the honeypot_time_limit.
    $time_limit = $this->getTimeLimit($form_values);

    // Make sure current time - (time_limit + form time value) is greater
    // than 0. If not, throw an error.
    if (!$honeypot_time || $this->timeService->getRequestTime() < ($honeypot_time + $time_limit)) {
      $this->log($form_state->getValue('form_id'), 'honeypot_time');
      $time_limit = $this->getTimeLimit();
      if ($identifier) {
        $this->keyValue->setWithExpire($identifier, $this->timeService->getRequestTime(), 3600 * 24);
      }
      $form_state->setErrorByName('', $this->t('There was a problem with your form submission. Please wait @limit seconds and try again.', ['@limit' => $time_limit]));
    }
  }

  /**
   * Validate the honeypot_time parameter received from a form submission.
   *
   * This validation function checks two things:
   * - The length of the parameter must be no greater than 128 characters.
   * - The parameter must contain only ASCII characters.
   *
   * @param string $identifier
   *   Form input.
   *
   * @return bool
   *   Whether the parameter passed validation.
   */
  protected function validateHoneypotTimeIdentifier(string $identifier) {
    // The identifier cannot be longer than 128 characters.
    if (strlen($identifier) > 128) {
      return FALSE;
    }
    // Only ASCII characters are allowed by the key/value schema.
    if (!mb_check_encoding($identifier, 'ASCII')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Logs blocked form submissions.
   *
   * @param string $form_id
   *   Form ID for the form on which submission was blocked.
   * @param string $type
   *   String indicating the reason the submission was blocked. Allowed values:
   *   - honeypot: If honeypot field was filled in.
   *   - honeypot_time: If form was completed before the configured time limit.
   */
  protected function log(string $form_id, string $type): void {
    $this->logFailure($form_id, $type);
    if ($this->config->get('log')) {
      $variables = [
        '%form'  => $form_id,
        '@cause' => ($type == 'honeypot') ? $this->t('submission of a value in the honeypot field') : $this->t('submission of the form in less than minimum required time'),
      ];
      $this->loggerFactory->get('honeypot')
        ->notice('Blocked submission of %form due to @cause.', $variables);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logFailure(string $form_id, string $type): void {
    $uid = $this->account->id();

    // Log failed submissions.
    $this->connection->insert('honeypot_user')
      ->fields([
        'uid' => $uid,
        'hostname' => $this->requestStack->getCurrentRequest()->getClientIp(),
        'timestamp' => $this->timeService->getRequestTime(),
      ])
      ->execute();

    // Allow other modules to react to honeypot rejections.
    // @todo Only accepts two args.
    $this->moduleHandler->invokeAll('honeypot_reject', [$form_id, $uid, $type]);
  }

}
