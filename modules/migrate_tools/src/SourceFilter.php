<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * Class to filter source by an ID list.
 */
class SourceFilter extends \FilterIterator implements MigrateSourceInterface {

  /**
   * Whether to filter the source IDs.
   */
  protected bool $filterSourceIds;

  /**
   * List of specific source IDs to import.
   *
   * The accept() method removes an item from this when it successfully filters
   * a value.
   */
  protected array $idList;

  /**
   * SourceFilter constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The ID map.
   * @param array $id_list
   *   The id list to use in the filter.
   */
  public function __construct(MigrateSourceInterface $source, array $id_list) {
    parent::__construct($source);
    $this->idList = $id_list;
    $this->filterSourceIds = !empty($this->idList);
  }

  /**
   * {@inheritdoc}
   */
  public function accept(): bool {
    // No idlist filtering, don't filter.
    if (!$this->filterSourceIds) {
      return TRUE;
    }
    // Some source plugins do not extend SourcePluginBase. These cannot be
    // filtered so warn and return all values.
    if (!$this->getInnerIterator() instanceof SourcePluginBase) {
      trigger_error(sprintf('The source plugin %s is not an instance of %s. Extend from %s to support idlist filtering.', $this->getInnerIterator()->getPluginId(), SourcePluginBase::class, SourcePluginBase::class));
      return TRUE;
    }

    $id_list_key = \array_search(array_values($this->getInnerIterator()->getCurrentIds()), $this->idList);
    if ($id_list_key !== FALSE) {
      // Row is included.
      unset($this->idList[$id_list_key]);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets the remaining ID list.
   *
   *   An array of the IDs which were not used by the filter.
   */
  public function getRemainingIdList(): array {
    return $this->idList;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->getInnerIterator()->fields();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    return $this->getInnerIterator()->prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return $this->getInnerIterator()->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->getInnerIterator()->getIds();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceModule() {
    return $this->getInnerIterator()->getSourceModule();
  }

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return $this->getInnerIterator()->count();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->getInnerIterator()->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->getInnerIterator()->getPluginDefinition();
  }

}
