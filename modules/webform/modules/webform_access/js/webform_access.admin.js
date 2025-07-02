/**
 * @file
 * JavaScript behaviors for webform access.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Initialize webform access group administer permission toggle.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformAccessGroupPermissions = {
    attach: function (context) {
      $(once('webform-access-group-permissions', '#edit-permissions', context)).each(function () {
        var $permissions = $(this);
        var $checkbox = $permissions.find('input[name="permissions[administer]"]');

        $checkbox.on('click', toggleAdminister);
        if ($checkbox.prop('checked')) {
          toggleAdminister();
        }

        function toggleAdminister() {
          var checked = $checkbox.prop('checked');
          $permissions.find(':checkbox').prop('checked', checked);
          $permissions.find(':checkbox:not([name="permissions[administer]"])').attr('disabled', checked);
        }
      });

    }
  };

})(jQuery, Drupal);
