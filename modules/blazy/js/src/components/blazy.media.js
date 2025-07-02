/**
 * @file
 * Provides Media module integration.
 */

(function ($, Drupal, _win, _doc) {

  'use strict';

  var MD = 'media';
  var ID = 'b-' + MD;
  var ID_ONCE = ID;
  var IFRAME = 'iframe';
  var MD_PLAYER = MD + '--player';
  var S_MOUNTED = 'is-' + ID;
  var S_PLAYER = '.' + MD_PLAYER;
  var S_ELEMENT = S_PLAYER + ':not(.' + S_MOUNTED + ')';
  var ICON = MD + '__icon';
  var S_PLAY = '.' + ICON + '--play';
  var S_CLOSE = '.' + ICON + '--close';
  var C_IS_PLAYING = 'is-playing';
  var C_IS_BODY_PLAYING = 'is-b-player-playing';
  var DATA = 'data-';
  var DATA_IFRAME_TITLE = DATA + IFRAME + '-title';
  var DATA_URL = DATA + 'b-url';
  var C_MD_ELEMENT = MD + '__element';
  var B_INSTAGRAM = 'b-instagram';
  var C_HIDDEN = 'visually-hidden';
  var FN_MULTIMEDIA = $.multimedia || false;

  /**
   * Blazy media utility functions.
   *
   * @param {HTMLElement} el
   *   The media player HTML element.
   */
  function process(el) {
    var $el = $(el);
    var iframe = $el.find(IFRAME);
    var btn = $el.find(S_PLAY);

    // Media player toggler is disabled, just display iframe.
    if (!$.isElm(btn)) {
      return;
    }

    var url = $.attr(btn, DATA_URL);
    var title = $.attr(btn, DATA_IFRAME_TITLE);
    var instagram = $el.hasClass(B_INSTAGRAM);
    var newIframe;

    if (url && $.sanitizer.isDangerous('src', url)) {
      return;
    }

    /**
     * Play the media.
     *
     * @param {Event} e
     *   The event triggered by a `click` event.
     *
     * @return {bool|mixed}
     *   Return false if url is not available.
     */
    function play(e) {
      e.preventDefault();

      // oEmbed/ Soundcloud needs internet, fails on disconnected local.
      if (!url) {
        return false;
      }

      // Reset any (local) video/ audio to avoid multiple elements from playing.
      if (FN_MULTIMEDIA) {
        FN_MULTIMEDIA.pause();
      }

      var target = this;
      var sPlayable = '.' + C_IS_PLAYING + ':not(.' + B_INSTAGRAM + ')';
      var playing = $.find(_doc, sPlayable);
      var player = target.parentNode;

      // Remove other playing remote videos.
      if ($.isElm(playing)) {
        var played = $.find(_doc, sPlayable + ' ' + IFRAME);
        // Remove the previous iframe.
        $.remove(played);
        playing.className = playing.className.replace(/(\S+)playing/, '');
      }

      $.addClass(player, C_IS_PLAYING);

      if (!instagram) {
        playNow(e);
      }
    }

    /**
     * Play the media.
     *
     * @param {Event} e
     *   The event triggered by a `click` event.
     */
    function playNow(e) {
      var target = e.target;
      var player = $.closest(target, S_PLAYER);
      var iframe = $.find(player, IFRAME);

      url = $.attr(target, DATA_URL);
      title = $.attr(target, DATA_IFRAME_TITLE);

      // Remove the existing iframe on the current clicked iframe.
      $.remove(iframe);

      // DOM ready fix, for slow iframe removal.
      _win.setTimeout(function () {
        // Cache iframe for the potential repeating clicks.
        if (!newIframe) {
          newIframe = $.create(IFRAME, C_MD_ELEMENT);

          // Saving another clicks for nested iframes.
          $.attr(newIframe, {
            src: url,
            allow: 'autoplay; fullscreen',
            title: Drupal.checkPlain(title)
          });
        }

        // Appends the iframe.
        player.appendChild(newIframe);

        $.addClass(_doc.body, C_IS_BODY_PLAYING);

        // Be sure to detach on your destroy method, or Drupal..detach:
        // $.off('blazy:mediaPlaying', onPlaying);
        // After calling:
        // $.on('blazy:mediaPlaying', onPlaying);
        $.trigger(_win, 'blazy:mediaPlaying', {
          player: player
        });
      });
    }

    /**
     * Close the media.
     *
     * @param {Event} e
     *   The event triggered by a `click` event.
     *
     * @return {bool|mixed}
     *   Return false if instagram API.
     */
    function stop(e) {
      e.preventDefault();

      var target = this;

      if (instagram) {
        $.addClass(target, C_HIDDEN);
        return false;
      }

      var player = target.parentNode;

      var iframe = $.find(player, IFRAME);
      if (player.className.match(C_IS_PLAYING)) {
        player.className = player.className.replace(/(\S+)playing/, '');
      }

      $.remove(iframe);
      $.removeClass(_doc.body, C_IS_BODY_PLAYING);

      // Be sure to detach on your destroy method, or Drupal..detach:
      // $.off('blazy:mediaStopped', onStopped);
      // After calling:
      // $.on('blazy:mediaStopped', onStopped);
      $.trigger(_win, 'blazy:mediaStopped', {
        player: player
      });
    }

    /**
     * Reacts on `blazy:done` event sprcific for Instagram HTML content.
     *
     * @param {Event} e
     *   The event triggered by a `blazy:done` event.
     */
    /*
    function onDone(e) {
      var target = e.target;
      var player = $.hasClass(target, MD_PLAYER) ? target : $.closest(target, S_PLAYER);
      var btn = $.find(player, S_PLAY);

      // Autoload instagram player on being lazy loaded.
      if ($.isElm(btn)) {
        btn.click();
      }
    }
     */

    // Remove iframe if any to avoid browser requesting them till clicked.
    $.remove(iframe);

    // Plays the media player.
    $el.on('click.' + ID, S_PLAY, play);

    // Closes the video.
    $el.on('click.' + ID, S_CLOSE, stop);

    var checkWidth = function () {
      var ws = $.windowSize();
      var data = $.parse(el.dataset.bMp);
      var min = $.matchMedia('1024px') && !data.fs ? 15 : 0;
      var width = data.owidth;
      var height = data.oheight;
      var as = $.image.scale(width, height, ws.width - min, ws.height - min);
      var p = el.parentNode;
      width = as.width > width ? width : as.width;

      if ($.hasClass(p, 'media-wrapper')) {
        p.style.width = width + 'px';
        if (data.ratio !== data.oratio) {
          el.style.padding = 'padding-bottom: ' + data.ratio + '%';
        }
      }
    };

    $.on(_win, 'resize.' + ID + ' orientationchange.' + ID, $.debounce(checkWidth, 210));

    // Listens to blazy:done event to auto-display instagram feeds.
    // if (instagram) {
    // $el.on('blazy:done', onDone);
    // }
    $.removeClass(_doc.body, C_IS_BODY_PLAYING);
    $el.addClass(S_MOUNTED);
  }

  /**
   * Theme function for a dynamic inline video.
   *
   * @param {Object} settings
   *   An object containing the link element which triggers the lightbox.
   *   This link must have [data-b-media]|[data-media] attribute containing
   *   video metadata. [data-media] is deprecated for [data-b-media].
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.blazyMedia = function (settings) {
    // PhotoSwipe5 has element, PhotoSwipe4 el, etc.
    var el = settings.el || settings.element;
    var $el = $(el);
    var alt = $.image.alt(el);
    var data = $.parse($.attr(el, 'data-b-' + MD));
    var provider = data.provider;
    var token = data.token;
    var width = $.toInt(data.width, 640);
    var height = $.toInt(data.height, 360);
    var pad = $.image.ratio(data);
    var imgUrl = $el.attr('data-box-url');
    var href = el.href;
    var oembedUrl = $el.attr('data-oembed-url', href, true);
    var defClass = MD + '__element';
    var imgClass = settings.imgClass ?
      defClass + ' ' + settings.imgClass :
      defClass;
    var idClass = data.id ? ' ' + MD + '--' + data.id : '';
    var player = data.playable || data.boxType === 'iframe' ? ' ' + MD_PLAYER : '';
    var ariaClose = Drupal.t('Stop and close the video');
    var ariaPlay = Drupal.t('Load and play the video');
    var bProvider = '';
    var bToken = '';
    var ws = $.windowSize();
    var fs = data.fs || false;
    var min = $.matchMedia('1024px') && !fs ? 15 : 0;
    var as = $.image.scale(width, height, ws.width - min, ws.height - min);
    var mp;
    var oheight = height;
    var owidth = width;
    var html = '';

    width = as.width > width ? width : as.width;
    height = as.height > height ? height : as.height;

    var obj = {
      width: width,
      height: height,
      ratio: ((height / width) * 100).toFixed(2),
      owidth: owidth,
      oheight: oheight,
      oratio: pad,
      fs: fs
    };

    mp = Drupal.checkPlain(JSON.stringify(obj));

    if (imgUrl) {
      html += '<img src="$imgUrl" class="$imgClass" alt="$alt" loading="lazy" decoding="async" />';
    }

    if (player) {
      if (provider) {
        bProvider = ' data-b-provider="' + provider + '"';
      }
      if (token) {
        bToken = ' data-b-token="' + token + '"';
      }

      html += '<span class="$icon $icon--close" aria-label="$ariaClose"></span>';
      html += '<span class="$icon $icon--play" data-b-url="$oembed" data-iframe-title="$alt" aria-label="$ariaPlay"$bProvider$bToken></span>';
    }

    html = '<div class="$md $idClass $md--switch $player $md--ratio $md--ratio--fluid" aria-live="polite" style="padding-bottom: $pad%" data-b-mp="$mp">' + html + '</div>';

    if (!settings.unwrap) {
      html = '<div class="$wrapper $wrapper--inline" style="width: $widthpx">' + html + '</div>';
    }

    return $.template(html, {
      md: MD,
      icon: ICON,
      ariaClose: Drupal.checkPlain(ariaClose),
      ariaPlay: Drupal.checkPlain(ariaPlay),
      bProvider: bProvider,
      bToken: bToken,
      idClass: idClass,
      player: player,
      pad: pad,
      mp: mp,
      imgUrl: imgUrl,
      imgClass: imgClass,
      alt: alt,
      oembed: oembedUrl,
      width: width,
      wrapper: MD + '-wrapper'
    });
  };

  /**
   * Attaches Blazy media behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyMedia = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.removeClass(_doc.body, C_IS_BODY_PLAYING);
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, this, this.document);
