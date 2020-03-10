/**
 * @file
 * Adds data targets for Bootstrap accordions.
 *
 * Initializes ids, classes, data targets and aria controls to cause Bootstrap
 * accordions to function correctly.
 */

(function($, Drupal) {
  Drupal.behaviors.azAccordionBlock = {
    attach() {
      // Check if accordion group exists on page.
      if ($(".accordion").length) {
        // Set counter for ID generation.
        let accordionIdCounter = 1;
        // Loop through all accordion groups on page, in case multiple groups.
        $(".accordion").each(function() {
          // Generate & set unique ID for each accordion group.
          const thisAccordionId = `accordion_${accordionIdCounter}`;
          $(this).attr("id", thisAccordionId);
          // Set counter for heading ID generation.
          let accordionHeadingIdCounter = 1;
          $(this)
            .find(".card")
            .each(function() {
              // Generate & set unique accordion heading ID.
              const thisAccordionHeadingId = `accordion_${accordionIdCounter}_heading_${accordionHeadingIdCounter}`;
              $(this)
                .find(".card-header")
                .attr("id", thisAccordionHeadingId);
              // Generate & set unique collapse ID.
              const thisAccordionCollapseId = `accordion_${accordionIdCounter}_collapse_${accordionHeadingIdCounter}`;
              // Set data attributes.
              $(this)
                .find("button.btn-link")
                .attr("data-target", `#${thisAccordionCollapseId}`);
              $(this)
                .find("button.btn-link")
                .attr("data-toggle", "collapse");
              $(this)
                .find("button.btn-link")
                .attr("type", "button");
              $(this)
                .find("button.btn-link")
                .attr("aria-controls", thisAccordionCollapseId);
              // Set attributes for collapse.
              $(this)
                .find(".collapse")
                .attr("id", thisAccordionCollapseId);
              $(this)
                .find(".collapse")
                .attr("aria-labeledby", thisAccordionHeadingId);
              $(this)
                .find(".collapse")
                .attr("data-parent", `#${thisAccordionId}`);
              accordionHeadingIdCounter += 1;
            });
          accordionIdCounter += 1;
        });
      }
    }
  };
})(jQuery, Drupal);
