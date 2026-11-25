<?php
echo '</main>';
echo '<script src="css/assets/dist/js/bootstrap.bundle.min.js"></script>'; 
echo '<script src="css/sidebars.js"></script>';
echo '
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

</body>';
echo '</html>';
?>