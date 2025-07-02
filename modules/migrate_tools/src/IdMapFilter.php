<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Class to filter ID map by an ID list.
 */
class IdMapFilter extends \FilterIterator implements MigrateIdMapInterface {

  /**
   * List of specific source IDs to import.
   */
  protected array $idList;

  /**
   * IdMapFilter constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map
   *   The ID map.
   * @param array $id_list
   *   The id list to use in the filter.
   */
  public function __construct(MigrateIdMapInterface $id_map, array $id_list) {
    parent::__construct($id_map);
    $this->idList = $id_list;
  }

  /**
   * {@inheritdoc}
   */
  public function accept(): bool {
    // Row is included.
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    if (empty($this->idList) || in_array(array_values($this->currentSource()), $this->idList)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function saveIdMapping(Row $row, array $destination_id_values, $status = self::STATUS_IMPORTED, $rollback_action = self::ROLLBACK_DELETE): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->saveIdMapping($row, $destination_id_values, $status, $rollback_action);
  }

  /**
   * {@inheritdoc}
   */
  public function saveMessage(array $source_id_values, $message, $level = MigrationInterface::MESSAGE_ERROR): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->saveMessage($source_id_values, $message, $level);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(array $source_id_values = [], $level = NULL): \Traversable {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->getMessages($source_id_values, $level);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareUpdate(): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->prepareUpdate();
  }

  /**
   * {@inheritdoc}
   */
  public function processedCount(): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->processedCount();
  }

  /**
   * {@inheritdoc}
   */
  public function importedCount(): int {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->importedCount();
  }

  /**
   * {@inheritdoc}
   */
  public function updateCount(): int {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->updateCount();
  }

  /**
   * {@inheritdoc}
   */
  public function errorCount(): int {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->errorCount();
  }

  /**
   * {@inheritdoc}
   */
  public function messageCount(): int {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->messageCount();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $source_id_values, $messages_only = FALSE): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->delete($source_id_values, $messages_only);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDestination(array $destination_id_values): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->deleteDestination($destination_id_values);
  }

  /**
   * {@inheritdoc}
   */
  public function clearMessages(): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->clearMessages();
  }

  /**
   * {@inheritdoc}
   */
  public function getRowBySource(array $source_id_values): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->getRowBySource($source_id_values);
  }

  /**
   * {@inheritdoc}
   */
  public function getRowByDestination(array $destination_id_values): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->getRowByDestination($destination_id_values);
  }

  /**
   * {@inheritdoc}
   */
  public function getRowsNeedingUpdate($count): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->getRowsNeedingUpdate($count);
  }

  /**
   * {@inheritdoc}
   */
  public function lookupSourceId(array $destination_id_values): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->lookupSourceId($destination_id_values);
  }

  /**
   * {@inheritdoc}
   */
  public function lookupDestinationIds(array $source_id_values): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->lookupDestinationIds($source_id_values);
  }

  /**
   * {@inheritdoc}
   */
  public function currentDestination(): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->currentDestination();
  }

  /**
   * {@inheritdoc}
   */
  public function currentSource(): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->currentSource() ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function destroy(): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->destroy();
  }

  /**
   * {@inheritdoc}
   */
  public function getQualifiedMapTableName(): string {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->getQualifiedMapTableName();
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage(MigrateMessageInterface $message): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->setMessage($message);
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdate(array $source_id_values): void {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    $map->setUpdate($source_id_values);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition(): array {
    $map = $this->getInnerIterator();
    \assert($map instanceof MigrateIdMapInterface);
    return $map->getPluginDefinition();
  }

}
