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

        document.documentElement.style.setProperty(
          '--sidebar-collapsible-width',
          `${sidebarCollapsibleWidth}px`,
        );

        const contentRegion = document.querySelector('main.main-content');
        if (contentRegion === null) return;

        const allFullWidthElements = contentRegion.querySelectorAll(
          '.paragraph.full-width-background',
        );
        if (allFullWidthElements.length === 0) return;

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
        const sidebarCollapsibleContainer = document.querySelector(
          '#az-sidebar-collapsible',
        );
        const sidebarCollapseBtn = document.querySelector(
          '#az-sidebar-collapse-btn',
        );
        const sidebarCollapseIcon = document.querySelector(
          '#az-sidebar-collapse-icon',
        );
        const sidebarCollapsibleRegion = document.querySelector(
          '.region-az-sidebar-collapsible',
        );

        if (
          sidebarCollapsibleContainer === null ||
          sidebarCollapseBtn === null ||
          sidebarCollapseIcon === null ||
          sidebarCollapsibleRegion === null
        )
          return;
        /*
          const mainContainer = document.querySelector(
            '#container-with-sidebar-collapsible',
          );
        */
        sidebarCollapsibleContainer.addEventListener(
          'hide.bs.collapse',
          (event) => {
            if (event.target.id === 'az-sidebar-collapsible') {
              event.target.classList.add('col-auto');
            }
          },
        );
        sidebarCollapsibleContainer.addEventListener(
          'hidden.bs.collapse',
          (event) => {
            if (event.target.id === 'az-sidebar-collapsible') {
              sidebarCollapsibleRegion.classList.add('d-none');
              sidebarCollapseIcon.textContent = 'left_panel_open';
              sidebarCollapseBtn.classList.add('stretched-link');
              const visuallyHiddenText =
                sidebarCollapseBtn.querySelector('.visually-hidden');
              if (visuallyHiddenText !== null) {
                visuallyHiddenText.textContent = 'Show sidebar menu';
              }
            }
            setWidthOfFullWidthElements();
          },
        );
        sidebarCollapsibleContainer.addEventListener(
          'show.bs.collapse',
          (event) => {
            if (event.target.id === 'az-sidebar-collapsible') {
              sidebarCollapsibleRegion.classList.remove('d-none');
              sidebarCollapseIcon.textContent = 'left_panel_close';
              sidebarCollapseBtn.classList.remove('stretched-link');
              const visuallyHiddenText =
                sidebarCollapseBtn.querySelector('.visually-hidden');
              if (visuallyHiddenText !== null) {
                visuallyHiddenText.textContent = 'Hide sidebar menu';
              }
            }
          },
        );
        sidebarCollapsibleContainer.addEventListener(
          'shown.bs.collapse',
          (event) => {
            if (event.target.id === 'az-sidebar-collapsible') {
              sidebarCollapsibleRegion.classList.remove('d-none');
              sidebarCollapseIcon.textContent = 'left_panel_close';
              event.target.classList.remove('col-auto');
            }
            setWidthOfFullWidthElements();
          },
        );
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
