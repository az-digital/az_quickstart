# Calendar Link

This module, provides two Twig functions to generate links for various
calendaring services.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/calendar_link).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/calendar_link).


## Table of contents

- Requirements
- Installation
- Configuration
- Usage
- Views support
- Examples
- Maintainers
- Sponsors


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

There is no configuration available for this module.


## Usage

This module provides two new Twig functions to generate calendar link URLs.

1. `calendar_link`
   Returns a string link for a specific calendar type. Available types are:

    - Apple iCal/Microsoft Outlook (`ics`)
    - Google calendar (`google`)
    - Outlook.com (`webOutlook`)
    - Yahoo! calendar (`yahoo`)

1. `calendar_links`
   Returns an array of links for all available calendar types. Each array
   element has the following keys/data:

    - `type_key`: The calendar type key (`ics`, `google`, etc.)
    - `type_name`: The calendar type name ("iCal", "Google", etc.)
    - `url`: The URL for the calendar item.


## Views support

When using values from Views results, only the default formatter for date
fields is supported. Most other date field formatters do not provide necessary
timezone data in rendered results to ensure correctness of the generated
calendar links. See
[Views support](https://www.drupal.org/project/calendar_link/issues/3249457)
for further details and discussion.


## Examples

Assume an example "Event" node with the extras fields:

- Title (string `title`)
- Start date/time (datetime `field_start`)
- End date/time (datetime `field_end`)
- All day event (boolean `field_all_day`)
- Description (text_format `body`)
- Location (string `field_location`)

In a template file, add the following code to generate a link to the event to a
Google calendar:

```twig
{% set link = calendar_link('google',
  node.title,
  node.field_start,
  node.field_end,
  node.field_all_day,
  node.body,
  node.field_location
)
%}
<a href="{{ link }}">Add to Google</a>
```

Or, add the following code to create a list of links for each service:

```twig
{% set links = calendar_links(
  node.title,
  node.field_start,
  node.field_end,
  node.field_all_day,
  node.body,
  node.field_location
)
%}
<ul>
{% for link in links %}
  <li>
    <a href="{{ link.url }}"
       class="calendar-link-{{ link.type_key }}">{{ link.type_name }}</a>
  </li>
{% endfor %}
</ul>
```


## Maintainers

- Christopher C. Wells - [wells](https://www.drupal.org/u/wells)


## Sponsors

- [Cascade Public Media](https://www.drupal.org/cascade-public-media)
