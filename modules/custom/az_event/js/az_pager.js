(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.azPager = {
    attach: function (context, settings) {
        const results = drupalSettings.azPager;
        console.log(results.currentDate);
        // Function to update a filter's internal date fields from datepicker.
        function updateCalendarFilters(startDate, endDate) {
            const $ancestor = $wrapper.closest(
            '.views-widget-az-calendar-filter',
            );

            const dates = [startDate, endDate];
            for (let i = 0; i < dates.length; i++) {
            const month = dates[i].getMonth() + 1;
            const day = dates[i].getDate();
            const year = dates[i].getFullYear();
            $ancestor.find('input').eq(i).val(`${year}-${month}-${day}`);
            }

            // Signal to UI that the inputs were updated programmatically.
            triggerFilterChange($ancestor, 0);
            $ancestor
            .find('.btn')
            .removeClass('active')
            .attr('aria-pressed', 'false');
        }

        // Set task to trigger filter element change.
        function triggerFilterChange($ancestor, delay) {
            if (task != null) {
            clearTimeout(task);
            }
            task = setTimeout(() => {
            // Only trigger if submit buttion isn't disabled.
            if (!$submitButton.prop('disabled')) {
                $ancestor.find('input').eq(0).change();
                $submitButton.click();
                task = null;
            }
            // The form is disabled and we are probably ajaxing.
            // Wait for a while.
            else {
                triggerFilterChange($ancestor, 200);
            }
            }, delay);
        }

        $('.pager__button--next', context).once('azPager').click(function (e) {
        e.preventDefault();
            // find mode
            let timeScale = results[timeScale];
            // change to next
            if(timeScale === 'day'){
                updateCalendarFilters(startDate, endDate);
            }

        });

        $('.pager__button--prev', context).once('azPager').click(function (e) {
        e.preventDefault();

        // Similar to the next button, implement the logic for the previous button here.
        });
    }
    };
})(jQuery, Drupal, drupalSettings);
