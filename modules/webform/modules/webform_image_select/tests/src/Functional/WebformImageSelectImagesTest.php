<?php

namespace Drupal\Tests\webform_image_select\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;
use Drupal\webform\WebformInterface;
use Drupal\webform_image_select\Entity\WebformImageSelectImages;

/**
 * Tests for webform image select image entity.
 *
 * @group webform_image_select
 */
class WebformImageSelectImagesTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_image_select', 'webform_image_select_test'];

  /**
   * Tests webform image select images entity.
   */
  public function testWebformImageSelectImages() {
    $assert_session = $this->assertSession();

    $normal_user = $this->drupalCreateUser();

    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);

    /* ********************************************************************** */

    $this->drupalLogin($normal_user);

    // Check get element images.
    $dogs = Yaml::decode("dog_1:
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
    $element = ['#images' => $dogs];
    $this->assertEquals(WebformImageSelectImages::getElementImages($element), $dogs);
    $element = ['#images' => 'dogs'];
    $this->assertEquals(WebformImageSelectImages::getElementImages($element), $dogs);
    $element = ['#images' => 'not-found'];
    $this->assertEquals(WebformImageSelectImages::getElementImages($element), []);

    $dogs = Yaml::decode("dog_1:
  text: 'Cute Dog 1'
  src: 'http://placedog.net/220/200'
dog_test_2:
  text: 'Cute Dog 2'
  src: 'http://placedog.net/180/200'
dog_test_3:
  text: 'Cute Dog 3'
  src: 'http://placedog.net/130/200'
dog_test_4:
  text: 'Cute Dog 4'
  src: 'http://placedog.net/270/200'");

    // Check get element images for manually defined images.
    $element = ['#images' => $dogs];
    $this->assertEquals(WebformImageSelectImages::getElementImages($element), $dogs);

    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images */
    $webform_images = WebformImageSelectImages::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'dogs_test',
      'title' => 'Dogs Test',
      'images' => Yaml::encode($dogs),
    ]);
    $webform_images->save();

    // Check get images.
    $this->assertEquals($webform_images->getImages(), $dogs);

    // Set invalid images.
    $webform_images->set('images', "not\nvalid\nyaml")->save();

    // Check invalid images.
    $this->assertEquals([], $webform_images->getImages());

    // Check normal user access denied.
    $this->drupalGet('/admin/structure/webform/options/images/manage');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/webform/options/images/manage/add');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/webform/options/images/manage/animals/edit');
    $assert_session->statusCodeEquals(403);

    // Check admin user access.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/options/images/manage');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/structure/webform/options/images/manage/add');
    $assert_session->statusCodeEquals(200);

    // Check image altered message.
    $this->drupalGet('/admin/structure/webform/options/images/manage/animals/edit');
    $assert_session->responseContains('The <em class="placeholder">Cute Animals</em> images are being altered by the <em class="placeholder">Webform Image Select test</em> module.');

    // Check hook_webform_image_select_images_alter().
    // Check hook_webform_image_select_images_WEBFORM_IMAGE_SELECT_IMAGES_ID_alter().
    $element = ['#images' => 'animals'];
    $images = WebformImageSelectImages::getElementImages($element);
    $this->debug($images);
    $this->assertEquals(array_keys($images), ['dog_1', 'dog_2', 'dog_3', 'dog_4', 'bear_1', 'bear_2', 'bear_3', 'bear_4']);
  }

}
