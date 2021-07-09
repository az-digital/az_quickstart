/**
 * @file
 * A JavaScript file for the datepicker calendar functionality.
 *
 */

(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.azCalendarFilter = {
    attach: function (context, settings) {

      var filterInformation = drupalSettings.azCalendarFilter;
      if (!drupalSettings.hasOwnProperty('calendarFilterRanges')) {
        drupalSettings.calendarFilterRanges = [];
      }

      // Drupal settings get merged rather than replaced during ajax.
      // We should clear out stale entries when we process a new cells.
      drupalSettings.azCalendarFilter = {};

      // Process cell date strings into javascript dates.
      for (var property in filterInformation) {
        if (filterInformation.hasOwnProperty(property)) {
          drupalSettings.calendarFilterRanges[property] = [];
          var ranges = filterInformation[property];
          for (var i = 0; i < ranges.length; i++) {
            drupalSettings.calendarFilterRanges[property].push([
              $.datepicker.parseDate( "@", ranges[i][0] * 1000),
              $.datepicker.parseDate( "@", ranges[i][1] * 1000)
            ]);
          }
        }
      }

      // We may have recieved new cell data. Refresh existing datepickers.
      $(".az-calendar-filter-calendar" ).datepicker( "refresh" );

      // Initialize calendar widget wrapper if needed.
      $(".az-calendar-filter-wrapper", context).once('azCalendarFilter').each(function(){
        var $wrapper = $(this);
        // rangeKey contains our filter identifier to find calendar cell data.
        var rangeKey = $wrapper.data('az-calendar-filter');
        var rangeStart = null;
        var rangeEnd = null;
        $wrapper.append('<div class="az-calendar-filter-buttons"></div><div class="az-calendar-filter-calendar"></div>');
        var $buttonWrapper = $wrapper.children(".az-calendar-filter-buttons");
        var $calendar = $wrapper.children(".az-calendar-filter-calendar");
        var $submitButton = $wrapper.closest(".views-exposed-form").find("button.form-submit");
        var task = null;

        // Function to update a filter's internal date fields from datepicker.
        function updateCalendarFilters(startDate, endDate) {
          var $ancestor = $wrapper.closest(".views-widget-az-calendar-filter");

          var dates = [startDate, endDate];
          for (var i = 0; i < dates.length; i++) {
            var month = dates[i].getMonth() + 1;
            var day = dates[i].getDate();
            var year = dates[i].getFullYear();
            $ancestor.find('input').eq(i).val(year + "-" + month + "-" + day);
          }

          // Signal to UI that the inputs were updated programmatically.
          triggerFilterChange($ancestor, 0);
          $ancestor.find('.btn').removeClass('active').attr('aria-pressed', 'false');
          var $form = $wrapper.closest("form");
        }

        // Set task to trigger filter element change.
        function triggerFilterChange($ancestor, delay) {
          if (task != null) {
            clearTimeout(task);
          }
          task = setTimeout(function(){
            // Only trigger if submit buttion isn't disabled.
            if (!$submitButton.prop("disabled")) {
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

        // Initialize the calendar datepicker options.
        $calendar.datepicker({
          dateFormat: "m-d-yy",
          showOtherMonths: true,
          selectOtherMonths: true,
          dayNamesMin: [ "S", "M", "T", "W", "T", "F", "S" ],
          beforeShowDay: function(date){
            // Loop through date ranges to determine if a day qualifies.
            var dateClass = "calendar-filter-day-no-events";
            var time = date.getTime();
            var withinRange = false;
            // Check if the date is within the selection window.
            if (rangeStart && rangeEnd) {
              if ((rangeStart <= time) && (rangeEnd >= time)) {
                withinRange = true;
                // Highlight a single-day range even if it has no events.
                if (rangeStart == rangeEnd) {
                  return [true, "calendar-filter-window"];
                }
              }
            }
            // Check if the cell information encapsulates this date.
            if (drupalSettings.calendarFilterRanges.hasOwnProperty(rangeKey)) {
              var ranges = drupalSettings.calendarFilterRanges[rangeKey];
                for (var i = 0; i < ranges.length; i++) {
                  if ((ranges[i][0].getTime() <= time) &&
                    (ranges[i][1].getTime() >= time))
                    {
                      dateClass = withinRange ? "calendar-filter-window" : "calendar-filter-day-events";
                    }
                }
            }
            return [true, dateClass];
          },
          onChangeMonthYear: function(year, month, inst){
            // When the month is changed, update the date input fields.
            var startDay = new Date(year, month - 1, 1);
            var endDay = new Date(year, month, 0);
            rangeStart = null;
            rangeEnd = null;
            updateCalendarFilters(startDay, endDay);
          },
          onSelect: function(datetext, inst){
            // When a day is selected, update the date input fields.
            var newDate = $.datepicker.parseDate( "m-d-yy", datetext );
            rangeStart = newDate.getTime();
            rangeEnd = newDate.getTime();
            updateCalendarFilters(newDate, newDate);
          }
        });
        $calendar.children(".ui-corner-all" ).removeClass("ui-corner-all");

        // Create the range selection buttons.
        $buttonWrapper.append('<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-today btn-block">Today</button>');
        $buttonWrapper.append('<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-week btn-block">This Week</button>');
        $buttonWrapper.append('<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-month btn-block mb-2">This Month</button>');

        // Handle button presses for calendar range selection buttions.
        $buttonWrapper.children('.calendar-filter-button').on( "click", function() {
          var $pressed = $(this);
          var current = new Date(Date.now());
          var today = new Date(current.getFullYear(), current.getMonth(), current.getDate());
          var month = current.getMonth();
          var year = current.getFullYear();
          var day = current.getDay();
          var diff = current.getDate() - day;
          var startDay = today;
          var endDay = today;
          if ($pressed.hasClass('calendar-filter-week')) {
            // Compute start and end days of the week.
            startDay = new Date(year, month, diff);
            endDay = new Date(year, month, diff + 6);
          }
          else if ($pressed.hasClass('calendar-filter-month')) {
            // Compute start and end days of the month.
            startDay = new Date(year, month, 1);
            endDay = new Date(year, month + 1, 0);
          }
          $calendar.datepicker('setDate', startDay);
          $calendar.datepicker('setDate', null);
          rangeStart = startDay.getTime();
          rangeEnd = endDay.getTime();
          updateCalendarFilters(startDay, endDay);
          $(".az-calendar-filter-calendar" ).datepicker( "refresh" );
          $pressed.addClass('active').attr('aria-pressed', 'true');
        });
      });
    }

  };

})(jQuery, Drupal, drupalSettings);
