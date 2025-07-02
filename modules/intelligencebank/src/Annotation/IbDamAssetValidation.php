<?php

namespace Drupal\ib_dam\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser widget validation annotation object.
 *
 * @Annotation
 */
class IbDamAssetValidation extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the widget validator.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The data type plugin ID, for which a constraint should be added.
   *
   * This is optional attribute.
   *
   * @var string
   */
  public $data_type;

  /**
   * A list of asset types on which plugin can be applicable.
   *
   * This is optional attribute.
   *
   * @var string[]
   */
  public $asset_types;

  /**
   * The constraint ID.
   *
   * This is optional attribute.
   *
   * @var string
   */
  public $constraint;

}
