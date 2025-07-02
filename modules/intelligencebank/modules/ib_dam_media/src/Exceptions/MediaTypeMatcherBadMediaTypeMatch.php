<?php

namespace Drupal\ib_dam_media\Exceptions;

use Drupal\ib_dam\Exceptions\IbDamException;
use Drupal\ib_dam\IbDamResourceModel;

/**
 * Class MediaTypeMatcherBadMediaTypeMatch.
 *
 * @package Drupal\ib_dam_media\Exceptions
 */
class MediaTypeMatcherBadMediaTypeMatch extends IbDamException {

  /**
   * MediaTypeMatcherBadMediaTypeMatch constructor.
   *
   * @param $source_type
   * @param \Drupal\ib_dam\IbDamResourceModel $source
   */
  public function __construct($source_type, IbDamResourceModel $source) {
    $log_message = "Can't find a media type match for the source type %source_type";
    $log_message_args = [
      '%source_type' => $source_type,
      '@source' => print_r($source, TRUE)
    ];

    $message = $this->t(
      'Unable to find right media type. Please see the logs for more information.'
    );
    $admin_message = $message;

    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
