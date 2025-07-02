<?php

namespace Drupal\auto_entitylabel\Plugin\Validation;

use Drupal\auto_entitylabel\EntityDecorator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * EntityLabelNotNull constraint validator.
 *
 * Custom override of NotNull constraint to allow empty entity labels to
 * validate before the automatic label is set.
 */
class EntityLabelNotNullConstraintValidator extends NotNullConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity decorator service.
   *
   * @var \Drupal\auto_entitylabel\EntityDecorator
   */
  protected $entityDecorator;

  /**
   * Creates an EntityLabelNotNullConstraintValidator object.
   *
   * @param \Drupal\auto_entitylabel\EntityDecorator $entityDecorator
   *   The entity decorator service.
   */
  public function __construct(EntityDecorator $entityDecorator) {
    $this->entityDecorator = $entityDecorator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('auto_entitylabel.entity_decorator'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    $typed_data = $this->getTypedData();
    if ($typed_data instanceof FieldItemList && $typed_data->isEmpty()) {
      $entity = $typed_data->getEntity();
      /** @var \Drupal\auto_entitylabel\AutoEntityLabelManager $decorated_entity */
      $decorated_entity = $this->entityDecorator->decorate($entity);
      if ($decorated_entity->hasLabel()
        && $decorated_entity->autoLabelNeeded()
        && $value->getFieldDefinition()->getName() == $decorated_entity->getLabelName()
      ) {
        return;
      }
    }

    parent::validate($value, $constraint);
  }

}
