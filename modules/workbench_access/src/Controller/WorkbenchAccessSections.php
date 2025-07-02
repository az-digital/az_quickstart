<?php

namespace Drupal\workbench_access\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates the sections list page.
 */
class WorkbenchAccessSections extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Workbench Access manager service.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManager
   */
  protected $manager;

  /**
   * The role section storage service.
   *
   * @var \Drupal\workbench_access\RoleSectionStorageInterface
   */
  protected $roleSectionStorage;

  /**
   * The user section storage service.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Constructs a new WorkbenchAccessConfigForm.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   The Workbench Access hierarchy manager.
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage
   *   The role section storage service.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   The user section storage service.
   */
  public function __construct(WorkbenchAccessManagerInterface $manager, RoleSectionStorageInterface $role_section_storage, UserSectionStorageInterface $user_section_storage) {
    $this->manager = $manager;
    $this->roleSectionStorage = $role_section_storage;
    $this->userSectionStorage = $user_section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workbench_access.scheme'),
      $container->get('workbench_access.role_section_storage'),
      $container->get('workbench_access.user_section_storage')
    );
  }

  /**
   * Returns the section assignment page.
   */
  public function page(AccessSchemeInterface $access_scheme) {
    $rows = [];
    $tree = $access_scheme->getAccessScheme()->getTree();
    foreach ($tree as $data) {
      // @todo Move to a theme function?
      // @todo format plural
      foreach ($data as $item_id => $item) {
        $editor_count = count($this->userSectionStorage->getEditors($access_scheme, $item_id));
        $role_count = count($this->roleSectionStorage->getRoles($access_scheme, $item_id));
        $row = [];
        $row[] = str_repeat('-', $item['depth']) . ' ' . $item['label'];
        $row[] = Link::fromTextAndUrl($this->t('@count editors', ['@count' => $editor_count]), Url::fromRoute('entity.access_scheme.by_user', [
          'access_scheme' => $access_scheme->id(),
          'id' => $item_id,
        ]));
        $row[] = Link::fromTextAndUrl($this->t('@count roles', ['@count' => $role_count]), Url::fromRoute('entity.access_scheme.by_role', [
          'access_scheme' => $access_scheme->id(),
          'id' => $item_id,
        ]));
        $rows[] = $row;
      }
    }
    return [
      '#type' => 'table',
      '#header' => [
        $access_scheme->getPluralLabel(),
        $this->t('Editors'),
        $this->t('Roles'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No sections are available.'),
    ];
  }

}
