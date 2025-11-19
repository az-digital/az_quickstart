((Drupal, once) => {
  Drupal.behaviors.azBarrioSidebarCollapsible = {
    attach: (context) => {
      function setWidthOfFullWidthElements() {
        const sidebarCollapsible = document.querySelector(
          '#az-sidebar-collapsible',
        );
        const sidebarCollapsibleWidth =
          sidebarCollapsible !== null
            ? sidebarCollapsible.getBoundingClientRect().width
            : 0;

        // Return if sidebar isn't visible (such as on mobile).
        if (sidebarCollapsibleWidth === 0) return;

        const contentRegion = document.querySelector('main.main-content');
        if (contentRegion !== null) {
          const allFullWidthElements = contentRegion.querySelectorAll(
            '.paragraph.full-width-background',
          );
          if (allFullWidthElements.length === 0) {
            return;
          }
        }

        document.documentElement.style.setProperty(
          '--sidebar-collapsible-width',
          `${sidebarCollapsibleWidth}px`,
        );

        const contentRegionPosition = contentRegion.getBoundingClientRect();
        const newLeftDistance =
          0 - contentRegionPosition.left + sidebarCollapsibleWidth;
        document.documentElement.style.setProperty(
          '--full-width-left-distance',
          `${newLeftDistance}px`,
        );
        document.documentElement.style.setProperty(
          '--full-width-right-distance',
          0,
        );
      }

      function addFullWidthListeners() {
        document.addEventListener('setFullWidthLayout', () => {
          setWidthOfFullWidthElements();
        });
      }

      function addSidebarCollapsibleListeners() {
        const sidebar = document.querySelector('#az-sidebar-collapsible');
        const sidebarBtn = document.querySelector('#az-sidebar-collapse-btn');
        const mainContainer = document.querySelector(
          '#container-with-sidebar-collapsible',
        );
        sidebar.addEventListener('hide.bs.collapse', (event) => {
          if (event.target.id === 'az-sidebar-collapsible') {
            event.target.classList.add('col-auto');
          }
        });
        sidebar.addEventListener('hidden.bs.collapse', (event) => {
          if (event.target.id === 'az-sidebar-collapsible') {
            document
              .querySelector('.region-az-sidebar-collapsible')
              .classList.add('d-none');
            document.querySelector('#az-sidebar-collapse-icon').textContent =
              'left_panel_open';
            sidebarBtn.classList.add('stretched-link');
            sidebarBtn.querySelector('.visually-hidden').textContent =
              'Show sidebar menu';
          }
          setWidthOfFullWidthElements();
        });
        sidebar.addEventListener('show.bs.collapse', (event) => {
          if (event.target.id === 'az-sidebar-collapsible') {
            document
              .querySelector('.region-az-sidebar-collapsible')
              .classList.remove('d-none');
            document.querySelector('#az-sidebar-collapse-icon').textContent =
              'left_panel_close';
            sidebarBtn.classList.remove('stretched-link');
            sidebarBtn.querySelector('.visually-hidden').textContent =
              'Hide sidebar menu';
          }
        });
        sidebar.addEventListener('shown.bs.collapse', (event) => {
          if (event.target.id === 'az-sidebar-collapsible') {
            document
              .querySelector('.region-az-sidebar-collapsible')
              .classList.remove('d-none');
            document.querySelector('#az-sidebar-collapse-icon').textContent =
              'left_panel_close';
            event.target.classList.remove('col-auto');
          }
          setWidthOfFullWidthElements();
        });
      }

      once('addFullWidthListeners', '#main').forEach(
        addFullWidthListeners,
        context,
      );
      once('azBarrioSidebarCollapsible', '#az-sidebar-collapsible').forEach(
        addSidebarCollapsibleListeners,
        context,
      );
    },
  };
})(Drupal, once);
