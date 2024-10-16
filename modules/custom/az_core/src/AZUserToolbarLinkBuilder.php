<?php

namespace Drupal\az_core;

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
   * Drupal\externalauth\AuthmapInterface definition.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   */
  public function __construct(AccountProxyInterface $account, EntityTypeManagerInterface $entityTypeManager, ?AuthmapInterface $authmap) {
    parent::__construct($account);
    $this->entityTypeManager = $entityTypeManager;
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
    // Only valid if we have the externalauth module.
    if (!empty($this->authmap)) {
      // Check if we have permission.
      if ($this->account->hasPermission('edit matching netid content')) {
        $auth = $this->authmap->get($this->account->id(), 'cas');
        if (($auth !== FALSE)) {
          // Check if we have a linked person.
          $persons = $this->entityTypeManager->getStorage('node')->loadByProperties([
            'field_az_netid' => $auth,
            'type' => 'az_person',
            'status' => [1, TRUE],
          ]);
          if (!empty($persons)) {
            $person = reset($persons);
            // If we have a linked az person, generate a link to edit form.
            if (!empty($build['#links'])) {
              $build['#links']['az_person_edit'] = [
                'title' => $this->t('Edit Person'),
                'url' => Url::fromRoute('entity.node.edit_form', ['node' => $person->id()]),
                'attributes' => [
                  'title' => $this->t('Edit person node'),
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
