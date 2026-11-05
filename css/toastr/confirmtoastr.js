function showConfirm(callback) {
  toastr.options = {
    closeButton: true,
    positionClass: 'toast-top-center',
    timeOut: '0',
    extendedTimeOut: '0',
    onclick: null,
    tapToDismiss: false,
    onCloseClick: function () {
      return false;
    },
    progressBar: false,
    preventDuplicates: true,
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut',
  };

  toastr.warning(
    'Are you sure you want to perform this action?<br />\n\<button type="button" class="btn clear">Yes</button>\n\<button type="button" class="btn clear">No</button>',
    '',
    {
      timeOut: '0',
      extendedTimeOut: '0',
      onShown: function () {
        var toast = $('.toast:last-child');
        toast.find('.clear').click(function () {
          if ($(this).index() === 0) {
            toastr.success('You clicked "Yes"');
            // Call the callback function with true
            callback(true);
          } else {
            toastr.info('You clicked "No"');
            // Call the callback function with false
            callback(false);
          }
          toastr.clear(toast, { force: true });
        });
      },
    }
  );

  // Prevent default behavior of buttons
  $(document).on('click', '.clear', function (e) {
    e.preventDefault();
  });
}