<?php

namespace Drupal\metatag_custom_tags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\metatag_custom_tags\MetaTagCustomTagInterface;

/**
 * Defines the custom meta tag entity type.
 *
 * @ConfigEntityType(
 *   id = "metatag_custom_tag",
 *   label = @Translation("Custom tag"),
 *   label_collection = @Translation("Custom tags"),
 *   label_singular = @Translation("Custom tag"),
 *   label_plural = @Translation("Custom tags"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Custom tag",
 *     plural = "@count Custom tags",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\metatag_custom_tags\MetaTagCustomTagListBuilder",
 *     "form" = {
 *       "add" = "Drupal\metatag_custom_tags\Form\MetaTagCustomTagForm",
 *       "edit" = "Drupal\metatag_custom_tags\Form\MetaTagCustomTagForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "metatag_custom_tag",
 *   admin_permission = "administer custom meta tags",
 *   links = {
 *     "collection" = "/admin/config/search/metatag/custom-tags",
 *     "add-form" = "/admin/config/search/metatag/custom-tags/add",
 *     "edit-form" = "/admin/config/search/metatag/custom-tags/{metatag_custom_tag}/edit",
 *     "delete-form" = "/admin/config/search/metatag/custom-tags/{metatag_custom_tag}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "htmlElement",
 *     "htmlNameAttribute",
 *     "htmlValueAttribute",
 *   },
 * )
 */
class MetaTagCustomTag extends ConfigEntityBase implements MetaTagCustomTagInterface {

  /**
   * The ID.
   */
  protected string $id;

  /**
   * The label.
   */
  protected string $label;

  /**
   * The description.
   */
  protected string $description;

  /**
   * The string this tag uses for the element itself.
   *
   * @var string
   */
  protected $htmlElement;

  /**
   * The attribute this tag uses for the name.
   *
   * @var string
   */
  protected $htmlNameAttribute;

  /**
   * The attribute this tag uses for the contents.
   *
   * @var string
   */
  protected $htmlValueAttribute;

}
