/**
 * @file
 * Provides the fieldgroup behaviors for field UI.
 */

(($, Drupal, once) => {
  Drupal.behaviors.fieldUIFieldsOverview = {
    attach(context, settings) {
      once('field-field-overview', 'table#field-overview', context).forEach(
        (table) => {
          Drupal.fieldUIOverview.attach(
            table,
            settings.fieldUIRowsData,
            Drupal.fieldUIFieldOverview,
          );
        },
      );
    },
  };

  /**
   * Row handlers for the 'Manage fields' screen.
   */
  Drupal.fieldUIFieldOverview = Drupal.fieldUIFieldOverview || {};

  Drupal.fieldUIFieldOverview.group = function fieldUIFieldOverview(row, data) {
    this.row = row;
    this.name = data.name;
    this.region = data.region;
    this.tableDrag = data.tableDrag;

    // Attach change listener to the 'group format' select.
    this.$formatSelect = $('select.field-group-type', row);
    this.$formatSelect.change(Drupal.fieldUIOverview.onChange);

    return this;
  };

  Drupal.fieldUIFieldOverview.group.prototype = {
    getRegion() {
      return 'main';
    },
    // eslint-disable-next-line no-unused-vars
    regionChange(region, recurse) {
      return {};
    },

    regionChangeFields(region, element, refreshRows) {
      // Create a new tabledrag rowObject, that will compute the group's child
      // rows for us.
      const { tableDrag } = element;
      // eslint-disable-next-line new-cap
      const rowObject = new tableDrag.row(element.row, 'mouse', true);
      // Skip the main row, we handled it above.
      rowObject.group.shift();

      // Let child rows handlers deal with the region change - without recursing
      // on nested group rows, we are handling them all here.
      $.each(rowObject.group, (index, childRow) => {
        const childRowHandler = $(childRow).data('fieldUIRowHandler');
        $.extend(refreshRows, childRowHandler.regionChange(region, false));
      });
    },
  };

  /**
   * Row handlers for the 'Manage display' screen.
   */
  Drupal.fieldUIDisplayOverview = Drupal.fieldUIDisplayOverview || {};

  Drupal.fieldUIDisplayOverview.group = function fieldUIDisplayOverview(
    row,
    data,
  ) {
    this.row = row;
    this.name = data.name;
    this.region = data.region;
    this.tableDrag = data.tableDrag;

    // Attach change listener to the 'group format' select.
    this.$regionSelect = $(row).find('select.field-region');
    this.$regionSelect.on('change', Drupal.fieldUIOverview.onChange);

    return this;
  };

  Drupal.fieldUIDisplayOverview.group.prototype = {
    getRegion: function getRegion() {
      return this.$regionSelect.val();
    },

    regionChange(region, recurse) {
      // Default recurse to true.
      recurse = typeof recurse === 'undefined' || recurse;

      // When triggered by a row drag, the 'region' select needs to be adjusted to
      // the new region.
      region = region.replace(/-/g, '_');
      this.$regionSelect.val(region);

      const refreshRows = {};
      refreshRows[this.name] = this.$regionSelect.get(0);

      if (recurse) {
        this.regionChangeFields(region, this, refreshRows);
      }

      return refreshRows;
    },

    regionChangeFields(region, element, refreshRows) {
      // Create a new tabledrag rowObject, that will compute the group's child
      // rows for us.
      const { tableDrag } = element;
      // eslint-disable-next-line new-cap
      const rowObject = new tableDrag.row(element.row, 'mouse', true);
      // Skip the main row, we handled it above.
      rowObject.group.shift();

      // Let child rows handlers deal with the region change - without recursing
      // on nested group rows, we are handling them all here.
      $.each(rowObject.group, (index, childRow) => {
        const childRowHandler = $(childRow).data('fieldUIRowHandler');
        $.extend(refreshRows, childRowHandler.regionChange(region, false));
      });
    },
  };
})(jQuery, Drupal, once);
