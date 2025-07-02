<?php

namespace Drupal\paragraphs_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Plugin\Field\FieldFormatter\ParagraphsSummaryFormatter;

/**
 * Plugin implementation of the 'paragraph_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "library_item_summary",
 *   label = @Translation("Library item summary"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
#[FieldFormatter(
  id: 'library_item_summary',
  label: new TranslatableMarkup('Library item summary'),
  field_types: [
    'entity_reference_revisions'
  ]
)]
class LibraryItemSummaryFormatter extends ParagraphsSummaryFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    if (!$items->getEntity()->isPublished()) {
      $published = [
        '#theme' => 'paragraphs_info_icon',
        '#message' => $this->t('Unpublished'),
        '#icon' => 'view',
      ];
      $elements[0]['info'] += $published;
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() == 'paragraphs_library_item' && $field_definition->getName() == 'paragraphs') {
      return TRUE;
    }
    return FALSE;
  }

}
