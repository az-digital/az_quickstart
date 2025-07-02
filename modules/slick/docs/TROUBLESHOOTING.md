
***
## <a name="troubleshooting"></a>TROUBLESHOOTING
Please read this before submitting issues. This 5-year+-old brief is worth
reading to save you from headaches.

If you don't find the below solve your issues, check out:
[Slick Gotchas](https://www.drupal.org/project/issues/search?issue_tags=slick%20gotchas)

1. When upgrading from Slick v1.3.6 to later version, try to re-save options at:
   * `/admin/config/media/slick`
   * `/admin/structure/types/manage/CONTENT_TYPE/display`
   * `/admin/structure/views/view/VIEW_NAME`

     Only if trouble to see the new options, or when options don't apply
     properly. Most likely true when the library adds/changes options, or the
     module does something new. This is normal for any library even commercial
     ones, so bear with it.

2. Always clear the cache, and re-generate JS (if aggregation is on) when
   updating the module to ensure things are picked up:
   * `/admin/config/development/performance`

3. If you are customizing template files, or theme functions, be sure to
   re-check against the latest.

4. Slick and Blazy versions must be the latest. Too much version difference will
   most likely cause issues. Different branches will surely error.

5. Skins are permanently cached. Clear cache if new skins you created or
   provided by sub-modules do not appear immediately.

6. Lightboxes under `Media switcher` option are permanently cached. Clear cache
   if new lightbox modules installed do not appear immediately.  

7. Do not use ID for theming. Use more reliable CSS classes, instead. ID is
   more useful for machines than themers. In Slick, it is to sync navigation.


## KNOWN ISSUES
1. Slick admin CSS may not be compatible with private or contrib admin
   themes. Only if trouble with admin display, please disable it at:

   `/admin/config/media/blazy`

2. [D7] The Slick lazyLoad is not compatible with Picture. Slick only
   facilitates Picture to get in. The image formatting is taken over by
   Picture. Some other options such as Aspect ratio is currently not
   supported either.

3. The following is not module related, but worth a note:
   * **Deprecated in 2.10**: lazyLoad `onDemand` has issue with dummy image
     excessive height. Added fixes to suppress it via option Aspect ratio
     (fluid). Or use `Blazy` lazyload for more advanced options.
   * If the total < `slidesToShow`, Slick behaves. Previously added a workaround
     to fix this, but later dropped and handed over to the core instead.
     Brought back the temp fix for 1.6+ as per 10/18/16:
     See https://github.com/kenwheeler/slick/issues/262
   * Fade option with `slideToShow` > 1 will screw up.
   * `variableWidth` ignores `slidesToShow`.
   * Too much `centerPadding` at small device affects `slidesToShow`.
   * If thumbnail display is `Infinite`, the main one must be infinite too, else
     incorrect syncing.
   * `adaptiveHeight` is no good for vertical.  

4. Lighbox integration
   * `Infinite` option will create duplicates or clone slides which look more
     obvious if `slidesToShow` > 1. This means that lightboxes (Colorbox,
     Photobox, PhotoSwipe) will have dups.  
     **Solution:**

     Disable `Infinite` option.
     UPDATE: 2021/6, added fix specific for Colorbox ported from Splide.
   * **Deprecated in 2.10**: Colorbox integration is not working well with
     lazyload `onDemand`.  
     **Solution:**

     Choose one of other lazyload options: **Anticipated, Blazy, Progressive**.

     As the issue with similar setup never happens with Photobox nor PhotoSwipe,
     we can only conclude Slick with lazyload onDemand just doesn't play nice
     with Colorbox.
   * **Deprecated in 2.10**: Photobox is best for:
     - infinite true + slidesToShow 1
     - infinite false + slidesToShow N

      If "infinite true + slidesToShow > 1" is a must, but you don't want dup
      thumbnails, simply override the JS to disable 'thumbs' option.

5. [D7] Slick carousel is designed for multi-value fields, specific to D7.
   Unfortunately no handy way to disable formatters for single value at D7.
   So the formatter is available even for single value, but not actually
   functioning, please ignore it till we can get rid of it at D7.
