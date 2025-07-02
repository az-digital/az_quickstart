Metatag Extended Permissions
----------------------------
This add-on for Metatag creates a new permission for each individual meta tag,
allowing for very fine controlled access over meta tag customization.


Usage
--------------------------------------------------------------------------------
* Enable the Metatag Extended Permissions module.
* Assign the appropriate permissions via the admin/people/permissions page.


Known issues
--------------------------------------------------------------------------------
This module introduces a possibility for dramatically increasing the number of
checkboxes on the permissions page. This can lead to the following problems:
* The permissions admin page or node forms taking a long time to load.
* PHP timeout errors on the permissions admin or node forms pages.
* Out-of-memory errors loading the above.
* The web server not being able to process the permissions form due to hitting
  PHP's max_input_vars limit.

Because of these, it is advised to fully test this module on a copy of a site
before enabling it on production, to help ensure these problems are not
encountered.


Updating from Metatag Access
--------------------------------------------------------------------------------
The original sandbox module for this functionality was called "Metatag Access".
Sites which used that submodule should switch to this module. Rather than
loosing their configuration, use the following update script to convert the
permissions.

/**
 * Replace Metatag Access with Metatag Extended Perms.
 */
function mysite_update_9001() {
  $installer = \Drupal::service('module_installer');

  // Install the Metatag Extended Permissions module.
  $installer->install(['metatag_extended_perms']);

  // Update the permissions.
  foreach (Role::loadMultiple() as $role) {
    // Keep track of whether the permissions changed for this role.
    $changed = FALSE;
    foreach ($role->getPermissions() as $key => $perm) {
      // Look for permissions that started with the old permission string.
      if (strpos($perm, 'access metatag tag') !== FALSE) {
        // Grand the new permission.
        $role->grantPermission(
          str_replace(
            'access metatag tag', 'access metatag', $perm
          )
        );

        // Track that the role's permissions changed.
        $changed = TRUE;
      }
    }

    // If the permissions changed, save the role.
    if ($changed) {
      $role->trustData()->save();
    }
  }

  // Uninstall the Metatag Access module.
  $installer->uninstall(['metatag_access']);
}


Credits / contact
--------------------------------------------------------------------------------
Originally written by Michael Petri [1].


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/u/michaelpetri
