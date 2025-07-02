<?php

namespace Drupal\embed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an embed type annotation object.
 *
 * @ingroup embed_api
 *
 * @Annotation
 */
class EmbedType extends Plugin {

  /**
   * The embed type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the embed type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * The name of the embed form class associated with this embed type.
   *
   * @var string
   */
  protected $embed_form_class;

}
