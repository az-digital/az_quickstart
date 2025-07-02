/**
 * @file
 *
 * IntelligenceBank Iframe Asset Browser.
 */
(function (window, $, Drupal, _ib, once) {
  'use strict';

  const DOWNLOAD_TYPE  = 'resource_download';
  const EMBED_TYPE     = 'resource_link';
  const ASSET_SELECTED = 'ib_dam_browser:set_data';

  var stack_modal = {
    "dir1": "down",
    "dir2": "left",
    "push": "top",
  };

  // Global id of the current asset browser instance.
  // It is used to fire an unique "asset selected" event,
  // only one instance subscribed to this event should save asset data.
  window.top.ibDamAppId = '';

  window.ibDamResizeWindow = function() {
    //$(".ui-front").dialog("option", { height: 'auto' } );
  }

  $(window).on('dialog:aftercreate', () => {
    // Since the dialog HTML is not part of the context, we can't use
    // context here.
    const $buttonPane = $(
      '.media-library-widget-modal .ui-dialog-buttonpane',
    );
    if (!$buttonPane.length) {
      return;
    }
    //$buttonPane.append(Drupal.theme('mediaLibrarySelectionCount'));
    //updateSelectionCount(settings.media_library.selection_remaining);
  });

  Drupal.behaviors.ibDamIfraneBrowserWidget = {
    attach: function (context, settings) {
      // Workaround for :has selector.
      $(window)
        .on('dialog:aftercreate', function(event, dialog, $element, settings) {
          $element.dialog("option", { height: 'auto' } );

          if ($('.ui-front form.ib-dam-browser-form').length) {
            $('.ui-front').addClass('ib-dam-browser-dialog');
          }
        });

      var appSettings  = settings.ib_dam.browser,
          $searchFrame = $('.ib-dam-app-browser', context);

      if (!$searchFrame.length) {
        return false;
      }

      $(once('ibDamIframeBrowserWidget', '.ib-dam-app-browser', context)).each(function () {
        attachApp(appSettings, $(this));
      });

      var assetEventListener = function(e) {
        var asset = getAppResponse(e.data, appSettings.allowedTypesList);
        var appId = _ib.isEmpty(window.top) || _ib.isEmpty(window.top.ibDamAppId)
          ? false
          : window.top.ibDamAppId;

        if (!appId || !asset || !isOriginalHost(e.origin, appSettings.host)) {
          return;
        }

        $.event.trigger(ASSET_SELECTED + ':' + appId, {
          appId: appId,
          asset: asset,
          settings: appSettings
        });
      };

      // Add global subscribers to route asset data to current active asset browser.
      window.addEventListener('message', assetEventListener, false);
      window.top.addEventListener('message', assetEventListener, false);
    }
  };

  function attachApp(appSettings, appEl) {
    var uuid    = 'ib-dam-' + generateGuid(),
      $throbber = $(Drupal.theme('ajaxProgressIndicatorFullscreen'));

    appEl.attr('id', uuid);

    var $submit = $('form:has(#'+uuid+')')
      .first()
      .find(appSettings.submitSelector)
      .hide();

    //appEl
    //  .before('<div style="height:100px;width:100%;"></div>');
    setTimeout(() => {
      window.ibDamResizeWindow();
      appEl.before($throbber)
        .attr('src', appSettings.appUrl)
        .on('load', function () {
          window.ibDamResizeWindow();
          appEl.focus();
          $throbber.remove();
          window.top.ibDamAppId = uuid;
        });
    }, 200);



    // @todo: PRE maybe we need also mouseout when deals with only modals.
    // This handler used to track current active asset browser,
    // because only mouse* events are fired on iframe elements.
    appEl.get(0).addEventListener('mouseover', function (e) {
      window.top.ibDamAppId = $(event.target).attr('id');
    }, true);

    // Subscribe each instance only to unique, per instance,
    // "asset selected" event.
    $(window).on(ASSET_SELECTED + ':' + uuid, function (e, data) {
      saveAssetsData(data.appId, data.asset, data.settings);
    });
  }

  /**
   * Save asset handler.
   *
   * Used to set value of hidden form element to pass asset data to backend.
   * Prepare data, set element value, UI interactions, show/hide info messages.
   */
  function saveAssetsData(appId, asset, settings) {
    var $appEl  = $('#'+ appId),
      $wrapper  = $appEl.parent('.ib-dam-app-wrapper'),
      $submit   = $('form:has(#'+appId+')').first().find(settings.submitSelector),
      $overlay  = Drupal.theme('ajaxOverlay', {idx: 100}),
      $throbber = $(Drupal.theme('ajaxProgressIndicatorFullscreen'));

    if (settings.debug === true) {
      console.log(asset);
    }

    if ((isEmbedType(asset) && settings.allowEmbed)
      || (isValidFileType(settings.fileExtensions, asset.filetype) && !isEmbedType(asset))
    ) {
      $wrapper.siblings('input[name="ib_dam_app[response_items]"]')
        .val(JSON.stringify(asset));

      $submit
        .after($throbber)
        .trigger('click');

      $wrapper.append($overlay);

      closeAllWarnings();
    }
    else {
      isEmbedType(asset)
        ? showWarning(settings.messages, 'embed')
        : showWarning(settings.messages, 'local')
    }
  }

  /**
   * Theme function for ajax progress Indicator.
   *
   * @return {object}
   *   This function returns a jQuery object.
   */
  Drupal.theme.ajaxProgressIndicator = function () {
    return $('<div class="ajax-progress-fullscreen"></div>');
  };

  /**
   * Theme function for ajax overlay element.
   *
   * @return {object}
   *   This function returns a jQuery object.
   */
  Drupal.theme.ajaxOverlay = function (settings) {
    return $('<div class="ib-dam-browser__widget-overlay"></div>').css('z-index', settings.idx);
  };

  function getHost(url) {
    var l = document.createElement('a');
    l.href = url;
    return l.hostname;
  }

  function isOriginalHost(source, expected) {
    return getHost(source) === expected;
  }

  function getAppResponse(dataString) {
    // We are not alone so checking here aliens before acting.
    if (typeof dataString === 'object') {
      return;
    }
    var data = JSON.parse(dataString);
    console.log(data);

    if (data.action === undefined) {
      return false;
    }
    return  [EMBED_TYPE, DOWNLOAD_TYPE].indexOf(data.action) !== -1
      ? data
      : false
  }

  function isEmbedType(item) {
    return item.action === EMBED_TYPE;
  }

  function isValidFileType(allowedList, type) {
    if (!allowedList.length) {
      return false;
    }
    return allowedList.indexOf(type.toLowerCase()) !== -1
  }

  function closeAllWarnings() {
    PNotify.removeStack(stack_modal);
  }

  function showWarning(messages, message_id) {
    var message = messages.filter(message => message.id === message_id)[0] ?? false;

    if (message && message.notification !== undefined && message.once === true) {
      message.notification.open();
      return;
    }

    message.notification = createNotification(message.title, message.text);
    handleNotificationClick(message.notification);
  }

  function handleNotificationClick(notification) {
    notification.get().click(function() {
      notification.remove();
    });
  }

  function createNotification(title, text) {
    return new PNotify({
      title: title,
      text: text,
      cornerClass: '',
      type: 'notice',
      stack: stack_modal,
      remove: true,
      hide: true,
      delay: 18000
    });
  }

  function generateGuid() {
    return Math.random().toString(36).substring(2, 15) +
      Math.random().toString(36).substring(2, 15);
  }

})(window, jQuery, Drupal, ibDam, once);
