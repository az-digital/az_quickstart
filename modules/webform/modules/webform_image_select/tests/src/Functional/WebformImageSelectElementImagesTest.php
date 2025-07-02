<?php

namespace Drupal\Tests\webform_image_select\Functional;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;

/**
 * Tests for webform image select images element.
 *
 * @group webform_image_select
 */
class WebformImageSelectElementImagesTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_image_select', 'webform_image_select_test'];

  /**
   * Tests webform images select images element.
   */
  public function testElementOptions() {
    $assert_session = $this->assertSession();

    // Check default value handling.
    $this->drupalGet('/webform/test_element_images');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_image_select_images: {  }
webform_image_select_images_default_value:
  dog_1:
    text: 'Cute Dog 1'
    src: 'https://placedog.net/220/200'
  dog_2:
    text: 'Cute Dog 2'
    src: 'https://placedog.net/180/200'
  dog_3:
    text: 'Cute Dog 3'
    src: 'https://placedog.net/130/200'
  dog_4:
    text: 'Cute Dog 4'
    src: 'https://placedog.net/270/200'
webform_image_select_element_images_entity: dogs
webform_image_select_element_images_custom:
  dog_1:
    text: 'Cute Dog 1'
    src: 'https://placedog.net/220/200'
  dog_2:
    text: 'Cute Dog 2'
    src: 'https://placedog.net/180/200'
  dog_3:
    text: 'Cute Dog 3'
    src: 'https://placedog.net/130/200'
  dog_4:
    text: 'Cute Dog 4'
    src: 'https://placedog.net/270/200'");

    // Check unique key validation with image src.
    $this->drupalGet('/webform/test_element_images');
    $edit = [
      'webform_image_select_images[images][items][0][src]' => 'src01',
      'webform_image_select_images[images][items][1][src]' => 'src02',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("The <em class=\"placeholder\">Image value</em> '' is already in use. It must be unique.");
  }

}
