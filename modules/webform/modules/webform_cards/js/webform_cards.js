/**
 * @file
 * JavaScript behaviors for webform cards.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.webform = Drupal.webform || {};
  Drupal.webform.cards = Drupal.webform.cards || {};
  // Autoforward (defaults to 1/4 second delay).
  Drupal.webform.cards.autoForwardDelay = Drupal.webform.cards.autoForwardDelay || 250;

  /**
   * Initialize webform cards.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformCards = {
    attach: function (context) {
      // Determine if the form is the context or it is within the context.
      var $forms = $(context).is('form.webform-submission-form')
        ? $(context)
        : $('form.webform-submission-form', context);

      $(once('webform-cards', $forms)).each(function () {
        // Form.
        var $form = $(this);

        // Options from data-* attributes.
        var options = {
          progressStates: $form[0].hasAttribute('data-progress-states'),
          progressLink: $form[0].hasAttribute('data-progress-link'),
          autoForward: $form[0].hasAttribute('data-auto-forward'),
          autoForwardHideNextButton: $form[0].hasAttribute('data-auto-forward-hide-next-button'),
          keyboard: $form[0].hasAttribute('data-keyboard'),
          previewLink: $form[0].hasAttribute('data-preview-link'),
          confirmation: $form[0].hasAttribute('data-confirmation'),
          track: $form.data('track'),
          toggle: $form[0].hasAttribute('data-toggle'),
          toggleHideLabel: $form.data('toggle-hide-label'),
          toggleShowLabel: $form.data('toggle-show-label'),
          ajaxEffect: $form.data('ajax-effect'),
          ajaxSpeed: $form.data('ajax-speed'),
          ajaxScrollTop: $form.data('ajax-scroll-top')
        };

        var currentPage = $form.data('current-page');

        // Progress.
        var $progress = $('.webform-progress');

        // Current card.
        var $currentCardInput = $form.find(':input[name="current_card"]');

        // Cards.
        var $allCards = $form.find('.webform-card');

        // Actions and buttons.
        var $formActions = $form.find('.form-actions').show();
        var $previewButton = $formActions.find('.webform-button--preview');
        var $submitButton = $formActions.find('.webform-button--submit');
        var $previousButton = $formActions.find('.webform-button--previous');
        var $nextButton = $formActions.find('.webform-button--next');

        // Preview.
        if (!$allCards.length) {
          setPreview();
          return;
        }

        // Display show/hide all cards link.
        if (options.toggle) {
          setToggle();
        }

        // Server-side validation errors.
        // @see \Drupal\Core\Render\Element\RenderElement::setAttributes
        var $invalidCards = $allCards.filter(':has(.form-item--error-message)');
        if ($invalidCards.length) {
          // Hide progress.
          $form.find('.webform-progress').hide();
          // Hide next and previous and only show the submit button.
          $previousButton.hide();
          $nextButton.hide();
          // Show invalid cards and shake'em.
          $invalidCards.addClass('webform-card--error');
          shake($invalidCards);
          return;
        }

        // Previous and next buttons.
        $previousButton.data('default-label', $previousButton.val());
        $nextButton.data('default-label', $nextButton.val());
        $previousButton.on('click', previousButtonClickEventHandler).show();
        $nextButton.on('click', nextButtonClickEventHandler).show();

        // Auto-forward.
        if (options.autoForward) {
          // Auto-forward on enter.
          $form.find('input')
            .not(':button, :submit, :reset, :image, :file')
            .on('keydown', function (event) {
              if (event.which === 13) {
                autoForwardEventHandler(event);
                // Disable auto submit.
                // @see Drupal.behaviors.webformDisableAutoSubmit
                event.preventDefault();
                return false;
              }
            });

          // Auto-forward on change.
          $form.find('select[data-images]:not([multiple]), input[type="range"].form-webform-rating')
            .on('change', autoForwardEventHandler);

          // Auto-forward radios with label.
          $form.find('input:radio, label[for]')
            .on('mouseup', function (event) {
              var $radio = (event.target.tagName === 'LABEL')
                ? $('#' + $(event.target).attr('for'))
                : $(this);
              if ($radio.is(':radio') && $radio.val() !== '_other_') {
                setTimeout(function () {
                  autoForwardEventHandler(event);
                });
              }
            });
        }

        // Keyboard navigation.
        if (options.keyboard) {
          $('body').on('keydown', function (event) {
            // Only track left and right keys.
            if (event.which !== 37 && event.which !== 39) {
              return;
            }

            // If input and the cursor is not at the end of the input, do not
            // trigger navigation.
            // @see https://stackoverflow.com/questions/21177489/selectionstart-selectionend-on-input-type-number-no-longer-allowed-in-chrome
            if (typeof event.target.value !== 'undefined'
              && typeof event.target.selectionStart !== 'undefined'
              && event.target.selectionStart !== null) {
              if (event.target.value.length !== event.target.selectionStart) {
                return;
              }
              // Ignore the left keydown event if the input has a value.
              if (event.target.value.length && event.which === 37) {
                return;
              }
            }

            // If input[type="radio"] ignore left/right keys which are used to
            // navigate between radio buttons.
            if (event.target.tagName === 'INPUT' && event.target.type === 'radio') {
              return;
            }

            switch (event.which) {
              // Left key triggers the previous button.
              case 37:
                setTimeout(function () {$previousButton.trigger('click');}, Drupal.webform.cards.autoForwardDelay);
                break;

              // Right key triggers the next button.
              case 39:
                setTimeout(function () {$nextButton.trigger('click');}, Drupal.webform.cards.autoForwardDelay);
                break;
            }
          });
        }

        // Track when cards are hidden/shown via #states conditional logic.
        if (options.progressStates) {
          $(document).on('state:visible state:visible-slide', function stateVisibleEventHandler(e) {
            if ($(e.target).hasClass('webform-card') && $.contains($form[0], e.target)) {
              trackProgress();
              trackActions();
            }
          });
        }

        // Custom events.
        // Add support for custom 'webform_cards:set_active_card' event.
        $allCards.on('webform_cards:set_active_card', function (event) {
          var $activeCard = $(event.target);
          setActiveCard($activeCard);
        });

        initialize();

        /* ****************************************************************** */
        // Private functions.
        /* ****************************************************************** */

        /**
         * Initialize the active card.
         */
        function initialize() {
          var currentCard = $currentCardInput.val();
          var $activeCard = currentCard ? $allCards.filter('[data-webform-key="' + currentCard + '"]') : [];
          if (!$activeCard.length) {
            $activeCard = $allCards.first();
          }
          setActiveCard($activeCard, true);
        }

        /**
         * Set the active card.
         *
         * @param {jQuery} $activeCard
         *   An jQuery object containing the active card.
         * @param {boolean} initialize
         *   Are cards being initialize.
         *   If TRUE, no transition or scrolling effects will be triggered.
         */
        function setActiveCard($activeCard, initialize) {
          if (!$activeCard.length) {
            return;
          }

          // Track the previous active card.
          var $prevCard = $allCards.filter('.webform-card--active');

          // Unset the previous active card and set the active card.
          $prevCard.removeClass('webform-card--active');
          $activeCard.addClass('webform-card--active');

          // Trigger card change event.
          $form.trigger('webform_cards:change', [$activeCard]);

          // Allow card change event to reset the active card, this allows for
          // card change event handler to apply custom validation
          // and conditional logic.
          $activeCard = $allCards.filter('.webform-card--active');
          if ($activeCard.get(0) === $prevCard.get(0)) {
            initialize = true;
          }

          // Show the active card.
          if (!initialize) {
            // Show the active card.
            applyAjaxEffect($activeCard);

            // Scroll to the top of the page or form.
            Drupal.webformScrollTop($activeCard, options.ajaxScrollTop);
          }

          // Focus the active card's first visible input.
          autofocus($activeCard);

          // Set current card.
          $currentCardInput.val($activeCard.data('webform-key'));
          $form.attr('data-webform-current-card', $activeCard.data('webform-key'));

          // Track the current page in a form data attribute and the URL.
          trackCurrentPage($activeCard);

          // Track progress.
          trackProgress();

          // Track actions.
          trackActions();
        }

        /**
         * Track the current page in a form data attribute and the URL.
         *
         * @param {jQuery} $activeCard
         *   An jQuery object containing the active card.
         *
         * @see \Drupal\webform\WebformSubmissionForm::form
         * @see Drupal.behaviors.webformWizardTrackPage
         */
        function trackCurrentPage($activeCard) {
          if (!options.track) {
            return;
          }

          var page = (options.track === 'index')
            ? ($allCards.index($activeCard) + 1)
            : $activeCard.data('webform-key');

          // Set form data attribute.
          $form.data('webform-wizard-current-page', page);

          // Set URL
          var url = window.location.toString();
          var regex = /([?&])page=[^?&]+/;
          if (url.match(regex)) {
            url = url.replace(regex, '$1page=' + page);
          }
          else {
            url = url + (url.indexOf('?') !== -1 ? '&page=' : '?page=') + page;
          }
          window.history.replaceState(null, null, url);
        }

        /**
         * Track actions
         */
        function trackActions() {
          var $activeCard = $allCards.filter('.webform-card--active');

          // Set the previous and next labels.
          setButtonLabel($previousButton, $activeCard.data('prev-button-label') || $previousButton.data('default-label'));
          setButtonLabel($nextButton, $activeCard.data('next-button-label') || $nextButton.data('default-label'));

          // Show/hide the previous button.
          var hasPrevCard = !!$activeCard.prevAll('.webform-card:not([style*="display: none"])').length;
          $previousButton.toggle(hasPrevCard);

          // Hide/show the next button and submit buttons.
          var hasNextCard = !!$activeCard.nextAll('.webform-card:not([style*="display: none"])').length;
          $previewButton.toggle(!hasNextCard);
          $submitButton.toggle(!hasNextCard);
          $nextButton.toggle(hasNextCard);

          // Hide the next button when auto-forwarding.
          if (hideAutoForwardNextButton()) {
            $nextButton.hide();
          }
        }

        /**
         * Track progress.
         *
         * @see webform/templates/webform-progress.html.twig
         * @see webform/templates/webform-progress-tracker.html.twig
         */
        function trackProgress() {
          // Hide/show cards and update steps.
          var cards = getCardsProgressSteps();
          for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            var cardAttributeName = '[data-webform-' + card.type + '="' + card.key + '"]';

            var $cardStep = $progress.find(cardAttributeName);

            // Set card and page step.
            $cardStep.find('[data-webform-progress-step]').attr('data-text', card.step);
            if (card.type === 'page') {
              continue;
            }

            // Hide/show card step.
            $cardStep.toggle(!card.hidden);

            // Set .is-active and .is-complete classes.
            $cardStep.toggleClass('is-active', card.active);
            $cardStep.toggleClass('is-complete', !card.active && card.complete);

            // Set 'Current' and 'Complete' state.
            var $cardState = $cardStep.find('[data-webform-progress-state]');
            $cardState.toggle(card.active || card.complete);
            if (card.active) {
              $cardState.html(Drupal.t('Current'));
            }
            if (card.complete) {
              $cardState.html(Drupal.t('Complete'));
            }

            // Link card step.
            if (options.progressLink) {
              var $links = $cardStep.find('[data-webform-progress-link]');
              $links.data('webform-key', card.key);
              if (card.complete) {
                if ($links.attr('role') !== 'link') {
                  $links
                    .attr({'role': 'link', 'title': card.title, 'aria-label': card.title, 'tabindex': '0'})
                    .on('click', function () {
                      var $card = $allCards.filter('[data-webform-key="' + $(this).data('webform-key') + '"]');
                      setActiveCard($card);
                    })
                    .on('keydown', function (event) {
                      if (event.which === 13) {
                        var $card = $allCards.filter('[data-webform-key="' + $(this).data('webform-key') + '"]');
                        setActiveCard($card);
                      }
                    });
                }
              }
              else if ($links.attr('role') === 'link') {
                $links.removeAttr('role title aria-label tabindex')
                  .off('click keydown');
              }
            }
          }

          // Set properties.
          var properties = getCardsProgressProperties();
          for (var property in properties) {
            if (properties.hasOwnProperty(property)) {
              var attribute = '[data-webform-progress-' + property + ']';
              var value = properties[property];
              $progress.find(attribute).html(value);
            }
          }

          // Set <progress> tag [value] and [max] attributes.
          $progress.find('progress').attr({
            value: properties.index,
            max: properties.total
          });
        }

        /**
         * Set show/hide all cards toggle button.
         */
        function setToggle() {
          var $toggle = $('<button type="button" class="webform-cards-toggle"></button>')
            .html(options.toggleShowLabel)
            .on('click', toggleEventHandler)
            .wrap('<div class="webform-cards-toggle-wrapper"></div>')
            .parent();
          $allCards.eq(0).before($toggle);
        }

        /**
         * Set preview.
         */
        function setPreview() {
          if (currentPage !== 'webform_preview' || !$form.find('.webform-preview').length) {
            return;
          }

          if (options.keyboard) {
            $('body').on('keydown', function (event) {
              switch (event.which) {
                case 37: // left.
                  setTimeout(function () {$previousButton.trigger('click');}, Drupal.webform.cards.autoForwardDelay);
                  break;

                case 39: // right
                  setTimeout(function () {$submitButton.trigger('click');}, Drupal.webform.cards.autoForwardDelay);
                  break;
              }
            });
          }
          setPreviewLinks();
        }

        /**
         * Set links to previous pages/cards in preview.
         */
        function setPreviewLinks() {
          var $button = $form.find('.js-webform-wizard-pages-link[data-webform-page="webform_start"]');

          // Link to previous pages in progress steps (aka bar).
          if (options.progressLink) {
            $progress.find('[data-webform-card]').each(function () {
              var $step = $(this);
              var card = $step.data('webform-card');
              var title = $step.attr('title');
              $step
                .find('[data-webform-progress-link]')
                .attr({'role': 'link', 'title': title, 'aria-label': title, 'tabindex': '0'})
                .on('click', function () {
                  // Set current card.
                  $currentCardInput.val(card);
                  // Click button to return to the 'webform_start' page.
                  $button.trigger('click');
                })
                .on('keydown', function (event) {
                  if (event.which === 13) {
                    $(this).trigger('click');
                  }
                });
            });
          }

          // Link to previous pages in preview.
          if (options.previewLink) {
            $form
              .find('.webform-card-edit[data-webform-card]')
              .each(function appendEditButton() {
                var $card = $(this);

                var card = $card.data('webform-card');
                var title = $card.attr('title');

                var $cardButton = $button.clone();
                $cardButton
                  .removeAttr('data-webform-page data-msg-required')
                  .attr('id', $cardButton.attr('id') + '-' + card)
                  .attr('name', $cardButton.attr('name') + '-' + card)
                  .attr('data-drupal-selector', $cardButton.attr('data-drupal-selector') + '-' + card)
                  .attr('title', Drupal.t("Edit '@title'", {'@title': title}).toString())
                  .on('click', function () {
                    // Set current card.
                    $currentCardInput.val(card);
                    // Click button to return to the 'webform_start' page.
                    $button.trigger('click');
                    return false;
                  });
                $card.append($cardButton).show();
              });
          }
        }

        /**
         * Get cards progress properties.
         *
         * Properties include index, total, percentage, and summary.
         *
         * @return {{summary: string, total: number, percentage: string,
         *   index: *}} Cards progress properties.
         */
        function getCardsProgressProperties() {
          var $activeCard = $allCards.filter('.webform-card--active');

          var $visibleCards = $allCards.filter(':not([style*="display: none"])');

          var index = (currentPage === 'webform_preview')
            ? $visibleCards.length + 1
            : $visibleCards.index($activeCard);

          var total = $visibleCards.length
            + ($previewButton.length ? 1 : 0)
            + (options.confirmation ? 1 : 0);

          var percentage = Math.round((index / (total - 1)) * 100);

          var summary = Drupal.t(
            '@index of @total',
            {'@index': index + 1, '@total': total}
          );

          return {
            index: index + 1,
            total: total,
            percentage: percentage + '%',
            summary: summary
          };
        }

        /**
         * Get cards as progress steps.
         *
         * @return {[]}
         *   Cards as progress steps.
         */
        function getCardsProgressSteps() {
          var $activeCard = $allCards.filter('.webform-card--active');
          var activeKey = $activeCard.data('webform-key');

          var cards = [];

          // Append cards.
          var step = 0;
          var isComplete = true;
          $allCards.each(function () {
            var $card = $(this);
            var key = $card.data('webform-key');
            var title = $card.data('title');

            // Set active and complete classes.
            var isActive = (activeKey === key);
            if (isActive) {
              isComplete = false;
            }

            // Hide/show progress based on conditional logic.
            var isHidden = false;
            if (options.progressStates) {
              isHidden = $card.is('[style*="display: none"]');
              if (!isHidden) {
                step++;
              }
            }
            else {
              step++;
            }

            cards.push({
              type: 'card',
              key: key,
              title: title,
              step: isHidden ? null : step,
              hidden: isHidden,
              active: isActive,
              complete: isComplete
            });
          });

          // Append preview and confirmation pages.
          $(['webform_preview', 'webform_confirmation']).each(function () {
            var $progressStep = $form.find('[data-webform-progress-steps] [data-webform-page="' + this + '"]');
            if ($progressStep.length) {
              step++;
              cards.push({
                type: 'page',
                key: this,
                step: step
              });
            }
          });
          return cards;
        }

        /**
         * Apply Ajax effect to elements.
         *
         * @param {jQuery} $elements
         *   An jQuery object containing elements to be displayed.
         */
        function applyAjaxEffect($elements) {
          switch (options.ajaxEffect) {
            case 'fade':
              $elements.hide().fadeIn(options.ajaxSpeed);
              break;

            case 'slide':
              $elements.hide().slideDown(options.ajaxSpeed);
              break;
          }
        }

        /* ****************************************************************** */
        // Event handlers.
        /* ****************************************************************** */

        /**
         * Toggle event handler.
         *
         * @param {jQuery.Event} event
         *   The event triggered.
         */
        function toggleEventHandler(event) {
          if ($form.hasClass('webform-cards-toggle-show')) {
            $form.removeClass('webform-cards-toggle-show');
            $(this)
              .attr('title', options.toggleShowLabel)
              .html(options.toggleShowLabel);
            var $activeCard = $allCards.filter('.webform-card--active');
            setActiveCard($activeCard);
          }
          else {
            $form.addClass('webform-cards-toggle-show');
            $(this)
              .attr('title', options.toggleHideLabel)
              .html(options.toggleHideLabel);
            var $visibleCards = $allCards.filter(':not([style*="display: none"])');
            applyAjaxEffect($visibleCards);
            $nextButton.hide();
            $previousButton.hide();
            $previewButton.show();
            $submitButton.show();

            // Trigger card change event with no active card.
            $form.trigger('webform_cards:change');
          }
        }

        /**
         * Previous button event handler.
         *
         * @param {jQuery.Event} event
         *   The event triggered.
         */
        function previousButtonClickEventHandler(event) {
          // Get previous visible card (not "display: none").
          var $previousCard = $allCards.filter('.webform-card--active')
            .prevAll('.webform-card:not([style*="display: none"])')
            .first();
          setActiveCard($previousCard);
          // Prevent the button's default behavior.
          event.preventDefault();
        }

        /**
         * Next button event handler.
         *
         * @param {jQuery.Event} event
         *   The event triggered.
         */
        function nextButtonClickEventHandler(event) {
          var validator = $form.validate(drupalSettings.cvJqueryValidateOptions);
          if (!$form.valid()) {
            // Focus first invalid input.
            validator.focusInvalid();
            // Shake the invalid card.
            var $activeCard = $allCards.filter('.webform-card--active');
            shake($activeCard);
          }
          else {
            // Get next visible card (not "display: none").
            var $nextCard = $allCards.filter('.webform-card--active')
              .nextAll('.webform-card:not([style*="display: none"])')
              .first();
            if ($nextCard.length) {
              setActiveCard($nextCard);
            }
            else if ($previewButton.length) {
              $previewButton.trigger('click');
            }
            else {
              $submitButton.trigger('click');
            }
          }
          // Prevent the button's default behavior.
          event.preventDefault();
        }

        /**
         * Auto forward event handler.
         *
         * @param {jQuery.Event} event
         *   The event triggered.
         */
        function autoForwardEventHandler(event) {
          if ($form.hasClass('webform-cards-toggle-show')) {
            return;
          }

          var $activeCard = $allCards.filter('.webform-card--active');
          var $allInputs = $activeCard.find('input:visible, select:visible, textarea:visible');
          var $autoForwardInputs = $activeCard.find('input:visible, select:visible');
          if (!$autoForwardInputs.length || $allInputs.length !== $autoForwardInputs.length) {
            return;
          }

          var inputValues = [];
          $autoForwardInputs.each(function () {
            var name = this.name;
            if (!(name in inputValues)) {
              inputValues[name] = false;
            }
            if (this.type === 'radio' && this.checked) {
              inputValues[name] = true;
            }
            else if (this.type === 'select-one' && this.selectedIndex !== -1) {
              inputValues[name] = true;
            }
            else if (this.type === 'range' && this.value) {
              inputValues[name] = true;
            }
          });

          // Only auto-forward when a single input is visible.
          if (Object.keys(inputValues).length > 1) {
            return;
          }

          var inputHasValue = inputValues.every(function (value) {
            return value;
          });
          if (inputHasValue) {
            setTimeout(function () {$nextButton.trigger('click');}, Drupal.webform.cards.autoForwardDelay);
          }
        }

        /**
         * Determine if next button is hidden when auto-forwarding
         *
         * @return {{boolean}}
         *   TRUE if next button should be hidden
         */
        function hideAutoForwardNextButton() {
          if (!options.autoForwardHideNextButton) {
            return false;
          }

          if ($form.hasClass('webform-cards-toggle-show')) {
            return false;
          }

          var $activeCard = $allCards.filter('.webform-card--active');
          var $allInputs = $activeCard.find('input:visible, select:visible, textarea:visible');
          var $autoForwardInputs = $activeCard.find('input[type="radio"], select[data-images]:not([multiple]), input[type="range"].form-webform-rating');
          if (!$autoForwardInputs.length || $allInputs.length !== $autoForwardInputs.length) {
            return false;
          }

          var inputValues = [];
          var name;
          var type;
          $autoForwardInputs.each(function () {
            name = this.name;
            type = this.type;
            if (type === 'radio') {
              inputValues[name] = 'radio';
            }
            else if (type === 'select-one') {
              inputValues[name] = 'select-one';
            }
            else if (type === 'range') {
              inputValues[name] = 'range';
            }
          });

          // Only auto-forward when a single input is visible.
          if (Object.keys(inputValues).length !== 1) {
            return false;
          }

          // Determine if the auto-forward input has a value.
          switch (type) {
            case 'radio':
              return $('[name="' + name + '"]:checked').length ? false : true;

            case 'range':
              return $('[name="' + name + '"]').val() !== '0' ? false : true;

            case 'select-one':
              return $('[name="' + name + '"]').val() ? false : true;
          }
        }

        /**
         * Auto focus a card's first input, if it has not been entered.
         *
         * @param {jQuery} $activeCard
         *   An jQuery object containing the active card.
         *
         */
        function autofocus($activeCard) {
          if (!$form.hasClass('js-webform-autofocus')) {
            return;
          }

          var $firstInput = $activeCard.find(':input:visible:not([type="submit"])').first();
          if ($firstInput.length && !inputHasValue($firstInput)) {
            $firstInput.trigger('focus');
          }
        }

        /**
         * Shake an element.
         *
         * @param {jQuery} $element
         *   A jQuery object containing an element to shake.
         *
         * @see https://stackoverflow.com/questions/4399005/implementing-jquerys-shake-effect-with-animate
         */
        function shake($element) {
          var intShakes = 3;
          var intDistance = 20;
          var intDuration = 450;
          $element.css('position', 'relative');
          for (var x = 1; x <= intShakes; x++) {
            $element
              .animate({left: (intDistance * -1)}, ((intDuration / intShakes) / 4))
              .animate({left: intDistance}, ((intDuration / intShakes) / 2))
              .animate({left: 0}, ((intDuration / intShakes) / 4));
          }
        }

        /**
         * Determine if an input has been entered.
         *
         * @param {jQuery} $input
         *   An jQuery object containing an :input.
         *
         * @return {boolean}
         *   TRUE if next button should be hidden
         */
        function inputHasValue($input) {
          var type = $input[0].type;
          var name = $input[0].name;
          switch (type) {
            case 'checkbox':
            case 'radio':
              return $('[name="' + name + '"]:checked').length ? true : false;

            case 'range':
              return $('[name="' + name + '"]').val() !== '0' ? true : false;

            case 'select-one':
            default:
              return $('[name="' + name + '"]').val() ? true : false;
          }
        }

        /**
         * Set button label value or HTML markup.
         *
         * @param {jQuery} $button
         *   A jQuery object containing a <button> or <input type="submit">.
         * @param {string} label
         *   The button's label.
         */
        function setButtonLabel($button, label) {
          if ($button[0].tagName === 'BUTTON') {
            $button.html(label);
          }
          else {
            $button.val(label);
          }
        }
      });

    }
  };

})(jQuery, Drupal);
