<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Functional;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Execution form test.
 *
 * @group migrate_tools
 */
final class MigrateExecutionFormTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'filter',
    'field',
    'node',
    'text',
    'taxonomy',
    'migrate',
    'migrate_plus',
    'migrate_tools',
    'migrate_tools_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  private VocabularyInterface $vocabulary;
  private QueryInterface $vocabularyQuery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->vocabulary = $this->createVocabulary([
      'vid' => 'fruit',
      'name' => 'Fruit',
    ]);
    $this->vocabularyQuery = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(TRUE);
    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests execution of import and rollback of a migration.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testExecution(): void {
    $group = 'default';
    $migration = 'fruit_terms';
    $urlPath = "/admin/structure/migrate/manage/{$group}/migrations/{$migration}/execute";
    $real_count = $this->vocabularyQuery->count()->execute();
    $expected_count = 0;
    $this->assertEquals($expected_count, $real_count);
    $this->drupalGet($urlPath);
    $this->assertSession()->responseContains('Choose an operation to run');
    $edit = [
      'operation' => 'import',
    ];
    $this->drupalGet($urlPath);
    $this->submitForm($edit, 'Execute');
    $real_count = $this->vocabularyQuery->count()->execute();
    $expected_count = 3;
    $this->assertEquals($expected_count, $real_count);
    $edit = [
      'operation' => 'rollback',
    ];
    $this->drupalGet($urlPath);
    $this->submitForm($edit, 'Execute');
    $real_count = $this->vocabularyQuery->count()->execute();
    $expected_count = 0;
    $this->assertEquals($expected_count, $real_count);
    $edit = [
      'operation' => 'import',
    ];
    $this->drupalGet($urlPath);
    $this->submitForm($edit, 'Execute');
    $real_count = $this->vocabularyQuery->count()->execute();
    $expected_count = 3;
    $this->assertEquals($expected_count, $real_count);
  }

  /**
   * Creates a custom vocabulary based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'vid' => 'foo'.
   *
   * @return \Drupal\taxonomy\VocabularyInterface
   *   Created vocabulary.
   */
  protected function createVocabulary(array $values = []): VocabularyInterface {
    // Find a non-existent random vocabulary name.
    if (!isset($values['vid'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (Vocabulary::load($id));
    }
    else {
      $id = $values['vid'];
    }
    $values += [
      'id' => $id,
      'name' => $id,
    ];
    $vocabulary = Vocabulary::create($values);
    $status = $vocabulary->save();

    $this->assertSame($status, SAVED_NEW);

    return $vocabulary;
  }

}
