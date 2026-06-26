(function (Drupal, once) {
  Drupal.behaviors.hidePageSummary = {
    attach(context) {
      // Find the pager summary and hide it
      const elements = once('hide-summary-key', '#az-page-summary', context);
      elements.forEach((element) => {
        element.classList.add('d-none');
      });
    },
  };
})(Drupal, once);
