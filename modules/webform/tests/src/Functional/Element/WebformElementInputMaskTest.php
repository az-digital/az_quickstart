<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for element input mask.
 *
 * @group webform
 */
class WebformElementInputMaskTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_test_element_input_masks'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_input_mask'];

  /**
   * Test element input mask.
   */
  public function testInputMask() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_input_mask');

    // Check default values.
    $this->postSubmission($webform);
    $this->assertWebformYaml("currency: '$ 1.00'
currency_negative: '-$ 1.00'
currency_positive_negative: '$ 1.00'
datetime: ''
decimal: ''
decimal_negative: ''
decimal_positive_negative: ''
email: ''
ip: ''
license_plate: ''
mac: ''
percentage: ''
phone: ''
ssn: ''
vin: ''
zip: ''
uppercase: ''
lowercase: ''
custom: ''
module: ''");

    // Check patterns.
    $edit = [
      'email' => 'example@example.com',
      'datetime' => '2007-06-09\'T\'17:46:21',
      'decimal' => '9.9',
      'decimal_negative' => '-9.999',
      'decimal_positive_negative' => '-9.999',
      'ip' => '255.255.255.255',
      'currency' => '$ 9.99',
      'currency_negative' => '-$ 9.99',
      'currency_positive_negative' => '-$ 9.99',
      'percentage' => '99 %',
      'phone' => '(999) 999-9999',
      'license_plate' => '9-AAA-999',
      'mac' => '99-99-99-99-99-99',
      'ssn' => '999-99-9999',
      'vin' => 'JA3AY11A82U020534',
      'zip' => '99999-9999',
      'uppercase' => 'UPPERCASE',
      'lowercase' => 'lowercase',
      'module' => '999',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertWebformYaml("currency: '$ 9.99'
currency_negative: '-$ 9.99'
currency_positive_negative: '-$ 9.99'
datetime: '2007-06-09''T''17:46:21'
decimal: '9.9'
decimal_negative: '-9.999'
decimal_positive_negative: '-9.999'
email: example@example.com
ip: 255.255.255.255
license_plate: 9-AAA-999
mac: 99-99-99-99-99-99
percentage: '99 %'
phone: '(999) 999-9999'
ssn: 999-99-9999
vin: JA3AY11A82U020534
zip: 99999-9999
uppercase: UPPERCASE
lowercase: lowercase
custom: ''
module: '999'");

    // Check pattern validation error messages.
    $edit = [
      'currency' => '$ 9.9_',
      'currency_negative' => '-$ 9.9_',
      'currency_positive_negative' => '-$ 9.9_',
      'decimal' => '9._',
      'decimal_negative' => '-9._',
      'decimal_positive_negative' => '-9._',
      'ip' => '255.255.255.__',
      'mac' => '99-99-99-99-99-_)',
      'percentage' => '_ %',
      'phone' => '(999) 999-999_',
      'ssn' => '999-99-999_',
      'zip' => '99999-999_',
      'module' => '99_',
    ];
    $this->postSubmission($webform, $edit);
    foreach ($edit as $name => $value) {
      $assert_session->responseContains('<em class="placeholder">' . $name . '</em> field is not in the right format.');
    }

    // Check currency submitted as the default input (ie $ 0.00) triggers
    // required validation.
    // @see \Drupal\webform\Plugin\WebformElement\TextBase::validateInputMask
    $this->postSubmission($webform, ['currency' => '$ 0.00']);
    $assert_session->responseContains('currency field is required.');

  }

}
