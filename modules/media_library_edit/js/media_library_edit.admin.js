(function ($, Drupal, window) {

  Drupal.behaviors.MediaLibraryWidgetWarn = {
    attach: function attach(context) {
      // Override the existing warning from media_library/js/media_library.ui.js
      // to disable for the edit link.
      var $editLink = $('.js-media-library-item a[href]:not(.media-library-edit__link)');
      $(once('media-library-warn-link', $editLink)).each(function (index) {
        $(this).on('click', function (e) {
          var message = Drupal.t('Unsaved changes to the form will be lost. Are you sure you want to leave?');
          var confirmation = window.confirm(message);
          if (!confirmation) {
            e.preventDefault();
          }
        });
      });
    }
  };

  Drupal.behaviors.MediaLibraryWidgetEditLink = {
    attach: function attach() {
      var $editLink = $('.media-library-widget .media-library-edit__link');
      $(once('media-library-edit-link', $editLink)).each(function (index) {
        $(this).on('click', function () {
          // Remove any "selected-media" classes.
          $(this).parent().parent().find('selected-media').removeClass('selected-media');
          // Mark the media item as selected to render it properly when submitting an ajax media edit request.
          $(this).parent().find('article').addClass('selected-media');
        })
      });
    },
  };

  $(window)
    .on('dialog:aftercreate', function(dialog, settings, $element) {
      if ($element[0].classList.contains('media-library-edit__modal')) {
        $element[0].scrollTop = 0;
      }
    });

})(jQuery, Drupal, window);
