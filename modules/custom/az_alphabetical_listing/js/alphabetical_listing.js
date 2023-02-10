/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function ($, Drupal) {
  Drupal.behaviors.azAlphabeticalListing = {
    attach: function attach() {
      $('#az-js-alpha-navigation li').each(function (index, element) {
        var groupId = $(element).children().attr('data-href');
        if ($(groupId).length !== 0) {
          $(element).removeClass('disabled');
          $(element).children().attr('tabindex', '0').attr('aria-hidden', 'false').attr('href', groupId);
        } else {
          $(element).addClass('disabled');
          $(element).children().attr('tabindex', '-1').attr('aria-hidden', 'true').removeAttr('href');
        }
      });
      function azAlphabeticalListingCheckNoResults() {
        var visibleResults = false;
        $('.az-alphabetical-listing-group-title').each(function (index, element) {
          if (!$(element).hasClass('hide-result')) {
            visibleResults = true;
          }
        });
        if (!visibleResults) {
          $('#az-js-alphabetical-listing-no-results').show();
        } else {
          $('#az-js-alphabetical-listing-no-results').hide();
        }
      }
      function azAlphabeticalListingGroupLoop() {
        $('.az-alphabetical-listing-group-title').each(function (index, element) {
          var elementId = $(element).attr('id');
          var group = elementId.toLowerCase();
          var targetGroup = ".az-alphabetical-letter-group-".concat(group);
          var visibleChildren = false;
          $(targetGroup).each(function (resultIndex, resultElement) {
            if (!$(resultElement).hasClass('hide-result')) {
              visibleChildren = true;
            }
          });
          var navTarget = $('#az-js-alpha-navigation').find(".page-link[data-href='#".concat(elementId, "']"));
          if (!visibleChildren) {
            $(element).hide();
            $(element).addClass('hide-result');
            navTarget.parent().addClass('disabled');
            navTarget.attr('tabindex', '-1').attr('aria-hidden', 'true').removeAttr('href');
          } else {
            $(element).show();
            $(element).removeClass('hide-result');
            navTarget.parent().removeClass('disabled');
            navTarget.attr('tabindex', '0').attr('aria-hidden', 'false').attr('href', $(element).attr('id'));
          }
        });
      }
      $('#az-js-alphabetical-listing-search').keyup(function (event) {
        var filter = $(event.currentTarget).val();
        $('.az-js-alphabetical-listing-search-result').each(function (index, element) {
          var searchResultText = $(element).find('.az-alphabetical-listing-item').text();
          if (searchResultText.search(new RegExp(filter, 'i')) < 0) {
            $(element).find('az-alphabetical-listing-item').attr('tabindex', '0');
            $(element).addClass('hide-result');
            $(element).hide();
          } else {
            $(element).find('.az-alphabetical-listing-item').attr('tabindex', '0');
            $(element).removeClass('hide-result');
            $(element).show();
          }
        });
        azAlphabeticalListingGroupLoop();
        azAlphabeticalListingCheckNoResults();
      });
      var $root = $('html, body');
      var breakpoint = 600;
      $('#az-js-alpha-navigation a').on('click', function (event) {
        event.preventDefault();
        var $alphaNav = $('#az-js-floating-alpha-nav-container');
        var href = $.attr(event.currentTarget, 'data-href');
        var fixedNavHeight = $alphaNav.outerHeight();
        var headingHeight = $('.az-alphabetical-listing-group-title:first').outerHeight();
        var offsetHeight = fixedNavHeight + headingHeight;
        if ($(window).width() <= breakpoint) {
          fixedNavHeight = 0;
        }
        $root.animate({
          scrollTop: $(href).offset().top - offsetHeight
        }, 500, function () {
          window.location.hash = href;
        });
      });
      if ($('body.toolbar-tray-open').length) {
        $('#az-js-floating-alpha-nav-container').css('top', '79px');
      } else if ($('body.toolbar-horizontal').length) {
        $('#az-js-floating-alpha-nav-container').css('top', '39px');
      }
    }
  };
})(jQuery, Drupal);