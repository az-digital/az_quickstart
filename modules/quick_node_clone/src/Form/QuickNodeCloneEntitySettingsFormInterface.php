<?php

namespace Drupal\quick_node_clone\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for an Clone Entity forms.
 */
interface QuickNodeCloneEntitySettingsFormInterface {

  /**
   * Sets the entity type the settings form is for.
   *
   * @param string $entityTypeId
   *   The entity type id i.e. article.
   */
  public function setEntityType($entityTypeId);

  /**
   * Returns the entity type Id. i.e. article.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId();

  /**
   * The array of config names.
   *
   * @return array
   *   The config.
   */
  public function getEditableConfigNames();

  /**
   * Returns the bundles for the entity.
   *
   * @return string
   *   The bundle of the entity.
   */
  public function getEntityBundles();

  /**
   * Returns the selected bundles on the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|mixed|null
   *   An array/mixed set of content types if there are any, or null.
   */
  public function getSelectedBundles(FormStateInterface $form_state);

  /**
   * Returns the description field.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   The description text.
   */
  public function getDescription(FormStateInterface $form_state);

  /**
   * Returns the default fields.
   *
   * @param string $value
   *   The bundle name.
   *
   * @return array
   *   The default fields.
   */
  public function getDefaultFields($value);

  /**
   * Return the configuration.
   *
   * @param string $value
   *   The setting name.
   *
   * @return array|mixed|null
   *   Returns the setting value if it exists, or NULL.
   */
  public function getSettings($value);

}
