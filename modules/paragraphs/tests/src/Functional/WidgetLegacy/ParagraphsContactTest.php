<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetLegacy;

use Drupal\contact\Entity\ContactForm;

/**
 * Tests paragraphs with contact forms.
 *
 * @group paragraphs
 */
class ParagraphsContactTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'contact',
  );

  /**
   * Tests adding paragraphs in contact forms.
   */
  public function testContactForm() {
    $this->loginAsAdmin([
      'administer contact forms',
      'access site-wide contact form'
    ]);
    // Add a paragraph type.
    $this->addParagraphsType('paragraphs_contact');
    $this->addParagraphsType('text');

    // Create a contact form.
    $contact_form = ContactForm::create(['id' => 'test_contact_form', 'label' => 'Test form']);
    $contact_form->save();
    // Add a paragraphs field to the contact form.
    $this->addParagraphsField($contact_form->id(), 'paragraphs', 'contact_message', 'entity_reference_paragraphs');

    // Add a paragraph to the contact form.
    $this->drupalGet('contact/test_contact_form');
    $this->submitForm([], 'paragraphs_paragraphs_contact_add_more');
    // Check that the paragraph is displayed.
    $this->assertSession()->pageTextContains('paragraphs_contact');
    $this->submitForm([], 'paragraphs_0_remove');
    $this->assertSession()->pageTextContains('Deleted Paragraph: paragraphs_contact');
  }
}
