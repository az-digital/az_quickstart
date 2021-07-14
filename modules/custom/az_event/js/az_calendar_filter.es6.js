/**
 * @file
 * A JavaScript file for the datepicker calendar functionality.
 *
 */

(($, Drupal, drupalSettings) => {
  Drupal.behaviors.azCalendarFilter = {
    attach(context, settings) {
      const filterInformation = drupalSettings.azCalendarFilter;
      if (!drupalSettings.hasOwnProperty("calendarFilterRanges")) {
        drupalSettings.calendarFilterRanges = [];
      }

      // Drupal settings get merged rather than replaced during ajax.
      // We should clear out stale entries when we process a new cells.
      drupalSettings.azCalendarFilter = {};
      settings.azCalendarFilter = {};

      // Process cell date strings into javascript dates.
      Object.keys(filterInformation).forEach(property => {
        if (filterInformation.hasOwnProperty(property)) {
          drupalSettings.calendarFilterRanges[property] = [];
          const ranges = filterInformation[property];
          for (let i = 0; i < ranges.length; i++) {
            drupalSettings.calendarFilterRanges[property].push([
              $.datepicker.parseDate("@", ranges[i][0] * 1000),
              $.datepicker.parseDate("@", ranges[i][1] * 1000)
            ]);
          }
        }
      });

      // We may have recieved new cell data. Refresh existing datepickers.
      $(".az-calendar-filter-calendar").datepicker("refresh");

      // Initialize calendar widget wrapper if needed.
      $(".az-calendar-filter-wrapper", context)
        .once("azCalendarFilter")
        // eslint-disable-next-line func-names
        .each(function() {
          const $wrapper = $(this);
          // rangeKey contains our filter identifier to find calendar cell data.
          const rangeKey = $wrapper.data("az-calendar-filter");
          let rangeStart = null;
          let rangeEnd = null;
          $wrapper.append(
            '<div class="az-calendar-filter-buttons"></div><div class="az-calendar-filter-calendar"></div>'
          );
          const $buttonWrapper = $wrapper.children(
            ".az-calendar-filter-buttons"
          );
          const $calendar = $wrapper.children(".az-calendar-filter-calendar");
          const $submitButton = $wrapper
            .closest(".views-exposed-form")
            .find("button.form-submit");
          let task = null;

          // Set task to trigger filter element change.
          function triggerFilterChange($ancestor, delay) {
            if (task != null) {
              clearTimeout(task);
            }
            task = setTimeout(() => {
              // Only trigger if submit buttion isn't disabled.
              if (!$submitButton.prop("disabled")) {
                $ancestor
                  .find("input")
                  .eq(0)
                  .change();
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

          // Function to update a filter's internal date fields from datepicker.
          function updateCalendarFilters(startDate, endDate) {
            const $ancestor = $wrapper.closest(
              ".views-widget-az-calendar-filter"
            );

            const dates = [startDate, endDate];
            for (let i = 0; i < dates.length; i++) {
              const month = dates[i].getMonth() + 1;
              const day = dates[i].getDate();
              const year = dates[i].getFullYear();
              $ancestor
                .find("input")
                .eq(i)
                .val(`${year}-${month}-${day}`);
            }

            // Signal to UI that the inputs were updated programmatically.
            triggerFilterChange($ancestor, 0);
            $ancestor
              .find(".btn")
              .removeClass("active")
              .attr("aria-pressed", "false");
          }

          // Initialize the calendar datepicker options.
          $calendar.datepicker({
            dateFormat: "m-d-yy",
            showOtherMonths: true,
            selectOtherMonths: true,
            dayNamesMin: ["S", "M", "T", "W", "T", "F", "S"],
            beforeShowDay(date) {
              // Loop through date ranges to determine if a day qualifies.
              let dateClass = "calendar-filter-day-no-events";
              const time = date.getTime();
              let withinRange = false;
              // Check if the date is within the selection window.
              if (rangeStart && rangeEnd) {
                if (rangeStart <= time && rangeEnd >= time) {
                  withinRange = true;
                  // Highlight a single-day range even if it has no events.
                  if (rangeStart === rangeEnd) {
                    return [true, "calendar-filter-window"];
                  }
                }
              }
              // Check if the cell information encapsulates this date.
              if (
                drupalSettings.calendarFilterRanges.hasOwnProperty(rangeKey)
              ) {
                const ranges = drupalSettings.calendarFilterRanges[rangeKey];
                for (let i = 0; i < ranges.length; i++) {
                  if (
                    ranges[i][0].getTime() <= time &&
                    ranges[i][1].getTime() >= time
                  ) {
                    dateClass = withinRange
                      ? "calendar-filter-window"
                      : "calendar-filter-day-events";
                  }
                }
              }
              return [true, dateClass];
            },
            onChangeMonthYear(year, month) {
              // When the month is changed, update the date input fields.
              const startDay = new Date(year, month - 1, 1);
              const endDay = new Date(year, month, 0);
              rangeStart = null;
              rangeEnd = null;
              updateCalendarFilters(startDay, endDay);
            },
            onSelect(datetext) {
              // When a day is selected, update the date input fields.
              const newDate = $.datepicker.parseDate("m-d-yy", datetext);
              rangeStart = newDate.getTime();
              rangeEnd = newDate.getTime();
              updateCalendarFilters(newDate, newDate);
            }
          });
          $calendar.children(".ui-corner-all").removeClass("ui-corner-all");

          // Create the range selection buttons.
          $buttonWrapper.append(
            '<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-today btn-block">Today</button>'
          );
          $buttonWrapper.append(
            '<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-week btn-block">This Week</button>'
          );
          $buttonWrapper.append(
            '<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-month btn-block mb-2">This Month</button>'
          );

          // Handle button presses for calendar range selection buttions.
          $buttonWrapper.children(".calendar-filter-button").on("click", e => {
            const $pressed = $(e.currentTarget);
            const current = new Date(Date.now());
            const today = new Date(
              current.getFullYear(),
              current.getMonth(),
              current.getDate()
            );
            const month = current.getMonth();
            const year = current.getFullYear();
            const day = current.getDay();
            const diff = current.getDate() - day;
            let startDay = today;
            let endDay = today;
            if ($pressed.hasClass("calendar-filter-week")) {
              // Compute start and end days of the week.
              startDay = new Date(year, month, diff);
              endDay = new Date(year, month, diff + 6);
            } else if ($pressed.hasClass("calendar-filter-month")) {
              // Compute start and end days of the month.
              startDay = new Date(year, month, 1);
              endDay = new Date(year, month + 1, 0);
            }
            $calendar.datepicker("setDate", startDay);
            $calendar.datepicker("setDate", null);
            rangeStart = startDay.getTime();
            rangeEnd = endDay.getTime();
            updateCalendarFilters(startDay, endDay);
            $(".az-calendar-filter-calendar").datepicker("refresh");
            $pressed.addClass("active").attr("aria-pressed", "true");
          });
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
