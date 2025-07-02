<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform token submission value.
 *
 * @group webform
 */
class WebformRenderingTest extends WebformBrowserTestBase {

  use AssertMailTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_rendering'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create filters.
    $this->createFilters();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test text format element.
   */
  public function testRendering() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_rendering');

    /* ********************************************************************** */
    // Preview.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_rendering');
    $this->submitForm([], 'Preview');

    // Check preview submission_label.
    $assert_session->responseContains('submission &lt;em&gt;label&lt;/em&gt; (&amp;&gt;&lt;#)');

    // Check preview textfield_plain_text.
    $assert_session->responseContains('{prefix}{default_value}{suffix}');

    // Check preview textfield_markup.
    $assert_session->responseContains('<label><em>textfield_markup</em></label>');
    $assert_session->responseContains('<em>{prefix}</em>{default_value}<em>{suffix}</em>');

    // Check preview textfield_special_characters.
    $assert_session->responseContains('<label>textfield_special_characters (&amp;&gt;&lt;#)</label>');
    $assert_session->responseContains('(&amp;&gt;&lt;#){default_value}(&amp;&gt;&lt;#)');

    // Check preview text_format_basic_html.
    $assert_session->responseContains('<p><em>{default_value}</em></p>');

    // Create a submission.
    $sid = $this->postSubmission($webform);

    /* ********************************************************************** */
    // Emails.
    /* ********************************************************************** */

    // Get sent emails.
    $sent_emails = $this->getMails();
    $html_email = $sent_emails[0];
    $text_email = $sent_emails[1];

    // Check HTML email.
    $this->assertEquals($html_email['subject'], 'submission label (&>');
    $this->assertEquals($html_email['params']['subject'], 'submission <em>label</em> (&><#)');
    $this->assertStringContainsString('<b>submission_label</b><br />submission &lt;em&gt;label&lt;/em&gt; (&amp;&gt;&lt;#)<br /><br />', $html_email['params']['body']);
    $this->assertStringContainsString('<b>textfield_plain_text</b><br />{prefix}{default_value}{suffix}<br /><br />', $html_email['params']['body']);
    $this->assertStringContainsString('<b><em>textfield_markup</em></b><br /><em>{prefix}</em>{default_value}<em>{suffix}</em><br /><br />', $html_email['params']['body']);
    $this->assertStringContainsString('<b>textfield_special_characters (&amp;&gt;&lt;#)</b><br />(&amp;&gt;&lt;#){default_value}(&amp;&gt;&lt;#)<br /><br />', $html_email['params']['body']);
    $this->assertStringContainsString('<b>text_format_basic_html</b><br /><p><em>{default_value}</em></p><br /><br />', $html_email['params']['body']);

    // Check plain text email.
    $this->assertEquals($text_email['subject'], 'submission label (&>');
    $this->assertEquals($text_email['params']['subject'], 'submission <em>label</em> (&><#)');
    $this->assertStringContainsString('submission_label: submission <em>label</em> (&><#)', $text_email['params']['body']);
    $this->assertStringContainsString('textfield_plain_text: {prefix}{default_value}{suffix}', $text_email['params']['body']);
    $this->assertStringContainsString('textfield_markup: {prefix}{default_value}{suffix}', $text_email['params']['body']);
    $this->assertStringContainsString('textfield_special_characters (&>: (&>{default_value}(&>', $text_email['params']['body']);
    $this->assertStringContainsString('text_format_basic_html:', $text_email['params']['body']);
    $this->assertStringContainsString('/{default_value}/', $text_email['params']['body']);

    /* ********************************************************************** */
    // Submission.
    /* ********************************************************************** */

    // Check view submission.
    $this->drupalGet("admin/structure/webform/manage/test_rendering/submission/$sid");

    // Check submission label token replacements.
    $assert_session->responseContains('<h1>submission &lt;em&gt;label&lt;/em&gt; (&amp;&gt;&lt;#)</h1>');
  }

}
