### Blazy
If you copy paste the lines here into CKEditor, be sure to view CKEditor
source and remove the surrounding `<code>...</code>`, in case mistakenly copied
over.  
Image or iframe is lazyloaded automatically unless disabled, no shortcodes
required. Usages for shortcodes: grid, customizing settings, embedding a known
entity. Pay attention to attributes, slashes, colons, single and double quotes:

1. **Basic**, with inline HTML:   
   `[blazy]...[item]...[/item]... [/blazy]`  
   where `[blazy]` is like `.field` container, `[item]` like `.field__item`.
2. **With self-closing** `data=ENTITY_TYPE:ID:FIELD_NAME:FIELD_IMAGE`, without
   inline HTML:  
   `[blazy data="node:44:field_media" /]`  
   `[blazy data="node:44:field_media:field_media_image" /]`  
   _Required_: `ENTITY_TYPE:ID:FIELD_NAME`, where `ENTITY_TYPE` is *node* --
   only tested with node, `ID` is *node ID*, `FIELD_NAME` can be field Media,
   Image, Text (long or with summary), must be multi-value, or unlimited.  
   _Optional_: `FIELD_IMAGE` named `field_media_image` (not `field_image`) as
   found at Media Image/ Video for hires poster image, must be similar and
   single-value field image for **all** media entities to have mixed media
   correctly.  
   **Repeat**, not `field_image` at Node, it is `field_media_image` at Media.
3. **With HTML settings**: any HTML settings relevant from `BlazyDefault`
   methods as seen at Filter, Field or Views UI forms:  
   `[blazy settings="{'style': 'column', 'grid': 4}" /]`
4. **Attributes**: The `[item]` can have class and caption attributes, e.g.:  
   `[item
   class="grid--card card"
   caption='Read <a href="https://mysite.com">more</a>'
   title="Awesome title"]`  
   The classes will be moved into `grid__content` to make it usable
   such as for Bootstrap well/ card. The caption and title into regular
   `blazy__caption`.  
   Use enclosing *single* quotes for HTML caption so you can have double
   quotes inside such as enclosing link HREF quotes, else broken. The link is
   normally converted automatically when using WYSIWYG. This will replace Filter
   caption if they both exist.
5. **Grid format**, a convenient shortcut for settings, hyphenated (`-`):  
   `STYLE:SMALL-MEDIUM-LARGE`  
    `STYLE` is only one of these: `column grid flex nativegrid`.  
    The rest refers to device sizes. Only `LARGE` with `nativegrid`, no others,
    can be numeric, or string. The rest must be numbers due to using no JS and
    or their contracts. The minimum is 1, not 0 (1-12 columns).  
    * `[blazy grid="column:2-3-4" /]`  
    * `[blazy grid="grid:2-3-4" /]`  
    * `[blazy grid="flex:2-3-4" /]`  
    * `[blazy grid="nativegrid:2-3-4" /]`, will be masonry  
    * `[blazy grid="nativegrid:2-3-4x4" /]`, will repeat  
    * `[blazy grid="nativegrid:2-3-4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2" /]`  
      is a shortcut for:  
      <code>
      [blazy settings="{<br>
        'style': 'nativegrid',<br>
        'grid_small': 2,<br>
        'grid_medium': 3,<br>
        'grid': '4x4 ...'<br>
      }" /]     
      </code>
    * `4x4` defines `WIDTHxHEIGHT` based on `grid-row` CSS property, see and
      override `blazy/css/components/grid/blazy.nativegrid.css`.
6. **Skipping**: to disable lazyload, add attribute `data-unblazy`:  
   * `<img data-unblazy />`
   * `<iframe data-unblazy />`
7. **[DEPRECATED]**, cannot co-exist: To build a grid of images/ videos, add
   attribute `data-grid` or `data-column` (only to the first item):  
   * `<img data-grid="1 3 4" />`
   * `<iframe data-column="1 3 4" />`  
     The numbers represent the amount of grids/ columns for small, medium and
     large devices respectively, space delimited. Be aware! All media items will
     be grouped regardless of their placements. That's why deprecated.

**Tips**, if any issues:  
* To set and forget, do not use `[blazy]` shortcode. It is only useful for grid,
  or customizing settings, or embedding a known entity.
* Attributes `data, settings` can be put together into one `[blazy]`.
* If the enclosing quotes are single, the inner ones, if any, must be double,
  and vice versa.
* `IMG/ IFRAME`, or other HTML as item contents can be wrapped with any relevant
  HTML tags, no problems.
* Except for self-closing one-liner `data` attribute, be sure grid items are
  stacked, separated by line breaks, or any relevant HTML tags, and wrapped
  each with `[item]` like so:   
  <code>
[blazy]<br>
&nbsp;&nbsp;[item]<br>&nbsp;&nbsp;&nbsp;&nbsp;&lt;IMG&gt;<br>&nbsp;&nbsp;[/item]<br>
&nbsp;&nbsp;[item]<br>&nbsp;&nbsp;&nbsp;&nbsp;&lt;IFRAME&gt;<br>&nbsp;&nbsp;[/item]<br>
&nbsp;&nbsp;[item]<br>&nbsp;&nbsp;&nbsp;&nbsp;&lt;p&gt;Any non-media HTML
content&lt;/p&gt;<br>&nbsp;&nbsp;[/item]<br>
[/blazy]
    </code>
