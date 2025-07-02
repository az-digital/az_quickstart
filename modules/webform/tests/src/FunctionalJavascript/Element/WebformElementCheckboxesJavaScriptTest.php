<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Element;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests webform checkboxes element.
 *
 * @group webform_javascript
 */
class WebformElementCheckboxesJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_checkboxes_all_none',
  ];

  /**
   * Tests check all or none of the above.
   */
  public function testCheckboxesAllNone() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_checkboxes_all_none');

    // Check that check all is toggled on when all the checkboxes.
    $assert_session->checkboxNotChecked('edit-checkboxes-all-all');
    $this->click('#edit-checkboxes-all-one');
    $this->click('#edit-checkboxes-all-two');
    $this->click('#edit-checkboxes-all-three');
    $assert_session->checkboxChecked('edit-checkboxes-all-all');

    // Check that the 'all' checkbox is unchecked when any checkbox is unchecked.
    $this->click('#edit-checkboxes-all-three');
    $assert_session->checkboxNotChecked('edit-checkboxes-all-all');

    // Check that all checkboxes are checked when checking 'all'.
    $assert_session->checkboxNotChecked('edit-checkboxes-all-three');
    $this->click('#edit-checkboxes-all-all');
    $assert_session->checkboxChecked('edit-checkboxes-all-three');

    // Check that checking 'none' disables all checkboxes.
    $this->click('#edit-checkboxes-none-none');
    $assert_session->checkboxNotChecked('edit-checkboxes-none-one');
    $assert_session->checkboxNotChecked('edit-checkboxes-none-two');
    $assert_session->checkboxNotChecked('edit-checkboxes-none-three');

    // Check the 'all' and 'none' work together.
    $this->click('#edit-checkboxes-both-none');
    $assert_session->checkboxNotChecked('edit-checkboxes-both-one');
    $assert_session->checkboxNotChecked('edit-checkboxes-both-two');
    $assert_session->checkboxNotChecked('edit-checkboxes-both-three');
    $assert_session->checkboxNotChecked('edit-checkboxes-both-all');

    $this->click('#edit-checkboxes-both-none');
    $this->click('#edit-checkboxes-both-all');
    $assert_session->checkboxChecked('edit-checkboxes-both-one');
    $assert_session->checkboxChecked('edit-checkboxes-both-two');
    $assert_session->checkboxChecked('edit-checkboxes-both-three');
    $assert_session->checkboxChecked('edit-checkboxes-both-all');

    // Check that 'all' is checked when form is prepopulated.
    $options = [
      'query' => [
        'checkboxes_all' => ['one', 'two', 'three'],
      ],
    ];
    $this->drupalGet('/webform/test_element_checkboxes_all_none', $options);
    $assert_session->checkboxChecked('edit-checkboxes-all-all');
  }

}
