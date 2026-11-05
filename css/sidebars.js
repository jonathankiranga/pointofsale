/* global bootstrap: false */
(function () {
  'use strict'
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  })
})()

var clientHeight = document.getElementById('headerid').clientHeight;

document.getElementById('mainsidebar').style.position='absolute';
document.getElementById('mainsidebar').style.top=(28+clientHeight)+'px';

document.getElementById('view-panel').style.position='absolute';
document.getElementById('view-panel').style.top=(28+clientHeight)+'px';

