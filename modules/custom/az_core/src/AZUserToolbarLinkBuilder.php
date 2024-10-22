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
    $additional_links = [];
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
    // Transform some of the user module links for clarity.
    if (!empty($build['#links'])) {
      $original_links = $build['#links'];
      if (!empty($original_links['account'])) {
        $original_links['account']['title'] = $this->t('View user account');
      }
      if (!empty($original_links['account_edit'])) {
        $original_links['account_edit']['title'] = $this->t('Edit user account');
      }
      // Add in our links among the user module links.
      $links = array_merge($additional_links, $original_links);
      $build['#links'] = $links;
    }
    return $build;
  }

}
