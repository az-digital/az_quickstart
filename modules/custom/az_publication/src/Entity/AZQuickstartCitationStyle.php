<?php

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\StyleSheet;

/**
 * Defines the Quickstart Citation Style entity.
 *
 * @ConfigEntityType(
 *   id = "az_citation_style",
 *   label = @Translation("Quickstart Citation Style"),
 *   label_collection = @Translation("Quickstart Citation Styles"),
 *   label_singular = @Translation("Quickstart Citation Style"),
 *   label_plural = @Translation("Quickstart Citation Styles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Quickstart Citation Style",
 *     plural = "@count Quickstart Citation Styles"
 *   ),
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
 *     "style",
 *     "custom"
 *   },
 *   config_prefix = "az_citation_style",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/az-quickstart/settings/az-publication/az_citation_style/{az_citation_style}",
 *     "add-form" = "/admin/config/az-quickstart/settings/az-publication/az_citation_style/add",
 *     "edit-form" = "/admin/config/az-quickstart/settings/az-publication/az_citation_style/{az_citation_style}/edit",
 *     "delete-form" = "/admin/config/az-quickstart/settings/az-publication/az_citation_style/{az_citation_style}/delete",
 *     "collection" = "/admin/config/az-quickstart/settings/az-publication/az_citation_style"
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

  /**
   * {@inheritdoc}
   */
  public function getStyleSheet() {
    $sheet = $this->getStyle();
    $custom = $this->getCustom();

    // If not a custom stylesheet, load via CSL package.
    if (empty($custom)) {
      try {
        $sheet = StyleSheet::loadStyleSheet($sheet);
      }
      catch (CiteProcException $e) {
        $sheet = '';
      }
    }

    return $sheet;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustom() {
    return (bool) $this
      ->get('custom');
  }

  /**
   * {@inheritdoc}
   */
  public function setCustom($custom) {
    $this
      ->set('custom', (bool) $custom);
    return $this;
  }

}
