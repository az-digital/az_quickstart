<?php

namespace Drupal\honeypot;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a service to append Honeypot protection to forms.
 */
interface HoneypotServiceInterface {

  /**
   * Builds an array of all the protected forms on the site.
   *
   * @return array
   *   An array whose values are the form_ids of all the protected forms
   *   on the site.
   */
  public function getProtectedForms(): array;

  /**
   * Looks up the time limit for the current user.
   *
   * @param array $form_values
   *   (optional) Array of form values.
   *
   * @return int
   *   The time limit in seconds.
   */
  public function getTimeLimit(array $form_values = []): int;

  /**
   * Adds honeypot protection to provided form.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state object.
   * @param array $options
   *   (optional) Array of options to be added to form. Currently accepts
   *   'honeypot' and 'time_restriction'.
   */
  public function addFormProtection(array &$form, FormStateInterface $form_state, array $options = []): void;

  /**
   * Logs the failed submission with timestamp and hostname.
   *
   * @param string $form_id
   *   Form ID for the rejected form submission.
   * @param string $type
   *   String indicating the reason the submission was blocked. Allowed values:
   *   - honeypot: If honeypot field was filled in.
   *   - honeypot_time: If form was completed before the configured time limit.
   */
  public function logFailure(string $form_id, string $type): void;

}
