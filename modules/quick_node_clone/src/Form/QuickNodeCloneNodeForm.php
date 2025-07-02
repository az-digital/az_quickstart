<?php

namespace Drupal\quick_node_clone\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;

/**
 * Form controller for Quick Node Clone edit forms.
 *
 * We can override most of the node form from here! Hooray!
 */
class QuickNodeCloneNodeForm extends NodeForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    // Brand the Publish / Unpublish buttons, but first check if they are still
    // there.
    $clone_string = $this->t('New Clone:');
    if (!empty($element['publish']['#value'])) {
      $element['publish']['#value'] = $clone_string . ' ' . $element['publish']['#value'];
    }
    if (!empty($element['unpublish']['#value'])) {
      $element['unpublish']['#value'] = $clone_string . ' ' . $element['unpublish']['#value'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entity;
    $insert = $node->isNew();
    $node->save();
    $node_link = $node->toLink($this->t('View'))->toString();
    $context = [
      '@type' => $node->getType(),
      '%title' => $node->label(),
      'link' => $node_link,
    ];
    $t_args = [
      '@type' => node_get_type_label($node),
      '%title' => $node->label(),
    ];

    if ($insert) {
      $this->logger('content')
        ->notice('@type: added %title (clone).', $context);
      $this->messenger()->addMessage($this->t('@type %title (clone) has been created.', $t_args));
    }

    if ($node->id()) {
      $form_state->setValue('nid', $node->id());
      $form_state->set('nid', $node->id());
      $storage = $form_state->getStorage();
      foreach ($storage['quick_node_clone_groups_storage'] as $group) {
        // Add node to all the groups the original was in
        // (if group and gnode modules aren't installed then nothing should ever
        // be set in this array anyway)
        $add_method = method_exists($group, 'addContent') ? 'addContent' : 'addRelationship';
        $group->{$add_method}($node, 'group_node:' . $node->bundle());
      }
      if ($node->access('view')) {
        $form_state->setRedirect(
          'entity.node.canonical',
          ['node' => $node->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }

    }
    else {
      // In the unlikely case something went wrong on save, the node will be
      // rebuilt and node form redisplayed the same way as in preview.
      $this->messenger()->addError($this->t('The cloned post could not be saved.'));
      $form_state->setRebuild();
    }

    return $insert;
  }

}
