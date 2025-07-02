<?php

namespace Drupal\schema_metatag_test;

use Drupal\schema_metatag\SchemaMetatagClient as SchemaMetatagClientOriginal;

/**
 * Class SchemaMetatagClient.
 *
 * A class to parse Schema.org data.
 *
 * @package Drupal\schema_metatag
 */
class SchemaMetatagClient extends SchemaMetatagClientOriginal {

  /**
   * {@inheritdoc}
   */
  public function getLocalFile() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getObjects($clear = FALSE) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($clear = FALSE) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectTree($clear = FALSE, $clear_objects = FALSE) {
    // Provide a tree that contains all objects used in tests, organized into
    // their actual hierarchy.
    return [
      'Thing' => [
        'Action' => [
          'ConsumeAction' => [
            'ReadAction' => [],
            'ViewAction' => [],
            'WatchAction' => [],
          ],
          'TradeAction' => [
            'BuyAction' => [],
            'OrderAction' => [],
          ],
          'OrganizeAction' => [
            'PlanAction' => [
              'ReserveAction' => [],
            ],
          ],
          'SearchAction' => [],
        ],
        'CreativeWork' => [
          'Article' => [],
          'Book' => [],
          'Clip' => [],
          'Comment' => [
            'Answer' => [],
          ],
          'Course' => [],
          'CreativeWorkSeason' => [],
          'MediaObject' => [
            'ImageObject' => [],
            'VideoObject' => [],
          ],
          'Movie' => [],
          'HowTo' => [
            'Recipe' => [],
          ],
          'HowToStep' => [],
          'Question' => [],
          'Review' => [],
          'SpecialAnnouncement' => [],
          'WebPage' => [
            'QAPage' => [],
            'FAQPage' => [],
          ],
          'WebPageElement' => [],
          'WebSite' => [],
        ],
        'DataType' => [
          'Boolean' => [],
          'Date' => [],
          'DateTime' => [],
          'Number' => [],
          'Text' => [
            'URL' => [],
          ],
          'Time' => [],
        ],
        'Event' => [
          'PublicationEvent' => [],
        ],
        'Intangible' => [
          'Brand' => [],
          'ContactPoint' => [
            'PostalAddress' => [],
          ],
          'EntryPoint' => [],
          'ItemList' => [
            'BreadcrumbList' => [],
          ],
          'JobPosting' => [],
          'Offer' => [],
          'ProgramMembership' => [],
          'Quantity' => [
            'Duration' => [],
            'Mass' => [],
          ],
          'Rating' => [
            'AggregateRating' => [],
          ],
          'Series' => [
            'CreativeWorkSeries' => [],
          ],
          'Service' => [
            'GovernmentService' => [],
          ],
          'SpeakableSpecification' => [],
          'StructuredValue' => [
            'ContactPoint' => [],
            'GeoCoordinates' => [],
            'MonetaryAmount' => [],
            'NutritionInformation' => [],
            'OpeningHoursSpecification' => [],
            'QuantitativeValue' => [],
          ],
        ],
        'Organization' => [
          'GovernmentOrganization' => [],
          'LocalBusiness' => [
            'FoodEstablishment' => [
              'Restaurant' => [],
            ],
            'GovernmentOffice' => [],
          ],
        ],
        'Person' => [],
        'Place' => [
          'AdministrativeArea' => [
            'Country' => [],
          ],
        ],
        'Product' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTree($parent_name = NULL, $depth = -1, $clear = FALSE, $clear_tree = FALSE, $clear_objects = FALSE) {
    // Override the original method to skip caching and use our test tree.
    $base_tree = $this->getObjectTree();
    return $this->getUncachedTree($base_tree, $parent_name, $depth);
  }

}
