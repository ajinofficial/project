@if ($errors->any())
    <div class="error-summary" role="alert">
        <strong>Check the product details</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div class="field-grid">
    <label>
        <span>Item name</span>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" required>
        @error('name') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>SKU / barcode</span>
        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="SKU-001 or barcode">
        @error('sku') <small>{{ $message }}</small> @enderror
    </label>
</div>

<div class="field-grid">
    <label>
        <span>Barcode</span>
        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" placeholder="Scan or enter barcode">
        @error('barcode') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>Brand</span>
        <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="Apple, Samsung, Cipla">
        @error('brand') <small>{{ $message }}</small> @enderror
    </label>
</div>

<div class="field-grid">
    <label>
        <span>Category</span>
        <input type="text" name="category" value="{{ old('category', $product->category) }}" placeholder="Grocery, Apparel, Hardware">
        @error('category') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>Status</span>
        <select name="status" required>
            <option value="draft" @selected(old('status', $product->status) === 'draft')>Draft / receiving</option>
            <option value="active" @selected(old('status', $product->status) === 'active')>Active / sellable</option>
            <option value="archived" @selected(old('status', $product->status) === 'archived')>Archived / discontinued</option>
        </select>
        @error('status') <small>{{ $message }}</small> @enderror
    </label>
</div>

<div class="field-grid">
    @php
        $purchasePriceValue = (float) old('purchase_price', $product->purchase_price ?? 0);
        $sellingPriceValue = (float) old('price', $product->price ?? 0);
        $profitPercentageValue = $purchasePriceValue > 0
            ? round((($sellingPriceValue - $purchasePriceValue) / $purchasePriceValue) * 100, 2)
            : 0;
    @endphp
    <label>
        <span>Purchase price</span>
        <input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" min="0" step="0.01" required data-replace-on-focus>
        @error('purchase_price') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>Profit percentage</span>
        <input type="number" name="profit_percentage" value="{{ old('profit_percentage', $profitPercentageValue) }}" min="0" max="100" step="0.01" required data-profit-percentage data-replace-on-focus>
        @error('profit_percentage') <small>{{ $message }}</small> @enderror
    </label>
</div>

<div class="field-grid">
    <label>
        <span>Minimum stock level</span>
        <input type="number" name="minimum_stock_level" value="{{ old('minimum_stock_level', $product->minimum_stock_level ?? auth()->user()->tenant->low_stock_threshold) }}" min="0" step="1" required data-replace-on-focus>
        @error('minimum_stock_level') <small>{{ $message }}</small> @enderror
    </label>
    <label>
        <span>Returned stock</span>
        <input type="number" name="returned_stock" value="{{ old('returned_stock', $product->returned_stock ?? 0) }}" min="0" step="1" data-replace-on-focus>
    </label>
</div>

<div class="field-grid">
    <label>
        <span>Reserved stock</span>
        <input type="number" name="reserved_stock" value="{{ old('reserved_stock', $product->reserved_stock ?? 0) }}" min="0" step="1" data-replace-on-focus>
    </label>
    <label>
        <span>Damaged stock</span>
        <input type="number" name="damaged_stock" value="{{ old('damaged_stock', $product->damaged_stock ?? 0) }}" min="0" step="1" data-replace-on-focus>
    </label>
</div>

<section class="product-image-uploader" data-product-image-uploader>
    <div>
        <span>Product image</span>
        <p>Upload a product photo and preview the common square crop used in lists and cards.</p>
    </div>

    <div class="image-crop-grid">
        <label class="image-upload-drop">
            <input type="file" accept="image/*" data-product-image-input>
            <b>Choose image</b>
            <small>JPG, PNG, or WebP. The saved image will be cropped square.</small>
        </label>

        <div class="crop-preview-card">
            <div class="crop-preview-frame">
                <img data-product-image-preview src="{{ old('image_url', $product->image_url) }}" alt="" @if (! old('image_url', $product->image_url)) hidden @endif>
                <span data-product-image-placeholder @if (old('image_url', $product->image_url)) hidden @endif>{{ strtoupper(substr(old('name', $product->name ?: 'P'), 0, 1)) }}</span>
            </div>
            <label class="crop-zoom-control">
                <span>Crop zoom</span>
                <input type="range" min="1" max="2" step="0.05" value="1" data-product-image-zoom>
            </label>
        </div>
    </div>

    <input type="hidden" name="cropped_image" value="" data-product-cropped-image>
    <input type="hidden" name="image_url" value="{{ old('image_url', $product->image_url) }}">
    @error('cropped_image') <small>{{ $message }}</small> @enderror
</section>

<label>
    <span>Description</span>
    <textarea name="description" rows="5" placeholder="Shelf location, package contents, reorder notes, or selling details.">{{ old('description', $product->description) }}</textarea>
    @error('description') <small>{{ $message }}</small> @enderror
