# Quickstart Icons
This module leverages the [UI Icons contrib module](https://www.drupal.org/project/ui_icons) to allow site editors to incorporate icons into their site's content. For additional documentation resources, please refer to the [Icon API docs](https://www.drupal.org/docs/develop/drupal-apis/icon-api).

## Icons packs

### Adding icon packs
Icons packs are registered via the `az_icons.icons.yml` file.

- Ensure you provide a unique ID for the new icon pack (i.e., `arizona_icons`, `material_icons`)
- Refer to the Icon API docs for configuration options, requirements, and settings

### Currently registered icon packs
- [Material Symbols Rounded](https://fonts.google.com/icons?icon.style=Rounded&icon.set=Material+Symbols&icon.size=24&icon.color=%23e3e3e3) _([Codepoints file](https://github.com/google/material-design-icons/blob/master/variablefont/MaterialSymbolsRounded%5BFILL%2CGRAD%2Copsz%2Cwght%5D.codepoints) provided by Material Symbols GitHub repo.)_
- [Arizona Icons](https://digital.arizona.edu/arizona-bootstrap/v5/docs/5.0/icons/)

## CKEditor & text formats
After registering a new icon pack in the `az_icons.icons.yml` file, you must enable the icon pack in the text format settings (`admin/config/content/formats/manage/<some_format>`). The checkbox is located under **Filter settings > Embed icon > Icon Pack selectable**.

Icons are currently available for use in the `Standard` text format.


## Formatting icons
- You can change the size of an icon at the moment you are embedding it.
- You can change the color of an icon by highlighting your icon and selecting a text color from the the "Styles" dropdown in the WYSIWYG editor.

**Note:** once an icon is placd in the editor, it cannot be modified the way media objects can; you must delete the icon and re-add it.