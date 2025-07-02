<?php

namespace Drupal\Tests\devel_generate\Traits;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;

/**
 * Provides methods to assist Devel Generate testing.
 *
 * Referenced in DevelGenerateBrowserTestBase and DevelGenerateCommandsTest.
 */
trait DevelGenerateSetupTrait {

  use CommentTestTrait;
  use EntityReferenceFieldCreationTrait;

  /**
   * Vocabulary for testing generation of terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Second vocabulary for testing generation of terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary2;

  /**
   * General set-up for all tests.
   */
  public function setUpData(): void {
    // Create user with devel_generate permissions and access to admin/content.
    $admin_user = $this->drupalCreateUser([
      'administer devel_generate',
      'access devel information',
      'access content overview',
    ]);
    $this->drupalLogin($admin_user);

    $entity_type_manager = $this->container->get('entity_type.manager');
    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic Page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
      $this->addDefaultCommentField('node', 'article');
    }

    // Enable translation for article content type (but not for page).
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    // Create languages for generated translations.
    ConfigurableLanguage::createFromLangcode('ca')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Creating a vocabulary to associate taxonomy terms generated.
    $this->vocabulary = Vocabulary::create([
      'name' => 'Vocab 1 ' . $this->randomString(15),
      'description' => $this->randomMachineName(),
      'vid' => 'vocab_1_' . mb_strtolower($this->randomMachineName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->vocabulary->save();
    // Enable translation for terms in this vocabulary.
    \Drupal::service('content_translation.manager')->setEnabled('taxonomy_term', $this->vocabulary->id(), TRUE);

    // Creates a field of an entity reference field storage on article.
    $field_name = 'taxonomy_' . $this->vocabulary->id();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $field_name, '', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $entity_type_manager->getStorage('entity_form_display')
      ->load('node.article.default')
      ->setComponent($field_name, [
        'type' => 'options_select',
      ])
      ->save();

    $entity_type_manager->getStorage('entity_view_display')
      ->load('node.article.default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_label',
      ])
      ->save();

    // Create the second vocabulary.
    $this->vocabulary2 = Vocabulary::create([
      'name' => 'Vocab 2 ' . $this->randomString(15),
      'vid' => 'vocab_2_' . mb_strtolower($this->randomMachineName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->vocabulary2->save();

  }

}
