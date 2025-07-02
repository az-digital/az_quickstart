/**
 * @file
 * bef_select_all_none.js
 *
 * Adds select all/none toggle functionality to an exposed filter.
 */

(function ($, once) {
  Drupal.behaviors.betterExposedFiltersSelectAllNone = {
    attach: function () {
      /*
       * Add Select all/none links to specified checkboxes
       */
      const selected = $('.form-checkboxes.bef-select-all-none:not(.bef-processed)');
      if (selected.length) {
        const selAll = Drupal.t('Select All');
        const selNone = Drupal.t('Select None');

        // Set up a prototype link and event handlers.
        const link = $('<a class="bef-toggle bef-toggle--select-all" href="#">' + selAll + '</a>');
        link.click(function (event) {
          // Don't actually follow the link...
          event.preventDefault();
          event.stopPropagation();

          if (selAll === $(this).text()) {
            // Select all the checkboxes.
            $(this)
              .html(selNone)
              .removeClass('bef-toggle--select-all')
              .addClass('bef-toggle--deselect-all')
              .siblings('.bef-select-all-none, .bef-tree')
              .find('input:checkbox').each(function () {
                $(this).prop('checked', true);
              })
              .end()

              // attr() doesn't trigger a change event, so we do it ourselves. But just on
              // one checkbox otherwise we have many spinning cursors.
              .find('input[type=checkbox]:first').change();
          }
          else {
            // Unselect all the checkboxes.
            $(this)
              .html(selAll)
              .removeClass('bef-toggle--deselect-all')
              .addClass('bef-toggle--select-all')
              .siblings('.bef-select-all-none, .bef-tree')
              .find('input:checkbox').each(function () {
                $(this).prop('checked', false);
              })
              .end()

              // attr() doesn't trigger a change event, so we do it ourselves. But just on
              // one checkbox otherwise we have many spinning cursors.
              .find('input[type=checkbox]:first').change();
          }
        });

        // Add link to the page for each set of checkboxes.
        selected
          .addClass('bef-processed')
          .each(function () {
            // Clone the link prototype and insert into the DOM.
            const newLink = link.clone(true);

            newLink.insertBefore($(this));

             // Show select all/none when single checkbox is checked/unchecked
             $('input:checkbox', this).click(function() {
              if ($(this).prop("checked") === true) {
                newLink.text(selNone);
              }
              else if ($(this).prop("checked") === false) {
                newLink.text(selAll);
              }
            });

            // If all checkboxes are already checked by default then switch to Select None.
            if ($('input:checkbox:checked', this).length === $('input:checkbox', this).length) {
              newLink.text(selNone).removeClass('bef-toggle--select-all').addClass('bef-toggle--deselect-all');
            }
          });
      }
    }
  };

  Drupal.behaviors.betterExposedFiltersAllNoneNested = {
    attach: function () {
      $(once('bef-all-none-nested', '.bef-select-all-none-nested ul li')).each(function () {
        const $this = $(this);
        // Check/uncheck child terms along with their parent.
        $this.find('input:checkbox:first').change(function () {
          $(this).closest('li').find('ul li input:checkbox').prop('checked', this.checked);
        });

        // When a child term is checked or unchecked, set the parent term's
        // status as needed.
        $this.find('ul input:checkbox').change(function () {
          // Determine the number of unchecked sibling checkboxes.
          const $this = $(this);
          const uncheckedSiblings = $this.closest('li').siblings('li').find('> div input:checkbox:not(:checked)').length;

          // If this term or any siblings are unchecked, uncheck the parent and
          // all ancestors.
          if (uncheckedSiblings || !this.checked) {
            $this.parents('ul').siblings('div').find('input:checkbox').prop('checked', false);
          }

          // If this and all sibling terms are checked, check the parent. Then
          // trigger the parent's change event to see if that change affects the
          // grandparent's checked state.
          if (this.checked && !uncheckedSiblings) {
            $(this).closest('ul').closest('li').find('input:checkbox:first').prop('checked', true).change();
          }
        });
      });
    }
  };

})(jQuery, once);
