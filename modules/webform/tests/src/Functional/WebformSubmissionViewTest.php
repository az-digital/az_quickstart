<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission view as HTML, YAML, and plain text.
 *
 * @group webform
 */
class WebformSubmissionViewTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Tests view submissions.
   */
  public function testView() {
    $assert_session = $this->assertSession();

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /* ********************************************************************** */

    $account = User::load(1);

    $webform_element = Webform::load('test_element');
    $sid = $this->postSubmission($webform_element);
    $submission = WebformSubmission::load($sid);

    $this->drupalLogin($admin_submission_user);

    $this->drupalGet('/admin/structure/webform/manage/test_element/submission/' . $submission->id());

    // Check displayed values.
    $elements = [
      'hidden' => '{hidden}',
      'value' => '{value}',
      'textarea' => "{textarea line 1}<br />\n{textarea line 2}",
      'empty' => '{Empty}',
      'textfield' => '{textfield}',
      'select' => 'one',
      'select_multiple' => 'one, two',
      'checkbox' => 'Yes',
      'checkboxes' => 'one, two',
      'radios' => 'Yes',
      'email' => '<a href="mailto:example@example.com">example@example.com</a>',
      'number' => '1',
      'range' => '1',
      'tel' => '<a href="tel:999-999-9999">999-999-9999</a>',
      'url' => '<a href="http://example.com">http://example.com</a>',
      'color' => '<font color="#ffffcc">█</font> #ffffcc',
      'weight' => '0',
      'date' => 'Tuesday, August 18, 2009',
      'datetime' => 'Tuesday, August 18, 2009 - 4:00 PM',
      'datelist' => 'Tuesday, August 18, 2009 - 4:00 PM',
      'dollars' => '$100.00',
      'text_format' => '<p>The quick brown fox jumped over the lazy dog.</p>',
      'entity_autocomplete_user' => '<a href="' . $account->toUrl()->setAbsolute(TRUE)->toString() . '" hreflang="en">admin</a>',
      'language_select' => 'English (en)',
    ];
    foreach ($elements as $label => $value) {
      $assert_session->responseContains("<label>$label</label>" . PHP_EOL . "        $value");
    }

    // Check details element.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="test_element--standard_elements" aria-expanded="true">Standard Elements</summary>'),
      deprecatedCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="test_element--standard_elements" aria-expanded="true" aria-pressed="true">Standard Elements</summary>'),
    );

    // Check empty details element removed.
    $assert_session->responseNotContains('Markup Elements');
  }

}
