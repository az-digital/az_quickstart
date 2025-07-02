<?php

namespace Drupal\field_group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldGroupFormatter annotation object.
 *
 * Formatters handle the display of fieldgroups.
 *
 * Additional annotation keys for formatters can be defined in
 * hook_field_group_formatter_info_alter().
 *
 * @Annotation
 *
 * @see \Drupal\field_group\FieldGroupFormatterPluginManager
 * @see \Drupal\field_group\FieldGroupFormatterInterface
 *
 * @ingroup field_formatter
 */
class FieldGroupFormatter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the formatter type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The name of the fieldgroup formatter class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

  /**
   * An array of contexts the formatter supports (form / view).
   *
   * @var array
   */
  public $supported_contexts = [];

  /**
   * The different format types available for this formatter.
   *
   * @var array
   */
  public $format_types = [];

  /**
   * Formatter weight.
   *
   * An integer to determine the weight of this formatter relative to other
   * formatter in the Field UI when selecting a formatter for a given group.
   *
   * @var int
   */
  public $weight = NULL;

}
