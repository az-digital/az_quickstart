<?php

namespace Drupal\exclude_node_title;

/**
 * Defines methods to manage Exclude Node Title settings.
 */
interface ExcludeNodeTitleManagerInterface {

  /**
   * Loads exclude mode for node type.
   *
   * @param mixed $param
   *   Can be NodeTypeInterface object or machine name.
   *
   * @return string
   *   Exclude mode.
   */
  public function getBundleExcludeMode($param);

  /**
   * Loads excluded view modes for node type.
   *
   * @param mixed $param
   *   Can be NodeTypeInterface object or machine name.
   *
   * @return array
   *   View modes.
   */
  public function getExcludedViewModes($param);

  /**
   * Loads excluded node ids list.
   *
   * @return array
   *   Nodes identifiers list.
   */
  public function getExcludedNodes();

  /**
   * Helper function to that extracts node information from $param.
   *
   * @param mixed $param
   *   Can be a NodeInterface object or integer value (nid).
   *
   * @return mixed
   *   Returns an array with node id and node type, or FALSE if errors exist.
   */
  public function getNodeInfo($param);

  /**
   * Checks if exclude from Search elements is enabled.
   *
   * @return bool
   *   Enabled status.
   */
  public function isSearchExcluded();

  /**
   * Tells if node should get hidden or not.
   *
   * @param mixed $param
   *   Can be a node object or integer value (nid).
   * @param string $view_mode
   *   Node view mode to check.
   *
   * @return bool
   *   Returns boolean TRUE if should be hidden, FALSE when not.
   */
  public function isTitleExcluded($param, $view_mode = 'full');

  /**
   * Tells if node is in exclude list.
   *
   * @param mixed $param
   *   Can be a node object or integer value (nid).
   */
  public function isNodeExcluded($param);

  /**
   * Remove the title from the variables array.
   *
   * @param mixed $vars
   *   Theme function variables.
   * @param mixed $node
   *   Can be NodeInterface object or integer id.
   * @param string $view_mode
   *   View mode name.
   */
  public function preprocessTitle(&$vars, $node, $view_mode);

  /**
   * Adds node to exclude list.
   *
   * @param mixed $param
   *   Can be a node object or integer value (nid).
   */
  public function addNodeToList($param);

  /**
   * Removes node exclude list.
   *
   * @param mixed $param
   *   Can be a node object or integer value (nid).
   */
  public function removeNodeFromList($param);

}
