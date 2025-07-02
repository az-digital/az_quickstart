/**
 * @file
 * Provides the core logic for fieldgroup.
 */

(($, Drupal, drupalSettings) => {
  /**
   * Drupal FieldGroup object.
   */
  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};
  Drupal.FieldGroup.groupWithFocus = null;

  Drupal.FieldGroup.setGroupWithFocus = (element) => {
    element.css({ display: 'block' });
    Drupal.FieldGroup.groupWithFocus = element;
  };

  /**
   * Behaviors.
   */
  Drupal.behaviors.fieldGroup = {
    attach(context, settings) {
      settings.field_group = settings.field_group || drupalSettings.field_group;
      if (typeof settings.field_group === 'undefined') {
        return;
      }

      // Execute all of them.
      $.each(Drupal.FieldGroup.Effects, function callback(func) {
        // We check for a wrapper function in Drupal.field_group as
        // alternative for dynamic string function calls.
        const type = func.toLowerCase().replace('process', '');
        if (
          typeof settings.field_group[type] !== 'undefined' &&
          typeof this.execute === 'function'
        ) {
          this.execute(context, settings, settings.field_group[type]);
        }
      });

      // Add a new ID to each fieldset.
      $('.group-wrapper fieldset').each((index, element) => {
        // Tats bad, but we have to keep the actual id to prevent layouts to break.
        const elementID = $(element).attr('id');
        const fieldgroupID = `field_group-${elementID} ${elementID}`;
        $(element).attr('id', fieldgroupID);
      });

      // Set the hash in url to remember last user selection.
      $('.group-wrapper ul li').each((index, element) => {
        const fieldGroupNavigationListIndex = $(element).index();
        $(element)
          .children('a')
          .click(() => {
            const fieldset = $('.group-wrapper fieldset').get(
              fieldGroupNavigationListIndex,
            );
            // Grab the first id, holding the wanted hashUrl.
            const hashUrl = $(fieldset)
              .attr('id')
              .replace(/^field_group-/, '')
              .split(' ')[0];
            window.location.hash = hashUrl;
          });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
