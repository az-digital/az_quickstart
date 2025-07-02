<?php

namespace Drupal\flag\TwigExtension;

use Drupal\Core\Entity\EntityInterface;
use Drupal\flag\FlagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig extension to get the flag count given a flag and flaggable.
 */
class FlagCount extends AbstractExtension {

  /**
   * The flag count.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  protected $flagCount;

  /**
   * Constructs \Drupal\flag\TwigExtension\FlagCount.
   *
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count
   *   The flag count service.
   */
  public function __construct($flag_count) {
    if (func_num_args() == 5) {
      $flag_count = func_get_arg(4);
    }
    $this->flagCount = $flag_count;
  }

  /**
   * Generates a list of all Twig functions that this extension defines.
   */
  public function getFunctions() {
    return [
      new TwigFunction('flagcount', [$this, 'count'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'flag.twig.count';
  }

  /**
   * Gets the number of flaggings for the given flag and flaggable.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $flaggable
   *   The flaggable entity.
   *
   * @return string
   *   The number of times the flaggings for the given parameters.
   */
  public function count(FlagInterface $flag, EntityInterface $flaggable) {
    $counts = $this->flagCount->getEntityFlagCounts($flaggable);
    return empty($counts) || !isset($counts[$flag->id()]) ? '0' : $counts[$flag->id()];
  }

}
