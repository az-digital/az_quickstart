<?php

declare(strict_types=1);

namespace Drupal\az_form_embed;

use Drupal\az_form_embed\Plugin\media\Source\FormEmbed;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\media\MediaInterface;

/**
 * Stops a module being turned off while Form embed content still needs it.
 *
 * - A vendor submodule, such as az_form_embed_slate, can't be turned off while
 *   a Form embed item holds a link its vendor claims. Rationale: those forms
 *   would vanish from pages with no warning, and editors couldn't even re-save
 *   the items, because no vendor would accept their links.
 * - az_form_embed itself can't be turned off while any Form embed item exists.
 *   Rationale: turning it off deletes the media type and the field that holds
 *   every link.
 *
 * Drupal runs this check on the Extend page, for drush pm:uninstall, and for
 * a config import that would turn the module off. It follows core's check
 * for text filters.
 *
 * @see \Drupal\filter\FilterUninstallValidator
 */
class FormEmbedUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * How many item names to list in the message, at most.
   */
  private const MAX_NAMES = 5;

  /**
   * Constructs a FormEmbedUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\az_form_embed\FormEmbedVendorManager $vendorManager
   *   Finds the turned-on vendors.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FormEmbedVendorManager $vendorManager,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    if ($module === 'az_form_embed') {
      $count = count($this->loadFormEmbedIds());
      return $count === 0 ? [] : [
        (string) $this->formatPlural($count, 'There is 1 Form embed media item. Delete it first.', 'There are @count Form embed media items. Delete them first.'),
      ];
    }

    // Find the vendors this module provides. If it provides none, turning it
    // off can't strand a Form embed item, so let it go.
    $vendor_ids = [];
    foreach ($this->vendorManager->getDefinitions() as $id => $definition) {
      if (($definition['provider'] ?? NULL) === $module) {
        $vendor_ids[] = $id;
      }
    }
    if ($vendor_ids === []) {
      return [];
    }

    $names = [];
    $storage = $this->entityTypeManager->getStorage('media');
    foreach ($storage->loadMultiple($this->loadFormEmbedIds()) as $media) {
      assert($media instanceof MediaInterface);
      $url = $media->getSource()->getSourceFieldValue($media);
      $vendor = is_string($url) ? $this->vendorManager->getClaimingVendor($url) : NULL;
      if ($vendor !== NULL && in_array($vendor->getPluginId(), $vendor_ids, TRUE)) {
        $names[] = $media->label();
      }
    }
    if ($names === []) {
      return [];
    }
    return [
      (string) $this->formatPlural(count($names), 'Its form provider is used by 1 Form embed media item: %names. Delete it first.', 'Its form provider is used by @count Form embed media items, including %names. Delete them first.', [
        '%names' => implode(', ', array_slice($names, 0, self::MAX_NAMES)),
      ]),
    ];
  }

  /**
   * Returns the ids of every media item whose type uses the Form embed source.
   *
   * @return array<int|string, int|string>
   *   Media ids, as the entity query returns them.
   */
  protected function loadFormEmbedIds(): array {
    $bundles = [];
    foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $media_type) {
      if ($media_type->getSource() instanceof FormEmbed) {
        $bundles[] = $media_type->id();
      }
    }
    if ($bundles === []) {
      return [];
    }
    return $this->entityTypeManager->getStorage('media')->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', $bundles, 'IN')
      ->execute();
  }

}
