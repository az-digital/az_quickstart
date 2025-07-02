<?php

namespace Drupal\Tests\webform\Functional\Paragraphs;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform paragraphs.
 *
 * @group webform
 */
class WebformParagraphsTest extends WebformParagraphsTestBase {

  /**
   * Tests paragraphs.
   */
  public function testParagraphs() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('webform_test_paragraphs');

    /* ********************************************************************** */
    // Inline.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Inline');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity;

    // Check that inline webform is loaded with the expected default value.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', '{default_value}');

    // Check the inline webform's confirmation page tokens use the paragraph.
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('webform_submission:source-type = paragraph');
    $assert_session->responseContains('webform_submission:source-id = ' . $paragraph->id());

    // Check that the submission source entity is the paragraph.
    $submission = $this->getLastSubmission($webform);
    $this->assertEquals($node->field_webform_test_paragraphs->entity, $submission->getSourceEntity());

    /* ********************************************************************** */
    // Inline (No source).
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Inline (No Source)');

    // Check that inline webform is loaded with the expected default value.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', '{default_value}');

    // Check the inline webform's confirmation page tokens use the paragraph.
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('webform_submission:source-type = node');
    $assert_session->responseContains('webform_submission:source-id = ' . $node->id());

    // Check that the submission source entity is the node.
    $submission = $this->getLastSubmission($webform);
    $this->assertEquals($node, $submission->getSourceEntity());

    /* ********************************************************************** */
    // Link.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Link');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity;

    // Check that webform link is rendered.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->linkExists('Go to Webform Test Paragraphs webform');
    $options = ['query' => ['source_entity_type' => 'paragraph', 'source_entity_id' => $paragraph->id()]];
    $webform_url = $webform->toUrl('canonical', $options);
    $assert_session->linkByHrefExists($webform_url->toString());

    // Check that linked webform is loaded with the expected default value.
    $this->drupalGet('/webform/webform_test_paragraphs', $options);
    $assert_session->fieldValueEquals('textfield', '{default_value}');

    // Check the linked webform's title.
    $assert_session->titleEquals('Webform Test Link > Webform test_paragraphs: Webform Test Paragraphs | Drupal');

    // Check the inline webform's confirmation page tokens use the paragraph.
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('webform_submission:source-type = paragraph');
    $assert_session->responseContains('webform_submission:source-id = ' . $paragraph->id());

    // Check that the submission source entity is the paragraph.
    $submission = $this->getLastSubmission($webform);
    $this->assertEquals($node->field_webform_test_paragraphs->entity, $submission->getSourceEntity());

    /* ********************************************************************** */
    // Multiple.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Multiple');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs[1]->entity;

    // Check that multiple webforms (inline and linked) are loaded.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $assert_session->linkExists('Go to Webform Test Paragraphs webform');
    $options = ['query' => ['source_entity_type' => 'paragraph', 'source_entity_id' => $paragraph->id()]];
    $webform_url = $webform->toUrl('canonical', $options);
    $assert_session->linkByHrefExists($webform_url->toString());

    // Check the linked webform's title and default value.
    $this->drupalGet('/webform/webform_test_paragraphs', $options);
    $assert_session->titleEquals('Webform Test Multiple > Webform test_paragraphs: Webform Test Paragraphs | Drupal');
    $assert_session->fieldValueEquals('textfield', '{default_value}');

    /* ********************************************************************** */
    // Nesting.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Nesting');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity->field_webform_test_nesting->entity;

    // Check that nested linked webform is loaded.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->linkExists('Go to Webform Test Paragraphs webform');
    $options = ['query' => ['source_entity_type' => 'paragraph', 'source_entity_id' => $paragraph->id()]];
    $webform_url = $webform->toUrl('canonical', $options);
    $assert_session->linkByHrefExists($webform_url->toString());

    // Check the nested linked webform's title and default value.
    $this->drupalGet('/webform/webform_test_paragraphs', $options);
    $assert_session->titleEquals('Webform Test Nesting > Webform test_paragraphs > Webform test nesting: Webform Test Paragraphs | Drupal');
    $assert_session->fieldValueEquals('textfield', '{default_value}');
  }

}
