<?php

namespace Drupal\linkit\Suggestion;

/**
 * Defines a entity suggestion.
 */
class EntitySuggestion extends DescriptionSuggestion {

  /**
   * The entity uuid.
   *
   * @var string
   */
  protected $entityUuid;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The substitution id.
   *
   * @var string
   */
  protected $substitutionId;

  /**
   * Sets the entity uuid.
   *
   * @param string $entity_uuid
   *   The entity uuid.
   *
   * @return $this
   */
  public function setEntityUuid($entity_uuid) {
    $this->entityUuid = $entity_uuid;
    return $this;
  }

  /**
   * Sets the entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return $this
   */
  public function setEntityTypeId($entity_type_id) {
    $this->entityTypeId = $entity_type_id;
    return $this;
  }

  /**
   * Sets the substitution id.
   *
   * @param string $substitution_id
   *   The substitution id.
   *
   * @return $this
   */
  public function setSubstitutionId($substitution_id) {
    $this->substitutionId = $substitution_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return parent::jsonSerialize() + [
      'entity_uuid' => $this->entityUuid,
      'entity_type_id' => $this->entityTypeId,
      'substitution_id' => $this->substitutionId,
    ];
  }

}
