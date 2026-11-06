<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Plugin\media\Source;

use Drupal\az_form_embed\Form\FormEmbedAddForm;
use Drupal\az_form_embed\FormEmbedVendorManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form hosted by another service, such as Slate, stored as its link.
 *
 * A media source is the plugin a media type is built on. It says which field
 * holds the media item's main value, here the form's link, and how to check
 * that value.
 *
 * This is Quickstart's own media source, not media_remote's. Rationale:
 * media_remote finds one formatter per media type and calls static methods on
 * it, so its save-time check can't tell which vendor a link was meant for.
 * This source checks links with the turned-on vendors instead, so an editor
 * hears which vendor refused a link and why.
 *
 * Careful: a media type's source can't be changed from the UI once the type
 * exists. Changing it later means an update on every site.
 *
 * @see \Drupal\az_form_embed\Plugin\Validation\Constraint\FormEmbedUrlConstraintValidator
 */
#[MediaSource(
  id: 'az_form_embed',
  label: new TranslatableMarkup('Form embed'),
  description: new TranslatableMarkup('A form hosted by another service, such as Slate, embedded from its link.'),
  allowed_field_types: ['string'],
  forms: ['media_library_add' => FormEmbedAddForm::class],
  default_thumbnail_filename: 'az-form-embed.png',
)]
class FormEmbed extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * Constructs a FormEmbed media source.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\az_form_embed\FormEmbedVendorManager $vendorManager
   *   Finds the turned-on vendors.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    ConfigFactoryInterface $config_factory,
    protected FormEmbedVendorManager $vendorManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('plugin.manager.az_form_embed_vendor'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'vendor' => $this->t('Form provider'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The Name field starts as "Slate form", for example, for the editor to
   * replace. It leaves out the link, because a pasted form link is long and
   * makes a poor name.
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case 'vendor':
      case 'default_name':
        $url = $this->getSourceFieldValue($media);
        $vendor = is_string($url) ? $this->vendorManager->getClaimingVendor($url) : NULL;
        if ($attribute_name === 'vendor') {
          return $vendor?->label();
        }
        return $vendor
          ? (string) $this->t('@vendor form', ['@vendor' => $vendor->label()])
          : (string) $this->t('Form');
    }
    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    // Return constraint options keyed by constraint id, not Constraint
    // objects. Rationale: that's what Media::validate() uses, and core's own
    // oEmbed source returns the same. PHPStan follows the interface's docs,
    // which say objects, so it skips the next line.
    /* @phpstan-ignore-next-line */
    return [
      'AzFormEmbedUrl' => [],
    ];
  }

}
