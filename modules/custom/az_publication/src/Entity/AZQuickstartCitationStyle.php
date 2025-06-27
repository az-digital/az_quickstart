<?php

declare(strict_types=1);

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\az_publication\AZQuickstartCitationStyleHtmlRouteProvider;
use Drupal\az_publication\AZQuickstartCitationStyleListBuilder;
use Drupal\az_publication\Form\AZQuickstartCitationStyleForm;
use Drupal\az_publication\Form\AZQuickstartCitationStyleDeleteForm;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\StyleSheet;

/**
 * Defines the Quickstart Citation Style entity.
 */
#[ConfigEntityType(
  id: 'az_citation_style',
  label: new TranslatableMarkup('Citation Style'),
  label_collection: new TranslatableMarkup('Citation Styles'),
  label_singular: new TranslatableMarkup('Citation Style'),
  label_plural: new TranslatableMarkup('Citation Styles'),
  handlers: [
    'view_builder' => EntityViewBuilder::class,
    'list_builder' => AZQuickstartCitationStyleListBuilder::class,
    'form': [
      'add' => AZQuickstartCitationStyleForm::class,
      'edit' => AZQuickstartCitationStyleForm::class,
      'delete' => AZQuickstartCitationStyleDeleteForm::class,
    ],
    'route_provider': [
      'html' => AZQuickstartCitationStyleHtmlRouteProvider::class,
    ],
  ],
  config_export: [
    'id',
    'label',
    'style',
    'custom'
  ],
  config_prefix: 'az_citation_style',
  admin_permission: 'administer site configuration',
  label_count: [
    'singular' => '@count Citation Style',
    'plural' => '@count Citation Styles',
  ],
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid'
  ],
  links: [
    'canonical' => '/admin/config/az-quickstart/settings/az-publication/style/{az_citation_style}',
    'add-form' => '/admin/config/az-quickstart/settings/az-publication/style/add',
    'edit-form' => '/admin/config/az-quickstart/settings/az-publication/style/{az_citation_style}/edit',
    'delete-form' => '/admin/config/az-quickstart/settings/az-publication/style/{az_citation_style}/delete',
    'collection' => '/admin/config/az-quickstart/settings/az-publication/styles'
  ],
)]
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
