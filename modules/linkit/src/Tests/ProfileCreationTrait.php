<?php

namespace Drupal\linkit\Tests;

use Drupal\linkit\Entity\Profile;

/**
 * Provides methods to create profiles based on default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait ProfileCreationTrait {

  /**
   * Creates a profile based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the profile, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array.
   *   The following defaults are provided:
   *   - id: Random string.
   *   - label: Random string.
   *
   * @return \Drupal\linkit\ProfileInterface
   *   The created profile entity.
   */
  protected function createProfile(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
    ];

    $profile = Profile::create($settings);
    $profile->save();

    $profile = Profile::load($profile->id());

    return $profile;
  }

}
