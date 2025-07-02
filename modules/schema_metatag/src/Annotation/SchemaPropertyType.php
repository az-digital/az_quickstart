<?php

namespace Drupal\schema_metatag\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Schema property type item annotation object.
 *
 * @see \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class SchemaPropertyType extends Plugin {

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

  /**
   * Property type.
   *
   * The type of Schema.org property being defined.
   *
   * @var string
   */
  public $property_type;

  /**
   * Sub-properties.
   *
   * A key/value array of sub-properties used in this property.
   *
   * Many property types consist of a collection of sub-properties, for instance
   * the Organization type includes name, url, and description. It also has
   * complex subproperties that themselves contain sub-properties, like image.
   *
   * @var array
   */
  public $sub_properties;

  /**
   * Tree parent.
   *
   * An array of the top level Schema.org class(es) used by this property type,
   * those that should be displayed as options for the @type property of the
   * property. All matching objects are pulled from Schema.org data. This is
   * only used by property types that create a sub-array using '@type', leave
   * empty for simple property types like Text or Number.
   *
   * @var array
   */
  public $tree_parent;

  /**
   * Tree depth.
   *
   * Goes with the $tree_parent, the depth used for the above parent(s) to
   * create the desired array of @type values. This will limit the depth
   * of elements pulled from the data. To pull all values, set this to -1.
   *
   * @var int
   */
  public $tree_depth;

}
