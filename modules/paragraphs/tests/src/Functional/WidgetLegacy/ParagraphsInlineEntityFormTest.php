<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetLegacy;

/**
 * Tests the configuration of paragraphs in relation to ief.
 *
 * @group paragraphs
 */
class ParagraphsInlineEntityFormTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'inline_entity_form',
  ];

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsIEFPreview() {
    // Create article content type with a paragraphs field.
    $this->addParagraphedContentType('article', 'field_paragraphs', 'entity_reference_paragraphs');
    $this->loginAsAdmin(['create article content', 'edit any article content']);

    // Create the paragraphs type simple.
    $this->addParagraphsType('simple');
    $this->addParagraphsType('text');

    // Create a reference to an article.
    $this->fieldUIAddNewField('admin/structure/paragraphs_type/simple', 'article', 'Article', 'field_ui:entity_reference:node', [
      'settings[target_type]' => 'node',
      'cardinality' => 'number',
      'cardinality_number' => 1,
    ], [
      'required' => TRUE,
      'settings[handler_settings][target_bundles][article]' => TRUE
    ]);

    // Enable IEF simple widget.
    $this->drupalGet('admin/structure/paragraphs_type/simple/form-display');
    $edit = [
      'fields[field_article][type]' => 'inline_entity_form_simple',
    ];
    $this->submitForm($edit, 'Save');

    // Set the paragraphs widget mode to preview.
    $this->setParagraphsWidgetMode('article', 'field_paragraphs', 'preview');

    // Create node with one paragraph.
    $this->drupalGet('node/add/article');
    $this->submitForm([], 'field_paragraphs_simple_add_more');

    // Set the values and save.
    $edit = [
      'title[0][value]' => 'Dummy1',
      'field_paragraphs[0][subform][field_article][0][inline_entity_form][title][0][value]' => 'Dummy2',
    ];
    $this->submitForm($edit, 'Save');

    // Go back into edit page.
    $node = $this->getNodeByTitle('Dummy1');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Try to open the previewed paragraph.
    $this->submitForm([], 'field_paragraphs_0_edit');
  }

  /**
   * Tests the reordering of previewed paragraphs.
   */
  public function testParagraphsIEFChangeOrder() {

    // Create article content type with a paragraphs field.
    $this->addParagraphedContentType('article', 'field_paragraphs', 'entity_reference_paragraphs');
    $this->loginAsAdmin(['create article content', 'edit any article content']);

    // Create the paragraphs type simple.
    $this->addParagraphsType('simple');
    $this->addParagraphsType('text');

    // Create a reference to an article.
    $this->fieldUIAddNewField('admin/structure/paragraphs_type/simple', 'article', 'Article', 'field_ui:entity_reference:node', [
      'settings[target_type]' => 'node',
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ], [
      'required' => TRUE,
      'settings[handler_settings][target_bundles][article]' => TRUE
    ]);

    // Set cardinality explicit to -1.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_paragraphs');
    $edit = [
      'field_storage[subform][cardinality]' => '-1',
    ];
    $this->submitForm($edit, 'Save settings');

    // Enable IEF simple widget.
    $this->drupalGet('admin/structure/paragraphs_type/simple/form-display');
    $edit = [
      'fields[field_article][type]' => 'inline_entity_form_simple',
    ];
    $this->submitForm($edit, 'Save');

    // Set the paragraphs widget mode to preview.
    $this->setParagraphsWidgetMode('article', 'field_paragraphs', 'preview');

    // Create node with one paragraph.
    $this->drupalGet('node/add/article');
    $this->submitForm([], 'field_paragraphs_simple_add_more');

    // Set the values and save.
    $edit = [
      'title[0][value]' => 'Article 1',
      'field_paragraphs[0][subform][field_article][0][inline_entity_form][title][0][value]' => 'Basic page 1',
    ];

    $this->submitForm($edit, 'Save');

    // Go back into edit page.
    $node = $this->getNodeByTitle('Article 1');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Create second paragraph.
    $this->submitForm([], 'field_paragraphs_simple_add_more');

    // Set the values of second paragraph and change the order.
    $edit = [
      'field_paragraphs[1][subform][field_article][0][inline_entity_form][title][0][value]' => 'Basic 2',
      'field_paragraphs[0][_weight]' => -1,
      'field_paragraphs[1][_weight]' => -2,
    ];
    $this->submitForm($edit, 'Save');
  }

}
