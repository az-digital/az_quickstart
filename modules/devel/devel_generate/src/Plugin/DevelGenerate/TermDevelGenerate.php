<?php

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a TermDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "term",
 *   label = @Translation("terms"),
 *   description = @Translation("Generate a given number of terms. Optionally delete current terms."),
 *   url = "term",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 10,
 *     "title_length" = 12,
 *     "minimum_depth" = 1,
 *     "maximum_depth" = 4,
 *     "kill" = FALSE,
 *   },
 *   dependencies = {
 *     "taxonomy",
 *   },
 * )
 */
class TermDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The vocabulary storage.
   */
  protected VocabularyStorageInterface $vocabularyStorage;

  /**
   * The term storage.
   */
  protected TermStorageInterface $termStorage;

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The content translation manager.
   */
  protected ?ContentTranslationManagerInterface $contentTranslationManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $entity_type_manager = $container->get('entity_type.manager');

    // @phpstan-ignore ternary.alwaysTrue (False positive)
    $content_translation_manager = $container->has('content_translation.manager') ? $container->get('content_translation.manager') : NULL;

    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->vocabularyStorage = $entity_type_manager->getStorage('taxonomy_vocabulary');
    $instance->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $instance->database = $container->get('database');
    $instance->contentTranslationManager = $content_translation_manager;

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $options = [];
    foreach ($this->vocabularyStorage->loadMultiple() as $vocabulary) {
      $options[$vocabulary->id()] = $vocabulary->label();
    }

    // Sort by vocabulary label.
    asort($options);
    // Set default to 'tags' only if it exists as a vocabulary.
    $default_vids = array_key_exists('tags', $options) ? 'tags' : '';
    $form['vids'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Vocabularies'),
      '#required' => TRUE,
      '#default_value' => $default_vids,
      '#options' => $options,
      '#description' => $this->t('Restrict terms to these vocabularies.'),
    ];
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of terms'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of characters in term names'),
      '#default_value' => $this->getSetting('title_length'),
      '#required' => TRUE,
      '#min' => 2,
      '#max' => 255,
    ];
    $form['minimum_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum depth for new terms in the vocabulary hierarchy'),
      '#description' => $this->t('Enter a value from 1 to 20.'),
      '#default_value' => $this->getSetting('minimum_depth'),
      '#min' => 1,
      '#max' => 20,
    ];
    $form['maximum_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum depth for new terms in the vocabulary hierarchy'),
      '#description' => $this->t('Enter a value from 1 to 20.'),
      '#default_value' => $this->getSetting('maximum_depth'),
      '#min' => 1,
      '#max' => 20,
    ];
    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing terms in specified vocabularies before generating new terms.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    // Add the language and translation options.
    $form += $this->getLanguageForm('terms');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values): void {
    $new_terms = $this->generateTerms($values);
    if (!empty($new_terms['terms'])) {
      $this->setMessage($this->formatPlural($new_terms['terms'], 'Created 1 new term', 'Created @count new terms'));

      // Helper function to format the number of terms and the list of terms.
      $format_terms_func = function (array $data, $level) {
        if ($data['total'] > 10) {
          $data['terms'][] = '...';
        }

        return $this->formatPlural($data['total'],
          '1 new term at level @level (@terms)',
          '@count new terms at level @level (@terms)',
          ['@level' => $level, '@terms' => implode(',', $data['terms'])]);
      };

      foreach ($new_terms['vocabs'] as $vid => $vlabel) {
        if (array_key_exists($vid, $new_terms)) {
          ksort($new_terms[$vid]);
          $termlist = implode(', ', array_map($format_terms_func, $new_terms[$vid], array_keys($new_terms[$vid])));
          $this->setMessage($this->t('In vocabulary @vlabel: @termlist', ['@vlabel' => $vlabel, '@termlist' => $termlist]));
        }
        else {
          $this->setMessage($this->t('In vocabulary @vlabel: No terms created', ['@vlabel' => $vlabel]));
        }
      }

    }

    if ($new_terms['terms_translations'] > 0) {
      $this->setMessage($this->formatPlural($new_terms['terms_translations'], 'Created 1 term translation', 'Created @count term translations'));
    }
  }

  /**
   * Deletes all terms of given vocabularies.
   *
   * @param array $vids
   *   Array of vocabulary ids.
   *
   * @return int
   *   The number of terms deleted.
   */
  protected function deleteVocabularyTerms(array $vids): int {
    $tids = $this->vocabularyStorage->getToplevelTids($vids);
    $terms = $this->termStorage->loadMultiple($tids);
    $total_deleted = 0;
    foreach ($vids as $vid) {
      $total_deleted += count($this->termStorage->loadTree($vid));
    }

    $this->termStorage->delete($terms);
    return $total_deleted;
  }

  /**
   * Generates taxonomy terms for a list of given vocabularies.
   *
   * @param array $parameters
   *   The input parameters from the settings form or drush command.
   *
   * @return array
   *   Information about the created terms.
   */
  protected function generateTerms(array $parameters): array {
    $info = [
      'terms' => 0,
      'terms_translations' => 0,
    ];
    $min_depth = $parameters['minimum_depth'];
    $max_depth = $parameters['maximum_depth'];

    // $parameters['vids'] from the UI has keys of the vocab ids. From drush
    // the array is keyed 0,1,2. Therefore create $vocabs which has keys of the
    // vocab ids, so it can be used with array_rand().
    $vocabs = array_combine($parameters['vids'], $parameters['vids']);

    // Delete terms from the vocabularies we are creating new terms in.
    if ($parameters['kill']) {
      $deleted = $this->deleteVocabularyTerms($vocabs);
      $this->setMessage($this->formatPlural($deleted, 'Deleted 1 existing term', 'Deleted @count existing terms'));
      if ($min_depth != 1) {
        $this->setMessage($this->t('Minimum depth changed from @min_depth to 1 because all terms were deleted', ['@min_depth' => $min_depth]));
        $min_depth = 1;
      }
    }

    // Build an array of potential parents for the new terms. These will be
    // terms in the vocabularies we are creating in, which have a depth of one
    // less than the minimum for new terms up to one less than the maximum.
    $all_parents = [];
    foreach ($parameters['vids'] as $vid) {
      $info['vocabs'][$vid] = $this->vocabularyStorage->load($vid)->label();
      // Initialise the nested array for this vocabulary.
      $all_parents[$vid] = ['top_level' => [], 'lower_levels' => []];
      $ids = [];
      for ($depth = 1; $depth < $max_depth; ++$depth) {
        $query = $this->termStorage->getQuery()->accessCheck(FALSE)->condition('vid', $vid);
        if ($depth == 1) {
          // For the top level the parent id must be zero.
          $query->condition('parent', 0);
        }
        else {
          // For lower levels use the $ids array obtained in the previous loop.
          $query->condition('parent', $ids, 'IN');
        }

        $ids = $query->execute();

        if (empty($ids)) {
          // Reached the end, no more parents to be found.
          break;
        }

        // Store these terms as parents if they are within the depth range for
        // new terms.
        if ($depth == $min_depth - 1) {
          $all_parents[$vid]['top_level'] = array_fill_keys($ids, $depth);
        }
        elseif ($depth >= $min_depth) {
          $all_parents[$vid]['lower_levels'] += array_fill_keys($ids, $depth);
        }
      }

      // No top-level parents will have been found above when the minimum depth
      // is 1 so add a record for that data here.
      if ($min_depth == 1) {
        $all_parents[$vid]['top_level'] = [0 => 0];
      }
      elseif (empty($all_parents[$vid]['top_level'])) {
        // No parents for required minimum level so cannot use this vocabulary.
        unset($vocabs[$vid]);
      }
    }

    if ($vocabs === []) {
      // There are no available parents at the required depth in any vocabulary,
      // so we cannot create any new terms.
      throw new \Exception(sprintf('Invalid minimum depth %s because there are no terms in any vocabulary at depth %s', $min_depth, $min_depth - 1));
    }

    // Insert new data:
    for ($i = 1; $i <= $parameters['num']; ++$i) {
      // Select a vocabulary at random.
      $vid = array_rand($vocabs);

      // Set the group to use to select a random parent from. Using < 50 means
      // on average half of the new terms will be top_level. Also if no terms
      // exist yet in 'lower_levels' then we have to use 'top_level'.
      $group = (mt_rand(0, 100) < 50 || empty($all_parents[$vid]['lower_levels'])) ? 'top_level' : 'lower_levels';
      $parent = array_rand($all_parents[$vid][$group]);
      $depth = $all_parents[$vid][$group][$parent] + 1;
      $name = $this->getRandom()->word(mt_rand(2, $parameters['title_length']));

      $values = [
        'name' => $name,
        'description' => 'Description of ' . $name . ' (depth ' . $depth . ')',
        'format' => filter_fallback_format(),
        'weight' => mt_rand(0, 10),
        'vid' => $vid,
        'parent' => [$parent],
        // Give hook implementations access to the parameters used for generation.
        'devel_generate' => $parameters,
      ];
      if (isset($parameters['add_language'])) {
        $values['langcode'] = $this->getLangcode($parameters['add_language']);
      }

      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->termStorage->create($values);

      // Populate all fields with sample values.
      $this->populateFields($term);
      $term->save();

      // Add translations.
      if (isset($parameters['translate_language']) && !empty($parameters['translate_language'])) {
        $info['terms_translations'] += $this->generateTermTranslation($parameters['translate_language'], $term);
      }

      // If the depth of the new term is less than the maximum depth then it can
      // also be saved as a potential parent for the subsequent new terms.
      if ($depth < $max_depth) {
        $all_parents[$vid]['lower_levels'] += [$term->id() => $depth];
      }

      // Store data about the newly generated term.
      ++$info['terms'];
      @$info[$vid][$depth]['total']++;
      // List only the first 10 new terms at each vocab/level.
      if (!isset($info[$vid][$depth]['terms']) || count($info[$vid][$depth]['terms']) < 10) {
        $info[$vid][$depth]['terms'][] = $term->label();
      }

      unset($term);
    }

    return $info;
  }

  /**
   * Create translation for the given term.
   *
   * @param array $translate_language
   *   Potential translate languages array.
   * @param \Drupal\taxonomy\TermInterface $term
   *   Term to add translations to.
   *
   * @return int
   *   Number of translations added.
   */
  protected function generateTermTranslation(array $translate_language, TermInterface $term): int {
    if (is_null($this->contentTranslationManager)) {
      return 0;
    }

    if (!$this->contentTranslationManager->isEnabled('taxonomy_term', $term->bundle())) {
      return 0;
    }

    if ($term->get('langcode')->getLangcode() === LanguageInterface::LANGCODE_NOT_SPECIFIED
      || $term->get('langcode')->getLangcode() === LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      return 0;
    }

    $num_translations = 0;
    // Translate term to each target language.
    $skip_languages = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      $term->get('langcode')->getLangcode(),
    ];
    foreach ($translate_language as $langcode) {
      if (in_array($langcode, $skip_languages)) {
        continue;
      }

      $translation_term = $term->addTranslation($langcode);
      $translation_term->setName($term->getName() . ' (' . $langcode . ')');
      $this->populateFields($translation_term);
      $translation_term->save();
      ++$num_translations;
    }

    return $num_translations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    // Get default settings from the annotated command definition.
    $defaultSettings = $this->getDefaultSettings();

    $bundles = self::csvToarray($options['bundles']);
    if (count($bundles) < 1) {
      throw new \Exception(dt('Please provide a vocabulary machine name (--bundles).'));
    }

    foreach ($bundles as $bundle) {
      // Verify that each bundle is a valid vocabulary id.
      if (!$this->vocabularyStorage->load($bundle)) {
        throw new \Exception(dt('Invalid vocabulary machine name: @name', ['@name' => $bundle]));
      }
    }

    $number = array_shift($args) ?: $defaultSettings['num'];
    if (!$this->isNumber($number)) {
      throw new \Exception(dt('Invalid number of terms: @num', ['@num' => $number]));
    }

    $minimum_depth = $options['min-depth'] ?? $defaultSettings['minimum_depth'];
    $maximum_depth = $options['max-depth'] ?? $defaultSettings['maximum_depth'];
    if ($minimum_depth < 1 || $minimum_depth > 20 || $maximum_depth < 1 || $maximum_depth > 20 || $minimum_depth > $maximum_depth) {
      throw new \Exception(dt('The depth values must be in the range 1 to 20 and min-depth cannot be larger than max-depth (values given: min-depth @min, max-depth @max)', ['@min' => $minimum_depth, '@max' => $maximum_depth]));
    }

    $values = [
      'num' => $number,
      'kill' => $options['kill'],
      'title_length' => 12,
      'vids' => $bundles,
      'minimum_depth' => $minimum_depth,
      'maximum_depth' => $maximum_depth,
    ];
    $add_language = self::csvToArray($options['languages']);
    // Intersect with the enabled languages to make sure the language args
    // passed are actually enabled.
    $valid_languages = array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL));
    $values['add_language'] = array_intersect($add_language, $valid_languages);

    $translate_language = self::csvToArray($options['translations']);
    $values['translate_language'] = array_intersect($translate_language, $valid_languages);
    return $values;
  }

}
