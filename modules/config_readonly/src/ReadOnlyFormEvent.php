<?php

namespace Drupal\config_readonly;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;

/**
 * Readonly form event.
 */
class ReadOnlyFormEvent extends Event {

  const NAME = 'config_readonly_form_event';

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * The raw form array.
   *
   * @var array
   */
  protected $form;

  /**
   * Flag as to whether the form is read only.
   *
   * @var bool
   */
  protected $readOnlyForm;

  /**
   * The list of config names on the route for this particular event.
   *
   * @var array
   */
  protected $configNames = [];

  /**
   * Constructs a new ReadOnlyFormEvent object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The raw form.
   */
  public function __construct(FormStateInterface $form_state, array $form) {
    $this->form = $form;
    $this->readOnlyForm = FALSE;
    $this->formState = $form_state;
  }

  /**
   * Get the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Get the raw form from the alter.
   *
   * @return array
   *   The raw form array.
   */
  public function getForm(): array {
    return $this->form;
  }

  /**
   * Mark the form as read-only.
   */
  public function markFormReadOnly(): void {
    $this->readOnlyForm = TRUE;
  }

  /**
   * Mark the form as editable.
   */
  public function markFormEditable(): void {
    $this->readOnlyForm = FALSE;
  }

  /**
   * Check whether the form is read-only.
   *
   * @return bool
   *   Whether the form is read-only.
   */
  public function isFormReadOnly(): bool {
    return $this->readOnlyForm;
  }

  /**
   * Sets the config names for this event.
   *
   * @param array $config_names
   *   The config names to be set in the event.
   */
  public function setEditableConfigNames(array $config_names): void {
    $this->configNames = $config_names;
  }

  /**
   * Returns an array of config names used in this event.
   *
   * @return array
   *   The Array of editable configs.
   */
  public function getEditableConfigNames(): array {
    return $this->configNames;
  }

}
