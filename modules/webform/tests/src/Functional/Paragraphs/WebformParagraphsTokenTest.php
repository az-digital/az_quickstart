<?php

namespace Drupal\Tests\webform\Functional\Paragraphs;

/**
 * Tests for webform paragraphs token.
 *
 * @group webform
 */
class WebformParagraphsTokenTest extends WebformParagraphsTestBase {

  /**
   * Tests paragraphs.
   */
  public function testParagraphsToken() {
    $assert_session = $this->assertSession();

    $default_data = "textfield: 'default_data'
test_value: 'default_data-[webform_submission:source-entity:field_webform_test_value]'
test_para_value: 'default_data-[webform_submission:source-entity:field_webform_test_para_value]'
test_node_value: 'default_data-[webform_submission:source-entity:field_webform_test_node_value]'";

    /* ********************************************************************** */
    // Inline.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Inline');

    // Check that tokens are replaced with corresponding paragraph field values.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $assert_session->fieldValueEquals('test_value', '{paragraph.field_webform_test_value}');
    $assert_session->fieldValueEquals('test_para_value', '{paragraph.field_webform_test_para_value}');
    $assert_session->fieldValueEquals('test_node_value', '[webform_submission:source-entity:field_webform_test_node_value]');

    // Set tokens using default data which overides default values.
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity;
    $paragraph->field_webform_test->default_data = $default_data;
    $paragraph->save();

    // Check that tokens are replaced with corresponding paragraph field values.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', 'default_data');
    $assert_session->fieldValueEquals('test_value', 'default_data-{paragraph.field_webform_test_value}');
    $assert_session->fieldValueEquals('test_para_value', 'default_data-{paragraph.field_webform_test_para_value}');
    $assert_session->fieldValueEquals('test_node_value', 'default_data-[webform_submission:source-entity:field_webform_test_node_value]');

    /* ********************************************************************** */
    // Inline (No source).
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Inline (No Source)');

    // Check that tokens are NOT replaced with corresponding
    // paragraph field values.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $assert_session->fieldValueEquals('test_value', '{node.field_webform_test_value}');
    $assert_session->fieldValueEquals('test_para_value', '[webform_submission:source-entity:field_webform_test_para_value]');
    $assert_session->fieldValueEquals('test_node_value', '{node.field_webform_test_node_value}');

    // Set tokens using default data which overides default values.
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity;
    $paragraph->field_webform_test->default_data = $default_data;
    $paragraph->save();

    // Check that tokens are NOT replaced with corresponding
    // paragraph field values.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('textfield', 'default_data');
    $assert_session->fieldValueEquals('test_value', 'default_data-{node.field_webform_test_value}');
    $assert_session->fieldValueEquals('test_para_value', 'default_data-[webform_submission:source-entity:field_webform_test_para_value]');
    $assert_session->fieldValueEquals('test_node_value', 'default_data-{node.field_webform_test_node_value}');

    /* ********************************************************************** */
    // Link.
    /* ********************************************************************** */

    $node = $this->getNode('Webform Test Link');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity;
    $options = ['query' => ['source_entity_type' => 'paragraph', 'source_entity_id' => $paragraph->id()]];

    // Check that tokens are replaced with corresponding
    // paragraph field values.
    $this->drupalGet('/webform/webform_test_paragraphs', $options);
    $assert_session->fieldValueEquals('textfield', '{default_value}');
    $assert_session->fieldValueEquals('test_value', '{paragraph.field_webform_test_value}');
    $assert_session->fieldValueEquals('test_para_value', '{paragraph.field_webform_test_para_value}');
    $assert_session->fieldValueEquals('test_node_value', '[webform_submission:source-entity:field_webform_test_node_value]');

    // Set tokens using default data which overides default values.
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $node->field_webform_test_paragraphs->entity;
    $paragraph->field_webform_test->default_data = $default_data;
    $paragraph->save();

    // Check that tokens are replaced with corresponding
    // paragraph field values.
    $this->drupalGet('/webform/webform_test_paragraphs', $options);
    $assert_session->fieldValueEquals('textfield', 'default_data');
    $assert_session->fieldValueEquals('test_value', 'default_data-{paragraph.field_webform_test_value}');
    $assert_session->fieldValueEquals('test_para_value', 'default_data-{paragraph.field_webform_test_para_value}');
    $assert_session->fieldValueEquals('test_node_value', 'default_data-[webform_submission:source-entity:field_webform_test_node_value]');
  }

}
