<?php

namespace Drupal\workbench_access\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\workbench_access\AccessControlHierarchyInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\SectionAssociationStorageInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the workbench_access set switch form.
 *
 * @internal
 */
class AssignUserForm extends FormBase {

  /**
   * The user account being edited.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Workbench Access manager.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManagerInterface
   */
  protected $manager;

  /**
   * The access scheme storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $schemeStorage;

  /**
   * The section storage handler.
   *
   * @var \Drupal\workbench_access\SectionAssociationStorageInterface
   */
  protected $sectionStorage;

  /**
   * The user section storage service.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * The role section storage service.
   *
   * @var \Drupal\workbench_access\RoleSectionStorageInterface
   */
  protected $roleSectionStorage;

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the form object.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   The workbench access manager.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $scheme_storage
   *   The access scheme storage handler.
   * @param \Drupal\workbench_access\SectionAssociationStorageInterface $section_storage
   *   The section storage handler.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   The user section storage service.
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage
   *   The role section storage service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal messenger service.
   */
  public function __construct(WorkbenchAccessManagerInterface $manager, ConfigEntityStorageInterface $scheme_storage, SectionAssociationStorageInterface $section_storage, UserSectionStorageInterface $user_section_storage, RoleSectionStorageInterface $role_section_storage, MessengerInterface $messenger) {
    $this->manager = $manager;
    $this->schemeStorage = $scheme_storage;
    $this->sectionStorage = $section_storage;
    $this->userSectionStorage = $user_section_storage;
    $this->roleSectionStorage = $role_section_storage;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workbench_access.scheme'),
      $container->get('entity_type.manager')->getStorage('access_scheme'),
      $container->get('entity_type.manager')->getStorage('section_association'),
      $container->get('workbench_access.user_section_storage'),
      $container->get('workbench_access.role_section_storage'),
      $container->get('messenger')
    );
  }

  /**
   * Checks access to the form from the route.
   *
   * This form is only visible on accounts that can use Workbench Access,
   * regardless of the current user's permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account accessing the form.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user being edited.
   */
  public function access(AccountInterface $account, AccountInterface $user) {
    return AccessResult::allowedIf($user->hasPermission('use workbench access'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workbench_access_assign_user';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    $this->user = $user;
    $form_enabled = FALSE;
    $active_schemes = [];

    // Load all schemes.
    /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $schemes */
    $schemes = $this->schemeStorage->loadMultiple();
    foreach ($schemes as $scheme) {
      $user_sections = $this->userSectionStorage->getUserSections($scheme, $user, FALSE);
      $options = $this->getFormOptions($scheme);
      $role_sections = $this->roleSectionStorage->getRoleSections($scheme, $user);
      $list = array_flip($role_sections);
      foreach ($options as $value => $label) {
        if (isset($list[$value])) {
          $options[$value] = '<strong>' . $label . ' * </strong>';
        }
      }
      if (!empty($options)) {
        $form[$scheme->id()] = [
          '#type' => 'fieldset',
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
          '#title' => $scheme->getPluralLabel(),
        ];
        $form[$scheme->id()]['active_' . $scheme->id()] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Assigned sections'),
          '#options' => $options,
          '#default_value' => $user_sections,
          '#description' => $this->t('Sections assigned by role are <strong>emphasized</strong> and marked with an * but not selected unless they are also assigned directly to the user. They need not be selected. Access granted by role cannot be revoked from this form.'),
        ];
        $form[$scheme->id()]['scheme_' . $scheme->id()] = [
          '#type' => 'value',
          '#value' => $scheme,
        ];
        $form_enabled = TRUE;
        $active_schemes[] = $scheme->id();
      }
    }
    if ($form_enabled) {
      $form['schemes'] = [
        '#type' => 'value',
        '#value' => $active_schemes,
      ];
      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#name' => 'save',
          '#value' => $this->t('Save'),
        ],
      ];
    }
    else {
      $form['help'] = [
        '#markup' => $this->t('You do not have permission to manage any assignments.'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $items = [];
    foreach ($values['schemes'] as $id) {
      $items[$id]['scheme'] = $values['scheme_' . $id];
      $items[$id]['selections'] = $values['active_' . $id];
    }
    foreach ($items as $item) {
      // Add sections.
      $sections = array_filter($item['selections'], function ($val) {
        return !empty($val);
      });
      $sections = array_keys($sections);
      $this->userSectionStorage->addUser($item['scheme'], $this->user, $sections);

      // Remove sections.
      $remove_sections = array_keys(array_filter($item['selections'], function ($val) {
        return empty($val);
      }));
      $this->userSectionStorage->removeUser($item['scheme'], $this->user, $remove_sections);
    }

    $this->messenger()->addMessage($this->t('Section assignments updated successfully.'));
  }

  /**
   * Gets available form options for this administrative user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   The access scheme being processed by the form.
   */
  public function getFormOptions(AccessSchemeInterface $scheme) {
    $options = [];
    $access_scheme = $scheme->getAccessScheme();
    if ($this->manager->userInAll($scheme)) {
      $list = $this->manager->getAllSections($scheme, FALSE);
    }
    else {
      // @todo new method needed?
      $list = $this->userSectionStorage->getUserSections($scheme);
      $list = $this->getChildren($access_scheme, $list);
    }
    foreach ($list as $id) {
      if ($section = $access_scheme->load($id)) {
        $options[$id] = str_repeat('-', $section['depth']) . ' ' . $section['label'];
      }
    }
    return $options;
  }

  /**
   * Gets the child sections of a base section.
   *
   * @param \Drupal\workbench_access\AccessControlHierarchyInterface $access_scheme
   *   The access scheme being processed by the form.
   * @param array $values
   *   Defined or selected values.
   *
   * @return array
   *   An array of section ids that this user may see.
   */
  protected function getChildren(AccessControlHierarchyInterface $access_scheme, array $values) {
    $tree = $access_scheme->getTree();
    $children = [];
    foreach ($values as $id) {
      foreach ($tree as $key => $data) {
        if ($id === $key) {
          $children += array_keys($data);
        }
        else {
          foreach ($data as $iid => $item) {
            if ($iid === $id || in_array($id, $item['parents'])) {
              $children[] = $iid;
            }
          }
        }
      }
    }
    return $children;
  }

}
