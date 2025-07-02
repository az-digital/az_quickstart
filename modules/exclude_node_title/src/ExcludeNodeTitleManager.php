<?php

namespace Drupal\exclude_node_title;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Service class for Exclude Node Title module settings management.
 */
class ExcludeNodeTitleManager implements ExcludeNodeTitleManagerInterface {

  /**
   * The Views Dynamic Entity Row config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settingsConfig;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Discovery and retrieval of entity type bundles manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Exclude Node Title settings array.
   *
   * @var array
   */
  protected $excludeSettings;

  /**
   * Constructs a ExcludeNodeTitleManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->settingsConfig = $config_factory
      ->getEditable('exclude_node_title.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleExcludeMode($param) {
    if (is_object($param) && $param instanceof NodeTypeInterface) {
      $param = $param->id();
    }

    $mode = $this->settingsConfig->get('content_types.' . $param);

    return !empty($mode) ? $mode : 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludedViewModes($param) {
    if (is_object($param) && $param instanceof NodeTypeInterface) {
      $param = $param->id();
    }

    $modes = $this->settingsConfig->get('content_type_modes.' . $param);

    return !empty($modes) ? $modes : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExcludedNodes() {
    $nid_list = $this->settingsConfig->get('nid_list');
    return (is_array($nid_list)) ? $nid_list : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeInfo($param) {
    $node_type = NULL;

    // We accept only integer and object.
    if (!is_object($param) && !is_numeric($param)) {
      return FALSE;
    }

    // If numeric, load the node with nid.
    if (is_numeric($param)) {
      $node = $this->entityTypeManager->getStorage('node')->load($param);
    }
    elseif (is_object($param) && $param instanceof NodeInterface) {
      $node = $param;
    }

    if (isset($node) && $node instanceof NodeInterface) {
      $node_type = $node->getType();
    }

    if (!isset($node) || !isset($node_type)) {
      return FALSE;
    }

    return ['nid' => $node->id(), 'node_type' => $node_type];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderType() {
    return $this->settingsConfig->get('type');
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderHidden() {
    return $this->getRenderType() === 'hidden';
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderRemove() {
    return $this->getRenderType() === 'remove';
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExcluded() {
    return !empty($this->settingsConfig->get('search'));
  }

  /**
   * {@inheritdoc}
   */
  public function isTitleExcluded($param, $view_mode = 'full') {
    if (!($node_info = $this->getNodeInfo($param))) {
      return FALSE;
    }

    if (!isset($this->excludeSettings)) {
      foreach ($this->bundleInfo->getBundleInfo('node') as $key => $val) {
        $this->excludeSettings[$key] = [
          'type'  => $this->getBundleExcludeMode($key),
          'modes' => $this->getExcludedViewModes($key),
        ];
      }
    }

    $node_type = $node_info['node_type'];
    $modes = $this->excludeSettings[$node_type]['modes'];
    if (!isset($this->excludeSettings[$node_type]['type'])) {
      return FALSE;
    }

    switch ($this->excludeSettings[$node_type]['type']) {
      case 'all':
        return in_array($view_mode, $modes);

      case 'user':
        if (!$node_info['nid']) {
          return FALSE;
        }

        if ($this->isNodeExcluded($node_info['nid'])) {
          return in_array($view_mode, $modes);
        }
        return FALSE;

      case 'none':
      default:
        return FALSE;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function isNodeExcluded($param) {
    if (is_object($param) && $param instanceof NodeInterface) {
      $param = $param->id();
    }

    if (!is_numeric($param)) {
      return FALSE;
    }

    return in_array($param, $this->getExcludedNodes());
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessTitle(&$vars, $node, $view_mode) {
    if ($this->isTitleExcluded($node, $view_mode)) {
      $node_info = $this->getNodeInfo($node);
      $node_type = $node_info['node_type'];

      switch ($view_mode) {
        case 'nodeform':
          $node_types = $this->bundleInfo->getBundleInfo('node');
          if (!empty($vars['head_title'])) {
            $vars['head_title']['title'] = new TranslatableMarkup('Edit @nodetype', ['@nodetype' => $node_types[$node_type]['label']]);
          }
          elseif (!empty($vars['title'])) {
            $vars['title'] = new TranslatableMarkup('Edit @nodetype', ['@nodetype' => $node_types[$node_type]['label']]);
          }
          break;

        default:
          if (!empty($vars['title'])) {
            if ($this->isRenderHidden()) {
              $vars['title_attributes']['class'][] = 'hidden';
            }
            elseif ($this->isRenderRemove()) {
              $vars['title'] = new HtmlEscapedText('');
            }
          }
          if (!empty($vars['page']) && is_array($vars['page'])) {
            if ($this->isRenderHidden()) {
              $vars['page']['#attributes']['class'][] = 'hidden';
            }
            elseif ($this->isRenderRemove()) {
              $vars['page']['#title'] = new HtmlEscapedText('');
            }
          }
          if (!empty($vars['elements']) && is_array($vars['elements'])) {
            if ($this->isRenderHidden()) {
              $vars['elements']['#attributes']['class'][] = 'hidden';
            }
            elseif ($this->isRenderRemove()) {
              $vars['elements']['#title'] = new HtmlEscapedText('');
            }
          }
          if (!empty($vars['label']) && is_array($vars['elements'])) {
            if ($this->isRenderHidden()) {
              $vars['label']['#attributes']['class'][] = 'hidden';
            }
            elseif ($this->isRenderRemove()) {
              $vars['label']['#title'] = new HtmlEscapedText('');
              $vars['label']['#markup'] = new HtmlEscapedText('');
              $vars['label'][0]['#context']['value'] = '';
            }
          }
          break;

      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addNodeToList($param) {
    if ($param instanceof NodeInterface) {
      $param = $param->id();
    }

    if (!is_numeric($param)) {
      return FALSE;
    }

    $exclude_list = $this->getExcludedNodes();
    if (!in_array($param, $exclude_list)) {
      $exclude_list[] = $param;
      $this->settingsConfig->set('nid_list', $exclude_list)->save();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeNodeFromList($param) {
    if ($param instanceof NodeInterface) {
      $param = $param->id();
    }

    if (!is_numeric($param)) {
      return FALSE;
    }

    $exclude_list = $this->getExcludedNodes();
    if (($key = array_search($param, $exclude_list)) !== FALSE) {
      unset($exclude_list[$key]);
      $this->settingsConfig->set('nid_list', $exclude_list)->save();
    }

    return $this;
  }

}
