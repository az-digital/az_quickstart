<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission webform element custom #format support.
 *
 * @group webform
 */
class WebformElementFormatCustomTest extends WebformElementBrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_format_custom'];

  /**
   * Tests element custom format.
   */
  public function testFormatCustom() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_element_format_custom');

    $files = $this->getTestFiles('image');
    $this->debug($files[0]);
    $edit = [
      'files[image_custom]' => \Drupal::service('file_system')->realpath($files[0]->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);

    // Retrieves the fid of the last inserted file.
    $fid = (int) \Drupal::database()->query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
    $file = File::load($fid);
    $file_name = $file->getFilename();
    $file_url = $file->createFileUrl(FALSE);

    /* ********************************************************************** */
    // Custom HTML.
    /* ********************************************************************** */

    $this->drupalGet("admin/structure/webform/manage/test_element_format_custom/submission/$sid");

    // Check basic custom HTML format.
    $assert_session->responseContains('<label>textfield_custom</label>');
    $assert_session->responseContains('<em>{textfield_custom}</em>');

    // Check basic custom token HTML format.
    $assert_session->responseContains('<label>textfield_custom_token</label>');
    $assert_session->responseContains('<em>{textfield_custom_token}</em>');

    // Check caught exception is displayed to users with update access.
    // @see \Drupal\webform\Twig\TwigExtension::renderTwigTemplate
    $assert_session->responseContains('(&quot;The &quot;[webform_submission:values:textfield_custom_token_exception]&quot; is being called recursively.&quot;)');
    $assert_session->responseContains('<label>textfield_custom_token_exception</label>');
    $assert_session->responseContains('<em>EXCEPTION</em>');

    // Check multiple custom HTML format.
    $assert_session->responseContains('<label>textfield_custom_value</label>');
    $assert_session->responseContains('<ul><li><em>One</em></li><li><em>Two</em></li><li><em>Three</em></li><li><em>Four</em></li><li><em>Five</em></li></ul>');

    // Check multiple custom HTML format.
    $assert_session->responseContains('<label>textfield_custom_value_multiple</label>');
    $assert_session->responseContains('<table>');
    $assert_session->responseContains('<tr ><td><em>One</em></td></tr>');
    $assert_session->responseContains('<tr style="background-color: #ffc"><td><em>Two</em></td></tr>');
    $assert_session->responseContains('<tr ><td><em>Three</em></td></tr>');
    $assert_session->responseContains('<tr style="background-color: #ffc"><td><em>Four</em></td></tr>');
    $assert_session->responseContains('<tr ><td><em>Five</em></td></tr>');
    $assert_session->responseContains('</table>');

    // Check image custom HTML format.
    $assert_session->responseContains('<label>image_custom</label>');
    $assert_session->responseContains('value: 1<br/>');
    $assert_session->responseContains("item['value']: $file_url<br/>");
    $assert_session->responseContains("item['raw']: $file_url<br/>");
    $assert_session->responseContains("item['link']:");
    $assert_session->responseContains('<span class="file file--mime-image-png file--image"><a href="' . $file->createFileUrl() . '" type="image/png">' . $file_name . '</a></span>');
    $assert_session->responseContains('item[\'id\']: 1<br/>');
    $assert_session->responseContains("item['url']: $file_url<br/>");
    $assert_session->responseContains('<img class="webform-image-file" alt="' . $file_name . '" title="' . $file_name . '" src="' . $file_url . '" />');

    // Check composite custom HTML format.
    $assert_session->responseContains('<label>address_custom</label>');
    $assert_session->responseContains('element.address: {address}<br/>');
    $assert_session->responseContains('element.address_2: {address_2}<br/>');
    $assert_session->responseContains('element.city: {city}<br/>');
    $assert_session->responseContains('element.state_province: {state_province}<br/>');
    $assert_session->responseContains('element.postal_code: {postal_code}<br/>');
    $assert_session->responseContains('element.country: {country}<br/>');

    // Check composite multiple custom HTML format.
    $assert_session->responseContains('<label>address_multiple_custom</label>');
    $assert_session->responseContains('<div>*****</div>
element.address: {02-address}<br/>
element.address_2: {02-address_2}<br/>
element.city: {02-city}<br/>
element.state_province: {02-state_province}<br/>
element.postal_code: {02-postal_code}<br/>
element.country: {02-country}<br/>
<div>*****</div>');

    // Check fieldset displayed as details.
    $assert_session->responseContains('<details class="webform-container webform-container-type-details js-form-wrapper form-wrapper" data-webform-element-id="test_element_format_custom--fieldset_custom" id="test_element_format_custom--fieldset_custom" open="open">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="test_element_format_custom--fieldset_custom" aria-expanded="true">fieldset_custom</summary>'),
      deprecatedCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="test_element_format_custom--fieldset_custom" aria-expanded="true" aria-pressed="true">fieldset_custom</summary>'),
    );

    // Check container custom HTML format.
    $assert_session->responseContains('<h3>fieldset_custom_children</h3>' . PHP_EOL . '<hr />');

    /* ********************************************************************** */
    // Custom Text.
    /* ********************************************************************** */

    $this->drupalGet("admin/structure/webform/manage/test_element_format_custom/submission/$sid/text");
    $assert_session->responseContains("textfield_custom: /{textfield_custom}/
textfield_custom_token: /{textfield_custom_token}/
textfield_custom_token_exception: /EXCEPTION/
textfield_custom_value:
- /One/
- /Two/
- /Three/
- /Four/
- /Five/

textfield_custom_value_multiple:
⦿ /One/
⦿ /Two/
⦿ /Three/
⦿ /Four/
⦿ /Five/


image_custom:
value: 1
item['value']: $file_url
item['raw']: $file_url
item['link']: $file_url
item['id']: 1
item['url']: $file_url

address_custom:
element.address: {address}
element.address_2: {address_2}
element.city: {city}
element.state_province: {state_province}
element.postal_code: {postal_code}
element.country: {country}

address_multiple_custom:
*****
element.address: {01-address}
element.address_2: {01-address_2}
element.city: {01-city}
element.state_province: {01-state_province}
element.postal_code: {01-postal_code}
element.country: {01-country}
*****
*****
element.address: {02-address}
element.address_2: {02-address_2}
element.city: {02-city}
element.state_province: {02-state_province}
element.postal_code: {02-postal_code}
element.country: {02-country}
*****


fieldset_custom
---------------
fieldset_custom_textfield: {fieldset_custom_textfield}

fieldset_custom_children
------------------------
fieldset_custom_children_textfield: {fieldset_custom_children_textfield}

");
  }

}
