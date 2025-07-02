<?php

namespace Drupal\devel\Form;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form API form to edit a state.
 */
class SystemStateEdit extends FormBase {

  /**
   * The state store.
   */
  protected StateInterface $state;

  /**
   * Logger service.
   */
  protected LoggerInterface $logger;

  /**
   * The dumper service.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->state = $container->get('state');
    $instance->messenger = $container->get('messenger');
    $instance->logger = $container->get('logger.channel.devel');
    $instance->stringTranslation = $container->get('string_translation');
    $instance->dumper = $container->get('devel.dumper');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_state_system_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $state_name = ''): array {
    // Get the old value.
    $old_value = $this->state->get($state_name);

    if (!isset($old_value)) {
      $this->messenger->addWarning($this->t('State @name does not exist in the system.', ['@name' => $state_name]));
      return $form;
    }

    // Only simple structures are allowed to be edited.
    $disabled = !$this->checkObject($old_value);

    if ($disabled) {
      $this->messenger->addWarning($this->t('Only simple structures are allowed to be edited. State @name contains objects.', ['@name' => $state_name]));
    }

    // First we show the user the content of the variable about to be edited.
    $form['value'] = [
      '#type' => 'item',
      '#title' => $this->t('Current value for %name', ['%name' => $state_name]),
      '#markup' => $this->dumper->dumpOrExport(input: $old_value),
    ];

    $transport = 'plain';

    if (!$disabled && is_array($old_value)) {
      try {
        $old_value = Yaml::encode($old_value);
        $transport = 'yaml';
      }
      catch (InvalidDataTypeException $e) {
        $this->messenger->addError($this->t('Invalid data detected for @name : %error', ['@name' => $state_name, '%error' => $e->getMessage()]));
        return $form;
      }
    }

    // Store in the form the name of the state variable.
    $form['state_name'] = [
      '#type' => 'value',
      '#value' => $state_name,
    ];
    // Set the transport format for the new value. Values:
    // - plain
    // - yaml.
    $form['transport'] = [
      '#type' => 'value',
      '#value' => $transport,
    ];

    $form['new_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('New value'),
      '#default_value' => $disabled ? '' : $old_value,
      '#disabled' => $disabled,
      '#rows' => 15,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#disabled' => $disabled,
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('devel.state_system_page'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    if ($values['transport'] == 'yaml') {
      // Try to parse the new provided value.
      try {
        $parsed_value = Yaml::decode($values['new_value']);
        $form_state->setValue('parsed_value', $parsed_value);
      }
      catch (InvalidDataTypeException $e) {
        $form_state->setErrorByName('new_value', $this->t('Invalid input: %error', ['%error' => $e->getMessage()]));
      }
    }
    else {
      $form_state->setValue('parsed_value', $values['new_value']);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save the state.
    $values = $form_state->getValues();
    $this->state->set($values['state_name'], $values['parsed_value']);

    $form_state->setRedirectUrl(Url::fromRoute('devel.state_system_page'));
    $this->messenger->addMessage($this->t('Variable %variable was successfully edited.', ['%variable' => $values['state_name']]));
    $this->logger->info('Variable %variable was successfully edited.', ['%variable' => $values['state_name']]);
  }

  /**
   * Helper function to determine if a variable is or contains an object.
   *
   * @param mixed $data
   *   Input data to check.
   *
   * @return bool
   *   TRUE if the variable is not an object and does not contain one.
   */
  protected function checkObject(mixed $data): bool {
    if (is_object($data)) {
      return FALSE;
    }

    if (is_array($data)) {
      // If the current object is an array, then check recursively.
      foreach ($data as $value) {
        // If there is an object the whole container is "contaminated".
        if (!$this->checkObject($value)) {
          return FALSE;
        }
      }
    }

    // All checks pass.
    return TRUE;
  }

}
