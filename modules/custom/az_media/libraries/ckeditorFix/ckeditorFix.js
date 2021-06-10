(function ($, Drupal) {
    Drupal.behaviors.customCKEditorConfig = {
      attach: function (context, settings) {
        if (typeof CKEDITOR !== "undefined") {
            CKEDITOR.dtd.$removeEmpty['i'] = false;
            CKEDITOR.dtd.$removeEmpty['span'] = false;
            console.log(CKEDITOR.dtd);

        }
      }
    }
  })(jQuery, Drupal);
