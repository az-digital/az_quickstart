<?php

namespace Drupal\ctools\Plugin;

use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for Relationship plugins.
 */
abstract class RelationshipBase extends PluginBase implements RelationshipInterface {
  use ContextAwarePluginTrait;
}
