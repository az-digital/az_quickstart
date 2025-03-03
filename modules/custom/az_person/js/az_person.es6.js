document.querySelectorAll('.horizontal-tabs-list').forEach(function(tabContainer) {
  tabContainer.setAttribute('aria-label', 'Additional information');
  tabContainer.setAttribute('role', 'tablist');
});

document.querySelectorAll('.horizontal-tab-button').forEach(function(tabSelected, index) {
  tabSelected.setAttribute('role', 'tab');
  tabSelected.setAttribute('aria-selected', index === 0 ? 'true' : 'false'); // Select the first tab by default
  tabSelected.setAttribute('aria-controls', `panel${index + 1}`); // Relates the tab to its panel
  tabSelected.setAttribute('tabindex', index === 0 ? '0' : '-1'); // Make the first tab focusable
});

