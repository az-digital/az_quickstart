(function ($, Drupal, once) {

  "use strict";

  Drupal.behaviors.environmentIndicatorSwitcher = {
    attach: function (context, settings) {
      $('#environment-indicator', context).bind('click', function () {
        $('#environment-indicator .environment-switcher-container', context).slideToggle('fast');
      });
    }
  };

  Drupal.behaviors.environmentIndicatorToolbar = {
    attach: function (context, settings) {
      if (typeof(settings.environmentIndicator) != 'undefined') {
        const $body = $('body');
        const borderWidth = getComputedStyle(document.body).getPropertyValue('--enviroment-indicator-border-width') || '6px';

        // Only apply text and background color if not using gin_toolbar
        if (!$body.hasClass('gin--vertical-toolbar') && !$body.hasClass('gin--horizontal-toolbar')) {
          $('.toolbar .toolbar-bar .toolbar-tab > .toolbar-item').css('background-color', settings.environmentIndicator.bgColor);
          $('#toolbar-bar .toolbar-tab a.toolbar-item', context).css('border-bottom', '0px');
          $('#toolbar-bar .toolbar-tab a.toolbar-item', context).css('color', settings.environmentIndicator.fgColor);
          $('#toolbar-bar', context).css('background-color', settings.environmentIndicator.bgColor);
          $('#toolbar-bar .toolbar-tab a.toolbar-item', context).not('.is-active').css('color', settings.environmentIndicator.fgColor);
        }

        // Set environment color for gin_toolbar vertical toolbar.
        if ($body.hasClass('gin--vertical-toolbar')) {
          $('.toolbar-menu-administration', context).css({'border-left-color': settings.environmentIndicator.bgColor, 'border-left-width': borderWidth});
          $('.toolbar-tray-horizontal .toolbar-menu li.menu-item', context).css({'margin-left': 'calc(var(--enviroment-indicator-border-width) * -0.5)'});
        }
        // Set environment color for gin_toolbar horizontal toolbar.
        if ($body.hasClass('gin--horizontal-toolbar')) {
          $('#toolbar-item-administration-tray').css({'border-top-color': settings.environmentIndicator.bgColor, 'border-top-width': borderWidth});
        }
        // Set environment color on the icon of the gin_toolbar
        if($body.hasClass('gin--horizontal-toolbar') || $body.hasClass('gin--vertical-toolbar')) {
          $('head', context).append("<style>.toolbar .toolbar-bar #toolbar-item-administration-tray a.toolbar-icon-admin-toolbar-tools-help.toolbar-icon-default::before{ background-color: " + settings.environmentIndicator.bgColor + " }</style>");
        }
      }
    }
  };

  Drupal.behaviors.environmentIndicatorTinycon = {
    attach: function (context, settings) {
      $(once('env-ind-tinycon', 'html', context)).each(function() {
        if (typeof(settings.environmentIndicator) != 'undefined' &&
          typeof(settings.environmentIndicator.addFavicon) != 'undefined' &&
          settings.environmentIndicator.addFavicon) {
          // Draw favicon label.
          Tinycon.setBubble(settings.environmentIndicator.name.slice(0, 1).trim());
          Tinycon.setOptions({
            background: settings.environmentIndicator.bgColor,
            colour: settings.environmentIndicator.fgColor
          });
        }
      })
    }
  }

})(jQuery, Drupal, once);
