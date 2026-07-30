@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-searchable-select]').forEach(function (component) {
                var search = component.querySelector('[data-searchable-select-input]');
                var select = component.querySelector('select');
                var menu = component.querySelector('[data-searchable-select-menu]');
                var status = component.querySelector('[data-searchable-select-status]');

                if (!search || !select || !menu || component.dataset.searchableSelectBound) return;
                component.dataset.searchableSelectBound = 'true';

                if (component.dataset.searchableSelectUrl) {
                    var remotePage = 0;
                    var remoteHasMore = true;
                    var remoteLoading = false;
                    var remoteRequest = 0;
                    var remoteTimer;
                    var remoteQuery = '';

                    function remoteCloseMenu() {
                        menu.hidden = true;
                        search.setAttribute('aria-expanded', 'false');
                    }

                    function remoteSelect(product) {
                        var option = Array.from(select.options).find(function (item) {
                            return String(item.value) === String(product.id);
                        });

                        if (!option) {
                            option = document.createElement('option');
                            option.value = product.id;
                            select.appendChild(option);
                        }

                        option.textContent = product.label;
                        option.dataset.purchasePrice = product.purchase_price || '';
                        option.dataset.sellingPrice = product.selling_price || '';
                        select.value = String(product.id);
                        search.value = product.label;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        remoteCloseMenu();
                    }

                    function appendRemoteProduct(product) {
                        if (menu.querySelector('[data-product-id="' + product.id + '"]')) return;
                        var item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'searchable-select__option';
                        item.dataset.productId = product.id;
                        item.setAttribute('role', 'option');
                        item.textContent = product.label;
                        item.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            remoteSelect(product);
                        });
                        menu.appendChild(item);
                    }

                    function showRemoteLoader() {
                        var loader = document.createElement('span');
                        loader.className = 'searchable-select__loader';
                        loader.dataset.searchableSelectLoader = '';
                        loader.textContent = 'Loading products';
                        menu.appendChild(loader);
                    }

                    async function loadRemoteProducts(reset, queryOverride) {
                        if ((!reset && remoteLoading) || (!reset && !remoteHasMore)) return;
                        if (reset) {
                            remotePage = 0;
                            remoteHasMore = true;
                            remoteQuery = queryOverride === undefined ? search.value.trim() : queryOverride;
                            menu.innerHTML = '';
                        }

                        remoteLoading = true;
                        var request = ++remoteRequest;
                        menu.querySelector('[data-searchable-select-loader]')?.remove();
                        showRemoteLoader();
                        menu.hidden = false;
                        search.setAttribute('aria-expanded', 'true');
                        status.textContent = 'Loading products…';

                        var url = new URL(component.dataset.searchableSelectUrl, window.location.origin);
                        url.searchParams.set('page', String(remotePage + 1));
                        if (remoteQuery) url.searchParams.set('search', remoteQuery);

                        try {
                            var response = await fetch(url, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (!response.ok) throw new Error('Unable to load products.');
                            var payload = await response.json();
                            if (request !== remoteRequest) return;

                            menu.querySelector('[data-searchable-select-loader]')?.remove();
                            payload.data.forEach(appendRemoteProduct);
                            remotePage = payload.meta.current_page;
                            remoteHasMore = payload.meta.has_more;

                            if (!menu.querySelector('.searchable-select__option')) {
                                var empty = document.createElement('span');
                                empty.className = 'searchable-select__empty';
                                empty.textContent = 'No products found';
                                menu.appendChild(empty);
                            } else if (!remoteHasMore) {
                                var end = document.createElement('span');
                                end.className = 'searchable-select__empty';
                                end.textContent = 'All products loaded';
                                menu.appendChild(end);
                            }
                            status.textContent = payload.meta.total + (payload.meta.total === 1 ? ' product found' : ' products found');
                        } catch (error) {
                            menu.querySelector('[data-searchable-select-loader]')?.remove();
                            var failed = document.createElement('button');
                            failed.type = 'button';
                            failed.className = 'searchable-select__empty';
                            failed.textContent = 'Could not load products. Click to retry.';
                            failed.addEventListener('click', function () { loadRemoteProducts(reset); });
                            menu.appendChild(failed);
                            status.textContent = 'Could not load products';
                        } finally {
                            if (request === remoteRequest) remoteLoading = false;
                        }
                    }

                    search.addEventListener('focus', function () {
                        loadRemoteProducts(true, select.value ? '' : search.value.trim());
                    });
                    search.addEventListener('input', function () {
                        select.value = '';
                        clearTimeout(remoteTimer);
                        remoteTimer = setTimeout(function () { loadRemoteProducts(true); }, 250);
                    });
                    search.addEventListener('keydown', function (event) {
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            menu.querySelector('.searchable-select__option')?.focus();
                        } else if (event.key === 'Escape') {
                            remoteCloseMenu();
                        }
                    });
                    menu.addEventListener('scroll', function () {
                        if (menu.scrollTop + menu.clientHeight >= menu.scrollHeight - 40) {
                            loadRemoteProducts(false);
                        }
                    });
                    document.addEventListener('click', function (event) {
                        if (!component.contains(event.target)) remoteCloseMenu();
                    });

                    var remoteSelected = select.selectedOptions[0];
                    if (remoteSelected && remoteSelected.value) search.value = remoteSelected.textContent.trim();
                    status.textContent = 'Scroll to load more products';
                    return;
                }

                var options = Array.from(select.options).filter(function (option) { return option.value !== ''; });

                function closeMenu() {
                    menu.hidden = true;
                    search.setAttribute('aria-expanded', 'false');
                }

                function renderOptions() {
                    var query = search.value.trim().toLowerCase();
                    var matches = options.filter(function (option) {
                        return query === '' || option.textContent.toLowerCase().includes(query);
                    });

                    menu.innerHTML = '';
                    matches.forEach(function (option) {
                        var item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'searchable-select__option';
                        item.setAttribute('role', 'option');
                        item.textContent = option.textContent.trim();
                        item.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            select.value = option.value;
                            search.value = option.textContent.trim();
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            closeMenu();
                        });
                        menu.appendChild(item);
                    });

                    if (matches.length === 0) {
                        var empty = document.createElement('span');
                        empty.className = 'searchable-select__empty';
                        empty.textContent = 'No products found';
                        menu.appendChild(empty);
                    }

                    status.textContent = matches.length + (matches.length === 1 ? ' product found' : ' products found');
                    menu.hidden = false;
                    search.setAttribute('aria-expanded', 'true');
                }

                search.addEventListener('focus', renderOptions);
                search.addEventListener('input', function () {
                    select.value = '';
                    renderOptions();
                });
                search.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        menu.querySelector('.searchable-select__option')?.focus();
                    } else if (event.key === 'Escape') {
                        closeMenu();
                    }
                });

                document.addEventListener('click', function (event) {
                    if (!component.contains(event.target)) closeMenu();
                });

                var selected = select.selectedOptions[0];
                if (selected && selected.value !== '') search.value = selected.textContent.trim();
                status.textContent = options.length + ' products available';
            });
        });
    </script>
@endonce
