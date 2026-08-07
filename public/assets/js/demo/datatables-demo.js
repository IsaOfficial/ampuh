// Call the DataTables jQuery plugin.
// Keep the controls outside the horizontal table scroller so mobile pagination
// does not get clipped when the table itself is wider than the screen.
$.fn.DataTable.ext.pager.numbers_length = 5;

$(document).ready(function() {
  $('table[data-server-side="true"], #dataTable').each(function() {
    if ($.fn.DataTable.isDataTable(this)) {
      return;
    }

    var $table = $(this);
    var isServerSide = $table.attr('data-server-side') === 'true';
    var ajaxUrl = $table.attr('data-ajax-url');
    var options = {
      autoWidth: false,
      deferRender: true,
      pagingType: 'simple_numbers',
      pageLength: 10,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      scrollX: true,
      language: {
        paginate: {
          previous: 'Prev',
          next: 'Next'
        }
      }
    };

    $table.closest('.table-responsive').addClass('datatable-responsive-shell');

    var defaultOrderColumn = parseInt($table.attr('data-order-column') || '2', 10);
    var defaultOrderDirection = $table.attr('data-order-direction') || 'desc';
    var disabledColumns = ($table.attr('data-order-disabled') || '')
      .split(',')
      .map(function(value) {
        return parseInt(value.trim(), 10);
      })
      .filter(function(value) {
        return !isNaN(value);
      });

    options.order = [
      [defaultOrderColumn, defaultOrderDirection]
    ];

    if (disabledColumns.length) {
      options.columnDefs = [{
        targets: disabledColumns,
        orderable: false
      }];
    }

    if (isServerSide && ajaxUrl) {
      options.processing = true;
      options.serverSide = true;
      options.ajax = {
        url: ajaxUrl,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        data: function(data) {
          var params = new URLSearchParams(window.location.search || '');

          params.forEach(function(value, key) {
            data['filter_' + key] = value || '';
          });
        },
        error: function(xhr, error, thrown) {
          console.error('DataTables AJAX error:', error, thrown, xhr.responseText);
        }
      };
    }

    var dataTable = $table.DataTable(options);

    if ($table.hasClass('table-trash-theme')) {
      $(dataTable.table().container()).addClass('datatable-trash-theme');
    }
  });
});
