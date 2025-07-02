<?php

namespace Drupal\entity_reference_revisions\Commands;

use Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Utils\StringUtils;

/**
 * A Drush commandfile.
 */
class EntityReferenceRevisionsCommands extends DrushCommands {

  /**
   * The purger service.
   *
   * @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger
   */
  protected $purger;

  /**
   * Constructs a ERRCommands object.
   *
   * @param \Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger $purger
   */
  public function __construct(EntityReferenceRevisionsOrphanPurger $purger) {
    $this->purger = $purger;
  }

  /**
   * Orphan composite revision deletion.
   *
   * @param $types
   *   A comma delimited list of entity types to check for orphans. Omit to
   *   choose from a list.
   * @usage drush err:purge paragraph
   *   Purge orphaned paragraphs.
   *
   * @command err:purge
   * @aliases errp
   */
  public function purge($types) {
    $this->purger->setBatch(StringUtils::csvToArray($types));
    drush_backend_batch_process();
  }

  /**
   * @hook interact err:purge
   */
  public function interact($input, $output) {
    if (empty($input->getArgument('types'))) {
      $choices = [];
      foreach ($this->purger->getCompositeEntityTypes() as $entity_type) {
        $choices[(string) $entity_type->id()] = (string) $entity_type->getLabel();
      }
      $selected = $this->io()->choice(dt("Choose the entity type to clear"), $choices);
      $input->setArgument('types', $selected);
    }
  }

}