</label>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var currency = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' });

            document.querySelectorAll('[data-replace-on-focus]').forEach(function (field) {
                field.addEventListener('focus', function () {
                    field.select();
                    field.dataset.valueSelected = 'true';
                });

                field.addEventListener('mouseup', function (event) {
                    if (field.dataset.valueSelected !== 'true') {
                        return;
                    }

                    event.preventDefault();
                    delete field.dataset.valueSelected;
                });
            });

            document.querySelectorAll('[data-product-save-form]').forEach(function (form) {
                var purchasePrice = form.querySelector('[name="purchase_price"]');
                var profitPercentage = form.querySelector('[data-profit-percentage]');

                if (!purchasePrice || !profitPercentage) {
                    return;
                }

                var preview = document.createElement('small');
                preview.setAttribute('aria-live', 'polite');
                profitPercentage.insertAdjacentElement('afterend', preview);

                function updateSellingPreview() {
                    var purchase = parseFloat(purchasePrice.value || '0');
                    var profit = parseFloat(profitPercentage.value || '0');
                    var selling = purchase + (purchase * (profit / 100));

                    preview.textContent = 'Selling price: ' + currency.format(Number.isFinite(selling) ? selling : 0);
                }

                purchasePrice.addEventListener('input', updateSellingPreview);
                profitPercentage.addEventListener('input', updateSellingPreview);
                updateSellingPreview();
            });

            document.querySelectorAll('[data-product-save-form]').forEach(function (form) {
                form.noValidate = true;

                function fieldLabel(field) {
                    var label = field.closest('label');
                    var labelText = label ? label.querySelector('span') : null;

                    return labelText ? labelText.textContent.trim() : 'This field';
                }

                function existingErrorElement(field) {
                    if (field.nextElementSibling && field.nextElementSibling.matches('[data-validation-error]')) {
                        return field.nextElementSibling;
                    }

                    return null;
                }

                function errorElement(field) {
                    var existingError = existingErrorElement(field);

                    if (existingError) {
                        return existingError;
                    }

                    var error = document.createElement('small');
                    error.setAttribute('data-validation-error', '');
                    error.setAttribute('role', 'alert');
                    field.insertAdjacentElement('afterend', error);

                    return error;
                }

                function validateField(field) {
                    var error = existingErrorElement(field);

                    if (!field.willValidate) {
                        return true;
                    }

                    if (field.checkValidity()) {
                        if (error) {
                            error.textContent = '';
                            error.hidden = true;
                        }

                        field.removeAttribute('aria-invalid');

                        return true;
                    }

                    error = errorElement(field);
                    error.textContent = field.validity.valueMissing
                        ? fieldLabel(field) + ' is required.'
                        : field.validationMessage;
                    error.hidden = false;
                    field.setAttribute('aria-invalid', 'true');

                    return false;
                }

                form.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.addEventListener('input', function () {
                        validateField(field);
                    });

                    field.addEventListener('change', function () {
                        validateField(field);
                    });
                });

                form.addEventListener('submit', function (event) {
                    var firstInvalid = null;

                    form.querySelectorAll('input, select, textarea').forEach(function (field) {
                        if (!validateField(field) && !firstInvalid) {
                            firstInvalid = field;
                        }
                    });

                    if (!firstInvalid) {
                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    firstInvalid.focus();
                });
            });

            document.querySelectorAll('[data-product-image-uploader]').forEach(function (uploader) {
                var fileInput = uploader.querySelector('[data-product-image-input]');
                var preview = uploader.querySelector('[data-product-image-preview]');
                var placeholder = uploader.querySelector('[data-product-image-placeholder]');
                var croppedInput = uploader.querySelector('[data-product-cropped-image]');
                var zoomInput = uploader.querySelector('[data-product-image-zoom]');
                var form = uploader.closest('form');
                var sourceImage = new Image();
                var hasUpload = false;

                function updatePreviewScale() {
                    if (preview) {
                        preview.style.transform = 'scale(' + zoomInput.value + ')';
                    }
                }

                fileInput.addEventListener('change', function () {
                    var file = fileInput.files && fileInput.files[0];

                    if (!file) {
                        return;
                    }

                    var reader = new FileReader();

                    reader.onload = function (event) {
                        hasUpload = true;
                        sourceImage = new Image();
                        sourceImage.onload = function () {
                            preview.src = event.target.result;
                            preview.hidden = false;
                            placeholder.hidden = true;
                            zoomInput.value = '1';
                            updatePreviewScale();
                        };
                        sourceImage.src = event.target.result;
                    };

                    reader.readAsDataURL(file);
                });

                zoomInput.addEventListener('input', updatePreviewScale);

                form.addEventListener('submit', function () {
                    if (!hasUpload || !sourceImage.naturalWidth || !sourceImage.naturalHeight) {
                        return;
                    }

                    var zoom = parseFloat(zoomInput.value || '1');
                    var outputSize = 900;
                    var canvas = document.createElement('canvas');
                    var context = canvas.getContext('2d');
                    var cropSize = Math.min(sourceImage.naturalWidth, sourceImage.naturalHeight) / zoom;
                    var sourceX = (sourceImage.naturalWidth - cropSize) / 2;
                    var sourceY = (sourceImage.naturalHeight - cropSize) / 2;

                    canvas.width = outputSize;
                    canvas.height = outputSize;
                    context.drawImage(sourceImage, sourceX, sourceY, cropSize, cropSize, 0, 0, outputSize, outputSize);
                    croppedInput.value = canvas.toDataURL('image/jpeg', 0.88);
                });
            });
        });
    </script>
@endonce
