<?php

namespace Drupal\workbench_access\Plugin\Deriver;

/**
 * Defines a selection handler deriver for filtered users.
 */
class UserFilteredSelectionDeriver extends TaxonomyHierarchySelectionDeriver {

  /**
   * {@inheritdoc}
   */
  protected $label = 'Filtered user selection: @name';

}
