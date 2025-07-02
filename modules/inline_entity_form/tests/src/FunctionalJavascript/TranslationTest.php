<?php

namespace Drupal\Tests\inline_entity_form\FunctionalJavascript;

use Drupal\node\Entity\Node;

/**
 * Tests translating inline entities.
 *
 * @group inline_entity_form
 */
class TranslationTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_translation',
    'inline_entity_form_translation_test',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_reference_type content',
      'edit any ief_reference_type content',
      'delete any ief_reference_type content',
      'create ief_test_complex content',
      'edit any ief_test_complex content',
      'delete any ief_test_complex content',
      'view own unpublished content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    $this->drupalLogin($this->user);

    // Allow referencing existing entities.
    $form_display_storage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $form_display_storage->load('node.ief_test_complex.default');
    $component = $display->getComponent('multi');
    $component['settings']['allow_existing'] = TRUE;
    $display->setComponent('multi', $component)->save();
  }

  /**
   * Tests translating inline entities.
   */
  public function testTranslation() {
    // Get the xpath selectors for the fields in this test.
    $first_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $first_name_field_xpath = $this->getXpathForNthInputByLabelText('First name', 1);
    $last_name_field_xpath = $this->getXpathForNthInputByLabelText('Last name', 1);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create a German node with a French translation.
    $first_inline_node = Node::create([
      'type' => 'ief_reference_type',
      'langcode' => 'de',
      'title' => 'Kann ein KÃ¤nguru hÃ¶her als ein Haus springen?',
      'first_name' => 'Dieter',
    ]);
    $translation = $first_inline_node->toArray();
    $translation['title'][0]['value'] = "Un kangourou peut-il sauter plus haut qu'une maison?";
    $translation['first_name'][0]['value'] = 'Pierre';
    $first_inline_node->addTranslation('fr', $translation);
    $first_inline_node->save();

    $this->drupalGet('node/add/ief_test_complex');
    $multi_fieldset = $assert_session->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    $multi_fieldset->pressButton('Add existing node');

    // Reference the German node.
    $this->assertNotEmpty($field = $assert_session->waitForElement('xpath', $this->getXpathForAutoCompleteInput()));
    $field->setValue('Kann ein KÃ¤nguru hÃ¶her als ein Haus springen? (' . $first_inline_node->id() . ')');
    $page->pressButton('Add node');
    $this->waitForRowByTitle('Kann ein KÃ¤nguru hÃ¶her als ein Haus springen?');

    // Add a new English inline node.
    $multi_fieldset->pressButton('Add new node');
    $this->assertNotEmpty($create_button = $assert_session->waitForButton('Create node'));
    $assert_session->elementExists('xpath', $first_nested_title_field_xpath)->setValue('Can a kangaroo jump higher than a house?');
    $assert_session->elementExists('xpath', $first_name_field_xpath)->setValue('John');
    $assert_session->elementExists('xpath', $last_name_field_xpath)->setValue('Smith');
    $create_button->press();
    $this->waitForRowByTitle('Can a kangaroo jump higher than a house?');
    $this->assertRowByTitle('Kann ein KÃ¤nguru hÃ¶her als ein Haus springen?');
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 2);
    $page->fillField('title[0][value]', 'A node');
    $page->selectFieldOption('langcode[0][value]', 'en');
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex A node has been created.');

    // Both inline nodes should now be in English.
    $first_inline_node = $this->drupalGetNodeByTitle('Kann ein KÃ¤nguru hÃ¶her als ein Haus springen?');
    $second_inline_node = $this->drupalGetNodeByTitle('Can a kangaroo jump higher than a house?');
    $this->assertSame('en', $first_inline_node->get('langcode')->value, 'The first inline entity has the correct langcode.');
    $this->assertEquals('en', $second_inline_node->get('langcode')->value, 'The second inline entity has the correct langcode.');

    // Edit the parent node and change the source language to German.
    $node = $this->drupalGetNodeByTitle('A node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->selectFieldOption('langcode[0][value]', 'de');
    $page->pressButton('Save');

    // Both inline nodes should now be in German.
    $first_inline_node = $this->drupalGetNodeByTitle('Kann ein KÃ¤nguru hÃ¶her als ein Haus springen?', TRUE);
    $second_inline_node = $this->drupalGetNodeByTitle('Can a kangaroo jump higher than a house?', TRUE);
    $this->assertSame('de', $first_inline_node->get('langcode')->value, 'The first inline entity has the correct langcode.');
    $this->assertSame('de', $second_inline_node->get('langcode')->value, 'The second inline entity has the correct langcode.');

    // Add a German -> French translation of the parent node.
    $this->drupalGet('node/' . $node->id() . '/translations/add/de/fr');

    $assert_session->elementTextContains('xpath', '//fieldset[@id="edit-multi"]/legend/span', 'Multiple nodes');

    // Confirm that the add and remove buttons are not present.
    $multi_fieldset = $assert_session->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    $this->assertEquals(FALSE, $multi_fieldset->hasButton('Add new node'));
    $this->assertEquals(FALSE, $multi_fieldset->hasButton('Remove'));

    // Confirm the presence of the two node titles, in the expected languages.
    $first_reference = $this->assertRowByTitle("Un kangourou peut-il sauter plus haut qu'une maison?");
    $second_reference = $this->assertRowByTitle('Can a kangaroo jump higher than a house?');
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 2);

    // Edit the first referenced translation.
    $first_reference->getParent()->pressButton('Edit');
    $this->assertNotEmpty($update_button = $assert_session->waitForButton('Update node'));
    $assert_session->elementExists('xpath', $first_nested_title_field_xpath)->setValue("Un kangourou peut-il sauter plus haut qu'une maison? - mis Ã  jour");
    $assert_session->elementExists('xpath', $first_name_field_xpath)->setValue('Damien');
    $update_button->press();
    $this->waitForRowByTitle("Un kangourou peut-il sauter plus haut qu'une maison? - mis Ã  jour");

    // Edit the second referenced translation.
    $second_reference->getParent()->pressButton('Edit');
    $this->assertNotEmpty($update_button = $assert_session->waitForButton('Update node'));
    $assert_session->elementExists('xpath', $first_nested_title_field_xpath)->setValue('tous les animaux qui sautent');
    $assert_session->elementExists('xpath', $first_name_field_xpath)->setValue('Jacques');
    $update_button->press();
    $this->waitForRowByTitle('tous les animaux qui sautent');
    $page->pressButton('Save (this translation)');
    $assert_session->pageTextContains('IEF test complex A node has been updated.');

    // Load using the original titles, confirming they haven't changed.
    $first_inline_node = $this->drupalGetNodeByTitle('Kann ein KÃ¤nguru hÃ¶her als ein Haus springen?', TRUE);
    $second_inline_node = $this->drupalGetNodeByTitle('Can a kangaroo jump higher than a house?', TRUE);

    // Confirm that the expected translated values are present.
    $this->assertEquals(TRUE, $first_inline_node->hasTranslation('fr'), 'The first inline entity has a FR translation');
    $this->assertEquals(TRUE, $second_inline_node->hasTranslation('fr'), 'The second inline entity has a FR translation');
    $first_translation = $first_inline_node->getTranslation('fr');
    $this->assertSame("Un kangourou peut-il sauter plus haut qu'une maison? - mis Ã  jour", $first_translation->title->value);
    $this->assertSame('Damien', $first_translation->first_name->value);
    $second_translation = $second_inline_node->getTranslation('fr');
    $this->assertEquals('tous les animaux qui sautent', $second_translation->title->value);
    $this->assertSame('Jacques', $second_translation->first_name->value);
  }

}
