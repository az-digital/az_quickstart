((Drupal) => {
    Drupal.behaviors.azParagraphsPushSidebarDown = {
      attach() {
        function _calculateParagraphHeight() {
            const content_region = document.getElementById('content');
            const sidebar_pusher = document.querySelectorAll('[push-sidebar-down="push-sidebar-down"]');

            console.log(sidebar_pusher[0]);
            const content_region_position = content_region.getBoundingClientRect();
            const content_region_top = content_region_position.top;
            const sidebar_pusher_position = sidebar_pusher[0].getBoundingClientRect();
            const sidebar_pusher_bottom = sidebar_pusher_position.bottom;
            const sidebar_top_margin = sidebar_pusher_bottom - content_region_top;
            document.documentElement.style.setProperty(
                "--sidebar-top-margin",
                `${sidebar_top_margin}px`
            );
        }
        // recalculate on resize
        window.addEventListener("resize", _calculateParagraphHeight, false);
        // recalculate on dom load
        document.addEventListener(
          "DOMContentLoaded",
          _calculateParagraphHeight,
          false
        );
        // recalculate on load (assets loaded as well)
        window.addEventListener("load", _calculateParagraphHeight);
      },
    };
  })(Drupal);
