<?php

namespace Drupal\webform_location_places\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformLocationBase;

/**
 * Provides a webform element for a location places element.
 *
 * @FormElement("webform_location_places")
 */
class WebformLocationPlaces extends WebformLocationBase {

  /**
   * {@inheritdoc}
   */
  protected static $name = 'places';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#app_id' => '',
      '#api_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getLocationAttributes() {
    return [
      'lat' => t('Latitude'),
      'lng' => t('Longitude'),
      'name' => t('Name'),
      'city' => t('City'),
      'country' => t('Country'),
      'country_code' => t('Country Code'),
      'administrative' => t('State/Province'),
      'county' => t('County'),
      'suburb' => t('Suburb'),
      'postcode' => t('Postal Code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);
    // Add Algolia application id and API key.
    /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
    $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
    if (!empty($element['#app_id'])) {
      $app_id = $element['#app_id'];
    }
    else {
      $app_id = $third_party_settings_manager->getThirdPartySetting('webform_location_places', 'default_algolia_places_app_id') ?: '';
    }
    if (!empty($element['#api_key'])) {
      $api_key = $element['#api_key'];
    }
    else {
      $api_key = $third_party_settings_manager->getThirdPartySetting('webform_location_places', 'default_algolia_places_api_key') ?: '';
    }
    $element['#attached']['drupalSettings']['webform']['location']['places'] = [
      'app_id' => $app_id,
      'api_key' => $api_key,
    ];

    // Attach library.
    $element['#attached']['library'][] = 'webform_location_places/webform_location_places.element';

    return $element;
  }

}
