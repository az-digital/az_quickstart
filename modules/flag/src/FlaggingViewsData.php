<?php

namespace Drupal\flag;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the flagging entity type.
 */
class FlaggingViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Remove the 'delete flagging' link that Views provides.
    unset($data['delete_flagging']);

    // Flag counts.
    $data['flag_counts']['table']['group'] = $this->t('Flagging');
    $data['flag_counts']['table']['join']['flagging'] = [
      'left_field' => 'flag_id',
      'field' => 'flag_id',
      'extra' => [[
        'left_field' => 'entity_id',
        'field' => 'entity_id',
      ],
      ],
    ];
    $data['flag_counts']['count'] = [
      'title' => $this->t('Flagging count'),
      'help' => $this->t('The number of flaggings an entity has.'),
      'field' => ['id' => 'numeric'],
      'filter' => ['id' => 'numeric'],
      'sort' => ['id' => 'standard'],
      'argument' => ['id' => 'numeric'],
    ];
    $data['flag_counts']['last_updated'] = [
      'title' => $this->t('Last flagging'),
      'help' => $this->t('Last time this entity has been flagged.'),
      'field' => ['id' => 'date'],
      'filter' => ['id' => 'date'],
      'sort' => ['id' => 'date'],
      'argument' => ['id' => 'date'],
    ];

    // Flag link.
    $data['flagging']['link_flag'] = [
      'field' => [
        'title' => $this->t('Flag link'),
        'help' => $this->t('Display flag/unflag link.'),
        'id' => 'flag_link',
      ],
    ];

    // Specialized is null/is not null field.
    $data['flagging']['flagged'] = [
      'title' => $this->t('Flagged'),
      'real field' => 'uid',
      'field' => [
        'id' => 'flag_flagged',
        'label' => $this->t('Flagged'),
        'help' => $this->t('A boolean field to show whether the flag is set or not.'),
      ],
      'filter' => [
        'id' => 'flag_filter',
        'label' => $this->t('Flagged'),
        'help' => $this->t('Filter to ensure content has or has not been flagged.'),
      ],
      'sort' => [
        'id' => 'flag_sort',
        'label' => $this->t('Flagged'),
        'help' => $this->t('Sort by whether entities have or have not been flagged.'),
      ],
    ];

    return $data;
  }

}
