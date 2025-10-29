<?php

declare(strict_types=1);

namespace Drupal\az_eds_user\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\az_eds_user\AzUserRoleQueryInterface;
use Drupal\az_eds_user\AzUserRoleQueryListBuilder;
use Drupal\az_eds_user\Form\AzUserRoleQueryForm;

/**
 * Defines the quickstart role query mapping entity type.
 */
#[ConfigEntityType(
  id: 'az_user_role_query',
  label: new TranslatableMarkup('Quickstart Role Query Mapping'),
  label_collection: new TranslatableMarkup('Quickstart Role Query Mappings'),
  label_singular: new TranslatableMarkup('quickstart role query mapping'),
  label_plural: new TranslatableMarkup('quickstart role query mappings'),
  config_prefix: 'az_user_role_query',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => AzUserRoleQueryListBuilder::class,
    'form' => [
      'add' => AzUserRoleQueryForm::class,
      'edit' => AzUserRoleQueryForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/config/people/ldap/az-quickstart',
    'add-form' => '/admin/config/people/ldap/az-quickstart/add',
    'edit-form' => '/admin/config/people/ldap/az-quickstart/{az_user_role_query}',
    'delete-form' => '/admin/config/people/ldap/az-quickstart/{az_user_role_query}/delete',
  ],
  admin_permission: 'administer az_user_role_query',
  label_count: [
    'singular' => '@count quickstart role query mapping',
    'plural' => '@count quickstart role query mappings',
  ],
  config_export: [
    'id',
    'label',
    'query',
    'role',
  ],
)]
final class AzUserRoleQuery extends ConfigEntityBase implements AzUserRoleQueryInterface {

  /**
   * The Role Query ID.
   */
  protected string $id;

  /**
   * The Role Query label.
   */
  protected string $label;

  /**
   * The Role Query query.
   */
  protected string $query;

  /**
   * The Role Query role.
   */
  protected string $role;

}
