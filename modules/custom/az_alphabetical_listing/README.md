# Quickstart Alphabetical Listing

Enabling the Quickstart Alphebetical Listing module adds the following to your Quickstart site:

- Field `field_az_alphabetical_index` | A field of type list, which contains all 26 letters of the alphabet as well as a number option.
- View `Quickstart Alphabetical Listing` | A view that pulls in content where the `field_az_alphabetical_index` value is filled in.

You must have administrator permissions in order to enable and configure the field and accompanying view.

## Adding the Alphabetical Index Field to Your Content Type

1. Navigate to `Structure > Content types > [your content type] > Manage fields`
2. Click "Add field"
3. From the "Re-use an existing field" dropdown, select `List (text): field_az_alphabetical_index` and provide a field label (recommended: `Alphabetical Index Letter`)
4. Save your changes
5. Modify the form display to place the Index letter field where you would like it to display
6. Modify the main/default display of your content type to disable the index letter field from view

## Configuring the Alphabetical Index View Mode

1. Navigate to `Structure > Content types > [your content type] > Manage Display`
2. Expand the "Custom display settings" accordion at the bottom of the page and enable the `Alphabetical Listing view mode`


## Leveraging the View

