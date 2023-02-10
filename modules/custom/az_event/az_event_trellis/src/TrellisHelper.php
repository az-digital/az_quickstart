<?php

namespace Drupal\az_event_trellis;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Contains constants and helpers for Trellis Events.
 */
final class TrellisHelper {

  /**
   * API base path.
   *
   * @var string
   */
  public static $apiBasePath = '/ws/rest/eventsapi/v1/';

  /**
   * Trellis Event view URL prefix.
   *
   * @var string
   */
  public static $eventViewBasePath = '/lightning/r/conference360__Event__c/';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new TrellisHelper object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns API URL for given Trellis Event ID.
   *
   * @param string $trellis_id
   *   Trellis Event ID.
   *
   * @return mixed
   *   String event URL or boolean FALSE if not found.
   */
  protected function getEventUrl($trellis_id) {
    if (isset($trellis_id)) {
      $hostname = $this->configFactory->get('az_event_trellis.settings')->get('api_hostname');
      return 'https://' . $hostname . $this->apiBasePath . $trellis_id;
    }

    return FALSE;
  }

}
