# Quickstart Alphabetical Listing

Enabling the Quickstart Alphebetical Listing module adds the following configuration options to your Quickstart site:

- Field `field_az_alphabetical_index` | A field of type list, which contains all 26 letters of the alphabet as well as a number option.
- View `Quickstart Alphabetical Listing` | A view that pulls in content where the `field_az_alphabetical_index` value is filled in.

You must have administrator permissions in order to enable the module and configure the field and accompanying view.

________________________

## Configuring the Alphabetical List on Your Site

### 1.) Add the Alphabetical Index Field to Your Content Type

1. Navigate to `Structure > Content types > [your content type] > Manage fields`
2. Click "Add field"
3. From the "Re-use an existing field" dropdown, select `List (text): field_az_alphabetical_index` and provide a field label (recommended: `Alphabetical Index Letter`)
4. Save your changes
5. Modify the form display to place the Alphabetical Index field where you would like it to display
6. Modify the main/default display of your content type to disable Alphabetical Index field from the display

### 2.) Enable the Alphabetical Index View Mode

In order for the Alphabetical Listing display to work properly, you must enable the view mode on your content type. The display will be automatically configured by the provided node template (no changes are required in the view mode apart from enabling it).

1. Navigate to `Structure > Content types > [your content type] > Manage Display`
2. Expand the "Custom display settings" accordion at the bottom of the page and enable the `Alphabetical Listing view mode`

### 3.) Leverage the View

The view that is provided by the Alphabetical Listing module can be configured to meet your site's use case, such as narrowing the scope to specific content types. The view is already configured to have the necessary classes, so no additional changes are required for the view to work unless you want to modify.

### (Optional) Enable the View on the Page Content Type

If you want to embed the Alphabetical Listing view on a page, you will need to enable the view on View pargraph type.

1. Navigate to `Structure > Paragraph types > View > Manage fields` and click "edit" on the View field
2. Under "Preselect View Options", check the box for `AZ Alphabetical Listing`
3. Save your changes

________________________

## Migration Considerations

For sites migrating from Quickstart 1.0 to 2.0, fields can be mapped from `field_uaqs_index_letter` to `field_az_alphabetical_index` respectively. The select list option keys remain consistent between the two different versions of Quickstart.
