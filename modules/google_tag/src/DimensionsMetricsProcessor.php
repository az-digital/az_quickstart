<?php

declare(strict_types=1);

namespace Drupal\google_tag;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\google_tag\Entity\TagContainer;

/**
 * Processes metrics and dimensions.
 */
final class DimensionsMetricsProcessor {

  /**
   * The Token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  private Token $token;

  /**
   * Route Match Service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * DimensionsMetricsProcessor constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route matcher.
   */
  public function __construct(Token $token, RouteMatchInterface $routeMatch) {
    $this->token = $token;
    $this->routeMatch = $routeMatch;
  }

  /**
   * Returns values for metrics and dimensions.
   *
   * @phpstan-return array<string, string|int|float|bool>
   */
  public function getValues(TagContainer $tag): array {
    $types = [];
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if (!$parameter instanceof EntityInterface) {
        continue;
      }
      $token_type = $parameter->getEntityTypeId();
      // Normalize for taxonomy entity token types.
      // @see token_entity_type_alter().
      if ($token_type === 'taxonomy_term' || $token_type === 'taxonomy_vocabulary') {
        $token_type = str_replace('taxonomy_', '', $token_type);
      }
      $types[$token_type] = $parameter;
    }
    $values = [];
    foreach ($tag->getDimensionsAndMetrics() as $custom) {
      $custom['value'] = $this->token->replace($custom['value'], $types, ['clear' => TRUE]);
      if ($custom['value'] === '') {
        continue;
      }
      if (($custom['type'] === 'metric') && is_numeric($custom['value'])) {
        $custom['value'] = (float) $custom['value'];
      }

      $values[$custom['name']] = $custom['value'];
    }
    return $values;
  }

}
