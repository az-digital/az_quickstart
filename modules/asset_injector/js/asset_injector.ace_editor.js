/**
 * @file
 * Asset Injector applies Ace Editor to simplify work.
 */

(function ($, Drupal, once) {
  Drupal.behaviors.assetInjector = {
    attach() {
      /* global ace */
      if (typeof ace === 'undefined' || typeof ace.edit !== 'function') {
        return;
      }
      $('body').addClass('ace-editor-added');
      $(once('ace-editor-added', '.ace-editor')).each(function () {
        const textarea = $(this).parent().siblings().find('textarea');
        const mode = $(textarea).attr('data-ace-mode');

        if (mode) {
          $(textarea).addClass('ace-editor-text-area');

          ace.config.set(
            'basePath',
            '//cdnjs.cloudflare.com/ajax/libs/ace/1.8.1',
          );
          ace.config.set(
            'modePath',
            '//cdnjs.cloudflare.com/ajax/libs/ace/1.8.1',
          );
          ace.config.set(
            'themePath',
            '//cdnjs.cloudflare.com/ajax/libs/ace/1.8.1',
          );

          const editor = ace.edit(this);
          editor.getSession().setMode(`ace/mode/${mode}`);
          editor.getSession().setTabSize(2);
          editor.getSession().setUseSoftTabs();

          editor.getSession().on('change', function () {
            textarea[0].value = editor.getSession().getValue();
          });

          editor.setValue(textarea[0].value);

          // When the form fails to validate because the text area is required,
          // shift the focus to the editor.
          textarea.on('focus', function () {
            editor.focus();
          });
        }
      });
    },
  };
})(jQuery, Drupal, once);
