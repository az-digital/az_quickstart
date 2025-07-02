<?php

namespace Drupal\smart_date\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A computed property for dates of Smart Date field items.
 */
class DateTimeComputed extends TypedData {

  /**
   * Cached computed date.
   *
   * @var \DateTime|null
   */
  protected $date = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (!$definition->getSetting('date source')) {
      throw new \InvalidArgumentException("The definition's 'date source' key has to specify the name of the date property to be computed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->date !== NULL) {
      return $this->date;
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $value = $item->{($this->definition->getSetting('date source'))};

    // A date cannot be created from a NULL value.
    if ($value === NULL) {
      return NULL;
    }

    try {
      $date = DrupalDateTime::createFromTimestamp($value);
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $this->date = $date;
      }
    }
    catch (\Exception $e) {
      // @todo Handle this.
    }
    return $this->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->date = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
