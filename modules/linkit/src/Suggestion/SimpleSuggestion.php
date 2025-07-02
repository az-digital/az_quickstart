<?php

namespace Drupal\linkit\Suggestion;

/**
 * Defines a simple suggestion.
 */
class SimpleSuggestion implements SuggestionInterface {

  /**
   * The suggestion label.
   *
   * @var string
   */
  protected $label;

  /**
   * The suggestion path.
   *
   * @var string
   */
  protected $path;

  /**
   * The suggestion status.
   *
   * @var string
   */
  protected $status;


  /**
   * The suggestion group.
   *
   * @var string
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup($group) {
    $this->group = $group;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return [
      'label' => $this->getLabel(),
      'path' => $this->getPath(),
      'status' => $this->getStatus(),
      'group' => $this->getGroup(),
    ];
  }

}
