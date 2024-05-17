<?php

declare(strict_types=1);

namespace Drupal\az_finder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Vocabulary;

class AZFinderVocabulary {
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function getVocabularyIdsForFilter($view_id, $display_id, $filter_id) {
    $vocabulary_ids = [];
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);

    if ($view) {
      $display = $view->getDisplay($display_id);
      $filters = $display['display_options']['filters'] ?? [];

      if (isset($filters[$filter_id])) {
        $filter = $filters[$filter_id];

        if (isset($filter['vid'])) {
          $vocabulary_id = $filter['vid'];
          $vocabulary = Vocabulary::load($vocabulary_id);
          if ($vocabulary) {
            $vocabulary_ids[] = $vocabulary->id();
          }
        }
      }
    }

    return $vocabulary_ids;
  }

  public function addTermsTable(&$form_section, $vocabulary_id, $view_id, $display_id, $config) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary_id);
    $config_id = "az_finder.tid_widget.$view_id.$display_id";
    $vocabulary_config_path = "$config_id:vocabularies.$vocabulary_id";

    $form_section['terms_table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Term'), $this->t('Override')],
      '#empty' => $this->t('No terms found.'),
    ];

    foreach ($terms as $term) {
      $term_tid = $term->tid;
      $form_section['terms_table'][$term_tid]['term_name'] = [
        '#markup' => str_repeat('-', $term->depth) . $term->name,
      ];
      $form_section['terms_table'][$term_tid]['override'] = [
        '#type' => 'select',
        '#options' => [
          '' => $this->t('Default'),
          'hide' => $this->t('Hide'),
          'disable' => $this->t('Disable'),
          'remove' => $this->t('Remove'),
          'expand' => $this->t('Expand'),
          'collapse' => $this->t('Collapse'),
        ],
        '#default_value' => $config->get("vocabularies.$vocabulary_id.terms.$term_tid.default_state"),
        '#config_target' => "$vocabulary_config_path.terms.$term_tid.default_state",
      ];
    }
  }
}
