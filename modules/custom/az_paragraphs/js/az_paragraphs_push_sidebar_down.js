/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal) {
  Drupal.behaviors.azParagraphsPushSidebarDown = {
    attach: function attach() {
      function _setSidebarTopMargin() {
        var content_region = document.getElementById('content');
        var sidebar_pusher = document.querySelectorAll('[push-sidebar-down="push-sidebar-down"]');
        var content_region_position = content_region.getBoundingClientRect();
        var style = sidebar_pusher[0].currentStyle || window.getComputedStyle(sidebar_pusher[0], '');
        var bottom_margin = style.marginBottom;
        var content_region_top = content_region_position.top;
        var sidebar_pusher_position = sidebar_pusher[0].getBoundingClientRect();
        var sidebar_pusher_bottom = sidebar_pusher_position.bottom;
        var sidebar_top_margin = sidebar_pusher_bottom - content_region_top;
        console.log(bottom_margin);
        document.documentElement.style.setProperty("--sidebar-top-margin", "calc(".concat(sidebar_top_margin, "px + ").concat(bottom_margin, ")"));
      }

      function _calculateLeftMargin() {
        var marginLeft = document.querySelectorAll('[push-sidebar-down="push-sidebar-down"]');
        var marginLeftPosition = marginLeft[0].getBoundingClientRect();
        var marginLeftLeft = marginLeftPosition.left;
        var marginLeftRight = marginLeftPosition.right;
        var negativeLeftMargin = 0 - marginLeftLeft;
        var rightMargin = 0 - marginLeftRight;
        document.documentElement.style.setProperty("--full-width-thirty-three-percent", "".concat(negativeLeftMargin, "px"));
        document.documentElement.style.setProperty("--full-width-sixty-six-percent", "".concat(rightMargin, "px"));
      }

      window.addEventListener("resize", _setSidebarTopMargin, false);
      window.addEventListener('DOMContentLoaded', function (event) {
        _setSidebarTopMargin();

        _calculateLeftMargin();
      });
      window.addEventListener("load", _setSidebarTopMargin);
    }
  };
})(Drupal);