<script>
(function () {
    var saving = false;
    document.addEventListener('input', function (event) {
        var field = event.target.closest('[data-service-action="expense"] input[name="unit_cost"], [data-service-action="payment"] input[name="amount"]');
        if (!field) return;
        var parts = field.value.replace(/[^0-9.]/g, '').split('.');
        var maxDigits = field.name === 'amount' ? 8 : 6;
        field.value = parts[0].slice(0, maxDigits) + (parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '');
    });
    document.addEventListener('submit', async function (event) {
        var form = event.target.closest('[data-service-action]');
        if (!form) return;
        event.preventDefault();
        if (saving) return;
        saving = true;
        var button = form.querySelector('button[type="submit"]');
        var summary = form.querySelector('[data-action-error]');
        var status = document.querySelector('[data-service-action-status]');
        status.textContent = '';
        summary.hidden = true;
        form.querySelectorAll('[data-field-error]').forEach(function (error) { error.remove(); });
        form.querySelectorAll('[aria-invalid]').forEach(function (field) { field.removeAttribute('aria-invalid'); });
        document.querySelectorAll('[data-service-action] button').forEach(function (item) { item.disabled = true; });
        button.classList.add('is-loading');
        button.setAttribute('aria-busy', 'true');
        try {
            var response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            });
            if (response.status === 422) {
                var validation = await response.json();
                Object.keys(validation.errors || {}).forEach(function (name) {
                    var field = form.elements.namedItem(name);
                    var label = field && field.closest('label');
                    if (!label) {
                        summary.textContent = validation.errors[name][0];
                        summary.hidden = false;
                        return;
                    }
                    var error = document.createElement('small');
                    error.dataset.fieldError = '';
                    error.setAttribute('role', 'alert');
                    error.textContent = validation.errors[name][0];
                    label.appendChild(error);
                    field.setAttribute('aria-invalid', 'true');
                });
                var first = form.querySelector('[aria-invalid]');
                if (first) first.focus();
                return;
            }
            if (!response.ok) throw new Error(window.t("common.request_failed_9fcda3"));
            var data = await response.json();
            var page = new DOMParser().parseFromString(data.html, 'text/html');
            var panelSelector = form.dataset.serviceAction === 'payment' ? '[data-payment-panel]' : '[data-expense-panel]';
            var panel = page.querySelector(panelSelector);
            var stats = page.querySelector('.service-stats');
            if (!panel || !stats) throw new Error(window.t("orders.missing_order_details_3ab8eb"));
            document.querySelector(panelSelector).replaceWith(panel);
            document.querySelector('.service-stats').replaceWith(stats);
            status.textContent = data.message;
        } catch (error) {
            summary.textContent = window.t("validation.unable_to_save_please_try_again_3c289d");
            summary.hidden = false;
        } finally {
            saving = false;
            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
            document.querySelectorAll('[data-service-action] button').forEach(function (item) { item.disabled = false; });
        }
    });
})();
</script>
