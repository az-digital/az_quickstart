<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis\Entity;

use Drupal\az_event_trellis\AZEventTrellisImportInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the trellis event import entity type.
 *
 * @ConfigEntityType(
 *   id = "az_event_trellis_import",
 *   label = @Translation("Trellis Event Import"),
 *   label_collection = @Translation("Trellis Event Imports"),
 *   label_singular = @Translation("trellis event import"),
 *   label_plural = @Translation("trellis event imports"),
 *   label_count = @PluralTranslation(
 *     singular = "@count trellis event import",
 *     plural = "@count trellis event imports",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\az_event_trellis\AZEventTrellisImportListBuilder",
 *     "form" = {
 *       "add" = "Drupal\az_event_trellis\Form\AZEventTrellisImportForm",
 *       "edit" = "Drupal\az_event_trellis\Form\AZEventTrellisImportForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "az_event_trellis_import",
 *   admin_permission = "administer az_event_trellis_import",
 *   links = {
 *     "collection" = "/admin/structure/az-event-trellis-import",
 *     "add-form" = "/admin/structure/az-event-trellis-import/add",
 *     "edit-form" = "/admin/structure/az-event-trellis-import/{az_event_trellis_import}",
 *     "delete-form" = "/admin/structure/az-event-trellis-import/{az_event_trellis_import}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "keyword",
 *     "owner",
 *   },
 * )
 */
final class AZEventTrellisImport extends ConfigEntityBase implements AZEventTrellisImportInterface {

  /**
   * The az_event_trellis_import ID.
   */
  protected string $id;

  /**
   * The az_event_trellis_import label.
   */
  protected string $label;

  /**
   * The az_event_trellis_import keyword.
   */
  protected string $keyword;

  /**
   * The az_event_trellis_import owner.
   */
  protected string $owner;

  /**
   * {@inheritdoc}
   */
  public function getEventIds() {
    // Build a list of query parameters.
    $params = [
      'publish' => 'true',
    ];
    $params['keyword'] = $this->get('keyword') ?? '';
    $params['owner'] = $this->get('owner') ?? '';
    $params = array_filter($params);
    // Let's refuse to search if there are no constraints except published.
    if (count($params) === 1) {
      return [];
    }
    return \Drupal::service('az_event_trellis.trellis_helper')->searchEvents($params);
  }

}
