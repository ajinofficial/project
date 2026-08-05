@if ($errors->any())
    <div class="error-summary" role="alert">
        <strong>Check the product details</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div class="field-grid">
    <label>
        <span>Item name</span>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" placeholder="Enter item name" required>
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
    <label>
        <span>Minimum stock level</span>
        <input type="number" name="minimum_stock_level" value="{{ old('minimum_stock_level', $product->minimum_stock_level ?? auth()->user()->tenant->low_stock_threshold) }}" min="0" step="1" required data-replace-on-focus>
        @error('minimum_stock_level') <small>{{ $message }}</small> @enderror
    </label>
</div>

<section class="product-image-uploader" data-product-image-uploader>
    <div>
        <span>Product image</span>
        <p>Upload a product photo, then zoom and reposition it for the square crop used in lists and cards.</p>
    </div>

    <div class="image-crop-grid">
        <label class="image-upload-drop">
            <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp" data-product-image-input>
            <b>Choose image</b>
            <small>JPG, PNG, or WebP up to 2 MB. The saved image will be cropped square.</small>
        </label>

        <div class="crop-preview-card">
            <div class="crop-preview-frame" tabindex="0" role="application" aria-label="Product image crop. Drag to reposition or use the arrow keys." data-product-crop-frame>
                <img data-product-image-preview src="{{ old('image_url', $product->image_url) }}" alt="" @if (! old('image_url', $product->image_url)) hidden @endif>
                <span data-product-image-placeholder @if (old('image_url', $product->image_url)) hidden @endif>{{ strtoupper(substr(old('name', $product->name ?: 'P'), 0, 1)) }}</span>
            </div>
            <small class="crop-position-hint" data-product-crop-hint>Choose an image to adjust its crop.</small>
            <label class="crop-zoom-control">
                <span>Crop zoom <output data-product-zoom-value>100%</output></span>
                <input type="range" min="1" max="3" step="0.05" value="1" aria-label="Crop zoom" data-product-image-zoom disabled>
            </label>
            <button class="crop-reset-button" type="button" data-product-crop-reset disabled>Reset crop</button>
            <small class="crop-file-error" role="alert" data-product-image-error hidden></small>
        </div>
    </div>

    <input type="hidden" name="cropped_image" value="" data-product-cropped-image>
    @error('cropped_image') <small>{{ $message }}</small> @enderror
    @error('product_image') <small>{{ $message }}</small> @enderror
</section>

<label>
    <span>Description</span>
    <textarea name="description" rows="5" placeholder="Shelf location, package contents, reorder notes, or selling details.">{{ old('description', $product->description) }}</textarea>
    @error('description') <small>{{ $message }}</small> @enderror
