## prepTime, cookTime and totalTime fields:

Use duration_field module to create these fields. This module stores time values
and provides tokens in ISO 8601 duration format, which is required for JSON-LD
validation at Google.

## recipeIngredient and recipeInstructions:

Using a simple text field with many allowed values works great here. You can
then use the basic token, e.g. [node:field_ingredient], and if it contains
multiple values the resulting JSON-LD will be correctly formatted automatically.
Theming will also be simple.
