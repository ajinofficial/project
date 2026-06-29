@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-product-save-form]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var button = form.querySelector('[data-product-save-button]');

                    if (!button) {
                        return;
                    }

                    button.disabled = true;
                    button.classList.add('is-loading');
                    button.setAttribute('aria-busy', 'true');
                });
            });
        });
    </script>
@endonce
