<?php

namespace Drupal\entity_embed\Controller;

use Drupal\Core\Controller\ControllerBase;

class EditEmbeddedEntity extends ControllerBase {

  /**
   * Redirects to an entity edit form based on its type and uuid.
   *
   * @param string $type
   *   The entity type.
   * @param string $uuid
   *   The entity uuid.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect destination response.
   */
  public function edit(string $type, string $uuid) {
    $entity = \Drupal::service('entity.repository')->loadEntityByUuid($type, $uuid);
    return $this->redirect("entity.$type.edit_form", [$type => $entity->id()]);
  }



}
