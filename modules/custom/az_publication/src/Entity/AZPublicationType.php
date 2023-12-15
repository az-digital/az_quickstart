<?php

declare(strict_types=1);

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the AZPublication Type entity.
 *
 * @ConfigEntityType(
 *   id = "az_publication_type",
 *   label = @Translation("Publication Type"),
 *   label_collection = @Translation("Publication Types"),
 *   label_singular = @Translation("Publication Type"),
 *   label_plural = @Translation("Publication Types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Publication Type",
 *     plural = "@count Publication Types"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\az_publication\AZPublicationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\az_publication\Form\AZPublicationTypeForm",
 *       "edit" = "Drupal\az_publication\Form\AZPublicationTypeForm",
 *       "delete" = "Drupal\az_publication\Form\AZPublicationTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\az_publication\AZPublicationTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type"
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}",
 *     "add-form" = "/admin/config/az-quickstart/settings/az-publication/type/add",
 *     "edit-form" = "/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/edit",
 *     "delete-form" = "/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/delete",
 *     "enable" = "/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/enable",
 *     "disable" = "/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/disable",
 *     "collection" = "/admin/config/az-quickstart/settings/az-publication/types"
 *   }
 * )
 */
class AZPublicationType extends ConfigEntityBase implements AZPublicationTypeInterface {

  /**
   * The Publication Type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Publication Type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Publication Type mapping.
   *
   * @var string
   */
  protected $type;

  /**
   * Gets the Publication Type mapping.
   *
   * @return string|null
   *   The type this is mapped to.
   */
  public function getType(): ?string {
    return $this->get('type');
  }

  /**
   * Sets the Publication Type mapping.
   *
   * @param string $type
   *   The type this is mapped to.
   *
   * @return $this
   */
  public function setType(string $type): self {
    $this->set('type', (string) $type);
    return $this;
  }

  /**
   * Gets the Publication Type mapping options.
   *
   * @return array
   *   An associative array of publication type mapping options.
   */
  public static function getTypeOptions():array {
    return [
      'article' => 'Article',
      'article-journal' => 'Journal Article',
      'article-magazine' => 'Magazine Article',
      'article-newspaper' => 'Newspaper Article',
      'bill' => 'Bill',
      'book' => 'Book',
      'broadcast' => 'Broadcast',
      'chapter' => 'Chapter',
      'classic' => 'Classic',
      'collection' => 'Collection',
      'dataset' => 'Dataset',
      'document' => 'Document',
      'entry' => 'Entry',
      'entry-dictionary' => 'Dictionary Entry',
      'entry-encyclopedia' => 'Encyclopedia Entry',
      'event' => 'Event',
      'figure' => 'Figure',
      'graphic' => 'Graphic',
      'hearing' => 'Hearing',
      'interview' => 'Interview',
      'legal_case' => 'Legal Case',
      'legislation' => 'Legislation',
      'manuscript' => 'Manuscript',
      'map' => 'Map',
      'motion_picture' => 'Motion Picture',
      'musical_score' => 'Musical Score',
      'pamphlet' => 'Pamphlet',
      'paper-conference' => 'Conference Paper',
      'patent' => 'Patent',
      'performance' => 'Performance',
      'periodical' => 'Periodical',
      'personal_communication' => 'Personal Communication',
      'post' => 'Post',
      'post-weblog' => 'Weblog Post',
      'regulation' => 'Regulation',
      'report' => 'Report',
      'review' => 'Review',
      'review-book' => 'Book Review',
      'software' => 'Software',
      'song' => 'Song',
      'speech' => 'Speech',
      'standard' => 'Standard',
      'thesis' => 'Thesis',
      'treaty' => 'Treaty',
      'webpage' => 'Webpage',
    ];
  }

}
