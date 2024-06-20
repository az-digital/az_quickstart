<?php

declare(strict_types=1);

namespace Drupal\az_finder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides helper methods for working with vocabularies in AZ Finder.
 */
class AZFinderVocabulary {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AZFinderVocabulary object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Get the vocabulary IDs for a filter.
   */
  public function getVocabularyIdsForFilter($view_id, $display_id, $filter_id) {
    $vocabulary_ids = [];
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    if ($view) {
      $display = $view->getDisplay($display_id);
      $filters = $display['display_options']['filters'] ?? [];
      foreach ($filters as $filter) {
        if (($filter['exposed'] ?? FALSE) !== TRUE) {
          continue;
        }
        if (isset($filter['plugin_id']) && $filter['plugin_id'] === 'taxonomy_index_tid') {
          $vocabulary_ids[] = $filter['vid'];
        }
      }
    }
    return $vocabulary_ids;
  }

  /**
   * Add a section to the form for configuring vocabulary terms.
   *
   * @param array $form_section
   *   The form section to add the terms table to.
   * @param int $vocabulary_id
   *   The vocabulary ID.
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   */
  public function addTermsTable(&$form_section, $vocabulary_id, $view_id, $display_id) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary_id);
    $config_id = "az_finder.tid_widget.$view_id.$display_id";
    $vocabulary_config_path = "$config_id:vocabularies.$vocabulary_id";

    $form_section['terms_table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Term'), $this->t('Override')],
      '#empty' => $this->t('No terms found.'),
    ];

    foreach ($terms as $term) {
      $form_section['terms_table'][$term->tid]['term_name'] = [
        '#markup' => str_repeat('-', $term->depth) . $term->name,
      ];
      $form_section['terms_table'][$term->tid]['override'] = [
        '#type' => 'select',
        '#options' => [
          '' => $this->t('Default'),
          'expand' => $this->t('Expand'),
          'collapse' => $this->t('Collapse'),
          'remove' => $this->t('Remove'),
        ],
        '#config_target' => "$vocabulary_config_path.terms.{$term->tid}.default_state",
      ];
    }
  }

}
