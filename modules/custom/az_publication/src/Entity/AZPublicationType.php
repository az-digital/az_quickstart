<?php

declare(strict_types=1);

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\az_publication\AZPublicationTypeHtmlRouteProvider;
use Drupal\az_publication\AZPublicationTypeListBuilder;
use Drupal\az_publication\Form\AZPublicationTypeDeleteForm;
use Drupal\az_publication\Form\AZPublicationTypeForm;

/**
 * Defines the publication type entity.
 */
#[ConfigEntityType(
  id: 'az_publication_type',
  label: new TranslatableMarkup('Publication type'),
  label_collection: new TranslatableMarkup('Publication types'),
  label_singular: new TranslatableMarkup('publication type'),
  label_plural: new TranslatableMarkup('publication types'),
  handlers: [
    'view_builder' => EntityViewBuilder::class,
    'list_builder' => AZPublicationTypeListBuilder::class,
    'form': [
      'add' => AZPublicationTypeForm::class,
      'edit' => AZPublicationTypeForm::class,
      'delete' => AZPublicationTypeDeleteForm::class,
    ],
    'route_provider': [
      'html' => AZPublicationTypeHtmlRouteProvider::class,
    ],
  ],
  config_export: [
    'id',
    'label',
    'type',
    'uuid',
    'status'
  ],
  config_prefix: 'type',
  admin_permission: 'administer publication type entities',
  label_count: [
    'singular' => '@count publication type',
    'plural' => '@count publication types',
  ],
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'type' => 'type',
    'uuid' => 'uuid',
    'status' => 'status',
  ],
  links: [
    'canonical' => '/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}',
    'add-form' => '/admin/config/az-quickstart/settings/az-publication/type/add',
    'edit-form' => '/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/edit',
    'delete-form' => '/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/delete',
    'enable' => '/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/enable',
    'disable' => '/admin/config/az-quickstart/settings/az-publication/type/{az_publication_type}/disable',
    'collection' => '/admin/config/az-quickstart/settings/az-publication/types'
  ],
)]
class AZPublicationType extends ConfigEntityBase implements AZPublicationTypeInterface {

  /**
   * The publication type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The publication type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The publication type mapping.
   *
   * @var string
   */
  protected $type;

  /**
   * Gets the publication type mapping options.
   *
   * @return array
   *   An associative array of publication type mapping options.
   */
  public static function getMappableTypeOptions():array {
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

  /**
   * {@inheritdoc}
   */
  public static function load($id): ?AZPublicationTypeInterface {
    return \Drupal::entityTypeManager()->getStorage('az_publication_type')->load($id);
  }

}
