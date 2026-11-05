/* global bootstrap: false */
(function () {
  'use strict';

  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );

  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  /*
   * Sidebar and content positioning is controlled by css/assets/css/style.css.
   * Do not assign inline position/top values here: those override the responsive
   * fixed-sidebar layout and can push menu headings outside the initial viewport.
   */
})();
