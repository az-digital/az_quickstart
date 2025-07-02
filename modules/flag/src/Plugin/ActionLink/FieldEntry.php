<?php

namespace Drupal\flag\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\flag\FlagInterface;

/**
 * Field Entry.
 *
 * @ActionLinkType(
 *  id = "field_entry",
 *  label = @Translation("Field Entry Form"),
 *  description = @Translation("Redirects the user to a field entry form.")
 * )
 */
class FieldEntry extends FormEntryTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getUrl($action, FlagInterface $flag, EntityInterface $entity) {
    switch ($action) {
      case 'flag':
        return Url::fromRoute('flag.field_entry', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);

      default:
        return Url::fromRoute('flag.field_entry.edit', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $options = parent::defaultConfiguration();

    // Change label for flag confirmation text.
    $options['flag_confirmation'] = $this->t('Enter flagging details');
    $options['edit_flagging'] = $this->t('Edit flagging details');
    $options['flag_update_button'] = $this->t('Update flagging');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['display']['settings']['link_options_' . $this->getPluginId()]['edit_flagging'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edit flagging details message'),
      '#default_value' => $this->configuration['edit_flagging'],
      '#description' => $this->t('Message displayed if the user has clicked the "Edit flag" link. Usually presented in the form such as, "Please enter the flagging details."'),
      // This will get changed to a state by flag_link_type_options_states().
      '#required' => TRUE,
    ];

    $form['display']['settings']['link_options_field_entry']['flag_update_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Update flagging button text'),
      '#default_value' => $this->configuration['flag_update_button'],
      '#description' => $this->t('The text for the submit button when updating a flagging.'),
      // This will get changed to a state by flag_link_type_options_states().
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $form_values = $form_state->getValues();

    if (empty($form_values['edit_flagging'])) {
      $form_state->setErrorByName('flagging_edit_title', $this->t('An edit flagging details message is required when using the field entry link type.'));
    }
  }

}
