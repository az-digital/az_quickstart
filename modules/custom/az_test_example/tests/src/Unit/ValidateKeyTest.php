<?php

namespace Drupal\Tests\az_test_example\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\az_test_example\Form\QuickstartTestExampleForm;

/**
 * Test of key validation function in example form.
 *
 * @group az_test_example
 */
class ValidateKeyTest extends UnitTestCase {

  /**
   * @covers \Drupal\az_test_example\Form\QuickstartTestExampleForm::isKeyValid
   */
  public function testKeyValidation() {

    // Value that should be accepted.
    $valid_key = 'V13872973987293472934723';

    // Value that should be rejected.
    $invalid_key = 'V23872973987293472934723';

    // Value that should be rejected due to length.
    $short_key = 'V1387';

    // Test that a valid key is accepted.
    $result = QuickstartTestExampleForm::isKeyValid($valid_key);
    $this->assertTrue($result);

    // Test that an invalid key is rejected.
    $result = QuickstartTestExampleForm::isKeyValid($invalid_key);
    $this->assertFalse($result);

    // Test that a short but otherwise valid key is rejected.
    $result = QuickstartTestExampleForm::isKeyValid($short_key);
    $this->assertFalse($result);
  }

}
