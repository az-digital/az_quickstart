<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Helper test class with some added functions for testing.
 */
trait PathautoTestHelperTrait {

  use PathAliasTestTrait;

  /**
   * Creates a pathauto pattern.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $pattern
   *   The path pattern.
   * @param int $weight
   *   (optional) The pattern weight.
   *
   * @return \Drupal\pathauto\PathautoPatternInterface
   *   The created pattern.
   */
  protected function createPattern($entity_type_id, $pattern, $weight = 10) {
    $type = ($entity_type_id == 'forum') ? 'forum' : 'canonical_entities:' . $entity_type_id;

    $pattern = PathautoPattern::create([
      'id' => mb_strtolower($this->randomMachineName()),
      'type' => $type,
      'pattern' => $pattern,
      'weight' => $weight,
    ]);
    $pattern->save();
    return $pattern;
  }

  /**
   * Add a bundle condition to a pathauto pattern.
   *
   * @param \Drupal\pathauto\PathautoPatternInterface $pattern
   *   The pattern.
   * @param string $entity_type
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   */
  protected function addBundleCondition(PathautoPatternInterface $pattern, $entity_type, $bundle) {
    $pattern->addSelectionCondition(
      [
        'id' => 'entity_bundle:' . $entity_type,
        'bundles' => [
          $bundle => $bundle,
        ],
        'negate' => FALSE,
        'context_mapping' => [
          $entity_type => $entity_type,
        ],
      ]
    );
  }

  /**
   * Assert the expected value for a token.
   */
  public function assertToken($type, $object, $token, $expected) {
    $bubbleable_metadata = new BubbleableMetadata();
    $tokens = \Drupal::token()->generate($type, [$token => $token], [$type => $object], [], $bubbleable_metadata);
    $tokens += [$token => ''];
    $this->assertSame($tokens[$token], $expected, t("Token value for [@type:@token] was '@actual', expected value '@expected'.", [
      '@type' => $type,
      '@token' => $token,
      '@actual' => $tokens[$token],
      '@expected' => $expected,
    ]));
  }

  /**
   * Create a path alias for an entity.
   */
  public function saveEntityAlias(EntityInterface $entity, $alias, $langcode = NULL) {
    // By default, use the entity language.
    if (!$langcode) {
      $langcode = $entity->language()->getId();
    }
    return $this->createPathAlias('/' . $entity->toUrl()->getInternalPath(), $alias, $langcode);
  }

  /**
   * Assert the expected value for an entity path alias.
   */
  public function assertEntityAlias(EntityInterface $entity, $expected_alias, $langcode = NULL) {
    // By default, use the entity language.
    if (!$langcode) {
      $langcode = $entity->language()->getId();
    }
    $this->assertAlias('/' . $entity->toUrl()->getInternalPath(), $expected_alias, $langcode);
  }

  /**
   * Assert that an alias exists for the given entity's internal path.
   */
  public function assertEntityAliasExists(EntityInterface $entity) {
    return $this->assertAliasExists(['path' => '/' . $entity->toUrl()->getInternalPath()]);
  }

  /**
   * Assert that the given entity does not have a path alias.
   */
  public function assertNoEntityAlias(EntityInterface $entity, $langcode = NULL) {
    // By default, use the entity language.
    if (!$langcode) {
      $langcode = $entity->language()->getId();
    }
    $this->assertEntityAlias($entity, '/' . $entity->toUrl()->getInternalPath(), $langcode);
  }

  /**
   * Assert that no alias exists matching the given entity path/alias.
   */
  public function assertNoEntityAliasExists(EntityInterface $entity, $alias = NULL) {
    $path = ['path' => '/' . $entity->toUrl()->getInternalPath()];
    if (!empty($alias)) {
      $path['alias'] = $alias;
    }
    $this->assertNoAliasExists($path);
  }

  /**
   * Assert the expected alias for the given source/language.
   */
  public function assertAlias($source, $expected_alias, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    \Drupal::service('path_alias.manager')->cacheClear($source);
    $entity_type_manager = \Drupal::entityTypeManager();
    if ($entity_type_manager->hasDefinition('path_alias')) {
      $entity_type_manager->getStorage('path_alias')->resetCache();
    }
    $this->assertEquals($expected_alias, \Drupal::service('path_alias.manager')->getAliasByPath($source, $langcode), t("Alias for %source with language '@language' is correct.",
      ['%source' => $source, '@language' => $langcode]));
  }

  /**
   * Assert that an alias exists for the given conditions.
   */
  public function assertAliasExists($conditions) {
    $path = $this->loadPathAliasByConditions($conditions);
    $this->assertNotEmpty($path, t('Alias with conditions @conditions found.', ['@conditions' => var_export($conditions, TRUE)]));
    return $path;
  }

  /**
   * Assert that no alias exists for the given conditions.
   */
  public function assertNoAliasExists($conditions) {
    $alias = $this->loadPathAliasByConditions($conditions);
    $this->assertEmpty($alias, t('Alias with conditions @conditions not found.', ['@conditions' => var_export($conditions, TRUE)]));
  }

  /**
   * Assert that exactly one alias matches the given conditions.
   */
  public function assertAliasIsUnique($conditions) {
    $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $query = $storage->getQuery()->accessCheck(FALSE);
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $entities = $storage->loadMultiple($query->execute());

    return $this->assertCount(1, $entities);
  }

  /**
   * Delete all path aliases.
   */
  public function deleteAllAliases() {
    \Drupal::service('pathauto.alias_storage_helper')->deleteAll();
    \Drupal::service('path_alias.manager')->cacheClear();
  }

  /**
   * Create a new vocabulary.
   *
   * @param array $values
   *   Vocabulary properties.
   *
   * @return \Drupal\taxonomy\VocabularyInterface
   *   The Vocabulary object.
   */
  public function addVocabulary(array $values = []) {
    $name = mb_strtolower($this->randomMachineName(5));
    $values += [
      'name' => $name,
      'vid' => $name,
    ];
    $vocabulary = Vocabulary::create($values);
    $vocabulary->save();

    return $vocabulary;
  }

  /**
   * Add a new taxonomy term to the given vocabulary.
   */
  public function addTerm(VocabularyInterface $vocabulary, array $values = []) {
    $values += [
      'name' => mb_strtolower($this->randomMachineName(5)),
      'vid' => $vocabulary->id(),
    ];

    $term = Term::create($values);
    $term->save();
    return $term;
  }

  /**
   * Helper for testTaxonomyPattern().
   */
  public function assertEntityPattern($entity_type, $bundle, $langcode, $expected) {

    $values = [
      'langcode' => $langcode,
      \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle') => $bundle,
    ];
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->create($values);

    $pattern = \Drupal::service('pathauto.generator')->getPatternByEntity($entity);
    $this->assertSame($expected, $pattern->getPattern());
  }

  /**
   * Load a taxonomy term by name.
   */
  public function drupalGetTermByName($name, $reset = FALSE) {
    if ($reset) {
      // @todo implement cache reset.
    }
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name]);
    return !empty($terms) ? reset($terms) : FALSE;
  }

}
