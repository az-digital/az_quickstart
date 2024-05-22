(($, Drupal) => {
  Drupal.behaviors.azAlphabeticalListing = {
    attach() {
      /**
       * Loop through each alpha navigation list item and determine if the
       * corresponding search result group exists on the page. If it doesn't
       * exist on the page, then hide the navigation item.
       */
      $('#az-js-alpha-navigation li').each((index, element) => {
        // Get ID of current nav item.
        const groupId = $(element).children().attr('data-href');

        // Enable nav item if results group exists on page
        if ($(groupId).length !== 0) {
          $(element).removeClass('disabled');
          $(element)
            .children()
            .attr('tabindex', '0')
            .attr('aria-hidden', 'false')
            .attr('href', groupId);
        }

        // Disable nav item if no results group exists on page
        else {
          $(element).addClass('disabled');
          $(element)
            .children()
            .attr('tabindex', '-1')
            .attr('aria-hidden', 'true')
            .removeAttr('href');
        }
      });

      /**
       *  function azAlphabeticalListingCheckNoResults()
       *
       *  Determines if there are no results that match the provided search query.
       *  If there are no results, then it will display the "no results" message.
       *  Otherwise, the "no results" message remains hidden;
       */
      function azAlphabeticalListingCheckNoResults() {
        let visibleResults = false;
        $('.az-alphabetical-listing-group-title').each((index, element) => {
          if (!$(element).hasClass('hide-result')) {
            visibleResults = true;
          }
        });

        if (!visibleResults) {
          $('#az-js-alphabetical-listing-no-results').show();
        } else {
          $('#az-js-alphabetical-listing-no-results').hide();
        }
      }

      /**
       * function azAlphabeticalListingGroupLoop()
       *
       * Check if search result "group" has no results by determining if it has
       * an immediate sibling of .az-alphabetical-listing-group-title
       */
      function azAlphabeticalListingGroupLoop() {
        $('.az-alphabetical-listing-group-title').each((index, element) => {
          // Get the ID of the current results group
          const elementId = $(element).attr('id');
          const group = elementId.toLowerCase();
          // Set the target class to search with
          const targetGroup = `.az-alphabetical-letter-group-${group}`;

          // Set variable to determine if there are visible children
          let visibleChildren = false;
          // Loop through each item in the results group
          $(targetGroup).each((resultIndex, resultElement) => {
            if (!$(resultElement).hasClass('hide-result')) {
              // Set variable to true if item isn't hidden
              visibleChildren = true;
            }
          });

          // Get nav item with data attribute that matches the group's ID
          const navTarget = $('#az-js-alpha-navigation').find(
            `.page-link[data-href='#${elementId}']`,
          );

          if (!visibleChildren) {
            // Hide title if no visible children in the group
            $(element).hide();
            $(element).addClass('hide-result');

            // Hide nav item if no visible children in the group
            navTarget.parent().addClass('disabled');
            navTarget
              .attr('tabindex', '-1')
              .attr('aria-hidden', 'true')
              .removeAttr('href');
          } else {
            // Show title if visible children in the group
            $(element).show();
            $(element).removeClass('hide-result');

            // Show nav item if visible children in the group
            navTarget.parent().removeClass('disabled');
            navTarget
              .attr('tabindex', '0')
              .attr('aria-hidden', 'false')
              .attr('href', $(element).attr('id'));
          }
        });
      }

      /**
       *  Perform search as query is entered into the search input field.
       */
      $('#az-js-alphabetical-listing-search').keyup((event) => {
        // Retrieve the input field text
        const filter = $(event.currentTarget).val();

        /**
         * Loop through the .az-js-alphabetical-listing-search-result items and
         * determine if the item should be shown or hidden, based on the search
         * query text provided.
         */
        $('.az-js-alphabetical-listing-search-result').each(
          (index, element) => {
            // Get text for current item in loop.
            const searchResultText = $(element)
              .find('.az-alphabetical-listing-item')
              .text();

            // Hide the item if it doesn't contain search query text.
            if (searchResultText.search(new RegExp(filter, 'i')) < 0) {
              $(element)
                .find('az-alphabetical-listing-item')
                .attr('tabindex', '0');
              $(element).addClass('hide-result');
              $(element).hide();
            }
            // Show the item is it does contain search query text.
            else {
              $(element)
                .find('.az-alphabetical-listing-item')
                .attr('tabindex', '0');
              $(element).removeClass('hide-result');
              $(element).show();
            }
          },
        );

        // Determine if groups have results shown
        azAlphabeticalListingGroupLoop();

        // Determine if "no results" message is needed
        azAlphabeticalListingCheckNoResults();
      });

      /**
       * On click of alpha navigation items, create a smooth scrolling effect.
       */
      const $root = $('html, body');
      const breakpoint = 600;

      $('#az-js-alpha-navigation a').on('click', (event) => {
        event.preventDefault();
        const $alphaNav = $('#az-js-floating-alpha-nav-container');
        const href = $.attr(event.currentTarget, 'data-href');
        let fixedNavHeight = $alphaNav.outerHeight();
        const headingHeight = $(
          '.az-alphabetical-listing-group-title:first',
        ).outerHeight();
        const offsetHeight = fixedNavHeight + headingHeight;

        if ($(window).width() <= breakpoint) {
          fixedNavHeight = 0;
        }

        $root.animate(
          {
            scrollTop: $(href).offset().top - offsetHeight,
          },
          500,
          () => {
            window.location.hash = href;
          },
        );
      });

      /**
       * Check if Drupal admin toolbar is present on the page, and if it is,
       * increase the top value of the alpha navigation to prevent overlap with the
       * Drupal admin toolbar.
       */
      if ($('body.toolbar-tray-open').length) {
        // Both toolbars open
        $('#az-js-floating-alpha-nav-container').css('top', '79px');
      }
      // Only black toolbar is open
      else if ($('body.toolbar-horizontal').length) {
        $('#az-js-floating-alpha-nav-container').css('top', '39px');
      }
    },
  };
})(jQuery, Drupal);
