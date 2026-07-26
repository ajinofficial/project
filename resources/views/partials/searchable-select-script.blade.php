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
