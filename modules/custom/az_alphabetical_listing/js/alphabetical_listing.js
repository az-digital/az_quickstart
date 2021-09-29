jQuery(document).ready(function($) {

  /**
   * Loop through each alpha navigation list item and determine if the
   * corresponding search result group exists on the page. If it doesn't
   * exist on the page, then hide the navigation item.
   */  
  $("#az-js-alpha-navigation li").each(function (i, li) {
    // Get ID of current nav item.
    var group_id = $(this).children().attr('data-href');

    // Enable nav item if results group exists on page
    if($(group_id).length != 0) {
      $(this).removeClass('disabled');
      $(this).children().attr('tabindex','0').attr('aria-hidden','false').attr('href', group_id);
    }

    // Disable nav item if no results group exists on page
    else {
      $(this).addClass('disabled');
      $(this).children().attr('tabindex','-1').attr('aria-hidden','true').removeAttr('href');
    }
  });

  /**
   *  function azAlphabeticalListingCheckNoResults()
   * 
   *  Determines if there are no results that match the provided search query.
   *  If there are no results, then it will display the "no results" message.
   *  Otherwise, the "no results" message remains hidden;
   */
  function azAlphabeticalListingCheckNoResults() {

    var visibleResults = false;
    $(".az-alphabetical-listing-group-title").each(function(){
      if(!$(this).hasClass("hide-result")) {
        visibleResults = true;
      }
    });

    if (!visibleResults) {
      $('#az-js-alphabetical-listing-no-results').show();
    } else {
      $('#az-js-alphabetical-listing-no-results').hide();
    }
  }


  /** 
   * function azAlphabeticalListingGroupLoop()
   * Arguments:
   *  - changeDisplay | boolean
   *    If TRUE, update the display of each heading as needed
   *  - updateNav | boolean
   *    If TURE, update display of corresponding nav item
   * 
   * Check if search result "group" has no results by determining if it has
   * an immediate sibling of .az-alphabetical-listing-group-title
   */
  function azAlphabeticalListingGroupLoop() {
    $(".az-alphabetical-listing-group-title").each(function(){
      // Get the ID of the current results group
      var thisId = $(this).attr("id");
      var thisGroup = thisId.toLowerCase();
      // Set the target class to search with
      var targetGroup = ".az-alphabetical-letter-group-" + thisGroup;

      // Set variable to determine if there are visible children
      var visibleChildren = false;
      // Loop through each item in the results group
      $(targetGroup).each(function(){
        if(!$(this).hasClass("hide-result")) {
          // Set variable to true if item isn't hidden
          visibleChildren = true;
        }
      });

     
      // Get nav item with data attribute that matches the group's ID
      var navTarget = $("#az-js-alpha-navigation").find(".page-link[data-href='#" + thisId + "']");

      if(!visibleChildren) {
        // Hide title if no visible children in the group
        $(this).hide();
        $(this).addClass("hide-result");


        // Hide nav item if no visible children in the group
        navTarget.parent().addClass("disabled");
        navTarget.attr('tabindex','-1').attr('aria-hidden','true').removeAttr('href');
      }
      else {
        // Show title if visible children in the group
        $(this).show();
        $(this).removeClass("hide-result");

        // Show nav item if visible children in the group
        navTarget.parent().removeClass("disabled");
        navTarget.attr('tabindex','0').attr('aria-hidden','false').attr('href', $(this).attr('id'));
      }

    });
  }


  /**
   *  Perform search as query is entered into the search input field.
   */  
  $("#az-js-alphabetical-listing-search").keyup(function () {
    // Retrieve the input field text
    var filter = $(this).val();

    /** 
     * Loop through the .az-js-alphabetical-listing-search-result items and
     * determine if the item should be shown or hidden, based on the search
     * query text provided.
     */ 
    $(".az-js-alphabetical-listing-search-result").each(function () {
      // Get text for current item in loop.
      var searchResultText = $(this).find(".az-alphabetical-listing-item").text();

      // Hide the item if it doesn't contain search query text.
      if(searchResultText.search(new RegExp(filter, "i")) < 0) {
        $(this).find('az-alphabetical-listing-item').attr('tabindex','0')
        $(this).addClass("hide-result");
        $(this).hide();
      }
      // Show the item is it does contain search query text.
      else {
        $(this).find('.az-alphabetical-listing-item').attr('tabindex','0')
        $(this).removeClass("hide-result");
        $(this).show();
      }
    });


    // Determine if groups have results shown
    azAlphabeticalListingGroupLoop();

    // Determine if "no results" message is needed
    azAlphabeticalListingCheckNoResults();
  });



  /**
   * On click of alpha navigation items, create a smooth scrolling effect.
   */
  var $root = $('html, body');
  var breakpoint = 600;

  $('#az-js-alpha-navigation a').on('click', function(event){

    event.preventDefault();
    var $alpha_nav = $('#az-js-floating-alpha-nav-container');
    var href = $.attr(this, 'data-href');
    var fixed_nav_height = $alpha_nav.outerHeight();
    var heading_height = $(".az-alphabetical-listing-group-title:first").outerHeight();

    if ($(window).width() <= breakpoint) {
      fixed_nav_height = 0;
    }
    if (!$alpha_nav.hasClass('affix')) {
      fixed_nav_height *= 2;
    }
    $root.animate({
      scrollTop: $(href).offset().top - fixed_nav_height - heading_height
    }, 500, function () {
      window.location.hash = href;
    });
  });
});
