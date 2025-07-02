<?php

namespace Drupal\config_distro\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Class DistroStorageImportEvent.
 *
 * This event allows subscribers to alter the configuration of the storage that
 * is being transformed.
 */
class DistroStorageImportEvent extends Event {}
