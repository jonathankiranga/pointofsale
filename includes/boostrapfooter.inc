<?php
echo '</main>';
echo '<script src="css/assets/dist/js/bootstrap.bundle.min.js"></script>'; 
echo '<script src="css/sidebars.js"></script>';
echo '
<script>
(function () {
    function routeFormsToDirectPage(form) {
        var current;
        try {
            current = new URL(window.location.href);
        } catch (error) {
            return;
        }

        var route = current.searchParams.get('link');
        if (!route || !/^[A-Za-z0-9_-]+$/.test(route)) {
            return;
        }

        var action = form.getAttribute('action');
        var target;
        try {
            target = new URL(action || current.href, current.href);
        } catch (error) {
            return;
        }

        if (target.origin !== current.origin || target.pathname !== current.pathname) {
            return;
        }

        var actionRoute = target.searchParams.get('link');
        if (actionRoute && actionRoute !== route) {
            return;
        }

        target.pathname = current.pathname.replace(/[^/]*$/, route + '.php');
        target.searchParams.delete('link');

        current.searchParams.forEach(function (value, key) {
            if (key !== 'link' && !target.searchParams.has(key)) {
                target.searchParams.append(key, value);
            }
        });

        form.setAttribute('action', target.pathname + (target.search ? target.search : ''));
    }

    function updateForms(root) {
        var scope = root && root.querySelectorAll ? root : document;
        if (scope.matches && scope.matches('form')) {
            routeFormsToDirectPage(scope);
        }
        Array.prototype.forEach.call(scope.querySelectorAll('form'), routeFormsToDirectPage);
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