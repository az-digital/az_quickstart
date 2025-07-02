<?php

namespace Drupal\webform_options_limit\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;

/**
 * Defines the interface for webform options limit handlers.
 */
interface WebformOptionsLimitHandlerInterface extends WebformHandlerInterface {

  /**
   * Default option value.
   */
  const DEFAULT_LIMIT = '_default_';

  /**
   * Option limit single remaining.
   */
  const LIMIT_STATUS_SINGLE = 'single';

  /**
   * Option limit multiple remaining.
   */
  const LIMIT_STATUS_MULTIPLE = 'multiple';

  /**
   * Option limit none remaining.
   */
  const LIMIT_STATUS_NONE = 'none';

  /**
   * Option limit unlimited.
   */
  const LIMIT_STATUS_UNLIMITED = 'unlimited';

  /**
   * Option limit eror.
   */
  const LIMIT_STATUS_ERROR = 'error';

  /**
   * Option limit action disable.
   */
  const LIMIT_ACTION_DISABLE = 'disable';

  /**
   * Option limit action remove.
   */
  const LIMIT_ACTION_REMOVE = 'remove';

  /**
   * Option limit action none.
   */
  const LIMIT_ACTION_NONE = 'none';

  /**
   * Option message label.
   */
  const MESSAGE_DISPLAY_LABEL = 'label';

  /**
   * Option message none.
   */
  const MESSAGE_DISPLAY_DESCRIPTION = 'description';

  /**
   * Option message none.
   */
  const MESSAGE_DISPLAY_NONE = 'none';

  /**
   * Set the webform source entity.
   *
   * Allows source entity to be injected for building the summary table.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A source entity.
   *
   * @return $this
   *   This webform handler.
   */
  public function setSourceEntity(EntityInterface $source_entity = NULL);

  /**
   * Get the webform source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   A source entity.
   */
  public function getSourceEntity();

  /**
   * Build summary table.
   *
   * @return array
   *   A renderable containing the options limit summary table.
   */
  public function buildSummaryTable();

}
