<?php

namespace Drupal\workbench_access\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file for Workbench Access.
 */
class WorkbenchAccessCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new WorkbenchAccessCommands instance.
   *
   * Note that this works for Drush < 13.
   * See https://www.drush.org/11.x/dependency-injection/#create-method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Installs the workbench_access test vocabulary.
   *
   * @command workbench_access:installTest
   * @aliases wa-test
   */
  public function installTest() {
    try {
      // Create a vocabulary.
      $vocabulary = Vocabulary::create([
        'name' => 'Workbench Access',
        'description' => 'Test taxonomy for Workbench Access',
        'vid' => 'workbench_access',
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'weight' => 100,
      ]);
      $vocabulary->save();
      // Create some terms.
      $terms = [
        'Alumni',
        'Faculty',
        'Staff',
        'Students',
      ];
      $children = [
        'Directory',
        'Information',
      ];

      foreach ($terms as $name) {
        $term = Term::create([
          'name' => $name,
          'description' => [],
          'vid' => $vocabulary->id(),
          'parent' => 0,
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ]);
        $term->save();
        foreach ($children as $child) {
          $child = Term::create([
            'name' => "$name $child",
            'description' => [],
            'vid' => $vocabulary->id(),
            'parent' => $term->id(),
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ]);
          $child->save();
        }
      }
    }
    catch (\Exception $e) {
      $this->logger()->warning(dt('The test vocabulary has already been created.'));
    }
    $this->logger()->success(dt('Workbench Access test vocabulary created.'));
  }

  /**
   * Flushes assigned user permissions.
   *
   * @command workbench_access:flush
   * @aliases wa-flush
   */
  public function flush() {
    $section_storage = $this->entityTypeManager->getStorage('section_association');
    foreach ($this->entityTypeManager->getStorage('access_scheme')->loadMultiple() as $scheme) {
      $sections = $section_storage->loadByProperties([
        'access_scheme' => $scheme->id(),
      ]);
      $section_storage->delete($sections);
    }
    $this->logger()->success(dt('User and role assignments cleared.'));
  }

}
