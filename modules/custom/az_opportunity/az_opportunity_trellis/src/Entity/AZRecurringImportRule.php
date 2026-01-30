<?php

declare(strict_types=1);

namespace Drupal\az_opportunity_trellis\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\az_opportunity_trellis\AZRecurringImportRuleInterface;
use Drupal\az_opportunity_trellis\AZRecurringImportRuleListBuilder;
use Drupal\az_opportunity_trellis\Form\AZRecurringImportRuleForm;

/**
 * Defines the recurring import rule entity type.
 */
#[ConfigEntityType(
  id: 'az_recurring_import_rule',
  label: new TranslatableMarkup('Recurring Import Rule'),
  label_collection: new TranslatableMarkup('Recurring Import Rules'),
  label_singular: new TranslatableMarkup('recurring import rule'),
  label_plural: new TranslatableMarkup('recurring import rules'),
  handlers: [
    'list_builder' => AZRecurringImportRuleListBuilder::class,
    'form' => [
      'add' => AZRecurringImportRuleForm::class,
      'edit' => AZRecurringImportRuleForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  config_prefix: 'az_recurring_import_rule',
  admin_permission: 'administer quickstart configuration',
  label_count: [
    'singular' => '@count recurring import rule',
    'plural' => '@count recurring import rules',
  ],
  links: [
    'collection' => '/admin/config/az-quickstart/settings/az-recurring-import-rule',
    'add-form' => '/admin/config/az-quickstart/settings/az-recurring-import-rule/add',
    'edit-form' => '/admin/config/az-quickstart/settings/az-recurring-import-rule/{az_recurring_import_rule}',
    'delete-form' => '/admin/config/az-quickstart/settings/az-recurring-import-rule/{az_recurring_import_rule}/delete',
  ],
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  config_export: [
    'id',
    'label',
    'owner',
    'host',
    'keyword',
    'attributes',
    'approval',
  ],
)]
final class AZRecurringImportRule extends ConfigEntityBase implements AZRecurringImportRuleInterface {

  /**
   * The az_recurring_import_rule ID.
   */
  protected string $id;

  /**
   * The az_recurring_import_rule label.
   */
  protected string $label;

  /**
   * The az_recurring_import_rule keyword.
   */
  protected string $keyword;

  /**
   * The az_recurring_import_rule owner.
   */
  protected string $owner;

  /**
   * The az_recurring_import_rule owner.
   */
  protected string $host;

  /**
   * The az_recurring_import_rule enterprise attributes.
   */
  protected ?array $attributes;

  /**
   * The az_recurring_import_rule approval status.
   */
  protected string $approval;

  /**
   * {@inheritdoc}
   */
  public function getQueryParameters() {
    // Build a list of query parameters.
    $params = [
      'publish' => 'true',
    ];
    $attributes = array_filter($this->attributes ?? []);
    $params += $attributes;
    $params['keyword'] = $this->get('keyword') ?? '';
    $params['owner'] = $this->get('owner') ?? '';
    $params['host'] = $this->get('host') ?? '';
    $params['approval'] = $this->get('approval') ?? '';
    $params = array_filter($params);
    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpportunityIds() {
    // Build a list of query parameters.
    $params = $this->getQueryParameters();

    // Let's refuse to search if there are no constraints except published.
    if (count($params) === 1) {
      return [];
    }

    return \Drupal::service('az_opportunity_trellis.trellis_helper')->searchOpportunities($params);
  }

}
