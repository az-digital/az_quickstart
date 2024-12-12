<?php

namespace Drupal\az_core;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\externalauth\AuthmapInterface;
use Drupal\user\ToolbarLinkBuilder;

/**
 * ToolbarLinkBuilder fills out the placeholders generated in user_toolbar().
 */
class AZUserToolbarLinkBuilder extends ToolbarLinkBuilder {

  /**
   * Authmap service definition.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Entity type manager service definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager service definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   */
  public function __construct(
    AccountProxyInterface $account,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    ?AuthmapInterface $authmap
  ) {
    parent::__construct($account);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->authmap = $authmap;
  }

  /**
   * Lazy builder callback for rendering toolbar links.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function renderToolbarLinks() {
    $build = parent::renderToolbarLinks();
    $additional_links = [];
    // Only valid if we have the externalauth module.
    if (!empty($this->authmap)) {
      // Check if we have permission.
      if ($this->account->hasPermission('edit matching netid content')) {
        $auth = $this->authmap->get($this->account->id(), 'cas');
        if (($auth !== FALSE)) {
          // Verify that 'field_az_netid' exists for 'az_person' content type.
          $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', 'az_person');
          if (isset($field_definitions['field_az_netid'])) {
            // Check if we have a linked person.
            $persons = $this->entityTypeManager->getStorage('node')->loadByProperties([
              'field_az_netid' => $auth,
              'type' => 'az_person',
              'status' => [1, TRUE],
            ]);
            if (!empty($persons)) {
              $person = reset($persons);
              // If we have a linked az person, generate links.
              $additional_links = [
                'az_person' => [
                  'title' => $this->t('View my web page'),
                  'url' => Url::fromRoute('entity.node.canonical', ['node' => $person->id()]),
                  'attributes' => [
                    'title' => $this->t('View my web page'),
                  ],
                ],
                'az_person_edit' => [
                  'title' => $this->t('Edit my web page'),
                  'url' => Url::fromRoute('entity.node.edit_form', ['node' => $person->id()]),
                  'attributes' => [
                    'title' => $this->t('Edit my web page'),
                  ],
                ],
              ];
            }
          }
        }
      }
    }


    return $build;
  }

}
