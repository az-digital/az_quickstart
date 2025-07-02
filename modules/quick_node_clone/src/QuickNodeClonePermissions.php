<?php

namespace Drupal\quick_node_clone;

use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Module permissions.
 */
class QuickNodeClonePermissions {
  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * Returns an array of permissions.
   *
   * @return array
   *   The permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function cloneTypePermissions() {
    return $this->generatePermissions(NodeType::loadMultiple(), [$this, 'buildPermissions']);
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "clone $type_id content" => [
        'title' => $this->t('%type_name: clone contents', $type_params),
      ],
      "clone own $type_id content" => [
        'title' => $this->t('%type_name: clone own content', $type_params),
      ],
    ];
  }

}
