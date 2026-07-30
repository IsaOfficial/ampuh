// Call the DataTables jQuery plugin.
// Keep the controls outside the horizontal table scroller so mobile pagination
// does not get clipped when the table itself is wider than the screen.
$(document).ready(function() {
  $('#dataTable').each(function() {
    if ($.fn.DataTable.isDataTable(this)) {
      return;
    }

    $(this).closest('.table-responsive').addClass('datatable-responsive-shell');

    $(this).DataTable({
      autoWidth: false,
      deferRender: true,
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
    });
  });
});