</label>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
                var zoomValue = uploader.querySelector('[data-product-zoom-value]');
                var cropFrame = uploader.querySelector('[data-product-crop-frame]');
                var cropHint = uploader.querySelector('[data-product-crop-hint]');
                var resetButton = uploader.querySelector('[data-product-crop-reset]');
                var imageError = uploader.querySelector('[data-product-image-error]');
                var form = uploader.closest('form');
                var sourceImage = new Image();
                var hasUpload = false;
                var panX = 0;
                var panY = 0;
                var dragStart = null;

                function clamp(value, minimum, maximum) {
                    return Math.min(maximum, Math.max(minimum, value));
                }

                function cropGeometry() {
                    var frameSize = cropFrame.clientWidth;
                    var zoom = parseFloat(zoomInput.value || '1');
                    var imageRatio = sourceImage.naturalWidth / sourceImage.naturalHeight;
                    var baseWidth = imageRatio >= 1 ? frameSize * imageRatio : frameSize;
                    var baseHeight = imageRatio >= 1 ? frameSize : frameSize / imageRatio;
                    var width = baseWidth * zoom;
                    var height = baseHeight * zoom;

                    return {
                        width: width,
                        height: height,
                        overflowX: Math.max(0, (width - frameSize) / 2),
                        overflowY: Math.max(0, (height - frameSize) / 2)
                    };
                }

                function updatePreview() {
                    zoomValue.value = Math.round(parseFloat(zoomInput.value || '1') * 100) + '%';

                    if (!hasUpload || !sourceImage.naturalWidth) {
                        return;
                    }

                    var geometry = cropGeometry();
                    preview.style.width = geometry.width + 'px';
                    preview.style.height = geometry.height + 'px';
                    preview.style.left = 'calc(50% - ' + (geometry.width / 2 + panX * geometry.overflowX) + 'px)';
                    preview.style.top = 'calc(50% - ' + (geometry.height / 2 + panY * geometry.overflowY) + 'px)';
                }

                fileInput.addEventListener('change', function () {
                    var file = fileInput.files && fileInput.files[0];

                    if (!file) {
                        return;
                    }

                    imageError.hidden = true;

                    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
                        fileInput.value = '';
                        imageError.textContent = file.size > 2 * 1024 * 1024
                            ? 'Choose an image no larger than 2 MB.'
                            : 'Choose a JPG, PNG, or WebP image.';
                        imageError.hidden = false;
                        return;
                    }

                    var reader = new FileReader();

                    reader.onload = function (event) {
                        hasUpload = true;
                        sourceImage = new Image();
                        sourceImage.onload = function () {
                            preview.src = event.target.result;
                            preview.setAttribute('data-crop-ready', '');
                            preview.hidden = false;
                            placeholder.hidden = true;
                            zoomInput.value = '1';
                            panX = 0;
                            panY = 0;
                            cropFrame.classList.add('is-adjustable');
                            cropHint.textContent = 'Drag the image to reposition it, or use the arrow keys.';
                            zoomInput.disabled = false;
                            resetButton.disabled = false;
                            updatePreview();
                        };
                        sourceImage.src = event.target.result;
                    };

                    reader.readAsDataURL(file);
                });

                zoomInput.addEventListener('input', updatePreview);
                resetButton.addEventListener('click', function () {
                    zoomInput.value = '1';
                    panX = 0;
                    panY = 0;
                    updatePreview();
                });

                cropFrame.addEventListener('pointerdown', function (event) {
                    if (!hasUpload) return;
                    dragStart = { x: event.clientX, y: event.clientY, panX: panX, panY: panY };
                    cropFrame.setPointerCapture(event.pointerId);
                    cropFrame.classList.add('is-dragging');
                });

                cropFrame.addEventListener('pointermove', function (event) {
                    if (!dragStart) return;
                    var geometry = cropGeometry();
                    panX = geometry.overflowX ? clamp(dragStart.panX - (event.clientX - dragStart.x) / geometry.overflowX, -1, 1) : 0;
                    panY = geometry.overflowY ? clamp(dragStart.panY - (event.clientY - dragStart.y) / geometry.overflowY, -1, 1) : 0;
                    updatePreview();
                });

                function endDrag() {
                    dragStart = null;
                    cropFrame.classList.remove('is-dragging');
                }

                cropFrame.addEventListener('pointerup', endDrag);
                cropFrame.addEventListener('pointercancel', endDrag);
                cropFrame.addEventListener('keydown', function (event) {
                    var movement = event.shiftKey ? 0.1 : 0.025;
                    if (event.key === 'ArrowLeft') panX = clamp(panX - movement, -1, 1);
                    else if (event.key === 'ArrowRight') panX = clamp(panX + movement, -1, 1);
                    else if (event.key === 'ArrowUp') panY = clamp(panY - movement, -1, 1);
                    else if (event.key === 'ArrowDown') panY = clamp(panY + movement, -1, 1);
                    else return;
                    event.preventDefault();
                    updatePreview();
                });

                window.addEventListener('resize', updatePreview);

                form.addEventListener('submit', function () {
                    if (!hasUpload || !sourceImage.naturalWidth || !sourceImage.naturalHeight) {
                        return;
                    }

                    var zoom = parseFloat(zoomInput.value || '1');
                    var outputSize = 900;
                    var canvas = document.createElement('canvas');
                    var context = canvas.getContext('2d');
                    var cropSize = Math.min(sourceImage.naturalWidth, sourceImage.naturalHeight) / zoom;
                    var sourceX = (sourceImage.naturalWidth - cropSize) / 2 + panX * (sourceImage.naturalWidth - cropSize) / 2;
                    var sourceY = (sourceImage.naturalHeight - cropSize) / 2 + panY * (sourceImage.naturalHeight - cropSize) / 2;

                    canvas.width = outputSize;
                    canvas.height = outputSize;
                    context.drawImage(sourceImage, sourceX, sourceY, cropSize, cropSize, 0, 0, outputSize, outputSize);
                    croppedInput.value = canvas.toDataURL('image/jpeg', 0.88);
                });
            });
        });
    </script>
@endonce
