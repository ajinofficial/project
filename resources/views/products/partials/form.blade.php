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

<label>
    <span>Supplier</span>
    <input type="text" name="supplier" value="{{ old('supplier', $product->supplier) }}" placeholder="Supplier name">
    @error('supplier') <small>{{ $message }}</small> @enderror
</label>

<div class="field-grid">
    <label>
        <span>Purchase price</span>
        <input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" min="0" step="0.01" required>
        @error('purchase_price') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>Selling price</span>
        <input type="number" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01" required>
        @error('price') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>Compare at price</span>
        <input type="number" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}" min="0" step="0.01">
        @error('compare_at_price') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>Stock on hand</span>
        <input type="number" name="inventory" value="{{ old('inventory', $product->inventory) }}" min="0" step="1" required>
        @error('inventory') <small>{{ $message }}</small> @enderror
    </label>
</div>

<div class="field-grid">
    <label>
        <span>Tax percentage</span>
        <input type="number" name="tax_percentage" value="{{ old('tax_percentage', $product->tax_percentage ?? auth()->user()->tenant->default_tax_percentage) }}" min="0" max="99.99" step="0.01" required>
        @error('tax_percentage') <small>{{ $message }}</small> @enderror
    </label>

    <label>
        <span>Minimum stock level</span>
        <input type="number" name="minimum_stock_level" value="{{ old('minimum_stock_level', $product->minimum_stock_level ?? auth()->user()->tenant->low_stock_threshold) }}" min="0" step="1" required>
        @error('minimum_stock_level') <small>{{ $message }}</small> @enderror
    </label>
</div>

<div class="field-grid stock-buckets">
    <label>
        <span>Reserved stock</span>
        <input type="number" name="reserved_stock" value="{{ old('reserved_stock', $product->reserved_stock ?? 0) }}" min="0" step="1">
    </label>
    <label>
        <span>Damaged stock</span>
        <input type="number" name="damaged_stock" value="{{ old('damaged_stock', $product->damaged_stock ?? 0) }}" min="0" step="1">
    </label>
    <label>
        <span>Returned stock</span>
        <input type="number" name="returned_stock" value="{{ old('returned_stock', $product->returned_stock ?? 0) }}" min="0" step="1">
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

    <label>
        <span>Image URL fallback</span>
        <input type="url" name="image_url" value="{{ old('image_url', $product->image_url) }}" placeholder="https://example.com/product.jpg">
        @error('image_url') <small>{{ $message }}</small> @enderror
        @error('cropped_image') <small>{{ $message }}</small> @enderror
    </label>
</section>

<label>
    <span>Description</span>
    <textarea name="description" rows="5" placeholder="Supplier notes, shelf location, package contents, reorder notes, or selling details.">{{ old('description', $product->description) }}</textarea>
    @error('description') <small>{{ $message }}</small> @enderror
</label>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
