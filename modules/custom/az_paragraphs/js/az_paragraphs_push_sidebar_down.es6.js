((Drupal) => {
    Drupal.behaviors.azParagraphsPushSidebarDown = {
      attach() {
        function _setSidebarTopMargin() {
            const content_region = document.getElementById('content');
            const sidebar_pusher = document.querySelectorAll('[push-sidebar-down="push-sidebar-down"]');
            const content_region_position = content_region.getBoundingClientRect();
            const style = sidebar_pusher[0].currentStyle || window.getComputedStyle(sidebar_pusher[0],'');
            const bottom_margin = style.marginBottom;
            const content_region_top = content_region_position.top;
            const sidebar_pusher_position = sidebar_pusher[0].getBoundingClientRect();
            const sidebar_pusher_bottom = sidebar_pusher_position.bottom;
            const sidebar_top_margin = sidebar_pusher_bottom - content_region_top;
            console.log(bottom_margin);
            document.documentElement.style.setProperty(
                "--sidebar-top-margin",
                `calc(${sidebar_top_margin}px + ${bottom_margin})`
            );
        }
        function _calculateLeftMargin() {
          const marginLeft = document.querySelectorAll('[push-sidebar-down="push-sidebar-down"]');
          const marginLeftPosition = marginLeft[0].getBoundingClientRect();
          const marginLeftLeft = marginLeftPosition.left;
          const marginLeftRight = marginLeftPosition.right;

          const negativeLeftMargin = 0 - marginLeftLeft;
          const rightMargin =  0 - marginLeftRight;

          document.documentElement.style.setProperty(
            "--full-width-thirty-three-percent",
            `${negativeLeftMargin}px`
          );
          document.documentElement.style.setProperty(
            "--full-width-sixty-six-percent",
            `${rightMargin}px`
          );
        }

        // recalculate on resize
        window.addEventListener("resize", _setSidebarTopMargin, false);
        // window.addEventListener("resize", _calculateLeftMargin, false);

        window.addEventListener('DOMContentLoaded', (event) => {
          _setSidebarTopMargin();
          _calculateLeftMargin();
        });
        // recalculate on load (assets loaded as well)
        window.addEventListener("load", _setSidebarTopMargin);

      },
    };
  })(Drupal);
