<?php

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Quickstart Citation Style entity.
 *
 * @ConfigEntityType(
 *   id = "az_citation_style",
 *   label = @Translation("Quickstart Citation Style"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\az_publication\AZQuickstartCitationStyleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\az_publication\Form\AZQuickstartCitationStyleForm",
 *       "edit" = "Drupal\az_publication\Form\AZQuickstartCitationStyleForm",
 *       "delete" = "Drupal\az_publication\Form\AZQuickstartCitationStyleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\az_publication\AZQuickstartCitationStyleHtmlRouteProvider",
 *     },
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "style"
 *   },
 *   config_prefix = "az_citation_style",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/az_citation_style/{az_citation_style}",
 *     "add-form" = "/admin/structure/az_citation_style/add",
 *     "edit-form" = "/admin/structure/az_citation_style/{az_citation_style}/edit",
 *     "delete-form" = "/admin/structure/az_citation_style/{az_citation_style}/delete",
 *     "collection" = "/admin/structure/az_citation_style"
 *   }
 * )
 */
class AZQuickstartCitationStyle extends ConfigEntityBase implements AZQuickstartCitationStyleInterface {

  /**
   * The Quickstart Citation Style ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Quickstart Citation Style label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Quickstart Citation Style csl.
   *
   * @var string
   */
  protected $style;

  /**
   * {@inheritdoc}
   */
  public function getStyle() {
    return $this
      ->get('style');
  }

  /**
   * {@inheritdoc}
   */
  public function setStyle($style) {
    $this
      ->set('style', $style);
    return $this;
  }

}
