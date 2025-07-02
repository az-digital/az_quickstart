<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests term matcher.
 *
 * @todo: Use TaxonomyTestTrait when the methods allow us to define own values.
 *
 * @group linkit
 */
class TermMatcherTest extends LinkitKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user 1 who has special permissions.
    $this->createUser();

    \Drupal::currentUser()->setAccount($this->createUser([], ['access content']));

    $this->installEntitySchema('taxonomy_term');

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    $testing_vocabulary_1 = $this->createVocabulary('testing_vocabulary_1');
    $testing_vocabulary_2 = $this->createVocabulary('testing_vocabulary_2');

    $this->createTerm($testing_vocabulary_1, ['name' => 'foo_bar']);
    $this->createTerm($testing_vocabulary_1, ['name' => 'foo_baz']);
    $this->createTerm($testing_vocabulary_1, ['name' => 'foo_foo']);
    $this->createTerm($testing_vocabulary_1, ['name' => 'bar']);
    $this->createTerm($testing_vocabulary_2, ['name' => 'foo_bar']);
    $this->createTerm($testing_vocabulary_2, ['name' => 'foo_baz']);
  }

  /**
   * Tests term matcher with default configuration.
   */
  public function testTermMatcherWidthDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:taxonomy_term', []);
    $suggestions = $plugin->execute('foo');
    $this->assertEquals(5, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

  /**
   * Tests term matcher with bundle filer.
   */
  public function testTermMatcherWidthBundleFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:taxonomy_term', [
      'settings' => [
        'bundles' => [
          'testing_vocabulary_1' => 'testing_vocabulary_1',
        ],
      ],
    ]);

    $suggestions = $plugin->execute('foo');
    $this->assertEquals(3, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

  /**
   * Tests term matcher with tokens in the matcher metadata.
   */
  public function testTermMatcherWidthMetadataTokens() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:taxonomy_term', [
      'settings' => [
        'metadata' => '[term:tid] [term:field_with_no_value]',
      ],
    ]);

    $suggestionCollection = $plugin->execute('Lorem');
    /** @var \Drupal\linkit\Suggestion\EntitySuggestion[] $suggestions */
    $suggestions = $suggestionCollection->getSuggestions();

    foreach ($suggestions as $suggestion) {
      $this->assertStringNotContainsString('[term:nid]', $suggestion->getDescription(), 'Raw token "[term:nid]" is not present in the description');
      $this->assertStringNotContainsString('[term:field_with_no_value]', $suggestion->getDescription(), 'Raw token "[term:field_with_no_value]" is not present in the description');
    }
  }

  /**
   * Creates and saves a vocabulary.
   *
   * @param string $name
   *   The vocabulary name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\VocabularyInterface
   *   The new vocabulary object.
   */
  private function createVocabulary($name) {
    $vocabularyStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
    $vocabulary = $vocabularyStorage->create([
      'name' => $name,
      'description' => $name,
      'vid' => mb_strtolower($name),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Creates and saves a new term with in vocabulary $vid.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary object.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The new taxonomy term object.
   */
  private function createTerm(VocabularyInterface $vocabulary, array $values = []) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);

    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $term = $termStorage->create($values + [
      'name' => $this->randomMachineName(),
      'description' => [
        'value' => $this->randomMachineName(),
        // Use the first available text format.
        'format' => $format->id(),
      ],
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    return $term;
  }

}
