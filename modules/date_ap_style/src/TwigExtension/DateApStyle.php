<?php

namespace Drupal\date_ap_style\TwigExtension;

use Drupal\date_ap_style\ApStyleDateFormatter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Custom twig filter to display dates as AP Style.
 */
class DateApStyle extends AbstractExtension {

  public function __construct(
    #[Autowire('@date_ap_style.formatter')]
    protected ApStyleDateFormatter $apStyleDateFormatter,
  ) {}

  /**
   * Generates a list of all Twig filters that this extension defines.
   *
   * @return string[]
   *   A key/value array that defines custom Twig filters. The key denotes the
   *   filter name used in the tag, e.g.:
   *   @code
   *   {{ foo|ap_style }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the filter does.
   */
  public function getFilters(): array {
    return [
      new TwigFilter('ap_style', [$this, 'apStyleFilter']),
    ];
  }

  /**
   * Twig filter callback to format a timestamp as AP Style.
   *
   * @param int $timestamp
   *   The timestamp to format.
   * @param array $options
   *   An array of options to customize the formatting.
   *
   * @return string
   *   The formatted date.
   */
  public function apStyleFilter(int $timestamp, array $options = []): string {
    return $this->apStyleDateFormatter->formatTimestamp($timestamp, $options);
  }

}
