<?php

namespace Drupal\workbench_access;

use Drupal\views\EntityViewsData;

/**
 * Provides the workbench access views integration.
 *
 * @internal
 */
class ViewsData extends EntityViewsData {

  /**
   * Returns the views data.
   *
   * @return array
   *   The views data.
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['section_association__user_id']['entity_id']['field']['id'] = 'field';
    $data['section_association__user_id']['entity_id']['field']['title'] = $this->t('Section Association ID');

    $data['section_association']['section_id']['field'] = [
      'id' => 'workbench_access_section_id',
      'title' => $this->t('Section ID'),
    ];
    $data['section_association']['section_id']['filter'] = [
      'id' => 'workbench_access_section_id',
      'title' => $this->t('Section ID'),
    ];
    /* Some notes for later:

    - Decide which tables to JOIN to, if any.
    - Decide which tables to form relationships for.
    - Define any configuration that will be necessary. I suspect that
    configuration per field will require specifying a specific scheme.
    - Explore whether the views_data_alter hook is still needed. In fact,
    we may need to do all of our computation there, with the exception
    of the base relationships.
    - However, if may be that the relationships require configuration.
     */
    return $data;
  }

}
