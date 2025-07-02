(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.smartDateLocalize = {
    attach(context, settings) {
      /**
       * Return a formatted string from a date Object mimicking PHP's date() functionality
       *
       * format  string  "Y-m-d H:i:s" or similar PHP-style date format string
       * date    mixed   Date Object, Datestring, or milliseconds
       *
       */
      function date(format, date) {
        if (!date || date === '') date = new Date();
        else if (!(date instanceof Date))
          date = new Date(date.replace(/-/g, '/')); // attempt to convert string to date object

        const locale = document.documentElement.lang;

        let string = '';
        const mo = date.getMonth(); // month (0-11)
        const m1 = mo + 1; // month (1-12)
        const dow = date.getDay(); // day of week (0-6)
        const d = date.getDate(); // day of the month (1-31)
        const y = date.getFullYear(); // 1999 or 2003
        const h = date.getHours(); // hour (0-23)
        const mi = date.getMinutes(); // minute (0-59)
        const s = date.getSeconds(); // seconds (0-59)
        const tzs = Intl.DateTimeFormat(locale, {
          timeZoneName: 'short',
          weekday: 'short',
          month: 'short',
        });
        const tzl = Intl.DateTimeFormat(locale, {
          timeZoneName: 'long',
          weekday: 'long',
          month: 'long',
        });

        // This needs refactoring anyway so skip ESLint error for new.
        // eslint-disable-next-line no-restricted-syntax
        for (let i of format.match(/(\\)*./g))
          switch (i) {
            case 'j': // Day of the month without leading zeros  (1 to 31)
              string += d;
              break;

            case 'S': // English ordinal suffix for the day of the month, 2 characters
              switch (d.toString().slice(-1)) {
                case '1':
                  string += d === 11 ? 'th' : 'st';
                  break;

                case '2':
                  string += d === 12 ? 'th' : 'nd';
                  break;

                case '3':
                  string += d === 13 ? 'th' : 'rd';
                  break;

                default:
                  string += 'th';
                  break;
              }
              break;

            case 'd': // Day of the month, 2 digits with leading zeros (01 to 31)
              string += d < 10 ? `0${d}` : d;
              break;

            case 'l': // (lowercase 'L') A full textual representation of the day of the week
              string += tzl
                .formatToParts(date)
                .find((part) => part.type === 'weekday').value;
              break;

            case 'w': // Numeric representation of the day of the week (0=Sunday,1=Monday,...6=Saturday)
              string += dow;
              break;

            case 'D': // A textual representation of a day, three letters
              string += tzs
                .formatToParts(date)
                .find((part) => part.type === 'weekday').value;
              break;

            case 'm': // Numeric representation of a month, with leading zeros (01 to 12)
              string += m1 < 10 ? `0${m1}` : m1;
              break;

            case 'n': // Numeric representation of a month, without leading zeros (1 to 12)
              string += m1;
              break;

            case 'F': // A full textual representation of a month, such as January or March
              string += tzl
                .formatToParts(date)
                .find((part) => part.type === 'month').value;
              break;

            case 'M': // A short textual representation of a month, three letters (Jan - Dec)
              string += tzs
                .formatToParts(date)
                .find((part) => part.type === 'month').value;
              break;

            case 'Y': // A full numeric representation of a year, 4 digits (1999 OR 2003)
              string += y;
              break;

            case 'y': // A two digit representation of a year (99 OR 03)
              string += y.toString().slice(-2);
              break;

            case 'H': // 24-hour format of an hour with leading zeros (00 to 23)
              string += h < 10 ? `0${h}` : h;
              break;

            case 'g': // 12-hour format of an hour without leading zeros (1 to 12)
              // eslint-disable-next-line no-case-declarations
              const hourNoZero = h === 0 ? 12 : h;
              string += hourNoZero > 12 ? hourNoZero - 12 : hourNoZero;
              break;

            case 'h': // 12-hour format of an hour with leading zeros (01 to 12)
              // eslint-disable-next-line no-case-declarations
              let hour = h === 0 ? 12 : h;
              hour = hour > 12 ? hour - 12 : hour;
              string += hour < 10 ? `0${hour}` : hour;
              break;

            case 'a': // Lowercase Ante meridiem and Post meridiem (am or pm)
              string += h < 12 ? 'am' : 'pm';
              break;

            case 'i': // Minutes with leading zeros (00 to 59)
              string += mi < 10 ? `0${mi}` : mi;
              break;

            case 's': // Seconds, with leading zeros (00 to 59)
              string += s < 10 ? `0${s}` : s;
              break;

            case 'c': // ISO 8601 date (eg: 2012-11-20T18:05:54.944Z)
              string += date.toISOString();
              break;

            case 'T': // Timezone identifier (abbreviation)
              string += tzs
                .formatToParts(date)
                .find((part) => part.type === 'timeZoneName').value;
              break;

            case 'e': // Timezone identifier (abbreviation)
              string += tzl
                .formatToParts(date)
                .find((part) => part.type === 'timeZoneName').value;
              break;

            default:
              if (i.startsWith('\\')) i = i.substr(1);
              string += i;
          }

        return string;
      }

      function localizeDate(element) {
        if (!element.dataset.format || !element.dateTime) {
          return;
        }
        const when = new Date(element.dateTime);
        const offset = element.dataset.tzoffset;

        // Check if timezones are different.
        if (when.getTimezoneOffset() === offset) {
          return;
        }

        element.textContent = date(element.dataset.format, when);
      }

      // Update the end values when the start is changed.
      once('smartDateLocalize', 'time.smart-date--localize', context).forEach(
        function (element) {
          localizeDate(element);
        },
      );
    },
  };
})(Drupal, drupalSettings, once);
