/**
 * @file
 * Provides Slick loader.
 */

(function ($, Drupal, drupalSettings, _d) {

  'use strict';

  var _id = 'slick';
  var _unslick = 'unslick';
  var _mounted = _id + '--initialized';
  var _element = '.' + _id + ':not(.' + _mounted + ')';
  var _elSlider = '.slick__slider';
  var _elArrow = '.slick__arrow';
  var _elBlazy = '.b-lazy[data-src]:not(.b-loaded)';
  var _elClose = '.media__icon--close';
  var _isPlaying = 'is-playing';
  var _isPaused = 'is-paused';
  var _hidden = 'visually-hidden';
  // @todo remove data-thumb for data-b-thumb at 3.x.
  var _dataThumb = 'data-thumb';
  var _dataBThumb = 'data-b-thumb';
  var _blazy = Drupal.blazy || {};

  /**
   * Slick utility functions.
   *
   * @param {HTMLElement} elm
   *   The slick HTML element.
   */
  function doSlick(elm) {
    var el = $(elm);
    var t = $('> ' + _elSlider, elm).length ? $('> ' + _elSlider, elm) : el;
    var a = $('> ' + _elArrow, elm);
    var o = t.data(_id) ? $.extend({}, drupalSettings.slick, t.data(_id)) : $.extend({}, drupalSettings.slick);
    var r = o.responsive && o.responsive.length ? o.responsive : false;
    var d = o.appendDots;
    var b;
    var isBlazy = o.lazyLoad === 'blazy' && _blazy;
    var isVideo = t.find('.media--player').length;
    var unSlick = t.hasClass(_unslick);

    // Populate defaults + globals into each breakpoint.
    if (!unSlick) {
      o.appendDots = d === _elArrow ? a : (d || $(t));
    }

    if (r) {
      for (b in r) {
        if (Object.prototype.hasOwnProperty.call(r, b) && r[b].settings !== _unslick) {
          r[b].settings = $.extend({}, drupalSettings.slick, globals(o), r[b].settings);
        }
      }
    }

    // Update the slick settings object.
    t.data(_id, o);
    o = t.data(_id);

    /**
     * The event must be bound prior to slick being called.
     */
    function beforeSlick() {
      if (o.randomize && !t.hasClass('slick-initiliazed')) {
        randomize();
      }

      if (!unSlick) {
        t.on('init.sl', function (e, slick) {
          // Puts dots in between arrows for easy theming like this: < ooooo >.
          if (d === _elArrow) {
            $(slick.$dots).insertAfter(slick.$prevArrow);
          }

          // Fixes for slidesToShow > 1, centerMode, clones with Blazy IO.
          var $src = t.find('.slick-cloned.slick-active ' + _elBlazy);
          if (isBlazy && $src.length && _blazy.init) {
            _blazy.init.load($src);
          }
        });
      }

      // Lazyload ahead with Blazy integration.
      if (isBlazy) {
        t.on('beforeChange.sl', function () {
          preloadBlazy(true);
        });
      }
      else {
        // Useful to hide caption during loading, but watch out unloading().
        var $media = $('.media', t);
        if ($media.length) {
          var isBlazyEl = $media.find('[data-src]').length || $media.hasClass('b-bg');
          if (isBlazyEl) {
            $media.closest('.slide__content').addClass('is-loading');
          }
        }
      }

      t.on('setPosition.sl', function (e, slick) {
        setPosition(slick);
      });
    }

    /**
     * Blazy is not loaded on slidesToShow > 1 with Infinite on, reload.
     *
     * @param {bool} ahead
     *   Whether to lazyload ahead, or not.
     */
    function preloadBlazy(ahead) {
      if (t.find(_elBlazy).length) {
        var $src = t.find(ahead ? '.slide:not(.slick-cloned) ' + _elBlazy : '.slick-active ' + _elBlazy);

        // If selectively fails, always suspect .slick-cloned being rebuilt.
        // This is not an issue if Infinite is disabled.
        if (!$src.length) {
          $src = t.find('.slick-cloned ' + _elBlazy);
        }
        if ($src.length && _blazy.init) {
          _blazy.init.load($src);
        }
      }
    }

    /**
     * Reacts on Slick afterChange event.
     */
    function afterChange() {
      if (isVideo) {
        closeOut();
      }

      if (isBlazy) {
        preloadBlazy(false);
      }
    }

    /**
     * The event must be bound after slick being called.
     */
    function afterSlick() {
      // Arrow down jumper.
      t.parent().on('click.sl', '.slick-down', function (e) {
        e.preventDefault();
        var b = $(this);

        $('html, body').stop().animate({
          scrollTop: $(b.data('target')).offset().top - (b.data('offset') || 0)
        }, 800, $.easing && o.easing ? o.easing : 'swing');
      });

      if (o.mouseWheel) {
        t.on('mousewheel.sl', function (e, delta) {
          e.preventDefault();
          return t.slick(delta < 0 ? 'slickNext' : 'slickPrev');
        });
      }

      if (!isBlazy) {
        t.on('lazyLoaded lazyLoadError', function (e, slick, img) {
          unloading(img);
        });
      }

      t.on('afterChange.sl', afterChange);

      // Turns off any video if any change to the slider.
      if (isVideo) {
        t.on('click.sl', _elClose, closeOut);
        t.on('click.sl', '.media__icon--play', pause);
      }

      el.removeClass(function (index, css) {
        return (css.match(/(\S+)loading/g) || []).join(' ');
      });
    }

    /**
     * Remove loadinbg classes if any.
     *
     * @param {HTMLElement} img
     *   The image HTML element.
     */
    function unloading(img) {
      var $img = $(img);
      var p = $img.closest('.slide') || $img.closest('.' + _unslick);

      // Cleans up (is-|media--)loading classes.
      $img.parentsUntil(p).removeClass(function (index, css) {
        return (css.match(/(\S+)loading/g) || []).join(' ');
      });
    }

    /**
     * Randomize slide orders, for ads/products rotation within cached blocks.
     */
    function randomize() {
      t.children().sort(function () {
        return 0.5 - Math.random();
      })
        .each(function () {
          t.append(this);
        });
    }

    /**
     * Updates arrows visibility based on available options.
     *
     * @param {Object} slick
     *   The slick instance object.
     */
    function setPosition(slick) {
      // Use the options that applies for the current breakpoint and not the
      // variable "o".
      // @see https://www.drupal.org/project/slick/issues/2480245
      var less = slick.slideCount <= slick.options.slidesToShow;
      var hide = less || slick.options.arrows === false;

      // Be sure the most complex slicks are taken care of as well, e.g.:
      // asNavFor with the main display containing nested slicks.
      if (t.attr('id') === slick.$slider.attr('id')) {
        // Removes padding rules, if no value is provided to allow non-inline.
        if (!slick.options.centerPadding || slick.options.centerPadding === '0') {
          slick.$list.css('padding', '');
        }

        // @todo: Remove temp fix for when total <= slidesToShow at 1.6.1+.
        // Ensures the fix doesn't break responsive options.
        // @see https://github.com/kenwheeler/slick/issues/262
        if (less && ((slick.$slideTrack.width() <= slick.$slider.width())
          || $(elm).hasClass('slick--thumbnail'))) {
          slick.$slideTrack.css({left: '', transform: ''});
        }

        // Cleans up preloader if any named b-loader due to clones.
        var $preloader = t.find('.b-loaded ~ .b-loader');
        if ($preloader.length) {
          $preloader.remove();
        }

        // Do not remove arrows, to allow responsive have different options.
        // Allows the down arrow to be prominent unless disabled.
        if (a.length) {
          $.each(['next', 'prev'], function (i, key) {
            $('.slick-' + key, a)[hide ? 'addClass' : 'removeClass'](_hidden);
          });
        }
      }
    }

    /**
     * Trigger the media close.
     */
    function closeOut() {
      // Clean up any pause marker at slider container.
      t.removeClass(_isPaused);
      var $playing = t.find('.' + _isPlaying);

      if ($playing.length) {
        $playing.removeClass(_isPlaying).find(_elClose).click();
      }
    }

    /**
     * Trigger pause on slick instance when media playing a video.
     */
    function pause() {
      t.addClass(_isPaused).slick('slickPause');
    }

    /**
     * Declare global options explicitly to copy into responsive settings.
     *
     * @param {Object} o
     *   The slick options object.
     *
     * @return {Object}
     *   The global options common for both main and responsive displays.
     */
    function globals(o) {
      return unSlick ? {} : {
        slide: o.slide,
        lazyLoad: o.lazyLoad,
        dotsClass: o.dotsClass,
        rtl: o.rtl,
        prevArrow: $('.slick-prev', a),
        nextArrow: $('.slick-next', a),
        appendArrows: a,
        customPaging: function (slick, i) {
          var slide = slick.$slides.eq(i);
          var container = slide.find('[' + _dataThumb + ']');
          var dotsThumb;
          var dataThumb = _dataThumb;

          if (!container.length) {
            container = slide.find('[' + _dataBThumb + ']');
            dataThumb = _dataBThumb;
          }

          if (container.length) {
            var alt = container.find('img').attr('alt');
            alt = alt ? Drupal.checkPlain(alt) : 'Preview';
            var img = '<img alt="' + Drupal.t(alt) + '" src="' + container.attr(dataThumb) + '">';
            dotsThumb = o.dotsClass.indexOf('thumbnail') > 0 ?
              '<div class="slick-dots__thumbnail">' + img + '</div>' : '';
          }

          var paging = slick.defaults.customPaging(slick, i);
          return dotsThumb ? paging.add(dotsThumb) : paging;
        }
      };
    }

    // Build the Slick.
    beforeSlick();
    t.slick(globals(o));
    afterSlick();

    // Destroy Slick if it is an enforced unslick.
    // This allows Slick lazyload to run, but prevents further complication.
    // Should use lazyLoaded event, but images are not always there.
    if (unSlick) {
      t.slick(_unslick);
    }

    // Add helper class for arrow visibility as they are outside slider.
    el.addClass(_mounted);
  }

  /**
   * Attaches slick behavior to HTML element identified by CSS selector .slick.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slick = {
    attach: function (context) {
      _d.once(doSlick, _id, _element, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        _d.once.removeSafely(_id, _element, context);
      }
    }
  };

})(jQuery, Drupal, drupalSettings, dBlazy);
