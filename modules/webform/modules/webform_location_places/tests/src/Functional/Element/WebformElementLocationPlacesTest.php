<?php

namespace Drupal\Tests\webform_location_places\Functional\Element;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for location (Algolia) places element.
 *
 * @group webform
 */
class WebformElementLocationPlacesTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_location_places', 'webform_location_places_test'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_loc_places'];

  /**
   * Test location (Algolia) places element.
   */
  public function testLocationPlaces() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_loc_places');

    $this->drupalGet('/webform/test_element_loc_places');

    // Check hidden attributes.
    $assert_session->responseContains('<input class="webform-location-places form-text" data-drupal-selector="edit-location-default-value" type="text" id="edit-location-default-value" name="location_default[value]" value="" size="60" maxlength="255" placeholder="Enter a location" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="lat" data-drupal-selector="edit-location-default-lat" type="hidden" name="location_default[lat]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="lng" data-drupal-selector="edit-location-default-lng" type="hidden" name="location_default[lng]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="name" data-drupal-selector="edit-location-default-name" type="hidden" name="location_default[name]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="city" data-drupal-selector="edit-location-default-city" type="hidden" name="location_default[city]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="country" data-drupal-selector="edit-location-default-country" type="hidden" name="location_default[country]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="country_code" data-drupal-selector="edit-location-default-country-code" type="hidden" name="location_default[country_code]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="administrative" data-drupal-selector="edit-location-default-administrative" type="hidden" name="location_default[administrative]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="county" data-drupal-selector="edit-location-default-county" type="hidden" name="location_default[county]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="suburb" data-drupal-selector="edit-location-default-suburb" type="hidden" name="location_default[suburb]" value="" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="postcode" data-drupal-selector="edit-location-default-postcode" type="hidden" name="location_default[postcode]" value="" />');

    // Check visible attributes.
    $assert_session->responseContains('<input class="webform-location-places form-text" data-drupal-selector="edit-location-attributes-value" type="text" id="edit-location-attributes-value" name="location_attributes[value]" value="" size="60" maxlength="255" />');
    $assert_session->responseContains('<input data-webform-location-places-attribute="lat" data-drupal-selector="edit-location-attributes-lat" type="text" id="edit-location-attributes-lat" name="location_attributes[lat]" value="" size="60" maxlength="255" class="form-text" />');

    // Check invalid validation.
    $edit = [
      'location_attributes_required[value]' => 'test',
    ];
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('The location_attributes_required is not valid.');

    // Check valid validation with lat(itude).
    $edit = [
      'location_attributes_required[value]' => 'test',
      'location_attributes_required[lat]' => 1,
    ];
    $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('The location_attributes_required is not valid.');

    // Check application id and API key is missing.
    $assert_session->responseNotContains('"app_id"');
    $assert_session->responseNotContains('"api_key"');

    // Set application id and API key.
    $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
    $third_party_settings_manager->setThirdPartySetting('webform_location_places', 'default_algolia_places_app_id', '{default_algolia_places_app_id}');
    $third_party_settings_manager->setThirdPartySetting('webform_location_places', 'default_algolia_places_api_key', '{default_algolia_places_api_key}');

    // Check application id and API key is set.
    $this->drupalGet('/webform/test_element_loc_places');
    $assert_session->responseContains('"app_id"');
    $assert_session->responseContains('"api_key"');
    $assert_session->responseContains('"webform":{"location":{"places":{"app_id":"{default_algolia_places_app_id}","api_key":"{default_algolia_places_api_key}"}}}');
  }

}
