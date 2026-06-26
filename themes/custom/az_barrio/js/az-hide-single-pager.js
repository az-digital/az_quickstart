(function (Drupal, once) {
    Drupal.behaviors.hidePageSummary = {
        attach: function (context) {
            // Find the pager summary and hide it
            const elements = context.querySelectorAll('#az-page-summary');
            elements.forEach(element => {
                element.classList.add('d-none');
            });
        },
    };
})(Drupal, once);