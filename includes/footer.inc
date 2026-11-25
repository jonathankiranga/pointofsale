<?php
echo '</div></div></main></main>';
echo '<script src="css/assets/dist/js/bootstrap.bundle.min.js"></script>'; 
echo '<script src="css/sidebars.js"></script>';
?>

  <div class="toast" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-body text-white">
    </div>
  </div>

  <div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" id="cancelModalBtn"  data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
          <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" id="closeModalBtn" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>

<script>
    
window.start_load = function(){
   $('body').prepend('<di id="preloader2"></di>')
 }

 window.end_load = function(){
   $('#preloader2').fadeOut('fast', function() {
       $(this).remove();
     })
 }
  
$('#closeModalBtn').on('click', function() {
  $('.modal').modal('hide');
});

$('#submit').click(function() {
  $('.modal').submit();
});



 window.uni_modal = function($title = '', $url = '', $size = "", $postData = {}) {
    start_load();
   $.ajax({
        url: $url,
        data: $postData, // Pass the postData as the data for the AJAX request
        type: 'POST', // Specify the request type as POST
        error: err => {
            console.log(err);
            alert("An error occurred");
        },
        success: function(resp) {
            if (resp) {
                $('#uni_modal .modal-title').html($title);
                $('#uni_modal .modal-body').html(resp);
                if ($size != '') {
                    $('#uni_modal .modal-dialog').addClass($size);
                } else {
                    $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md");
                }
                $('#uni_modal').modal('show');
                end_load();
                // Load nicEdit on the textarea element within the modal
               var editor= new nicEditor({fullPanel : true}).panelInstance('ParameterName');  
                var content = editor.instanceById('ParameterName').getContent();
            }
        }
    });
   
}
   
 window._confjson = function($msg = '', $func = '', $params = {}) {
 
     $('#confirm_modal #confirm').attr('onclick', $func + "(" + JSON.stringify($params) + ")");
     $('#confirm_modal .modal-body').html($msg)
     $('#confirm_modal').modal('show')
     // Hide the confirmation modal after clicking the "Continue" button
    $('#confirm_modal #confirm').on('click', function() {
      $('#confirm_modal').modal('hide');
     });
    
    $('#confirm_modal #cancelModalBtn').on('click', function() {
      $('#confirm_modal').modal('hide');
    });
  }
  
  
  window._conf = function($msg='',$func='',$params = []){
     $('#confirm_modal #confirm').attr('onclick',$func+"("+$params.join(',')+")")
     $('#confirm_modal .modal-body').html($msg)
     $('#confirm_modal').modal('show')
      // Hide the confirmation modal after clicking the "Continue" button
    $('#confirm_modal #confirm').on('click', function() {
      $('#confirm_modal').modal('hide');
     });
    
    $('#confirm_modal #cancelModalBtn').on('click', function() {
      $('#confirm_modal').modal('hide');
    });
  }
  
 
   window.alert_toast= function($msg = 'TEST',$bg = 'success'){
      $('#alert_toast').removeClass('bg-success')
      $('#alert_toast').removeClass('bg-danger')
      $('#alert_toast').removeClass('bg-info')
      $('#alert_toast').removeClass('bg-warning')

    if($bg == 'success')
      $('#alert_toast').addClass('bg-success')
    if($bg == 'danger')
      $('#alert_toast').addClass('bg-danger')
    if($bg == 'info')
      $('#alert_toast').addClass('bg-info')
    if($bg == 'warning')
      $('#alert_toast').addClass('bg-warning')
    $('#alert_toast .toast-body').html($msg)
    $('#alert_toast').toast({delay:3000}).toast('show');
  }
  
  $(document).ready(function(){
    $('#preloader').fadeOut('fast', function() {
        $(this).remove();
      })
  })


        $('#<?php echo isset($_GET['link']) ? $_GET['link'] : '' ?>Nav').removeClass('link-dark')
    	$('#<?php echo isset($_GET['link']) ? $_GET['link'] : '' ?>Nav').addClass('active')
        
        window.addEventListener('load', function() {
            var forms = document.querySelectorAll('form');
            for (var i = 0; i < forms.length; i++) {
              forms[i].setAttribute('autocomplete', 'off');
            }

            var inputs = document.querySelectorAll('input');
            for (var j = 0; j < inputs.length; j++) {
              inputs[j].setAttribute('autocapitalize', 'none');
              inputs[j].setAttribute('autocorrect', 'off');
              inputs[j].setAttribute('spellcheck', 'false');
            }
          });
</script>
 
<script>
(function () {
    function preserveCurrentBackOfficeRoute(form) {
        var action = form.getAttribute('action');
        if (!action || !window.location.search) {
            return;
        }

        var target;
        try {
            target = new URL(action, window.location.href);
        } catch (error) {
            return;
        }

        if (target.origin === window.location.origin &&
            target.pathname === window.location.pathname &&
            target.search === '') {
            form.setAttribute('action', window.location.pathname + window.location.search);
        }
    }

    function updateForms(root) {
        var scope = root && root.querySelectorAll ? root : document;
        if (scope.matches && scope.matches('form')) {
            preserveCurrentBackOfficeRoute(scope);
        }
        Array.prototype.forEach.call(scope.querySelectorAll('form'), preserveCurrentBackOfficeRoute);
    }

    updateForms(document);

    if (document.body && typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                    if (node.nodeType === 1) {
                        updateForms(node);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
})();
</script>

</body>
</html>