/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.azCalendarFilter = {
    attach: function attach(context, settings) {
      var filterInformation = drupalSettings.azCalendarFilter;
      if (!drupalSettings.hasOwnProperty('calendarFilterRanges')) {
        drupalSettings.calendarFilterRanges = [];
      }
      drupalSettings.azCalendarFilter = {};
      settings.azCalendarFilter = {};
      Object.keys(filterInformation).forEach(function (property) {
        if (filterInformation.hasOwnProperty(property)) {
          drupalSettings.calendarFilterRanges[property] = [];
          var ranges = filterInformation[property];
          for (var i = 0; i < ranges.length; i++) {
            drupalSettings.calendarFilterRanges[property].push([$.datepicker.parseDate('@', ranges[i][0] * 1000), $.datepicker.parseDate('@', ranges[i][1] * 1000)]);
          }
        }
      });
      $('.az-calendar-filter-calendar').datepicker('refresh');
      $(once('azCalendarFilter', '.az-calendar-filter-wrapper', context)).each(function () {
        var $wrapper = $(this);
        var rangeKey = $wrapper.data('az-calendar-filter');
        var rangeStart = null;
        var rangeEnd = null;
        $wrapper.append('<div class="az-calendar-filter-buttons"></div><div class="az-calendar-filter-calendar"></div>');
        var $buttonWrapper = $wrapper.children('.az-calendar-filter-buttons');
        var $calendar = $wrapper.children('.az-calendar-filter-calendar');
        var $submitButton = $wrapper.closest('.views-exposed-form').find('button.form-submit');
        var $dropDown = $wrapper.closest('.views-exposed-form').find('.form-select');
        var task = null;
        function triggerFilterChange($ancestor, delay) {
          if (task != null) {
            clearTimeout(task);
          }
          task = setTimeout(function () {
            if (!$submitButton.prop('disabled')) {
              $ancestor.find('input').eq(0).change();
              $submitButton.click();
              task = null;
            } else {
              triggerFilterChange($ancestor, 200);
            }
          }, delay);
        }
        $dropDown.on('change', function () {
          var $ancestor = $wrapper.closest('.views-widget-az-calendar-filter');
          triggerFilterChange($ancestor, 0);
        });
        function updateCalendarFilters(startDate, endDate) {
          var $ancestor = $wrapper.closest('.views-widget-az-calendar-filter');
          var dates = [startDate, endDate];
          for (var i = 0; i < dates.length; i++) {
            var month = dates[i].getMonth() + 1;
            var day = dates[i].getDate();
            var year = dates[i].getFullYear();
            $ancestor.find('input').eq(i).val("".concat(year, "-").concat(month, "-").concat(day));
          }
          triggerFilterChange($ancestor, 0);
          $ancestor.find('.btn').removeClass('active').attr('aria-pressed', 'false');
        }
        var $inputWrapper = $wrapper.closest('.views-widget-az-calendar-filter');
        var initial = $inputWrapper.find('input').eq(0).val();
        var calendarInitialDay = new Date();
        if (typeof initial !== 'undefined') {
          var initialDates = initial.split('-');
          if (initialDates.length === 3) {
            calendarInitialDay = new Date(initialDates[0], initialDates[1] - 1, initialDates[2]);
          }
        }
        $calendar.datepicker({
          dateFormat: 'm-d-yy',
          showOtherMonths: true,
          selectOtherMonths: true,
          defaultDate: calendarInitialDay,
          dayNamesMin: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
          beforeShowDay: function beforeShowDay(date) {
            var dateClass = 'calendar-filter-day-no-events';
            var time = date.getTime();
            var withinRange = false;
            if (rangeStart && rangeEnd) {
              if (rangeStart <= time && rangeEnd >= time) {
                withinRange = true;
                if (rangeStart === rangeEnd) {
                  return [true, 'calendar-filter-window'];
                }
              }
            }
            if (drupalSettings.calendarFilterRanges.hasOwnProperty(rangeKey)) {
              var ranges = drupalSettings.calendarFilterRanges[rangeKey];
              for (var i = 0; i < ranges.length; i++) {
                if (ranges[i][0].getTime() <= time && ranges[i][1].getTime() >= time) {
                  dateClass = withinRange ? 'calendar-filter-window' : 'calendar-filter-day-events';
                }
              }
            }
            return [true, dateClass];
          },
          onChangeMonthYear: function onChangeMonthYear(year, month) {
            var startDay = new Date(year, month - 1, 1);
            var endDay = new Date(year, month, 0);
            rangeStart = null;
            rangeEnd = null;
            updateCalendarFilters(startDay, endDay);
          },
          onSelect: function onSelect(datetext) {
            var newDate = $.datepicker.parseDate('m-d-yy', datetext);
            rangeStart = newDate.getTime();
            rangeEnd = newDate.getTime();
            updateCalendarFilters(newDate, newDate);
          }
        });
        $calendar.children('.ui-corner-all').removeClass('ui-corner-all');
        $buttonWrapper.append('<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-today btn-block">Today</button>');
        $buttonWrapper.append('<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-week btn-block">This Week</button>');
        $buttonWrapper.append('<button type="button" class="btn btn-hollow-primary calendar-filter-button calendar-filter-month btn-block mb-2">This Month</button>');
        $buttonWrapper.children('.calendar-filter-button').on('click', function (e) {
          var $pressed = $(e.currentTarget);
          var current = new Date(Date.now());
          var today = new Date(current.getFullYear(), current.getMonth(), current.getDate());
          var month = current.getMonth();
          var year = current.getFullYear();
          var day = current.getDay();
          var diff = current.getDate() - day;
          var startDay = today;
          var endDay = today;
          if ($pressed.hasClass('calendar-filter-week')) {
            startDay = new Date(year, month, diff);
            endDay = new Date(year, month, diff + 6);
          } else if ($pressed.hasClass('calendar-filter-month')) {
            startDay = new Date(year, month, 1);
            endDay = new Date(year, month + 1, 0);
          }
          $calendar.datepicker('setDate', startDay);
          $calendar.datepicker('setDate', null);
          rangeStart = startDay.getTime();
          rangeEnd = endDay.getTime();
          updateCalendarFilters(startDay, endDay);
          $('.az-calendar-filter-calendar').datepicker('refresh');
          $pressed.addClass('active').attr('aria-pressed', 'true');
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings, once);