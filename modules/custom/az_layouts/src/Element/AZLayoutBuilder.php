<?php

namespace Drupal\az_layouts\Element;

use Drupal\layout_builder\Element\LayoutBuilder;
use Drupal\Core\Url;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Defines a render element for building the Layout Builder UI.
 *
 * @RenderElement("layout_builder")
 *
 * @internal
 *   Plugin classes are internal.
 */
class AZLayoutBuilder extends LayoutBuilder {

  // Value to use for LayoutBuilder forms that need expanded width.
  const BUILDER_WIDTH = 500;

  /**
   * Pre-render callback: Renders the Layout Builder UI.
   */
  protected function buildAdministrativeSection(SectionStorageInterface $section_storage, $delta) {
    $build = parent:: buildAdministrativeSection($section_storage, $delta);

    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();
    $section = $section_storage->getSection($delta);
    $layout = $section->getLayout();
    $layout_definition = $layout->getPluginDefinition();

    // Override the existing route by using az_layouts.choose_inline_block,
    // which skips directly to custom block selection.
    foreach ($layout_definition->getRegions() as $region => $info) {
      $build['layout-builder__section'][$region]['layout_builder_add_block']['link']['#url'] = Url::fromRoute('az_layouts.choose_inline_block',
        [
          'section_storage_type' => $storage_type,
          'section_storage' => $storage_id,
          'delta' => $delta,
          'region' => $region,
        ],
        [
          'attributes' => [
            'class' => [
              'use-ajax',
              'layout-builder__link',
              'layout-builder__link--add',
            ],
            'data-dialog-type' => 'dialog',
            'data-dialog-renderer' => 'off_canvas',
          ],
        ]
      );
    }

    return $build;
  }

}
