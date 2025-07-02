(function ($, Drupal, once) {

  'use strict';

  /**
  * Set content fields to visible when tabs are created. After an action
  * being performed, stay on the same perspective.
  *
  * @param $parWidget
  *   Paragraphs widget.
  * @param $parTabs
  *   Paragraphs tabs.
  * @param $parContent
  *   Paragraphs content tab.
  * @param $parBehavior
  *   Paragraphs behavior tab.
  * @param $mainRegion
  *   Main paragraph region.
  */
  var setUpTab = function ($parWidget, $parTabs, $parContent, $parBehavior, $mainRegion) {
    var $tabContent = $parTabs.find('.paragraphs_content_tab');
    var $tabBehavior = $parTabs.find('.paragraphs_behavior_tab');
    if ($tabBehavior.hasClass('is-active')) {
      $parWidget.removeClass('content-active').addClass('behavior-active');
      $tabContent.removeClass('is-active');
      $tabContent.find('a').removeClass('is-active');
      $tabBehavior.addClass('is-active');
      $tabBehavior.find('a').addClass('is-active');
    }
    else {
      // Activate content tab visually if there is no previously
      // activated tab.
      if (!($mainRegion.hasClass('content-active'))
        && !($mainRegion.hasClass('behavior-active'))) {
        $tabContent.addClass('is-active');
        $tabContent.find('a').addClass('is-active');
        $parWidget.addClass('content-active');
      }

      $parTabs.removeClass('paragraphs-tabs-hide');
      if ($parBehavior.length === 0) {
        $parTabs.addClass('paragraphs-tabs-hide');
      }
    }
  };

  /**
  * Switching active class between tabs.
  * @param $parTabs
  *   Paragraphs tabs.
  * @param $clickedTab
  *   Clicked tab.
  * @param $parWidget
  *   Paragraphs widget.
  */
  var switchActiveClass = function ($parTabs, $clickedTab, $parWidget) {
    var $clickedTabParent = $clickedTab.parent();

    $parTabs.find('li').removeClass('is-active');
    $parTabs.find('li').find('a').removeClass('is-active');
    $clickedTabParent.addClass('is-active');
    $clickedTabParent.find('a').addClass('is-active');

    $parWidget.removeClass('behavior-active content-active');
    if ($clickedTabParent.hasClass('paragraphs_content_tab')) {
      $parWidget.addClass('content-active');
      $parWidget.find('.paragraphs-add-wrapper').parent().removeClass('hidden');
    }
    else {
      $parWidget.addClass('behavior-active');
      $parWidget.find('.paragraphs-add-wrapper').parent().addClass('hidden');
    }
  };

  /**
   * Add class to first paragraph in the viewport.
   *
   * In order to have a persistent position when switching tabs,
   * we add a class to the first paragraph visible in the viewport.
   */
  var markFirstVisibleParagraph = function (totalTopOffset) {
    var $window = $(window);
    var bottomOfScreen = $window.scrollTop() + $window.height();
    var topOfScreen = $window.scrollTop() + totalTopOffset;
    var $firstParagraph = false;
    // @todo Make sure to skip non-Paragraph draggable field widget items here.
    var $allParagraphs = $('.node-form .draggable');

    $allParagraphs.each(function () {
      var $this = $(this);
      var topOfElement = $this.offset().top;
      var bottomOfElement = $this.offset().top + $this.height();

      // Search for paragraphs inside the viewport.
      if ((bottomOfScreen > topOfElement) && (topOfScreen < bottomOfElement)) {
        if ($firstParagraph) {
          // Find next best Paragraph in Viewport.
          if (topOfElement > topOfScreen ) {
            // Second top in screen or first nested in screen.
            $firstParagraph = $this;
            return false;
          }
          else if(topOfElement > bottomOfScreen) {
            // Choose previous element.
            return false;
          }
        }

        $firstParagraph = $this;
        if (topOfScreen < topOfElement) {
          // Choose this element as it starts in viewport.
          return false;
        }
      }
    });

    if ($firstParagraph) {
      // Remove potential previous marker.
      $('.first-paragraph').removeClass('first-paragraph');
      // Add the class to the first paragraph in the viewport.
      $firstParagraph.addClass('first-paragraph paragraph-hover');
    }

    return $firstParagraph;
  };

  /**
   * For body tag, adds tabs for selecting how the content will be displayed.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.bodyTabs = {
    attach: function (context) {
      var $topLevelParWidgets = $('.paragraphs-tabs-wrapper', context).filter(function() {
        return $(this).parents('.paragraphs-nested').length === 0;
      });

      // Initialization.
      $(once('paragraphs-bodytabs', $topLevelParWidgets)).each(function() {
        var $parWidget = $(this);
        var $parTabs = $parWidget.find('.paragraphs-tabs');

        // Create click event.
        $parTabs.find('a').click(function(e) {
          var toolbarHeight = $('.toolbar-tray-horizontal').outerHeight() || 0;
          var adminToolbarsOffset = $('#toolbar-bar').outerHeight() + toolbarHeight;
          var totalTopOffset = adminToolbarsOffset + $('.paragraphs-tabs').outerHeight();
          var $firstParagraph;
          var currentParagraphOffset = 0;
          var $window = $(window);

          $firstParagraph = markFirstVisibleParagraph(totalTopOffset);

          // Set the proper window position for each tab.
          if ($firstParagraph) {
            // Maintain vertical offset in addition to the toolbar heights.
            currentParagraphOffset = $firstParagraph.offset().top - ($window.scrollTop() + totalTopOffset);
            // Ignore a negative offset.
            if (currentParagraphOffset < 0) {
              currentParagraphOffset = 0;
            }
          }

          e.preventDefault();
          switchActiveClass($parTabs, $(this), $parWidget);

          // We need to check to which position to scroll to, whenever we need to scroll.
          // If the first paragraph is the same, we maintain the scroll position, otherwise scroll to top of the paragraph.
          if ($firstParagraph) {
            $('html, body').scrollTop($firstParagraph.offset().top - totalTopOffset - currentParagraphOffset);

            // Reset the first paragraph class with a delay, in order for the background change to be visible.
            setTimeout(function() {
              $('.first-paragraph').removeClass('paragraph-hover');
            }, 1000);
          }

        });
      });

      if ($('.paragraphs-tabs-wrapper', context).length > 0) {
        $topLevelParWidgets.each(function() {
          var $parWidget = $(this);
          var $parTabs = $parWidget.find('.paragraphs-tabs');
          var $parContent = $parWidget.find('.paragraphs-content');
          var $parBehavior = $parWidget.find('.paragraphs-behavior');
          var $mainRegion = $parWidget.find('.layout-region-node-main');
          setUpTab($parWidget, $parTabs, $parContent, $parBehavior, $mainRegion);
        });
      }
    }
  };
})(jQuery, Drupal, once);

