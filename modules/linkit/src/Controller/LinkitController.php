<?php

namespace Drupal\linkit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\linkit\ProfileInterface;

/**
 * Provides route responses for linkit.module.
 */
class LinkitController extends ControllerBase {

  /**
   * Route title callback.
   *
   * @param \Drupal\linkit\ProfileInterface $linkit_profile
   *   The profile.
   *
   * @return string
   *   The profile label as a render array.
   */
  public function profileTitle(ProfileInterface $linkit_profile) {
    return $this->t('Edit %label profile', ['%label' => $linkit_profile->label()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\linkit\ProfileInterface $linkit_profile
   *   The profile.
   * @param string $plugin_instance_id
   *   The plugin instance id.
   *
   * @return string
   *   The title for the matcher edit form.
   */
  public function matcherTitle(ProfileInterface $linkit_profile, $plugin_instance_id) {
    /** @var \Drupal\linkit\MatcherInterface $matcher */
    $matcher = $linkit_profile->getMatcher($plugin_instance_id);
    return $this->t('Edit %label matcher', ['%label' => $matcher->getLabel()]);
  }

}
