<?php

namespace Drupal\az_exposed_filters\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an AZ exposed filters widget item annotation object.
 *
 * @see \Drupal\az_exposed_filters\Plugin\AzExposedFiltersFilterWidgetManager
 * @see plugin_api
 *
 * @Annotation
 */
class AzExposedFiltersFilterWidget extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
