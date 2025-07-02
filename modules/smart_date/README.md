# Smart Date

This module attempts to provide a more user-friendly date field, by upgrading
the functionality in core in a number of ways:

- Admin UI: Includes the concept of duration, so that a field can have a default
  duration (e.g. 1 hour) and the end time will be auto-populated based on the
  start. The overall goal is to provide a smart interface for time range/event
  data entry, more inline with calendar applications which editors will be
  familiar with.

- All Day Events Most calendar applications provide a one-click option to make
  a an event, appointment, or other time-related content span a full day. This
  module brings that same capability to Drupal.

- Formatting: More sophisticated output formatting, for example to show the
  times as a range but with a single output of the date. In the settings a site
  builder can control how date the ranges will be output, at a very granular
  level.

- Performance: Dates are stored as timestamps to improve performance, especially
  when filtering or sorting.

Overall, the approach in this module is to leverage core's existing Datetime
functionality, using the timestamp storage capability also in core, with some
custom Javascript to add intelligence to the admin interface, and a suite of
options to ensure dates can be formatted to suit any site's needs.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/smart_date).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/smart_date).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. To use the field provided by this module, add a new field to your content
   type, for example at:
   Administration » Structure » Content Types » Event » Manage Fields
2. `"Smart date range"` will be listed in the Date and time section of field options.
3. In the settings, you have the option to specify:
    - a default date and time, either a fixed value (such as the start of a
      conference) or a relative date (such as '+1 Saturday').
    - Duration increments: These are the values (in minutes) that will be made
      available to editors creating content of this type. For example, if a
      conference will have only 30 and 60 minutes sessions, you can restrict
      authors to only use these values by making them the only ones listed. If
      your durations include the lower case word 'custom' then it will also be
      possible to define a custom duration by specifying the end time. If not,
      the end date and time fields will be read only. Note that the increments
      must be provided as a comma-separated string. Also note that to allow for
      the use of the `"All day"` functionality for editors, it is necessary to
      allow either a custom increment, or a value of 1439, which Smart Date uses
      for all day events, but which will only allow for single-day events.
    - Default duration: Define which of the provided duration increments should
      be used by default, so that the end date and time can be automatically
      defined as the start values are populated.
4. This module also provides a rich set of options for display configuration,
   which can be accessed in the `"Manage display"` tab of your content type.
   Smart Date Formats are a new configuration entity that gives you granular
   control of the formatting of your date and time ranges, and can be
   translated, to provide optimal structure for each language. On install a set
   of defaults are provided, but these can be edited, or new formats created.
    - In the row for your Smart Date field, click the cogwheel at the far right.
    - Within the dropdown select the format you want to use for output.

5. To edit or create a format, go to Administration » Regional and language
   » Smart date formats. There you can click the edit button on any row to
   reconfigure an existing format, or click add at the top to create a new one.
    - PHP date and time formats are specified separately, because the module
      attempts to intelligently display a more compact output when possible. For
      example when the start and end are on the same day, the date will only be
      shown once. If you're not familiar with the syntax for specifying PHP date
      strings, consult the reference at:
      `https://www.php.net/manual/en/function.date.php`
    - You can specify what will be used to separate the date and time, and
      between the start and end values. You can also specify whether the time or
      date should be displayed first.
    - You can also determine how you want all day events to display. You can
      specify a string such as "All day" or if nothing is provided only the date
      will be shown.
    - You can choose to omit either the time or date format, as in the default
      formats `"Time only"` or `"Date only"`. You must provide one or the other,
      or else nothing would be displayed.


## Maintainers
[//]: # cSpell:disable
[//]: # Do not add maintainers to cspell-project-words file


- Martin Anderson-Clutz - [mandclu](https://www.drupal.org/u/mandclu)
- Stefan Korn - [stefan.korn](https://www.drupal.org/u/stefankorn)
