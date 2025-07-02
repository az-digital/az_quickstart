<?php

namespace Drupal\asset_injector\Entity;

/**
 * Defines the JS Injector entity.
 *
 * @ConfigEntityType(
 *   id = "asset_injector_js",
 *   label = @Translation("JS Injector"),
 *   list_cache_tags = { "library_info" },
 *   handlers = {
 *     "access" = "Drupal\asset_injector\AssetInjectorAccessControlHandler",
 *     "list_builder" = "Drupal\asset_injector\AssetInjectorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\asset_injector\Form\AssetInjectorJsForm",
 *       "edit" = "Drupal\asset_injector\Form\AssetInjectorJsForm",
 *       "delete" = "Drupal\asset_injector\Form\AssetInjectorDeleteForm",
 *       "enable" = "Drupal\asset_injector\Form\AssetInjectorEnableForm",
 *       "disable" = "Drupal\asset_injector\Form\AssetInjectorDisableForm",
 *       "duplicate" = "Drupal\asset_injector\Form\AssetInjectorJsDuplicateForm",
 *     }
 *   },
 *   config_prefix = "js",
 *   admin_permission = "administer js assets injector",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/asset-injector/js/{asset_injector_js}",
 *     "edit-form" = "/admin/config/development/asset-injector/js/{asset_injector_js}",
 *     "delete-form" = "/admin/config/development/asset-injector/js/{asset_injector_js}/delete",
 *     "enable" = "/admin/config/development/asset-injector/js/{asset_injector_js}/enable",
 *     "disable" = "/admin/config/development/asset-injector/js/{asset_injector_js}/disable",
 *     "collection" = "/admin/structure/conditions_group"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "code",
 *     "conditions_require_all",
 *     "conditions",
 *     "contexts",
 *     "header",
 *     "preprocess",
 *     "jquery",
 *     "noscript",
 *     "noscriptRegion"
 *   }
 * )
 */
class AssetInjectorJs extends AssetInjectorBase {

  /**
   * Load JS in the header of the page.
   *
   * @var bool
   */
  public $header;

  /**
   * Preprocess JS before adding.
   *
   * @var bool
   */
  public $preprocess = TRUE;

  /**
   * Require jquery.
   *
   * @var string
   */
  public $jquery = FALSE;

  /**
   * Code for <noscript> tag.
   *
   * @var string
   */
  public $noscript;

  /**
   * Region to insert <noscript> code into.
   *
   * @var array
   */
  public $noscriptRegion = [];

  /**
   * Gets the file extension of the asset.
   *
   * @return string
   *   JS extension.
   */
  public function extension() {
    return 'js';
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfo() {
    $path = $this->filePathRelativeToDrupalRoot();
    $library_info = [
      'header' => $this->header,
      'js' => [
        $path => [
          'preprocess' => $this->preprocess,
        ],
      ],
    ];

    if ($this->jquery) {
      $library_info['dependencies'] = ['core/jquery'];
    }
    return $library_info;
  }

}
