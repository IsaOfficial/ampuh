(function(window, document) {
  'use strict';

  function toArray(list) {
    return Array.prototype.slice.call(list || []);
  }

  function getCheckboxes(selector) {
    return toArray(document.querySelectorAll(selector));
  }

  function isVisibleInCurrentTablePage(checkbox) {
    var row = checkbox.closest('tr');
    return !!(row && row.offsetParent !== null);
  }

  function getVisibleCheckboxes(selector) {
    return getCheckboxes(selector).filter(isVisibleInCurrentTablePage);
  }

  function countSelected(selector) {
    var selected = {};

    getCheckboxes(selector).forEach(function(checkbox) {
      if (checkbox.checked) {
        selected[checkbox.value] = true;
      }
    });

    return Object.keys(selected).length;
  }

  function bindVisibleSelectAll(options) {
    var selectAll = document.getElementById(options.selectAllId);
    var selector = options.checkboxSelector;

    if (!selectAll || !selector) {
      return null;
    }

    function syncSelectAll() {
      var visibleCheckboxes = getVisibleCheckboxes(selector);
      var checkedCount = visibleCheckboxes.filter(function(checkbox) {
        return checkbox.checked;
      }).length;

      selectAll.checked = visibleCheckboxes.length > 0 && checkedCount === visibleCheckboxes.length;
      selectAll.indeterminate = checkedCount > 0 && checkedCount < visibleCheckboxes.length;
    }

    selectAll.addEventListener('change', function() {
      getVisibleCheckboxes(selector).forEach(function(checkbox) {
        checkbox.checked = selectAll.checked;
      });
      syncSelectAll();
    });

    document.addEventListener('change', function(event) {
      if (event.target && event.target.matches(selector)) {
        syncSelectAll();
      }
    });

    var table = selectAll.closest('table');
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && table) {
      window.jQuery(table).on('draw.dt', syncSelectAll);
    }

    syncSelectAll();

    return {
      selectedCount: function() {
        return countSelected(selector);
      },
      sync: syncSelectAll
    };
  }

  window.AmpuhBulkActions = {
    bindVisibleSelectAll: bindVisibleSelectAll,
    selectedCount: countSelected
  };
})(window, document);
