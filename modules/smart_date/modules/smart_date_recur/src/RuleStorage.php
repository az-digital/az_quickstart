<?php

namespace Drupal\smart_date_recur;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for Smart Date Recur's rules.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class, adding
 * required special handling for rule entities.
 */
class RuleStorage extends SqlContentEntityStorage implements RuleStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getRuleIdsToCheck() {
    $select = $this->database->select($this->getBaseTable(), 'alias');
    $select->isNull('limit');
    $select->addField('alias', 'rid');

    return $select->execute()->fetchCol();
  }

}
