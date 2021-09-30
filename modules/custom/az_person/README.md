# AZ Person README

## Displays

The AZ Person content type display cannot be modified via the Drupal UI, as the content type utilizes templates. There are three displays for the content type:

- Default (full)
- Card
- Row

## Support Considerations & Future Additions

## Adding New Fields

If additional fields are added to the Person content type in the future, you will need to update the `core.entity_form_display.node.az_person.default` file in two locations for the added field(s) to take effect fully.

The AZ SEO module is enabled by default on Quickstart 2.0 sites. This module contains an additional overridden `core.entity_form_display.node.az_person.default` file located in `az_seo/config/quickstart`. This means you will need to update the `core.entity_form_display.node.az_person.default` file in both the `az_person` and `az_seo` modules. The difference being the `az_person` version of the file should not contain any `az_metatag` configuration; and the `az_seo` module should contain the `az_metatag` configuration.