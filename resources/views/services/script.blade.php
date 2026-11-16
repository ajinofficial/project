<script>
(function () {
    var createForm = document.querySelector('#new-service .service-form');
    var createButton = createForm.querySelector('button[type="submit"]');
    var createError = createForm.querySelector('[data-service-create-error]');
    createForm.elements.charge.addEventListener('input', function () {
        var parts = this.value.replace(/[^0-9.]/g, '').split('.');
        this.value = parts[0].slice(0, 8) + (parts.length > 1 ? '.' + parts.slice(1).join('').slice(0, 2) : '');
    });
    var categories = @json($serviceCategories);
    var categoryField = createForm.elements.service_category;
    var typeField = createForm.elements.service_type;
    var customField = createForm.elements.custom_service_type;
    var customLabel = createForm.querySelector('[data-custom-service]');
    function toggleCustom() {
        var custom = typeField.value === 'Custom service';
        customLabel.hidden = !custom;
        customField.disabled = !custom;
        customField.required = custom;
    }
    function updateTypes(selected) {
        typeField.replaceChildren(new Option('Select service', ''));
        (categories[categoryField.value] || []).concat(categoryField.value ? ['Custom service'] : []).forEach(function (type) {
            typeField.add(new Option(type, type));
        });
        typeField.disabled = !categoryField.value;
        if (selected) {
            typeField.value = (categories[categoryField.value] || []).includes(selected) ? selected : 'Custom service';
            if (typeField.value === 'Custom service' && selected !== 'Custom service') customField.value = selected;
        }
        toggleCustom();
    }
    categoryField.addEventListener('change', function () { updateTypes(''); });
    typeField.addEventListener('change', toggleCustom);
    updateTypes(typeField.dataset.selected);
    createForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (createButton.disabled) return;
        createForm.querySelectorAll('label small[role="alert"]').forEach(function (error) { error.remove(); });
        createForm.querySelectorAll('[aria-invalid]').forEach(function (field) { field.removeAttribute('aria-invalid'); });
        createError.hidden = true;
        var missingField = !categoryField.value ? categoryField : (typeField.value === 'Custom service' && !customField.value.trim() ? customField : null);
        if (missingField) {
            var error = document.createElement('small');
            error.setAttribute('role', 'alert');
            error.textContent = missingField === categoryField ? 'Please select a service category.' : 'Please enter a custom service.';
            missingField.closest('label').appendChild(error);
            missingField.setAttribute('aria-invalid', 'true');
            missingField.focus();
            return;
        }
        createButton.disabled = true;
        createButton.classList.add('is-loading');
        createButton.setAttribute('aria-busy', 'true');
        createButton.querySelector('.product-save-button__loading').setAttribute('aria-hidden', 'false');
        var created = false;
        try {
            var payload = new FormData(createForm);
            if (typeField.value === 'Custom service') payload.set('service_type', customField.value.trim());
            var response = await fetch(createForm.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: payload
            });
            if (response.status === 422) {
                var validation = await response.json();
                Object.keys(validation.errors || {}).forEach(function (name) {
                    var field = createForm.elements.namedItem(name);
                    if (!field) return;
                    var error = document.createElement('small');
                    error.setAttribute('role', 'alert');
                    error.textContent = validation.errors[name][0];
                    field.setAttribute('aria-invalid', 'true');
                    field.closest('label').appendChild(error);
                });
                var firstInvalid = createForm.querySelector('[aria-invalid="true"]');
                if (firstInvalid) firstInvalid.focus();
                return;
            }
            if (!response.ok) throw new Error('Unable to create service order. Please try again.');
            var data = await response.json();
            window.location.assign(data.redirect);
            created = true;
        } catch (error) {
            createError.textContent = 'Unable to create service order. Please try again.';
            createError.hidden = false;
        } finally {
            if (created) return;
            createButton.disabled = false;
            createButton.classList.remove('is-loading');
            createButton.removeAttribute('aria-busy');
            createButton.querySelector('.product-save-button__loading').setAttribute('aria-hidden', 'true');
        }
    });
    var form = document.querySelector('[data-service-filters]');
    var listing = document.querySelector('[data-service-listing]');
    var results = document.querySelector('[data-service-results]');
    var loader = document.querySelector('[data-service-loading]');
    var errorMessage = document.querySelector('[data-service-error]');
    var activeRequest = null;
    var searchTimer = null;

    function filterUrl() {
        var url = new URL(form.action);
        new FormData(form).forEach(function (value, key) {
            if (value !== '') url.searchParams.set(key, value);
        });
        return url.href;
    }

    async function loadListing(url, pushState) {
        window.clearTimeout(searchTimer);
        if (activeRequest) activeRequest.abort();
        var controller = new AbortController();
        activeRequest = controller;
        listing.classList.add('is-loading');
        loader.setAttribute('aria-hidden', 'false');
        results.setAttribute('aria-busy', 'true');
        errorMessage.hidden = true;
        try {
            var response = await fetch(url, {
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal
            });
            if (!response.ok || response.redirected) throw new Error('Unable to load services');
            var page = new DOMParser().parseFromString(await response.text(), 'text/html');
            var nextResults = page.querySelector('[data-service-results]');
            if (!nextResults) throw new Error('Service listing was not found');
            if (activeRequest !== controller) return;
            results.innerHTML = nextResults.innerHTML;
            var params = new URL(url).searchParams;
            form.elements.search.value = params.get('search') || '';
            form.elements.status.value = params.get('status') || '';
            if (pushState !== false) window.history.pushState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError' && activeRequest === controller) errorMessage.hidden = false;
        } finally {
            if (activeRequest === controller) {
                activeRequest = null;
                listing.classList.remove('is-loading');
                loader.setAttribute('aria-hidden', 'true');
                results.removeAttribute('aria-busy');
            }
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        loadListing(filterUrl());
    });
    form.addEventListener('change', function () { loadListing(filterUrl()); });
    form.elements.search.addEventListener('input', function () {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () { loadListing(filterUrl()); }, 350);
    });
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-service-clear], [data-service-results] .product-pagination a');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        loadListing(link.href);
    });
    window.addEventListener('popstate', function () { loadListing(window.location.href, false); });
})();
</script>
