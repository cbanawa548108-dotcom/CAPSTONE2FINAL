<x-app-layout>
<x-slot name="title">Inventory — FreshTrack</x-slot>

<script>
function inventoryData() {
    return {
    addProductModal:false,
    stockInModal:false,
    stockOutModal:false,
    editModal:false, 
    viewModal:false, 
    deleteModal:false,
    selected:null,
    sidebarOpen: true,
    activeSection: 'overview',
    productSearchQuery: '',
    productCategoryFilter: '',
    productStatusFilter: '',
    historyFiltersOpen: false,
    historySearch: '',
    historyTypeFilter: '',
    historyDateFrom: '',
    historyDateTo: '',
    stockInStep: 1,
    stockInItems: [],
    itemsPerPage: 5,
    currentPage: 1,
    
    // Stock Out data
    stockOutItems: [],
    stockOutType: 'sale',
    stockOutReason: '',
    stockOutDate: new Date().toISOString().split('T')[0],
    stockOutReference: '',
    stockOutSearchQuery: '',
    stockOutFilterCategory: '',
    selectedBatch: null,
    stockOutMode: null,
    stockOutQuantity: 0,
    
    // Stock Adjustment data
    adjustmentModal: false,
    adjustmentType: 'add',
    adjustmentItems: [],
    adjustmentReason: '',
    adjustmentNotes: '',
    adjustmentDate: new Date().toISOString().split('T')[0],
    adjustmentReference: '',
    selectedProductForAdjustment: null,
    adjustmentQuantity: 0,
    adjustmentSearchQuery: '',
    adjustmentFilterCategory: '',
    currentItem: {
        product: '',
        quantity: 0,
        unitCost: 0,
        batchId: '',
        expirationDate: '',
        shelfLife: 0
    },
    supplier: '',
    dateReceived: new Date().toISOString().split('T')[0],
    referenceNumber: '',
    
    // Add Product data
    newProduct: {
        name: '',
        category: '',
        unit: '',
        description: '',
        skuCode: '',
        shelfLife: 0
    },
    
    // Product shelf life data (in days)
    productShelfLife: {
        'Mango': 14,
        'Durian': 7,
        'Pomelo': 21,
        'Mangosteen': 14,
        'Lanzones': 10,
        'Banana': 7,
        'Pineapple': 14
    },

    matchesProduct(name, category, stock, reorderLevel) {
        const query = this.productSearchQuery.trim().toLowerCase();
        const status = stock <= 0 ? 'out' : (stock <= reorderLevel ? 'low' : 'available');
        const productName = String(name || '').toLowerCase();
        const productCategory = String(category || '').toLowerCase();

        return (!query || productName.includes(query) || productCategory.includes(query))
            && (!this.productCategoryFilter || category === this.productCategoryFilter)
            && (!this.productStatusFilter || status === this.productStatusFilter);
    },

    hasMatchingProductRows(element) {
        return Array.from(element.querySelectorAll('[data-product-row]')).some(row =>
            this.matchesProduct(
                row.dataset.productName,
                row.dataset.productCategory,
                Number(row.dataset.stock),
                Number(row.dataset.reorderLevel)
            )
        );
    },

    matchesHistory(type, item, reference, details, date) {
        const query = this.historySearch.trim().toLowerCase();
        const searchable = [type, item, reference, details].join(' ').toLowerCase();

        return (!query || searchable.includes(query))
            && (!this.historyTypeFilter || type === this.historyTypeFilter)
            && (!this.historyDateFrom || date >= this.historyDateFrom)
            && (!this.historyDateTo || date <= this.historyDateTo);
    },

    clearHistoryFilters() {
        this.historySearch = '';
        this.historyTypeFilter = '';
        this.historyDateFrom = '';
        this.historyDateTo = '';
    },

    exportInventoryHistory(element) {
        const rows = Array.from(element.querySelectorAll('[data-history-row]'))
            .filter(row => this.matchesHistory(
                row.dataset.type,
                row.dataset.item,
                row.dataset.reference,
                row.dataset.details,
                row.dataset.date
            ));

        if (!rows.length) {
            alert('There are no inventory history records to export for these filters.');
            return;
        }

        const escapeCsv = value => {
            let text = String(value ?? '');
            if (/^[=+\-@]/.test(text)) text = "'" + text;
            return '"' + text.replace(/"/g, '""') + '"';
        };
        const csvRows = [
            ['Type', 'Product', 'Batch / Reference', 'Quantity', 'Details', 'Date'],
            ...rows.map(row => [
                row.dataset.type,
                row.dataset.item,
                row.dataset.reference,
                row.dataset.quantity,
                row.dataset.details,
                row.dataset.date
            ])
        ];
        const csv = '\uFEFF' + csvRows.map(row => row.map(escapeCsv).join(',')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `inventory-history-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    },
    
    generateSKUCode(productName) {
        if (!productName) return '';
        const prefix = productName.substring(0, 3).toUpperCase();
        const timestamp = Date.now().toString().slice(-4);
        const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
        return `${prefix}-${timestamp}${random}`;
    },
    
    getShelfLifeForProduct(productName) {
        return this.productShelfLife[productName] || 14;
    },
    
    updateProductSKU() {
        if (this.newProduct.name) {
            this.newProduct.skuCode = this.generateSKUCode(this.newProduct.name);
            this.newProduct.shelfLife = this.getShelfLifeForProduct(this.newProduct.name);
        }
    },
    
    generateBatchId(product) {
        const prefix = product.substring(0, 3).toUpperCase();
        const random = Math.floor(Math.random() * 900) + 100;
        return `${prefix}-${random}`;
    },
    
    generateReferenceNumber() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 9000) + 1000;
        return `SI-${year}${month}-${random}`;
    },
    
    calculateDates(product, dateReceived) {
        const shelfLife = this.productShelfLife[product] || 14;
        const received = new Date(dateReceived);
        const expiration = new Date(received);
        expiration.setDate(expiration.getDate() + shelfLife);
        
        return {
            shelfLife: shelfLife,
            expirationDate: expiration.toISOString().split('T')[0]
        };
    },
    
    addStockItem() {
        if (this.currentItem.product && this.currentItem.quantity > 0 && this.currentItem.unitCost > 0) {
            const dates = this.calculateDates(this.currentItem.product, this.dateReceived);
            const batchId = this.generateBatchId(this.currentItem.product);
            
            this.stockInItems.push({
                product: this.currentItem.product,
                batchId: batchId,
                quantity: this.currentItem.quantity,
                unitCost: this.currentItem.unitCost,
                totalCost: this.currentItem.quantity * this.currentItem.unitCost,
                expirationDate: dates.expirationDate,
                shelfLife: dates.shelfLife
            });
            
            // Reset current item form
            this.currentItem = { product: '', quantity: 0, unitCost: 0, batchId: '', expirationDate: '', shelfLife: 0 };
        }
    },
    
    removeStockItem(index) {
        this.stockInItems.splice(index, 1);
        // Adjust current page if needed
        if (this.stockInItems.length > 0 && this.currentPage > this.totalPages) {
            this.currentPage = this.totalPages;
        }
        // Reset to page 1 if all items are removed
        if (this.stockInItems.length === 0) {
            this.currentPage = 1;
        }
    },
    
    getTotalAmount() {
        return this.stockInItems.reduce((sum, item) => sum + item.totalCost, 0);
    },
    
    get paginatedItems() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        return this.stockInItems.slice(start, end);
    },
    
    get totalPages() {
        return Math.ceil(this.stockInItems.length / this.itemsPerPage);
    },
    
    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
        }
    },
    
    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
        }
    },
    
    goToPage(page) {
        this.currentPage = page;
    },
    
    // Stock Out functions
    generateStockOutReference() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 9000) + 1000;
        return `SO-${year}${month}-${random}`;
    },
    
    get availableStock() {
        // Get from actual inventory data passed from backend
        var stock = [];
        @if(isset($inventoryItems))
            @foreach($inventoryItems as $item)
                @foreach($item->batches as $batch)
                    @if($batch->status === 'available' && $batch->quantity > 0)
                        stock.push({
                            product: @js($item->name),
                            batchId: {{ $batch->id }},
                            batchCode: @js($batch->batch_code),
                            quantity: {{ $batch->quantity }},
                            unit: @js($item->unit),
                            category: @js($item->category),
                            freshness: {{ $batch->remaining_shelf_life ?? 0 }}
                        });
                    @endif
                @endforeach
            @endforeach
        @endif
        return stock;
    },
    
    get filteredStock() {
        let filtered = this.availableStock;
        
        // Search filter
        if (this.stockOutSearchQuery) {
            const query = this.stockOutSearchQuery.toLowerCase();
            filtered = filtered.filter(item => 
                item.product.toLowerCase().includes(query) ||
                item.batchCode.toLowerCase().includes(query)
            );
        }
        
        // Category filter
        if (this.stockOutFilterCategory) {
            filtered = filtered.filter(item => item.category === this.stockOutFilterCategory);
        }
        
        return filtered;
    },
    
    get filteredAdjustmentStock() {
        let filtered = this.availableStock;
        
        // Search filter
        if (this.adjustmentSearchQuery) {
            const query = this.adjustmentSearchQuery.toLowerCase();
            filtered = filtered.filter(item => 
                item.product.toLowerCase().includes(query) ||
                item.batchId.toLowerCase().includes(query)
            );
        }
        
        // Category filter
        if (this.adjustmentFilterCategory) {
            filtered = filtered.filter(item => item.category === this.adjustmentFilterCategory);
        }
        
        return filtered;
    },
    
    selectBatchForStockOut(batch) {
        this.selectedBatch = batch;
        this.stockOutMode = null;
        this.stockOutQuantity = 0;
    },

    selectAnotherStockOutBatch() {
        this.selectedBatch = null;
        this.stockOutMode = null;
        this.stockOutQuantity = 0;
        document.getElementById('stock-out-available-batches')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    get selectedBatchRemainingQuantity() {
        if (!this.selectedBatch) return 0;
        const alreadySelected = this.stockOutItems.find(item => item.batchId === this.selectedBatch.batchId);
        return Math.max(0, this.selectedBatch.quantity - (alreadySelected ? alreadySelected.quantity : 0));
    },

    addWholeSelectedBatch() {
        if (!this.selectedBatch || this.selectedBatchRemainingQuantity <= 0) return;

        const existingIndex = this.stockOutItems.findIndex(item => item.batchId === this.selectedBatch.batchId);
        const batchItem = {
            product: this.selectedBatch.product,
            batchId: this.selectedBatch.batchId,
            batchCode: this.selectedBatch.batchCode,
            quantity: this.selectedBatch.quantity,
            availableQty: this.selectedBatch.quantity,
            unit: this.selectedBatch.unit
        };

        if (existingIndex >= 0) {
            this.stockOutItems[existingIndex] = batchItem;
        } else {
            this.stockOutItems.push(batchItem);
        }

        this.selectedBatch = null;
        this.stockOutMode = null;
        this.stockOutQuantity = 0;
    },
    
    addSelectedBatch() {
        const quantity = parseFloat(this.stockOutQuantity);
        if (this.selectedBatch && this.stockOutMode === 'partial' && quantity > 0 && quantity <= this.selectedBatchRemainingQuantity) {
            // Check if batch already added
            const existingIndex = this.stockOutItems.findIndex(item => item.batchId === this.selectedBatch.batchId);
            
            if (existingIndex >= 0) {
                this.stockOutItems[existingIndex].quantity += quantity;
            } else {
                // Add new item
                this.stockOutItems.push({
                    product: this.selectedBatch.product,
                    batchId: this.selectedBatch.batchId,
                    batchCode: this.selectedBatch.batchCode,
                    quantity: quantity,
                    availableQty: this.selectedBatch.quantity,
                    unit: this.selectedBatch.unit
                });
            }
            
            // Reset selection
            this.selectedBatch = null;
            this.stockOutMode = null;
            this.stockOutQuantity = 0;
            this.stockOutSearchQuery = '';
        }
    },
    
    addStockOutItem(product, batchId, quantity) {
        this.stockOutItems.push({
            product: product,
            batchId: batchId,
            quantity: quantity,
            availableQty: quantity
        });
    },
    
    removeStockOutItem(index) {
        this.stockOutItems.splice(index, 1);
    },
    
    saveStockOut() {
        var self = this;
        if (this.stockOutType && this.stockOutDate && this.stockOutItems.length > 0) {
            var mappedItems = [];
            for (var i = 0; i < this.stockOutItems.length; i++) {
                var item = this.stockOutItems[i];
                mappedItems.push({
                    batch_id: item.batchId,
                    quantity: parseFloat(item.quantity)
                });
            }
            
            var stockOutData = {
                items: mappedItems,
                type: this.stockOutType,
                notes: this.stockOutReason || 'Stock out transaction',
                reference: this.stockOutReference,
                date: this.stockOutDate
            };

            console.log('Sending Stock Out:', stockOutData);

            fetch('/api/inventory/stock-out', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(stockOutData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Stock Out completed successfully!');
                    self.resetStockOutModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to process stock out'));
                    console.error('Stock Out Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Stock Out Error:', error);
            });
        }
    },
    
    resetStockOutModal() {
        this.stockOutModal = false;
        this.stockOutItems = [];
        this.stockOutType = 'sale';
        this.stockOutReason = '';
        this.stockOutDate = new Date().toISOString().split('T')[0];
        this.stockOutReference = '';
        this.stockOutSearchQuery = '';
        this.stockOutFilterCategory = '';
        this.selectedBatch = null;
        this.stockOutMode = null;
        this.stockOutQuantity = 0;
    },
    
    // Stock Adjustment functions
    generateAdjustmentReference() {
        const date = new Date();
        const year = date.getFullYear().toString().substr(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 900) + 100;
        return `ADJ-${year}${month}-${random}`;
    },
    
    selectProductForAdjustment(product) {
        this.selectedProductForAdjustment = product;
        this.adjustmentQuantity = 0;
    },
    
    addAdjustmentItem() {
        if (this.selectedProductForAdjustment && this.adjustmentQuantity > 0) {
            const existingIndex = this.adjustmentItems.findIndex(
                item => item.batchId === this.selectedProductForAdjustment.batchId
            );
            
            if (existingIndex >= 0) {
                // Update existing item
                this.adjustmentItems[existingIndex].quantity = parseFloat(this.adjustmentQuantity);
                this.adjustmentItems[existingIndex].type = this.adjustmentType;
            } else {
                // Add new item
                this.adjustmentItems.push({
                    product: this.selectedProductForAdjustment.product,
                    batchId: this.selectedProductForAdjustment.batchId,
                    currentStock: this.selectedProductForAdjustment.quantity,
                    quantity: parseFloat(this.adjustmentQuantity),
                    type: this.adjustmentType
                });
            }
            
            // Reset selection
            this.selectedProductForAdjustment = null;
            this.adjustmentQuantity = 0;
        }
    },
    
    removeAdjustmentItem(index) {
        this.adjustmentItems.splice(index, 1);
    },
    
    saveAdjustment() {
        var self = this;
        if (this.adjustmentItems.length > 0 && this.adjustmentReason) {
            var mappedItems = [];
            for (var i = 0; i < this.adjustmentItems.length; i++) {
                var item = this.adjustmentItems[i];
                mappedItems.push({
                    batch_id: item.batchId,
                    quantity: parseFloat(item.quantity)
                });
            }
            
            var adjustmentData = {
                items: mappedItems,
                type: this.adjustmentType,
                reason: this.adjustmentReason,
                notes: this.adjustmentNotes || '',
                reference: this.adjustmentReference,
                date: this.adjustmentDate
            };

            console.log('Sending Stock Adjustment:', adjustmentData);

            fetch('/api/inventory/stock-adjustment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(adjustmentData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Stock Adjustment completed successfully!');
                    self.resetAdjustmentModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to process adjustment'));
                    console.error('Adjustment Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Adjustment Error:', error);
            });
        }
    },
    
    resetAdjustmentModal() {
        this.adjustmentModal = false;
        this.adjustmentType = 'add';
        this.adjustmentItems = [];
        this.adjustmentReason = '';
        this.adjustmentNotes = '';
        this.adjustmentDate = new Date().toISOString().split('T')[0];
        this.adjustmentReference = '';
        this.selectedProductForAdjustment = null;
        this.adjustmentQuantity = 0;
        this.adjustmentSearchQuery = '';
        this.adjustmentFilterCategory = '';
    },
    
    saveStockIn() {
        var self = this;
        if (this.supplier && this.dateReceived && this.stockInItems.length > 0) {
            // Prepare data for API
            var mappedItems = [];
            for (var i = 0; i < this.stockInItems.length; i++) {
                var item = this.stockInItems[i];
                mappedItems.push({
                    product_id: item.product_id || null,
                    product_name: item.product,
                    quantity: parseFloat(item.quantity),
                    price_per_unit: parseFloat(item.unitCost),
                    batch_code: item.batchId,
                    expiry_date: item.expirationDate,
                    supplier: self.supplier
                });
            }
            
            var stockInData = {
                supplier: this.supplier,
                received_date: this.dateReceived,
                reference_number: this.referenceNumber,
                items: mappedItems
            };

            console.log('Sending Stock In:', stockInData);

            // Call API
            fetch('/api/inventory/stock-in', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(stockInData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Stock In completed successfully!\n\nReference: ' + self.referenceNumber);
                    
                    // Reset form
                    self.stockInItems = [];
                    self.supplier = '';
                    self.dateReceived = new Date().toISOString().split('T')[0];
                    self.referenceNumber = '';
                    self.stockInStep = 1;
                    self.stockInModal = false;
                    
                    // Reload page to show updated data
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to save stock in'));
                    console.error('Stock In Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Stock In Error:', error);
            });
        }
    },
    
    nextStep() {
        if (this.stockInStep === 1 && this.supplier && this.dateReceived) {
            if (!this.referenceNumber) {
                this.referenceNumber = this.generateReferenceNumber();
            }
            this.stockInStep = 2;
        } else if (this.stockInStep === 2 && this.stockInItems.length > 0) {
            this.stockInStep = 3;
        }
    },
    
    prevStep() {
        if (this.stockInStep > 1) {
            this.stockInStep--;
        }
    },
    
    resetStockInModal() {
        this.stockInModal = false;
        this.stockInStep = 1;
        this.stockInItems = [];
        this.currentPage = 1;
        this.supplier = '';
        this.dateReceived = new Date().toISOString().split('T')[0];
        this.referenceNumber = '';
    },
    
    saveProduct() {
        var self = this;
        if (this.newProduct.name && this.newProduct.category && this.newProduct.unit) {
            var productData = {
                name: this.newProduct.name,
                category: this.newProduct.category,
                unit: this.newProduct.unit,
                description: this.newProduct.description || '',
                sku_code: this.newProduct.skuCode,
                shelf_life: this.newProduct.shelfLife,
                price_per_unit: 0, // Default price, will be set during stock in
                reorder_level: 50
            };

            console.log('Saving Product:', productData);

            fetch('/api/products', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(productData)
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Product added successfully!');
                    
                    // Reset form
                    self.newProduct = {
                        name: '',
                        category: '',
                        unit: '',
                        description: '',
                        skuCode: '',
                        shelfLife: 0
                    };
                    self.addProductModal = false;
                    
                    // Reload page to show new product
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to add product'));
                    console.error('Add Product Error:', result);
                }
            })
            .catch(function(error) {
                alert('Error: Failed to connect to server');
                console.error('Add Product Error:', error);
            });
        } else {
            alert('Please fill in all required fields (Name, Category, Unit)');
        }
    }
    };
}
</script>

<div x-data="inventoryData()" @open-add.window="stockInModal=true" class="inventory-layout flex gap-6">

{{-- Expandable Sidebar --}}
<div class="transition-all duration-300 flex-shrink-0"
     :class="sidebarOpen ? 'w-64' : 'w-20'">
    <div class="card sticky top-6 flex flex-col h-[calc(100vh-140px)]">
        {{-- Toggle Button --}}
        <button @click="sidebarOpen = !sidebarOpen" 
                class="absolute -right-3 top-6 w-6 h-6 bg-violet-600 text-white rounded-full shadow-lg hover:bg-violet-700 transition-colors flex items-center justify-center z-10">
            <svg class="w-3.5 h-3.5 transition-transform duration-300" 
                 :class="sidebarOpen ? 'rotate-0' : 'rotate-180'" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Sidebar Header --}}
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center gap-3" :class="!sidebarOpen && 'justify-center'">
                <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                    <p class="font-bold text-gray-900 text-sm">Inventory</p>
                    <p class="text-xs text-gray-500">Management</p>
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <nav class="flex-1 overflow-y-auto py-4">
            <div class="space-y-1 px-3">
                {{-- Inventory Overview --}}
                <a @click.prevent="activeSection = 'overview'" 
                   :class="activeSection === 'overview' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Overview' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Overview</span>
                </a>

                {{-- Products --}}
                <a @click.prevent="activeSection = 'products'" 
                   :class="activeSection === 'products' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Products' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Products</span>
                </a>

                {{-- Stock In --}}
                <a @click.prevent="activeSection = 'stock-in'" 
                   :class="activeSection === 'stock-in' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Stock In' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Stock In</span>
                </a>

                {{-- Stock Out --}}
                <a @click.prevent="activeSection = 'stock-out'" 
                   :class="activeSection === 'stock-out' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Stock Out' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16v-4m0 0V8m0 4h-4m4 0h4m-6 8h-2a2 2 0 01-2-2v-2m0 0V8m0 4h4m-4 0H7"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Stock Out</span>
                </a>

                {{-- Stock Adjustment --}}
                <a @click.prevent="activeSection = 'adjustment'" 
                   :class="activeSection === 'adjustment' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'Adjustment' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">Adjustment</span>
                </a>

                {{-- Inventory History --}}
                <a @click.prevent="activeSection = 'history'" 
                   :class="activeSection === 'history' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-gray-600 hover:bg-gray-50 border-transparent'"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all cursor-pointer border"
                   :title="!sidebarOpen ? 'History' : ''">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="text-sm font-semibold whitespace-nowrap">History</span>
                </a>
            </div>
        </nav>

        {{-- Sidebar Footer --}}
        <div class="p-4 border-t border-gray-200" x-show="sidebarOpen" x-transition>
            <div class="bg-violet-50 rounded-xl p-3">
                <p class="text-xs font-bold text-violet-900">Quick Stats</p>
                <p class="text-lg font-black text-violet-700 mt-1">{{ number_format(isset($inventoryItems) ? $inventoryItems->sum('stock_quantity') : 0, 0) }} kg</p>
                <p class="text-xs text-violet-600">Total Stock</p>
            </div>
        </div>
    </div>
</div>

{{-- Main Content Area --}}
<div class="flex-1 min-w-0">

{{-- Inventory Overview Section --}}
<div x-show="activeSection === 'overview'" x-transition>
{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
    <div>
        <h1 class="text-[26px] font-black text-gray-900">Inventory Management</h1>
        <p class="text-[13.5px] text-gray-500 mt-0.5">Monitor stock levels, batches, and supplier data in real-time</p>
    </div>
    <div class="flex items-center gap-3">
        <button class="btn btn-outline btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export
        </button>
        <button @click="stockInModal=true" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Stock
        </button>
    </div>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 fade-up delay-1">
@php
$totalInventoryStock = isset($inventoryItems) ? $inventoryItems->sum('stock_quantity') : 0;
$availableInventoryStock = isset($inventoryItems)
    ? $inventoryItems->sum(fn ($item) => $item->batches->where('status', 'available')->where('quantity', '>', 0)->sum('quantity'))
    : 0;
$criticalInventoryItems = isset($inventoryItems)
    ? $inventoryItems->filter(fn ($item) => $item->stock_quantity > 0 && $item->stock_quantity <= $item->reorder_level)->count()
    : 0;
$outOfStockInventoryItems = isset($inventoryItems)
    ? $inventoryItems->filter(fn ($item) => $item->stock_quantity <= 0)->count()
    : 0;
$summaryCards = [
    ['Total Stock',   number_format($totalInventoryStock, 0) . ' kg', 'g-violet', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'All current stock'],
    ['Available',     number_format($availableInventoryStock, 0) . ' kg', 'g-green', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'Ready to sell'],
    ['Critical Items', $criticalInventoryItems . ($criticalInventoryItems === 1 ? ' item' : ' items'), 'g-rose', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'At or below reorder level'],
    ['Out of Stock', $outOfStockInventoryItems . ($outOfStockInventoryItems === 1 ? ' item' : ' items'), 'g-amber', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636', 'Depleted'],
];
@endphp
@foreach($summaryCards as [$l,$v,$g,$iconPath,$s])
<div class="card shimmer card-lift p-5">
    <div class="icon-ring mb-3">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
        </svg>
    </div>
    <p class="text-[22px] font-black text-gray-900">{{ $v }}</p>
    <p class="text-[12.5px] font-semibold text-gray-600">{{ $l }}</p>
    <p class="text-[11.5px] text-gray-400 mt-0.5">{{ $s }}</p>
</div>
@endforeach
</div>

{{-- Filters --}}
<div class="card p-4 mb-5 fade-up delay-2">
    <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-2.5 bg-gray-100 rounded-2xl px-4 py-2.5 flex-1 min-w-52 max-w-sm border-2 border-transparent focus-within:border-violet-400 focus-within:bg-white transition-all">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" placeholder="Search fruit, batch ID, supplier…" class="bg-transparent text-[13px] outline-none w-full placeholder-gray-400">
        </div>
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Status</option><option>Available</option><option>Low Stock</option><option>Critical</option><option>Out of Stock</option>
        </select>
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Suppliers</option><option>Davao Fresh Farms</option><option>Mt. Apo Growers</option><option>Sta. Cruz Orchards</option>
        </select>
        <select class="inp" style="width:auto;padding:10px 14px;border-radius:12px">
            <option>All Fruits</option><option>Mango</option><option>Durian</option><option>Pomelo</option><option>Mangosteen</option>
        </select>
    </div>
</div>

{{-- Grid Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-6 fade-up delay-3">
@php
$fruitIconPath = 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4';
@endphp

@if(isset($groupedItems) && count($groupedItems) > 0)
@foreach($groupedItems as $fruit => $batches)
@php
    // Calculate total quantity and worst status for the fruit
    $totalQty = 0;
    $worstStatus = 'Available';
    $worstBadge = 'badge-green';
    $statusPriority = ['Out of Stock' => 4, 'Critical' => 3, 'Low Stock' => 2, 'Available' => 1];
    $highestPriority = 0;
    $totalValue = 0;
    $batchCount = 0;
    
    foreach ($batches as $batch) {
        $qty = (float) str_replace(' kg', '', $batch[2]);
        $totalQty += $qty;
        
        // Extract price from string like "₱120.00/kg"
        $priceString = $batch[5];
        $priceValue = (float) str_replace(['₱', '/kg', ','], '', $priceString);
        $totalValue += ($qty * $priceValue);
        $batchCount++;
        
        $status = $batch[6];
        $priority = $statusPriority[$status] ?? 0;
        if ($priority > $highestPriority) {
            $highestPriority = $priority;
            $worstStatus = $status;
            $worstBadge = $batch[7];
        }
    }
    
    // Calculate average price
    $avgPrice = $totalQty > 0 ? ($totalValue / $totalQty) : 0;
    $avgPriceFormatted = '₱' . number_format($avgPrice, 2) . '/kg';
@endphp

<div class="card overflow-hidden card-lift fade-up delay-{{ min($loop->index+1,8) }}">
    {{-- Fruit header --}}
    <div class="bg-gray-50 border-b border-gray-100 px-5 pt-5 pb-4 relative">
        <div class="absolute top-3 right-3">
            <span class="badge {{ $worstBadge }} text-[10px]">{{ $worstStatus }}</span>
        </div>
        <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl flex items-center justify-center mb-3 shadow-sm">
            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fruitIconPath }}"/>
            </svg>
        </div>
        <p class="font-black text-gray-900 text-[16px] leading-tight">{{ $fruit }}</p>
        <p class="text-gray-400 text-[11px] font-medium mt-0.5">{{ count($batches) }} batch{{ count($batches) > 1 ? 'es' : '' }}</p>
    </div>
    
    {{-- Card body --}}
    <div class="p-4">
        {{-- Total Quantity Summary --}}
        <div class="mb-4 pb-3 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[22px] font-black text-gray-900">{{ $totalQty }} kg</p>
                    <p class="text-[11.5px] text-gray-400">Total stock</p>
                </div>
                <div class="text-right">
                    <p class="text-[14px] font-bold text-violet-700">{{ $avgPriceFormatted }}</p>
                    <p class="text-[11px] text-gray-400">Avg price</p>
                </div>
            </div>
        </div>
        
        {{-- Batches List --}}
        <div class="space-y-2 mb-3">
            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Batches</p>
            
            @foreach($batches as $batch)
            <div class="bg-gray-50 rounded-lg p-2.5 border border-gray-200 hover:border-violet-300 transition-all cursor-pointer"
                 x-on:click="viewModal=true; selected='{{ $batch[1] }}'">
                <div class="flex items-start justify-between mb-1.5">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="font-mono text-[11.5px] font-bold text-gray-900">{{ $batch[1] }}</p>
                            <span class="badge {{ $batch[7] }} text-[9px]">{{ $batch[6] }}</span>
                            @if($batch[11] === 'New Stock')
                            <span class="badge bg-blue-100 text-blue-700 border-blue-200 text-[9px]">New Stock</span>
                            @else
                            <span class="badge bg-gray-100 text-gray-600 border-gray-200 text-[9px]">Old Stock</span>
                            @endif
                        </div>
                        <p class="text-[13px] font-bold text-gray-900">{{ $batch[2] }}</p>
                    </div>
                    <div class="text-right flex-shrink-0 ml-2">
                        <p class="text-[11px] font-semibold text-violet-700">{{ $batch[5] }}</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-[10px] text-gray-400">
                    <span class="flex items-center gap-1 truncate">
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Exp: {{ $batch[3] }}
                    </span>
                    <span class="flex items-center gap-1 ml-2">
                        <span class="{{ $batch[10] <= 7 ? 'text-red-500' : ($batch[10] <= 14 ? 'text-amber-500' : 'text-green-600') }} font-semibold">{{ $batch[10] }}d</span>
                    </span>
                </div>
                
                {{-- Freshness progress bar --}}
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $batch[8] < 20 ? 'bg-red-400' : ($batch[8] < 50 ? 'bg-amber-400' : 'bg-violet-500') }}"
                             style="width:{{ $batch[8] }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        {{-- Add Batch Button --}}
        <button @click="stockInModal=true" class="w-full btn btn-outline btn-sm text-[11px] py-2 flex items-center justify-center gap-1.5 border-dashed hover:border-violet-500 hover:text-violet-700 hover:bg-violet-50">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Batch
        </button>
    </div>
</div>
@endforeach
@else
{{-- Empty State --}}
<div class="col-span-full">
    <div class="card p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">No Products Yet</h3>
        <p class="text-sm text-gray-500 mb-4">Add your first product to get started</p>
        <button @click="addProductModal=true" class="btn btn-violet btn-sm mx-auto">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </button>
    </div>
</div>
@endif
</div>
</div>

{{-- Products Section --}}
<div x-show="activeSection === 'products'" x-transition x-cloak>
    <div class="relative mb-6 overflow-hidden rounded-3xl border border-violet-100 bg-gradient-to-br from-white via-white to-violet-50 p-6 shadow-sm fade-up">
        <div class="pointer-events-none absolute -right-10 -top-16 h-48 w-48 rounded-full bg-violet-100/70 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 shadow-sm ring-1 ring-violet-200">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-violet-600">Inventory management</p>
                    <h1 class="text-[26px] font-black leading-tight text-gray-900">Products Catalog</h1>
                    <p class="mt-1 text-[13px] text-gray-500">Manage product details, pricing, and stock health in one place.</p>
                </div>
            </div>
            <button @click="addProductModal=true" class="btn btn-violet btn-md text-[13px] shadow-lg shadow-violet-200/70">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </button>
        </div>
    </div>
    
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-up delay-1">
        @php
        $totalProductCount = $inventoryItems->count();
        $activeProductCount = $inventoryItems->filter(fn ($item) => $item->stock_quantity > 0)->count();
        $lowStockCount = $inventoryItems->filter(fn ($item) => $item->stock_quantity > 0 && $item->stock_quantity <= $item->reorder_level)->count();
        $outOfStockCount = $inventoryItems->filter(fn ($item) => $item->stock_quantity <= 0)->count();
        $productSummary = [
            ['Total Products', $totalProductCount, 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'All types'],
            ['In Stock', $activeProductCount, 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'Currently available'],
            ['Low Stock', $lowStockCount, 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'Needs restock'],
            ['Out of Stock', '0 items', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636', 'Depleted'],
        ];
        @endphp
        @foreach($productSummary as $card)
        <div class="card card-lift relative overflow-hidden p-5">
            <div class="absolute inset-x-0 top-0 h-1 {{ $loop->first ? 'bg-violet-500' : ($loop->iteration === 2 ? 'bg-green-500' : ($loop->iteration === 3 ? 'bg-amber-400' : 'bg-red-400')) }}"></div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[12px] font-semibold text-gray-500">{{ $card[0] }}</p>
                    <p class="mt-2 text-[28px] font-black leading-none text-gray-900">{{ $card[0] === 'Out of Stock' ? $outOfStockCount : $card[1] }}</p>
                    <p class="mt-2 text-[11px] text-gray-400">{{ $card[3] }}</p>
                </div>
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl {{ $loop->first ? 'bg-violet-100 text-violet-600' : ($loop->iteration === 2 ? 'bg-green-100 text-green-600' : ($loop->iteration === 3 ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600')) }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card[2] }}"/>
                    </svg>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Search and Filters --}}
    <div class="card mb-5 border border-gray-100 p-4 shadow-sm fade-up delay-2">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            {{-- Search Bar --}}
            <label class="flex min-w-52 flex-1 items-center gap-2.5 rounded-2xl border-2 border-transparent bg-gray-50 px-4 py-3 transition-all focus-within:border-violet-400 focus-within:bg-white lg:max-w-md">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model.debounce.150ms="productSearchQuery" type="search" placeholder="Search by product or category..." aria-label="Search products" class="w-full bg-transparent text-[13px] outline-none placeholder-gray-400">
            </label>

            <div class="flex flex-wrap items-center gap-2.5">
                <select x-model="productCategoryFilter" aria-label="Filter by category" class="inp" style="width:auto;padding:11px 14px;border-radius:12px">
                    <option value="">All Categories</option>
                    @foreach($inventoryItems->pluck('category')->filter()->unique()->sort() as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
                <select x-model="productStatusFilter" aria-label="Filter by stock status" class="inp" style="width:auto;padding:11px 14px;border-radius:12px">
                    <option value="">All Stock Statuses</option>
                    <option value="available">Available</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
                <button x-show="productSearchQuery || productCategoryFilter || productStatusFilter"
                        @click="productSearchQuery = ''; productCategoryFilter = ''; productStatusFilter = ''"
                        x-cloak type="button" class="rounded-xl px-3 py-2.5 text-[12px] font-semibold text-violet-700 transition hover:bg-violet-50">
                    Clear filters
                </button>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
            <p class="text-[11px] text-gray-500">Use search and filters to quickly find a product.</p>
            <span class="text-[11px] font-semibold text-gray-600">{{ $totalProductCount }} products</span>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="card overflow-hidden p-0 fade-up delay-3">
        <div class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-gray-900">All Products</h2>
                <p class="mt-0.5 text-[11px] text-gray-500">Product details and current stock levels</p>
            </div>
            <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-[11px] font-bold text-gray-600">{{ $totalProductCount }} total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/80">
                        <th class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-gray-500">Product Name</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-gray-500">Category</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-gray-500">Unit Price</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-gray-500">Total Stock</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="productRows">
                    @if(isset($inventoryItems) && $inventoryItems->count() > 0)
                        @foreach($inventoryItems as $item)
                        <tr data-product-row
                            data-product-name="{{ $item->name }}"
                            data-product-category="{{ $item->category ?? '' }}"
                            data-stock="{{ $item->stock_quantity }}"
                            data-reorder-level="{{ $item->reorder_level }}"
                            x-show="matchesProduct($el.dataset.productName, $el.dataset.productCategory, Number($el.dataset.stock), Number($el.dataset.reorderLevel))"
                            class="border-b border-gray-100 transition-colors hover:bg-gray-50">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-gray-900 text-sm">{{ $item->name }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-sm text-gray-600">{{ $item->category ?? 'N/A' }}</td>
                            <td class="py-4 px-4 text-sm font-semibold text-gray-900">₱{{ number_format($item->price_per_unit, 2) }}/{{ $item->unit }}</td>
                            <td class="py-4 px-4 text-sm font-semibold text-gray-900">{{ number_format($item->stock_quantity, 0) }} {{ $item->unit }}</td>
                            <td class="py-4 px-4">
                                @php
                                    $status = 'Available';
                                    $badgeClass = 'badge-green';
                                    if ($item->stock_quantity <= 0) {
                                        $status = 'Out of Stock';
                                        $badgeClass = 'badge-gray';
                                    } elseif ($item->stock_quantity <= $item->reorder_level) {
                                        $status = 'Low Stock';
                                        $badgeClass = 'badge-amber';
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <button class="p-2 hover:bg-violet-50 rounded-lg text-gray-400 hover:text-violet-600 transition-colors" title="View Details">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 hover:bg-violet-50 rounded-lg text-gray-400 hover:text-violet-600 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 hover:bg-red-50 rounded-lg text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        <tr x-show="!hasMatchingProductRows($refs.productRows)" x-cloak>
                            <td colspan="6" class="px-4 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-500">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-800">No matching products</p>
                                    <p class="mt-1 text-xs text-gray-500">Try another search or clear the filters to see your full catalog.</p>
                                    <button @click="productSearchQuery = ''; productCategoryFilter = ''; productStatusFilter = ''" type="button" class="mt-3 text-xs font-bold text-violet-700 hover:text-violet-900">Clear all filters</button>
                                </div>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-500 text-sm font-semibold">No products found</p>
                                    <p class="text-gray-400 text-xs mt-1">Add your first product to get started</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Stock In Section --}}
<div x-show="activeSection === 'stock-in'" x-transition x-cloak>
    <div class="relative mb-6 overflow-hidden rounded-3xl border border-gray-100 bg-white p-6 shadow-sm fade-up">
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-gray-600">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7 9m5-5 5 5M5 14v4a2 2 0 002 2h10a2 2 0 002-2v-4"/>
                    </svg>
                </div>
                <div>
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500">Receiving activity</p>
                    <h1 class="text-[26px] font-black leading-tight text-gray-900">Stock In Records</h1>
                    <p class="mt-1 text-[13px] text-gray-500">Review incoming batches, suppliers, quantities, and recorded costs.</p>
                </div>
            </div>
            <button @click="stockInModal=true" class="btn btn-violet btn-md text-[13px] shadow-lg shadow-violet-200/70">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Stock In
            </button>
        </div>
    </div>

    <div class="card overflow-hidden p-0 fade-up delay-1">
        <div class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Incoming batches</h2>
                <p class="mt-0.5 text-[11px] text-gray-500">Most recently recorded stock receipts</p>
            </div>
            <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-[11px] font-bold text-gray-600">{{ isset($stockInRecords) ? count($stockInRecords) : 0 }} {{ isset($stockInRecords) && count($stockInRecords) === 1 ? 'record' : 'records' }}</span>
        </div>
        <div class="space-y-3 p-4 sm:p-5">
            @if(isset($stockInRecords) && count($stockInRecords) > 0)
            @foreach($stockInRecords as $stockIn)
            <div class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition-colors hover:bg-gray-100/70 sm:flex-row sm:items-center sm:gap-5 sm:px-5">
                <div class="flex min-w-0 flex-1 items-start gap-3 sm:items-center sm:gap-4">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7 9m5-5 5 5"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold text-gray-900">{{ $stockIn[1] }}</p>
                            <span class="rounded-md bg-gray-100 px-2 py-1 font-mono text-[10px] font-semibold text-gray-600">{{ $stockIn[0] }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                            <p class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m12-13a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span>{{ $stockIn[3] }}</span>
                            </p>
                            <p class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                <span>{{ $stockIn[4] }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3 sm:justify-end sm:border-0 sm:pt-0">
                    <div class="text-left sm:min-w-24 sm:text-right">
                        <p class="text-base font-black leading-tight text-gray-900">{{ $stockIn[2] }}</p>
                        <p class="mt-1 text-xs font-semibold text-gray-600">{{ $stockIn[5] }}</p>
                    </div>
                    <div class="sm:min-w-20 sm:text-right">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold text-gray-600">
                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                            {{ $stockIn[6] }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Empty State --}}
            <div class="py-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-500">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4"/>
                    </svg>
                </div>
                <h3 class="mb-2 text-base font-bold text-gray-900">No stock receipts yet</h3>
                <p class="mb-4 text-sm text-gray-500">Record an incoming batch to start tracking your receiving history.</p>
                <button @click="stockInModal=true" class="btn btn-violet btn-sm mx-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Stock In
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Stock Out Section --}}
<div x-show="activeSection === 'stock-out'" x-transition x-cloak>
    <div class="mb-6 flex flex-col gap-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm fade-up sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-gray-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 5-5m-5 5-5-5M5 20h14"/>
                </svg>
            </div>
            <div>
                <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-500">Inventory movement</p>
                <h1 class="text-[26px] font-black leading-tight text-gray-900">Stock Out Records</h1>
                <p class="mt-1 text-[13px] text-gray-500">A clear history of completed sales and outgoing stock.</p>
            </div>
        </div>
        <button @click="stockOutModal=true; stockOutReference=generateStockOutReference()" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Record Stock Out
        </button>
    </div>

    <div class="card overflow-hidden p-0 fade-up delay-1">
        <div class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recent transactions</h2>
                <p class="mt-0.5 text-[11px] text-gray-500">Latest stock removals recorded in the system</p>
            </div>
            <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-[11px] font-bold text-gray-600">
                {{ isset($stockOutRecords) ? count($stockOutRecords) : 0 }} {{ isset($stockOutRecords) && count($stockOutRecords) === 1 ? 'transaction' : 'transactions' }}
            </span>
        </div>
        <div class="space-y-3 p-4 sm:p-5">
            @if(isset($stockOutRecords) && count($stockOutRecords) > 0)
            @foreach($stockOutRecords as $stockOut)
            <div class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-4 transition-colors hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex min-w-0 flex-1 items-start gap-3 sm:items-center sm:gap-4">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 5-5m-5 5-5-5M5 20h14"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold text-gray-900">{{ $stockOut[1] }}</p>
                            <span class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 font-mono text-[10px] font-semibold text-gray-600">{{ $stockOut[0] }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                            <p class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span>{{ $stockOut[3] }}</span>
                            </p>
                            <p class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                <span>{{ $stockOut[4] }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3 sm:justify-end sm:border-0 sm:pt-0">
                    <div class="text-left sm:min-w-24 sm:text-right">
                        <p class="text-base font-black leading-tight text-gray-900">{{ $stockOut[2] }}</p>
                        <p class="mt-1 text-xs font-semibold text-gray-600">{{ $stockOut[5] }}</p>
                    </div>
                    <div class="sm:min-w-20 sm:text-right">
                        <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[10px] font-bold text-gray-600">
                            {{ $stockOut[6] }}
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Empty State --}}
            <div class="py-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-500">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 5-5m-5 5-5-5M5 20h14"/>
                    </svg>
                </div>
                <h3 class="mb-2 text-base font-bold text-gray-900">No outgoing transactions yet</h3>
                <p class="mb-4 text-sm text-gray-500">Completed stock removals will appear here with their quantities and references.</p>
                <button @click="stockOutModal=true; stockOutReference=generateStockOutReference()" class="btn btn-violet btn-sm mx-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Record Stock Out
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Stock Adjustment Section --}}
<div x-show="activeSection === 'adjustment'" x-transition x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Stock Adjustments</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">Manual corrections and inventory adjustments</p>
        </div>
        <button @click="adjustmentModal=true; adjustmentReference=generateAdjustmentReference()" class="btn btn-violet btn-md text-[13px]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Adjustment
        </button>
    </div>

    <div class="card p-6">
        <div class="space-y-4">
            @if(isset($adjustmentRecords) && count($adjustmentRecords) > 0)
            @foreach($adjustmentRecords as $adjustment)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors border border-gray-100">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <p class="font-bold text-gray-900 text-sm">{{ $adjustment[1] }}</p>
                            <span class="text-xs font-mono text-gray-500">{{ $adjustment[0] }}</span>
                            <span class="badge {{ $adjustment[6] }} text-xs">{{ $adjustment[2] }}</span>
                        </div>
                        <div class="flex items-center gap-4 mt-1">
                            <p class="text-xs text-gray-500">Reason: {{ $adjustment[3] }}</p>
                        </div>
                        <div class="flex items-center gap-4 mt-1">
                            <p class="text-xs text-gray-400">By: {{ $adjustment[5] }} · {{ $adjustment[4] }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No Adjustments Yet</h3>
                <p class="text-sm text-gray-500 mb-4">Create your first stock adjustment</p>
                <button @click="adjustmentModal=true; adjustmentReference=generateAdjustmentReference()" class="btn btn-violet btn-sm mx-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Adjustment
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Inventory History Section --}}
<div x-show="activeSection === 'history'" x-transition x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7 fade-up">
        <div>
            <h1 class="text-[26px] font-black text-gray-900">Inventory History</h1>
            <p class="text-[13.5px] text-gray-500 mt-0.5">Complete audit trail of all inventory movements</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="historyFiltersOpen = !historyFiltersOpen" :aria-expanded="historyFiltersOpen.toString()" type="button" class="btn btn-outline btn-md text-[13px]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <span x-text="historyFiltersOpen ? 'Hide Filters' : 'Filter'"></span>
            </button>
            <button @click="exportInventoryHistory($refs.historyList)" type="button" class="btn btn-outline btn-md text-[13px]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </button>
        </div>
    </div>

    <div x-show="historyFiltersOpen" x-cloak x-transition class="card mb-4 border border-gray-100 p-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-xs font-semibold text-gray-600">
                Search history
                <input x-model.debounce.150ms="historySearch" type="search" placeholder="Product, batch, reference..." class="inp mt-1.5 w-full">
            </label>
            <label class="text-xs font-semibold text-gray-600">
                Movement type
                <select x-model="historyTypeFilter" class="inp mt-1.5 w-full">
                    <option value="">All movement types</option>
                    <option value="Stock In">Stock In</option>
                    <option value="Sale">Sale</option>
                    <option value="Stock Out">Stock Out</option>
                    <option value="Adjustment">Adjustment</option>
                    <option value="Spoilage">Spoilage</option>
                    <option value="Wastage">Wastage</option>
                    <option value="Transfer">Transfer</option>
                    <option value="Sample">Sample</option>
                    <option value="Other">Other</option>
                </select>
            </label>
            <label class="text-xs font-semibold text-gray-600">
                From
                <input x-model="historyDateFrom" type="date" class="inp mt-1.5 w-full">
            </label>
            <label class="text-xs font-semibold text-gray-600">
                To
                <input x-model="historyDateTo" type="date" class="inp mt-1.5 w-full">
            </label>
        </div>
        <div class="mt-3 flex justify-end">
            <button @click="clearHistoryFilters()" type="button" class="rounded-xl px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-100">Clear filters</button>
        </div>
    </div>

    @php $historyRecordCount = isset($historyRecords) ? $historyRecords->count() : 0; @endphp
    <div class="card overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Movement log</h2>
                <p class="mt-0.5 text-[11px] text-gray-500">Filter the records or export the matching results.</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-bold text-gray-600">{{ $historyRecordCount }} records</span>
        </div>
        <div x-ref="historyList" class="space-y-2 p-4 sm:p-5">
            @forelse($historyRecords ?? [] as $history)
            <div data-history-row
                 data-type="{{ $history['type'] }}"
                 data-item="{{ $history['item'] }}"
                 data-reference="{{ $history['reference'] }}"
                 data-quantity="{{ $history['quantity'] }}"
                 data-details="{{ $history['details'] }}"
                 data-date="{{ $history['date_filter'] }}"
                 x-show="matchesHistory($el.dataset.type, $el.dataset.item, $el.dataset.reference, $el.dataset.details, $el.dataset.date)"
                 class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-gray-50/70 p-3 transition-colors hover:bg-gray-100/70 sm:flex-row sm:items-center">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 5-5m-5 5-5-5M5 20h14"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-[10px] font-bold text-gray-600">{{ $history['type'] }}</span>
                            <p class="text-sm font-bold text-gray-900">{{ $history['item'] }}</p>
                            <span class="rounded-md bg-gray-100 px-2 py-0.5 font-mono text-[10px] text-gray-500">{{ $history['reference'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ $history['details'] }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-gray-200/70 pt-2 sm:justify-end sm:border-0 sm:pt-0">
                    <p class="text-sm font-bold text-gray-900">{{ $history['quantity'] }}</p>
                    <p class="min-w-32 text-right text-xs text-gray-500">{{ $history['date'] }}</p>
                </div>
            </div>
            @empty
            <div class="py-12 text-center">
                <p class="text-sm font-semibold text-gray-700">No inventory movements recorded yet.</p>
                <p class="mt-1 text-xs text-gray-500">Stock receipts, sales, removals, and adjustments will appear here.</p>
            </div>
            @endforelse
            @if($historyRecordCount > 0)
            <div x-show="!Array.from($refs.historyList.querySelectorAll('[data-history-row]')).some(row => matchesHistory(row.dataset.type, row.dataset.item, row.dataset.reference, row.dataset.details, row.dataset.date))"
                 x-cloak class="py-12 text-center">
                <p class="text-sm font-semibold text-gray-700">No matching history records</p>
                <p class="mt-1 text-xs text-gray-500">Adjust or clear the filters to see other inventory movements.</p>
                <button @click="clearHistoryFilters()" type="button" class="mt-3 text-xs font-bold text-gray-700 hover:text-gray-900">Clear filters</button>
            </div>
            @endif
        </div>
    </div>
</div>

</div>

{{-- View Modal --}}
<div x-show="viewModal" @click.self="viewModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg border border-violet-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="bg-gray-50 border-b border-gray-100 p-6 relative rounded-t-3xl">
            <div class="absolute top-4 right-4"><button @click="viewModal=false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-200 transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white border border-gray-200 rounded-2xl flex items-center justify-center shadow-sm">
                    <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-gray-900">Mango</h3>
                    <p class="text-gray-500 text-[13px] font-mono" x-text="'Batch: '+selected"></p>
                    <span class="badge badge-green text-[10px] mt-1">Available</span>
                </div>
            </div>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">
            @foreach([['Current Stock','285 kg'],['Unit Price','₱120/kg'],['Supplier','Davao Fresh Farms'],['Expiration','Jun 26, 2026'],['Received Date','Jun 16, 2026'],['Shelf Life','12 days'],['Storage','Room A · Shelf 3'],['Freshness Score','92/100']] as [$l,$v])
            <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">{{ $l }}</p>
                <p class="text-[14px] font-bold text-gray-900 mt-1">{{ $v }}</p>
            </div>
            @endforeach
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button @click="viewModal=false" class="btn btn-violet btn-md flex-1">Close</button>
        </div>
    </div>
</div>

{{-- Add Stock In Transaction Modal --}}
<div x-show="stockInModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    {{-- Backdrop for Stock In Modal --}}
    <div x-show="stockInModal" @click="resetStockInModal()" 
         class="fixed inset-0 bg-black/40 transition-opacity"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>
    
    {{-- Stock In Modal Content --}}
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-5xl border border-violet-100 max-h-[90vh] overflow-hidden flex flex-col z-10" 
         x-show="stockInModal"
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        {{-- Header with Progress Steps --}}
        <div class="border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
            <div class="flex items-center justify-between px-6 py-5">
                <div>
                    <h3 class="font-black text-gray-900 text-[18px]">New Stock In Transaction</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Record incoming inventory from supplier</p>
                </div>
                <button @click="resetStockInModal()" class="p-2 hover:bg-white rounded-xl text-gray-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            {{-- Progress Indicator --}}
            <div class="px-6 pb-5">
                <div class="flex items-center justify-between max-w-2xl mx-auto">
                    {{-- Step 1 --}}
                    <div class="flex items-center flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all"
                                 :class="stockInStep >= 1 ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-400'">
                                <span x-show="stockInStep > 1">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span x-show="stockInStep <= 1">1</span>
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-bold" :class="stockInStep >= 1 ? 'text-violet-700' : 'text-gray-400'">Transaction Info</p>
                                <p class="text-[10px] text-gray-400">Supplier & Date</p>
                            </div>
                        </div>
                        <div class="flex-1 h-0.5 mx-3" :class="stockInStep >= 2 ? 'bg-violet-600' : 'bg-gray-200'"></div>
                    </div>
                    
                    {{-- Step 2 --}}
                    <div class="flex items-center flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all"
                                 :class="stockInStep >= 2 ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-400'">
                                <span x-show="stockInStep > 2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span x-show="stockInStep <= 2">2</span>
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-bold" :class="stockInStep >= 2 ? 'text-violet-700' : 'text-gray-400'">Add Items</p>
                                <p class="text-[10px] text-gray-400">Products & Batches</p>
                            </div>
                        </div>
                        <div class="flex-1 h-0.5 mx-3" :class="stockInStep >= 3 ? 'bg-violet-600' : 'bg-gray-200'"></div>
                    </div>
                    
                    {{-- Step 3 --}}
                    <div class="flex items-center">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all"
                                 :class="stockInStep >= 3 ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-400'">3</div>
                            <div class="hidden sm:block">
                                <p class="text-xs font-bold" :class="stockInStep >= 3 ? 'text-violet-700' : 'text-gray-400'">Review & Submit</p>
                                <p class="text-[10px] text-gray-400">Confirm Details</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Content Area --}}
        <div class="flex-1 overflow-y-auto">
            
            {{-- STEP 1: Transaction Information --}}
            <div x-show="stockInStep === 1" x-transition class="p-8">
                <div class="max-w-2xl mx-auto space-y-6">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-violet-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h4 class="text-xl font-black text-gray-900 mb-2">Transaction Details</h4>
                        <p class="text-sm text-gray-500">Enter supplier information and delivery date</p>
                    </div>
                    
                    <div class="bg-violet-50 border border-violet-200 rounded-xl p-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-violet-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs font-bold text-violet-900">Reference Number</p>
                                <p class="text-sm font-mono text-violet-700" x-text="referenceNumber || 'Auto-generated'"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="inp-label">Supplier *</label>
                            <select x-model="supplier" class="inp" required>
                                <option value="">Select Supplier</option>
                                <option>Davao Fresh Farms</option>
                                <option>Mt. Apo Growers</option>
                                <option>Sta. Cruz Orchards</option>
                                <option>Mindanao Fruit Co.</option>
                                <option>Golden Harvest Trading</option>
                            </select>
                        </div>
                        <div>
                            <label class="inp-label">Date Received *</label>
                            <input type="date" x-model="dateReceived" class="inp" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="inp-label">Notes (Optional)</label>
                        <textarea rows="3" placeholder="Delivery notes, quality observations, special conditions..." class="inp"></textarea>
                    </div>
                </div>
            </div>
            
            {{-- STEP 2: Add Items --}}
            <div x-show="stockInStep === 2" x-transition class="p-8">
                <div class="max-w-5xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {{-- Left: Add Item Form --}}
                        <div class="lg:col-span-1">
                            <div class="sticky top-0">
                                <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-2xl border-2 border-violet-200 p-6">
                                    <div class="flex items-center gap-3 mb-5">
                                        <div class="w-10 h-10 bg-violet-600 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-gray-900">Add Product Item</h4>
                                            <p class="text-xs text-gray-500">Fill in product details</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="inp-label">Product *</label>
                                            <select x-model="currentItem.product" class="inp bg-white" required>
                                                <option value="">Select Product</option>
                                                @if(isset($inventoryItems) && $inventoryItems->count() > 0)
                                                    @foreach($inventoryItems as $item)
                                                        <option value="{{ $item->name }}">{{ $item->name }}</option>
                                                    @endforeach
                                                @else
                                                    <option disabled>No products available - Add products first</option>
                                                @endif
                                            </select>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="inp-label">Quantity (kg) *</label>
                                                <input type="number" x-model="currentItem.quantity" min="0" step="0.01" placeholder="0.00" class="inp bg-white" required>
                                            </div>
                                            <div>
                                                <label class="inp-label">Unit Cost (₱) *</label>
                                                <input type="number" x-model="currentItem.unitCost" min="0" step="0.01" placeholder="0.00" class="inp bg-white" required>
                                            </div>
                                        </div>
                                        
                                        {{-- Auto-generated Details Preview --}}
                                        <template x-if="currentItem.product && currentItem.quantity > 0 && currentItem.unitCost > 0">
                                            <div class="bg-white rounded-xl p-4 border-2 border-violet-300 shadow-sm">
                                                <p class="text-xs font-bold text-violet-700 uppercase mb-3 flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    Auto-Generated
                                                </p>
                                                <div class="space-y-2.5">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-gray-600">Batch ID</span>
                                                        <span class="text-xs font-bold font-mono text-gray-900 bg-gray-100 px-2 py-1 rounded" x-text="generateBatchId(currentItem.product)"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-gray-600">Shelf Life</span>
                                                        <span class="text-xs font-bold text-gray-900" x-text="calculateDates(currentItem.product, dateReceived).shelfLife + ' days'"></span>
                                                    </div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs text-gray-600">Expiration</span>
                                                        <span class="text-xs font-bold text-gray-900" x-text="calculateDates(currentItem.product, dateReceived).expirationDate"></span>
                                                    </div>
                                                    <div class="h-px bg-gray-200 my-2"></div>
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-bold text-gray-700">Total Cost</span>
                                                        <span class="text-sm font-black text-violet-700" x-text="'₱' + (currentItem.quantity * currentItem.unitCost).toFixed(2)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        
                                        <button @click.stop="addStockItem()" 
                                                :disabled="!currentItem.product || currentItem.quantity <= 0 || currentItem.unitCost <= 0"
                                                :class="(!currentItem.product || currentItem.quantity <= 0 || currentItem.unitCost <= 0) ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-lg'"
                                                class="w-full btn btn-violet btn-md text-sm transition-all">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Add to List
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Right: Items List --}}
                        <div class="lg:col-span-2">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-lg font-black text-gray-900">Items in Transaction</h4>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <span x-show="stockInItems.length === 0">No items added yet</span>
                                        <span x-show="stockInItems.length > 0" x-text="stockInItems.length + ' item' + (stockInItems.length !== 1 ? 's' : '') + ' added'"></span>
                                    </p>
                                </div>
                                <div x-show="stockInItems.length > itemsPerPage" class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">
                                        Showing <span x-text="((currentPage - 1) * itemsPerPage) + 1"></span>-<span x-text="Math.min(currentPage * itemsPerPage, stockInItems.length)"></span> of <span x-text="stockInItems.length"></span>
                                    </span>
                                </div>
                            </div>
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl overflow-hidden min-h-[400px]">
                        <template x-if="stockInItems.length === 0">
                            <div class="text-center py-20 bg-gray-50 h-full flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <p class="text-base font-bold text-gray-500 mb-1">No items in transaction</p>
                                <p class="text-sm text-gray-400">Use the form on the left to add products</p>
                            </div>
                        </template>

                        <template x-if="stockInItems.length > 0">
                            <div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                                            <tr>
                                                <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Product</th>
                                                <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Batch ID</th>
                                                <th class="text-right py-3 px-4 text-xs font-bold text-gray-600 uppercase">Quantity</th>
                                                <th class="text-right py-3 px-4 text-xs font-bold text-gray-600 uppercase">Unit Cost</th>
                                                <th class="text-right py-3 px-4 text-xs font-bold text-gray-600 uppercase">Total</th>
                                                <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 uppercase">Expiration</th>
                                                <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 uppercase">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            <template x-for="(item, index) in paginatedItems" :key="index">
                                                <tr class="border-b border-gray-100 hover:bg-violet-50 transition-colors">
                                                    <td class="py-4 px-4">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center">
                                                                <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                                </svg>
                                                            </div>
                                                            <span class="text-sm font-bold text-gray-900" x-text="item.product"></span>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-600" x-text="item.batchId"></span>
                                                    </td>
                                                    <td class="py-4 px-4 text-right">
                                                        <span class="text-sm font-bold text-gray-900" x-text="item.quantity + ' kg'"></span>
                                                    </td>
                                                    <td class="py-4 px-4 text-right">
                                                        <span class="text-sm text-gray-600" x-text="'₱' + item.unitCost.toFixed(2)"></span>
                                                    </td>
                                                    <td class="py-4 px-4 text-right">
                                                        <span class="text-sm font-black text-violet-700" x-text="'₱' + item.totalCost.toFixed(2)"></span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <div>
                                                            <p class="text-xs font-semibold text-gray-700" x-text="item.expirationDate"></p>
                                                            <p class="text-xs text-gray-400" x-text="item.shelfLife + ' days'"></p>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-4 text-center">
                                                        <button @click.stop="removeStockItem((currentPage - 1) * itemsPerPage + index)" 
                                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                                                title="Remove item">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination Controls --}}
                                <div x-show="stockInItems.length > itemsPerPage" class="bg-gray-50 border-t border-gray-200 px-6 py-3">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-600">
                                            Page <span class="font-bold" x-text="currentPage"></span> of <span class="font-bold" x-text="totalPages"></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            {{-- Previous Button --}}
                                            <button @click="prevPage()" 
                                                    :disabled="currentPage === 1"
                                                    :class="currentPage === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-200'"
                                                    class="p-2 rounded-lg text-gray-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>
                                            
                                            {{-- Page Numbers --}}
                                            <template x-for="page in totalPages" :key="page">
                                                <button @click="goToPage(page)"
                                                        :class="currentPage === page ? 'bg-violet-600 text-white' : 'text-gray-600 hover:bg-gray-200'"
                                                        class="w-8 h-8 rounded-lg text-xs font-bold transition-colors"
                                                        x-text="page">
                                                </button>
                                            </template>
                                            
                                            {{-- Next Button --}}
                                            <button @click="nextPage()" 
                                                    :disabled="currentPage === totalPages"
                                                    :class="currentPage === totalPages ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-200'"
                                                    class="p-2 rounded-lg text-gray-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Summary Bar --}}
                                <div class="bg-violet-50 border-t-2 border-violet-200 px-6 py-4">
                                    <div class="flex items-center justify-between max-w-md ml-auto">
                                        <div class="text-center">
                                            <p class="text-xs font-bold text-violet-600 uppercase">Total Items</p>
                                            <p class="text-2xl font-black text-violet-900" x-text="stockInItems.length"></p>
                                        </div>
                                        <div class="w-px h-12 bg-violet-200"></div>
                                        <div class="text-center">
                                            <p class="text-xs font-bold text-violet-600 uppercase">Total Quantity</p>
                                            <p class="text-2xl font-black text-violet-900" x-text="stockInItems.reduce((sum, item) => sum + parseFloat(item.quantity), 0).toFixed(2) + ' kg'"></p>
                                        </div>
                                        <div class="w-px h-12 bg-violet-200"></div>
                                        <div class="text-center">
                                            <p class="text-xs font-bold text-violet-600 uppercase">Total Amount</p>
                                            <p class="text-2xl font-black text-violet-900" x-text="'₱' + getTotalAmount().toFixed(2)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- STEP 3: Review & Submit --}}
            <div x-show="stockInStep === 3" x-transition class="px-6 py-4 overflow-y-auto" style="max-height: calc(90vh - 300px);">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="text-lg font-black text-gray-900 mb-1">Review Transaction</h4>
                        <p class="text-xs text-gray-500">Verify all details before submitting</p>
                    </div>
                    
                    {{-- Transaction Summary --}}
                    <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl border-2 border-violet-200 p-4 mb-3">
                        <h5 class="text-xs font-bold text-violet-900 uppercase mb-2 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Transaction Details
                        </h5>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-white rounded-lg p-2.5 border border-violet-100">
                                <p class="text-[10px] text-gray-500 mb-0.5">Reference Number</p>
                                <p class="text-xs font-bold font-mono text-gray-900" x-text="referenceNumber"></p>
                            </div>
                            <div class="bg-white rounded-lg p-2.5 border border-violet-100">
                                <p class="text-[10px] text-gray-500 mb-0.5">Date Received</p>
                                <p class="text-xs font-bold text-gray-900" x-text="dateReceived"></p>
                            </div>
                            <div class="bg-white rounded-lg p-2.5 border border-violet-100 col-span-2">
                                <p class="text-[10px] text-gray-500 mb-0.5">Supplier</p>
                                <p class="text-xs font-bold text-gray-900" x-text="supplier"></p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Items Summary --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden mb-3">
                        <div class="bg-gray-50 border-b border-gray-200 px-4 py-2">
                            <h5 class="text-xs font-bold text-gray-900 uppercase flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Stock Items (<span x-text="stockInItems.length"></span>)
                            </h5>
                        </div>
                        <div class="p-3 space-y-1.5 max-h-40 overflow-y-auto">
                            <template x-for="(item, index) in stockInItems" :key="index">
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-xs truncate" x-text="item.product"></p>
                                            <p class="text-[10px] text-gray-500 truncate">
                                                <span class="font-mono" x-text="item.batchId"></span> · 
                                                <span x-text="item.expirationDate"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-2">
                                        <p class="text-sm font-black text-gray-900" x-text="item.quantity + ' kg'"></p>
                                        <p class="text-xs font-bold text-violet-700" x-text="'₱' + item.totalCost.toFixed(2)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="bg-violet-600 px-4 py-2.5">
                            <div class="flex items-center justify-between text-white">
                                <span class="font-bold uppercase text-xs">Grand Total</span>
                                <span class="text-xl font-black" x-text="'₱' + getTotalAmount().toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="text-xs font-bold text-amber-900 mb-0.5">Confirm Transaction</p>
                            <p class="text-[10px] text-amber-700">Review carefully. This will update inventory levels.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Footer with Navigation --}}
        <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex items-center justify-between">
                {{-- Back Button --}}
                <button @click="prevStep()" 
                        x-show="stockInStep > 1"
                        class="btn btn-outline btn-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </button>
                <button @click="resetStockInModal()" 
                        x-show="stockInStep === 1"
                        class="btn btn-outline btn-md">
                    Cancel
                </button>
                
                <div class="flex gap-3">
                    {{-- Next Button (Steps 1-2) --}}
                    <button @click="nextStep()" 
                            x-show="stockInStep < 3"
                            :disabled="(stockInStep === 1 && (!supplier || !dateReceived)) || (stockInStep === 2 && stockInItems.length === 0)"
                            :class="(stockInStep === 1 && (!supplier || !dateReceived)) || (stockInStep === 2 && stockInItems.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                            class="btn btn-violet btn-md min-w-32">
                        <span x-show="stockInStep === 1">Continue</span>
                        <span x-show="stockInStep === 2">Review</span>
                        <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    
                    {{-- Submit Button (Step 3) --}}
                    <button @click="saveStockIn()" 
                            x-show="stockInStep === 3"
                            class="btn btn-violet btn-md min-w-32">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submit Transaction
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Product to Master List Modal --}}
<div x-show="addProductModal" @click.self="addProductModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg border border-violet-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <div>
                <h3 class="font-bold text-gray-900 text-[17px]">Add New Product</h3>
                <p class="text-xs text-gray-500 mt-0.5">Add a new product to the master catalog</p>
            </div>
            <button @click="addProductModal=false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="inp-label">Product Name *</label>
                <input type="text" 
                       x-model="newProduct.name" 
                       @input="updateProductSKU()"
                       placeholder="e.g., Mango, Durian, Pomelo" 
                       class="inp" 
                       required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="inp-label">Category *</label>
                    <select x-model="newProduct.category" class="inp" required>
                        <option value="">Select Category</option>
                        <option>Tropical Fruit</option>
                        <option>Citrus</option>
                        <option>Seasonal</option>
                    </select>
                </div>
                <div>
                    <label class="inp-label">Unit of Measure *</label>
                    <select x-model="newProduct.unit" class="inp" required>
                        <option value="">Select Unit</option>
                        <option>kg (Kilogram)</option>
                        <option>pc (Piece)</option>
                        <option>box (Box)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="inp-label">SKU/Code (Auto-generated)</label>
                <input type="text" 
                       x-model="newProduct.skuCode" 
                       placeholder="Will be generated automatically" 
                       class="inp bg-gray-50 cursor-not-allowed" 
                       readonly>
                <p class="text-xs text-gray-500 mt-1">SKU code is automatically generated based on product name</p>
            </div>
            <div>
                <label class="inp-label">Description</label>
                <textarea rows="3" 
                          x-model="newProduct.description" 
                          placeholder="Product description, origin, or special notes..." 
                          class="inp"></textarea>
            </div>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button @click="addProductModal=false" class="btn btn-outline btn-md flex-1">Cancel</button>
            <button @click="saveProduct()" class="btn btn-violet btn-md flex-1">Add Product</button>
        </div>
    </div>
</div>

{{-- Edit Modal (Keep for editing individual items from Overview) --}}
<div x-show="editModal" @click.self="editModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md border border-violet-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 text-[15px]" x-text="'Edit Stock — '+selected"></h3>
            <button @click="editModal=false" class="p-2 hover:bg-gray-100 rounded-xl text-gray-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-6 space-y-4">
            <div><label class="inp-label">Fruit Type</label><select class="inp"><option>Mango</option><option>Durian</option><option>Pomelo</option><option>Mangosteen</option><option>Lanzones</option><option>Banana</option><option>Pineapple</option></select></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="inp-label">Batch ID</label><input type="text" value="MNG-001" placeholder="MNG-003" class="inp"></div>
                <div><label class="inp-label">Quantity (kg)</label><input type="number" value="285" placeholder="0" class="inp"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="inp-label">Unit Price (₱)</label><input type="number" value="120" placeholder="0" class="inp"></div>
                <div><label class="inp-label">Expiration Date</label><input type="date" value="2026-06-26" class="inp"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="inp-label">Remaining Shelf Life (days)</label><input type="number" value="12" placeholder="0" class="inp"></div>
                <div><label class="inp-label">Supplier</label><select class="inp"><option>Davao Fresh Farms</option><option>Mt. Apo Growers</option><option>Sta. Cruz Orchards</option></select></div>
            </div>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button @click="editModal=false" class="btn btn-outline btn-md flex-1">Cancel</button>
            <button @click="editModal=false" class="btn btn-violet btn-md flex-1">Save Changes</button>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div x-show="deleteModal" @click.self="deleteModal=false" class="modal-bg fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" x-cloak x-transition>
    <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-sm text-center border border-red-100" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5"><svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
        <h3 class="text-[18px] font-bold text-gray-900 mb-2">Remove Stock Item?</h3>
        <p class="text-[13.5px] text-gray-500 mb-6"><span class="font-bold text-gray-800" x-text="selected"></span> will be permanently removed from inventory.</p>
        <div class="flex gap-3">
            <button @click="deleteModal=false" class="btn btn-outline btn-md flex-1">Cancel</button>
            <button @click="deleteModal=false" class="btn bg-red-600 hover:bg-red-700 text-white btn-md flex-1">Remove</button>
        </div>
    </div>
</div>

{{-- Stock Out Modal --}}
<div x-show="stockOutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    {{-- Backdrop --}}
    <div x-show="stockOutModal" @click="resetStockOutModal()" 
         class="fixed inset-0 bg-black/40 transition-opacity"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>
    
    {{-- Modal Content --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl border border-violet-100 max-h-[90vh] overflow-hidden flex flex-col z-10" 
         x-show="stockOutModal"
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        {{-- Header --}}
        <div class="border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <h3 class="font-black text-gray-900 text-base">Record Stock Out</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5">Remove inventory from stock</p>
                </div>
                <button @click="resetStockOutModal()" class="p-1.5 hover:bg-white rounded-lg text-gray-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Content --}}
        <div class="flex-1 overflow-y-auto" style="max-height: calc(90vh - 150px);">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4">
                
                {{-- Left Column: Transaction Details --}}
                <div class="lg:col-span-1 space-y-3">
                    <div class="bg-violet-50 border-2 border-violet-200 rounded-xl p-3 sticky top-0">
                        <h4 class="text-xs font-black text-violet-900 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Transaction Details
                        </h4>
                        
                        <div class="space-y-2.5">
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Reference Number</label>
                                <input type="text" x-model="stockOutReference" class="inp bg-white text-xs py-1.5" readonly>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Date *</label>
                                <input type="date" x-model="stockOutDate" class="inp bg-white text-xs py-1.5" required>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Type *</label>
                                <select x-model="stockOutType" class="inp bg-white text-xs py-1.5" required>
                                    <option value="sale">Sale</option>
                                    <option value="spoilage">Spoilage</option>
                                    <option value="wastage">Wastage</option>
                                    <option value="return">Return</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="sample">Sample</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Notes</label>
                                <textarea rows="2" x-model="stockOutReason" placeholder="Optional..." class="inp bg-white text-xs py-1.5"></textarea>
                            </div>
                        </div>
                        
                        {{-- Summary --}}
                        <div class="mt-3 pt-3 border-t-2 border-violet-300">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-violet-900 font-semibold">Items:</span>
                                <span class="font-black text-violet-900" x-text="stockOutItems.length"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-violet-900 font-semibold">Selected batches:</span>
                                <span class="font-black text-violet-900" x-text="stockOutItems.length + ' batch(es)'"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Right Column: Product Selection --}}
                <div class="lg:col-span-2 space-y-3">
                    
                    {{-- Search & Filter Section --}}
                    <div id="stock-out-available-batches" class="bg-white rounded-xl border-2 border-gray-200 p-3">
                        <h4 class="text-xs font-black text-gray-900 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search Products
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-2.5 py-1.5 border-2 border-transparent focus-within:border-violet-300 focus-within:bg-white transition-all">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" 
                                       x-model="stockOutSearchQuery" 
                                       placeholder="Search..." 
                                       class="bg-transparent text-xs outline-none w-full">
                            </div>
                            
                            <select x-model="stockOutFilterCategory" class="inp text-xs py-1.5">
                                <option value="">All Categories</option>
                                <option value="Tropical Fruit">Tropical Fruit</option>
                                <option value="Citrus">Citrus</option>
                                <option value="Seasonal">Seasonal</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Available Products List --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-3 py-2">
                            <h4 class="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Available Inventory
                                <span class="ml-auto text-[10px] font-normal bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded" x-text="filteredStock.length + ' items'"></span>
                            </h4>
                        </div>
                        
                        <div class="p-2 max-h-40 overflow-y-auto space-y-1.5">
                            <template x-if="filteredStock.length === 0">
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <p class="text-xs text-gray-500">No products found</p>
                                </div>
                            </template>
                            
                            <template x-for="batch in filteredStock" :key="batch.batchId">
                                <div @click="selectBatchForStockOut(batch)"
                                     :class="selectedBatch?.batchId === batch.batchId ? 'border-violet-500 bg-violet-50' : 'border-gray-200 hover:border-violet-300 hover:bg-violet-50'"
                                     class="cursor-pointer p-2 border-2 rounded-lg transition-all group">
                                    <div class="flex items-center gap-2">
                                        <div :class="selectedBatch?.batchId === batch.batchId ? 'bg-violet-200' : 'bg-gray-100 group-hover:bg-violet-100'" 
                                             class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors">
                                            <svg class="w-3.5 h-3.5" :class="selectedBatch?.batchId === batch.batchId ? 'text-violet-700' : 'text-gray-400 group-hover:text-violet-600'" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <p class="font-bold text-gray-900 text-xs truncate" x-text="batch.product"></p>
                                                <span class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded-md font-mono font-bold bg-violet-100 text-violet-800 border border-violet-200">
                                                    <span class="font-sans uppercase tracking-wide text-[8px] text-violet-500">Batch</span>
                                                    <span x-text="batch.batchCode"></span>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px] text-gray-500">
                                                <span>Available: <strong x-text="batch.quantity + ' ' + batch.unit"></strong></span>
                                                <span class="px-1.5 py-0.5 rounded" 
                                                      :class="batch.freshness > 14 ? 'bg-green-100 text-green-700' : (batch.freshness > 7 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                                      x-text="batch.freshness + 'd'"></span>
                                            </div>
                                        </div>
                                        <button type="button"
                                                @click.stop="selectBatchForStockOut(batch)"
                                                :class="selectedBatch?.batchId === batch.batchId ? 'bg-violet-600 text-white' : 'bg-violet-100 text-violet-700 hover:bg-violet-200'"
                                                class="flex-shrink-0 rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-colors">
                                            <span x-text="selectedBatch?.batchId === batch.batchId ? 'Selected' : 'Select batch'"></span>
                                        </button>
                                        <div x-show="selectedBatch?.batchId === batch.batchId" class="flex-shrink-0">
                                            <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Add Quantity Section --}}
                    <div x-show="selectedBatch" x-transition class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl border-2 border-violet-300 p-3">
                        <h4 class="text-xs font-black text-violet-900 mb-2">
                            Stock out from <span x-text="selectedBatch?.product"></span>
                            <span class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded-md font-mono font-bold bg-violet-100 text-violet-800 border border-violet-200">
                                <span class="font-sans uppercase tracking-wide text-[8px] text-violet-500">Batch</span>
                                <span x-text="selectedBatch?.batchCode"></span>
                            </span>
                        </h4>
                        <p class="text-[11px] text-violet-700 mb-2">
                            Choose whether to remove the entire batch or only part of it. Available:
                            <strong x-text="selectedBatch?.quantity + ' ' + selectedBatch?.unit"></strong>
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="addWholeSelectedBatch()"
                                    class="btn bg-violet-600 hover:bg-violet-700 text-white btn-sm text-xs px-3 py-2">
                                Remove whole batch (<span x-text="selectedBatch?.quantity + ' ' + selectedBatch?.unit"></span>)
                            </button>
                            <button type="button" @click="stockOutMode = 'partial'; stockOutQuantity = 0"
                                    :class="stockOutMode === 'partial' ? 'bg-violet-100 border-violet-400 text-violet-800' : 'bg-white border-violet-200 text-violet-700 hover:bg-violet-50'"
                                    class="btn btn-sm border text-xs px-3 py-2">
                                Remove part of batch
                            </button>
                        </div>
                        <div x-show="stockOutMode === 'partial'" x-transition class="flex items-end gap-2 mt-3">
                            <div class="flex-1">
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Quantity (<span x-text="selectedBatch?.unit"></span>) *</label>
                                <input type="number"
                                       x-model="stockOutQuantity"
                                       :max="selectedBatchRemainingQuantity"
                                       min="0.01"
                                       step="0.01"
                                       placeholder="Enter amount to remove"
                                       class="inp bg-white text-sm font-bold py-1.5"
                                       @keydown.enter="addSelectedBatch()">
                                <p class="text-[10px] text-violet-700 mt-0.5">
                                    Maximum: <span class="font-bold" x-text="selectedBatchRemainingQuantity + ' ' + selectedBatch?.unit"></span>
                                </p>
                            </div>
                            <button @click="addSelectedBatch()"
                                    :disabled="!selectedBatch || stockOutMode !== 'partial' || stockOutQuantity <= 0 || stockOutQuantity > selectedBatchRemainingQuantity"
                                    :class="(!selectedBatch || stockOutMode !== 'partial' || stockOutQuantity <= 0 || stockOutQuantity > selectedBatchRemainingQuantity) ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="btn bg-violet-600 hover:bg-violet-700 text-white btn-sm whitespace-nowrap text-xs px-3 py-1.5">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add partial quantity
                            </button>
                        </div>
                    </div>
                    
                    {{-- Selected Items Table --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Batches in this transaction (<span x-text="stockOutItems.length"></span>)
                                </h4>
                                <button x-show="stockOutItems.length > 0" type="button" @click="selectAnotherStockOutBatch()"
                                        class="text-[10px] font-bold text-violet-700 hover:text-violet-900 whitespace-nowrap">
                                    + Add another batch
                                </button>
                            </div>
                        </div>
                        
                        <div class="min-h-[120px] max-h-40 overflow-y-auto">
                            <template x-if="stockOutItems.length === 0">
                                <div class="text-center py-8 bg-gray-50">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-xs text-gray-500 font-medium">No items selected</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Select one or more batches above, choose full or partial quantity for each, then record them in one transaction.</p>
                                </div>
                            </template>
                            
                            <template x-if="stockOutItems.length > 0">
                                <div class="p-2 space-y-1.5">
                                    <template x-for="(item, index) in stockOutItems" :key="index">
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-200 hover:bg-violet-50">
                                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                                <div class="w-7 h-7 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-bold text-gray-900 text-xs truncate" x-text="item.product"></p>
                                                    <span class="inline-flex items-center gap-1 mt-1 text-[10px] px-1.5 py-0.5 rounded-md font-mono font-bold bg-violet-100 text-violet-800 border border-violet-200">
                                                        <span class="font-sans uppercase tracking-wide text-[8px] text-violet-500">Batch</span>
                                                        <span x-text="item.batchCode || item.batchId"></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span class="text-sm font-black text-violet-600" x-text="item.quantity + ' ' + item.unit"></span>
                                                <button @click="removeStockOutItem(index)" 
                                                        class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-100 rounded transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        {{-- Footer --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5">
            <div class="flex items-center justify-between">
                <button @click="resetStockOutModal()" class="btn btn-outline btn-sm text-xs px-4">
                    Cancel
                </button>
                <button @click="saveStockOut()" 
                        :disabled="!stockOutType || !stockOutDate || stockOutItems.length === 0"
                        :class="(!stockOutType || !stockOutDate || stockOutItems.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="btn bg-violet-600 hover:bg-violet-700 text-white btn-sm text-xs px-4">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Record Stock Out
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Stock Adjustment Modal --}}
<div x-show="adjustmentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    {{-- Backdrop --}}
    <div x-show="adjustmentModal" @click="resetAdjustmentModal()" 
         class="fixed inset-0 bg-black/40 transition-opacity"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>
    
    {{-- Modal Content --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl border border-violet-100 max-h-[90vh] overflow-hidden flex flex-col z-10" 
         x-show="adjustmentModal"
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        {{-- Header --}}
        <div class="border-b border-gray-100 bg-gradient-to-r from-violet-50 to-purple-50">
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <h3 class="font-black text-gray-900 text-base">Stock Adjustment</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5">Correct inventory discrepancies</p>
                </div>
                <button @click="resetAdjustmentModal()" class="p-1.5 hover:bg-white rounded-lg text-gray-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Content --}}
        <div class="flex-1 overflow-y-auto" style="max-height: calc(90vh - 135px);">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4">
                
                {{-- Left Column: Adjustment Details --}}
                <div class="lg:col-span-1 space-y-3">
                    <div class="bg-violet-50 border-2 border-violet-200 rounded-xl p-3 sticky top-0">
                        <h4 class="text-xs font-black text-violet-900 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Adjustment Details
                        </h4>
                        
                        <div class="space-y-2.5">
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Reference Number</label>
                                <input type="text" x-model="adjustmentReference" class="inp bg-white text-xs py-1.5" readonly>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Date *</label>
                                <input type="date" x-model="adjustmentDate" class="inp bg-white text-xs py-1.5" required>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Reason *</label>
                                <select x-model="adjustmentReason" class="inp bg-white text-xs py-1.5" required>
                                    <option value="">Select Reason</option>
                                    <option value="inventory_count">Physical Inventory Count</option>
                                    <option value="data_entry_error">Data Entry Error</option>
                                    <option value="system_glitch">System Glitch</option>
                                    <option value="measurement_error">Measurement Error</option>
                                    <option value="found_stock">Found/Discovered Stock</option>
                                    <option value="reconciliation">Account Reconciliation</option>
                                    <option value="audit">Audit Correction</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">Notes</label>
                                <textarea rows="2" x-model="adjustmentNotes" placeholder="Optional details..." class="inp bg-white text-xs py-1.5"></textarea>
                            </div>
                        </div>
                        
                        {{-- Summary --}}
                        <div class="mt-3 pt-3 border-t-2 border-violet-300">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-violet-900 font-semibold">Items:</span>
                                <span class="font-black text-violet-900" x-text="adjustmentItems.length"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-violet-900 font-semibold">Adjustments:</span>
                                <div class="text-right">
                                    <div class="font-bold text-green-600" x-text="'+' + adjustmentItems.filter(i => i.type === 'add').reduce((sum, i) => sum + i.quantity, 0).toFixed(2) + ' kg'"></div>
                                    <div class="font-bold text-red-600" x-text="'-' + adjustmentItems.filter(i => i.type === 'subtract').reduce((sum, i) => sum + i.quantity, 0).toFixed(2) + ' kg'"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Items to Adjust List --}}
                    <div class="bg-white rounded-xl border-2 border-violet-200 overflow-hidden">
                        <div class="bg-violet-50 border-b-2 border-violet-200 px-3 py-2">
                            <h4 class="text-xs font-black text-violet-900 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Items to Adjust
                                <span class="ml-auto text-[10px] font-normal bg-violet-200 text-violet-900 px-1.5 py-0.5 rounded" x-text="adjustmentItems.length"></span>
                            </h4>
                        </div>
                        
                        <div class="p-2 max-h-32 overflow-y-auto space-y-1.5">
                            <template x-if="adjustmentItems.length === 0">
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-xs text-gray-500">No items added</p>
                                </div>
                            </template>
                            
                            <template x-for="(item, index) in adjustmentItems" :key="index">
                                <div class="p-2 bg-gray-50 border-2 border-gray-200 rounded-lg">
                                    <div class="flex items-start gap-2">
                                        <div :class="item.type === 'add' ? 'bg-green-100' : 'bg-red-100'" 
                                             class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" :class="item.type === 'add' ? 'text-green-600' : 'text-red-600'" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.type === 'add' ? 'M12 4v16m8-8H4' : 'M20 12H4'"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <p class="font-bold text-gray-900 text-xs truncate" x-text="item.product"></p>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-mono bg-gray-200 text-gray-600" x-text="item.batchId"></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px]">
                                                <span class="text-gray-500">Current: <strong x-text="item.currentStock + ' kg'"></strong></span>
                                                <span class="font-bold" 
                                                      :class="item.type === 'add' ? 'text-green-600' : 'text-red-600'"
                                                      x-text="(item.type === 'add' ? '+' : '-') + item.quantity + ' kg'"></span>
                                            </div>
                                        </div>
                                        <button @click="removeAdjustmentItem(index)" 
                                                class="p-1 hover:bg-red-100 rounded text-red-500 transition-colors flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                
                {{-- Right Column: Product Selection --}}
                <div class="lg:col-span-2 space-y-3">
                    
                    {{-- Adjustment Type Selector --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 p-3">
                        <h4 class="text-xs font-black text-gray-900 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            Adjustment Type
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="adjustmentType='add'" 
                                    :class="adjustmentType === 'add' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-gray-50 border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="p-2.5 border-2 rounded-xl transition-all flex items-center justify-center gap-2 font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Stock
                            </button>
                            <button @click="adjustmentType='subtract'" 
                                    :class="adjustmentType === 'subtract' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-gray-50 border-gray-300 text-gray-600 hover:border-gray-400'"
                                    class="p-2.5 border-2 rounded-xl transition-all flex items-center justify-center gap-2 font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                </svg>
                                Subtract Stock
                            </button>
                        </div>
                    </div>
                    
                    {{-- Search & Filter Section --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 p-3">
                        <h4 class="text-xs font-black text-gray-900 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search Products
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-2.5 py-1.5 border-2 border-transparent focus-within:border-violet-300 focus-within:bg-white transition-all">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" 
                                       x-model="adjustmentSearchQuery" 
                                       placeholder="Search product or batch..." 
                                       class="bg-transparent text-xs outline-none w-full">
                            </div>
                            
                            <select x-model="adjustmentFilterCategory" class="inp text-xs py-1.5">
                                <option value="">All Categories</option>
                                <option value="Tropical Fruit">Tropical Fruit</option>
                                <option value="Citrus">Citrus</option>
                                <option value="Seasonal">Seasonal</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Available Products List --}}
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-3 py-2">
                            <h4 class="text-xs font-black text-gray-900 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Select Product Batch
                                <span class="ml-auto text-[10px] font-normal bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded" x-text="filteredAdjustmentStock.length + ' items'"></span>
                            </h4>
                        </div>
                        
                        <div class="p-2 max-h-36 overflow-y-auto space-y-1.5">
                            <template x-if="filteredAdjustmentStock.length === 0">
                                <div class="text-center py-6">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <p class="text-xs text-gray-500">No products found</p>
                                </div>
                            </template>
                            
                            <template x-for="batch in filteredAdjustmentStock" :key="batch.batchId">
                                <div @click="selectProductForAdjustment(batch)"
                                     :class="selectedProductForAdjustment?.batchId === batch.batchId ? 'border-violet-500 bg-violet-50' : 'border-gray-200 hover:border-violet-300 hover:bg-violet-50'"
                                     class="cursor-pointer p-2 border-2 rounded-lg transition-all group">
                                    <div class="flex items-center gap-2">
                                        <div :class="selectedProductForAdjustment?.batchId === batch.batchId ? 'bg-violet-200' : 'bg-gray-100 group-hover:bg-violet-100'" 
                                             class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" :class="selectedProductForAdjustment?.batchId === batch.batchId ? 'text-violet-700' : 'text-gray-400 group-hover:text-violet-600'" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <p class="font-bold text-gray-900 text-xs truncate" x-text="batch.product"></p>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-mono bg-gray-100 text-gray-600" x-text="batch.batchId"></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px] text-gray-500">
                                                <span>Current: <strong x-text="batch.quantity + ' kg'"></strong></span>
                                                <span class="px-1.5 py-0.5 rounded" 
                                                      :class="batch.freshness > 70 ? 'bg-green-100 text-green-700' : (batch.freshness > 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                                                      x-text="batch.freshness + '%'"></span>
                                            </div>
                                        </div>
                                        <div x-show="selectedProductForAdjustment?.batchId === batch.batchId" class="flex-shrink-0">
                                            <svg class="w-4 h-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Add Quantity Section --}}
                    <div x-show="selectedProductForAdjustment" x-transition class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl border-2 border-violet-300 p-3">
                        <h4 class="text-xs font-black text-violet-900 mb-2">Enter Adjustment Quantity</h4>
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="text-[10px] font-bold text-violet-900 uppercase mb-1 block">
                                    <span x-show="adjustmentType === 'add'">Quantity to Add (kg) *</span>
                                    <span x-show="adjustmentType === 'subtract'">Quantity to Subtract (kg) *</span>
                                </label>
                                <input type="number" 
                                       x-model="adjustmentQuantity"
                                       min="0.01"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="inp text-xs py-1.5 font-bold"
                                       :class="adjustmentType === 'add' ? 'border-green-300 focus:border-green-500' : 'border-red-300 focus:border-red-500'"
                                       @keydown.enter="addAdjustmentItem()">
                            </div>
                            <button @click="addAdjustmentItem()"
                                    :disabled="!selectedProductForAdjustment || adjustmentQuantity <= 0"
                                    :class="(!selectedProductForAdjustment || adjustmentQuantity <= 0) ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="btn bg-violet-600 hover:bg-violet-700 text-white text-xs px-3 py-2 h-[34px]">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                        <p class="text-[10px] text-violet-700 mt-1.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Current stock: <strong x-text="selectedProductForAdjustment?.quantity + ' kg'"></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Footer --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    <span x-show="adjustmentItems.length === 0">Add products to adjust inventory</span>
                    <span x-show="adjustmentItems.length > 0" class="font-semibold text-gray-700">
                        <span x-text="adjustmentItems.length"></span> item<span x-show="adjustmentItems.length !== 1">s</span> ready to adjust
                    </span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="resetAdjustmentModal()" class="btn btn-outline text-xs px-3 py-1.5">
                        Cancel
                    </button>
                    <button @click="saveAdjustment()" 
                            :disabled="adjustmentItems.length === 0 || !adjustmentReason"
                            :class="(adjustmentItems.length === 0 || !adjustmentReason) ? 'opacity-50 cursor-not-allowed' : ''"
                            class="btn bg-violet-600 hover:bg-violet-700 text-white text-xs px-3 py-1.5">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submit Adjustment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</x-app-layout>
