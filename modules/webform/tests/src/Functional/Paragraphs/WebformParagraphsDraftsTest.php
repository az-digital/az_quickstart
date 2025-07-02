<?php

namespace Drupal\Tests\webform\Functional\Paragraphs;

/**
 * Tests for webform paragraphs drafts.
 *
 * @group webform
 */
class WebformParagraphsDraftsTest extends WebformParagraphsTestBase {

  /**
   * Tests paragraphs draft (which are not working as expected).
   */
  public function testParagraphsDrafts() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Inline.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Inline');

    // Check that draft is saved.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $edit = ['textfield' => '{draft_value}'];
    $this->submitForm($edit, 'Save Draft');
    $assert_session->fieldValueEquals('textfield', '{draft_value}');

    // Check that draft is NOT loaded when the page is reloaded.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseNotContains('A partially-completed form was found. Please complete the remaining portions.');
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $assert_session->fieldValueNotEquals('textfield', '{draft_value}');

    /* ********************************************************************** */
    // Inline (No Source).
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Inline (No Source)');

    // Check that draft is saved.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $edit = ['textfield' => '{draft_value}'];
    $this->submitForm($edit, 'Save Draft');
    $assert_session->fieldValueEquals('textfield', '{draft_value}');

    // Check that draft is loaded when the page is reloaded.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('A partially-completed form was found. Please complete the remaining portions.');
    $assert_session->fieldValueNotEquals('textfield', '{default_value}');
    $assert_session->fieldValueEquals('textfield', '{draft_value}');

    /* ********************************************************************** */
    // Link.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Link');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity;
    $options = ['query' => ['source_entity_type' => 'paragraph', 'source_entity_id' => $paragraph->id()]];

    // Check that draft is saved.
    $this->drupalGet('/webform/webform_test_paragraphs', $options);
    $edit = ['textfield' => '{draft_value}'];
    $this->submitForm($edit, 'Save Draft');
    $assert_session->fieldValueEquals('textfield', '{draft_value}');

    // Check that draft is NOT loaded when the page is reloaded.
    $this->drupalGet('/webform/webform_test_paragraphs', $options);
    $assert_session->responseNotContains('A partially-completed form was found. Please complete the remaining portions.');
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $assert_session->fieldValueNotEquals('textfield', '{draft_value}');
  }

}
