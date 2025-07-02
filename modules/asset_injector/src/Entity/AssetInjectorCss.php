<?php

namespace Drupal\asset_injector\Entity;

/**
 * Defines the CSS Injector entity.
 *
 * @ConfigEntityType(
 *   id = "asset_injector_css",
 *   label = @Translation("CSS Injector"),
 *   list_cache_tags = { "library_info" },
 *   handlers = {
 *     "access" = "Drupal\asset_injector\AssetInjectorAccessControlHandler",
 *     "list_builder" = "Drupal\asset_injector\AssetInjectorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\asset_injector\Form\AssetInjectorCssForm",
 *       "edit" = "Drupal\asset_injector\Form\AssetInjectorCssForm",
 *       "delete" = "Drupal\asset_injector\Form\AssetInjectorDeleteForm",
 *       "enable" = "Drupal\asset_injector\Form\AssetInjectorEnableForm",
 *       "disable" = "Drupal\asset_injector\Form\AssetInjectorDisableForm",
 *       "duplicate" = "Drupal\asset_injector\Form\AssetInjectorCssDuplicateForm",
 *     },
 *   },
 *   config_prefix = "css",
 *   admin_permission = "administer css assets injector",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/asset-injector/css/{asset_injector_css}",
 *     "edit-form" = "/admin/config/development/asset-injector/css/{asset_injector_css}",
 *     "delete-form" = "/admin/config/development/asset-injector/css/{asset_injector_css}/delete",
 *     "enable" = "/admin/config/development/asset-injector/css/{asset_injector_css}/enable",
 *     "disable" = "/admin/config/development/asset-injector/css/{asset_injector_css}/disable",
 *     "collection" = "/admin/structure/conditions_group"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "code",
 *     "conditions_require_all",
 *     "conditions",
 *     "contexts",
 *     "media",
 *     "preprocess"
 *   }
 * )
 */
class AssetInjectorCss extends AssetInjectorBase {

  /**
   * Media selector.
   *
   * @var string
   */
  public $media = '';

  /**
   * Preprocess CSS before adding.
   *
   * @var bool
   */
  public $preprocess = TRUE;

  /**
   * Gets the file extension of the asset.
   *
   * @return string
   *   Css extension.
   */
  public function extension() {
    return 'css';
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfo() {
    $path = $this->filePathRelativeToDrupalRoot();
    $library_info = [
      'css' => [
        'theme' => [
          $path => [
            'weight' => 0,
            'preprocess' => $this->preprocess,
            'media' => $this->media,
          ],
        ],
      ],
    ];
    return $library_info;
  }

}
