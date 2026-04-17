/**
 * JewelSync ERP - Main JavaScript
 */

$(document).ready(function() {
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').alert('close');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Print invoice
    $('.btn-print').on('click', function() {
        window.print();
    });
    
    // Calculate billing items dynamically
    initBillingCalculations();
    
    // Initialize date pickers with today's date
    $('input[type="date"]').each(function() {
        if (!$(this).val()) {
            $(this).val(new Date().toISOString().split('T')[0]);
        }
    });
    
});

/**
 * Initialize billing calculations
 */
function initBillingCalculations() {
    // When metal rate or purity changes
    $(document).on('change', '.metal-type, .purity-select', function() {
        var row = $(this).closest('.invoice-item-row');
        updateMetalRate(row);
    });
    
    // When weight or rate changes
    $(document).on('input', '.gross-weight, .net-weight, .rate-per-gram, .wastage-percent, .making-charge-rate', function() {
        var row = $(this).closest('.invoice-item-row');
        calculateItemTotal(row);
    });
    
    // Making charge type change
    $(document).on('change', '.making-charge-type', function() {
        var row = $(this).closest('.invoice-item-row');
        calculateItemTotal(row);
    });
}

/**
 * Update metal rate based on selection
 */
function updateMetalRate(row) {
    var metalType = row.find('.metal-type').val();
    var purity = row.find('.purity-select').val();
    
    if (metalType && purity) {
        $.ajax({
            url: '/ajax/get-metal-rate.php',
            type: 'POST',
            data: { metal_type: metalType, purity: purity },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.find('.rate-per-gram').val(response.rate);
                    calculateItemTotal(row);
                }
            }
        });
    }
}

/**
 * Calculate item total
 */
function calculateItemTotal(row) {
    var grossWeight = parseFloat(row.find('.gross-weight').val()) || 0;
    var netWeight = parseFloat(row.find('.net-weight').val()) || 0;
    var wastagePercent = parseFloat(row.find('.wastage-percent').val()) || 0;
    var ratePerGram = parseFloat(row.find('.rate-per-gram').val()) || 0;
    var makingChargeType = row.find('.making-charge-type').val();
    var makingChargeRate = parseFloat(row.find('.making-charge-rate').val()) || 0;
    
    // Calculate wastage weight
    var wastageWeight = netWeight * (wastagePercent / 100);
    var totalWeight = netWeight + wastageWeight;
    
    // Calculate amounts
    var metalAmount = totalWeight * ratePerGram;
    
    var makingChargeAmount = 0;
    if (makingChargeType === 'per_gram') {
        makingChargeAmount = totalWeight * makingChargeRate;
    } else {
        makingChargeAmount = makingChargeRate;
    }
    
    var itemTotal = metalAmount + makingChargeAmount;
    
    // Update fields
    row.find('.wastage-weight').val(wastageWeight.toFixed(3));
    row.find('.total-weight').val(totalWeight.toFixed(3));
    row.find('.metal-amount').val(metalAmount.toFixed(2));
    row.find('.making-charge-amount').val(makingChargeAmount.toFixed(2));
    row.find('.item-total').val(itemTotal.toFixed(2));
    
    // Recalculate invoice totals
    calculateInvoiceTotals();
}

/**
 * Calculate invoice totals
 */
function calculateInvoiceTotals() {
    var subtotal = 0;
    var totalMetalAmount = 0;
    var totalMakingAmount = 0;
    
    // Calculate totals from all items
    $('.invoice-item-row').each(function() {
        var metalAmount = parseFloat($(this).find('.metal-amount').val()) || 0;
        var makingAmount = parseFloat($(this).find('.making-charge-amount').val()) || 0;
        var itemTotal = parseFloat($(this).find('.item-total').val()) || 0;
        
        subtotal += itemTotal;
        totalMetalAmount += metalAmount;
        totalMakingAmount += makingAmount;
    });
    
    var discountAmount = parseFloat($('#discount_amount').val()) || 0;
    var taxableAmount = subtotal - discountAmount;
    
    // Calculate GST based on Indian market rates
    // Gold/Silver: 3% GST, Making Charges: 5% GST
    var metalGSTRate = 3; // Gold/Silver: 3%
    var makingGSTRate = 5; // Making charges (job work): 5%
    
    var metalGST = totalMetalAmount * metalGSTRate / 100;
    var makingGST = totalMakingAmount * makingGSTRate / 100;
    var totalGST = metalGST + makingGST;
    
    // Split GST into CGST and SGST (half each for intra-state)
    var cgst = totalGST / 2;
    var sgst = totalGST / 2;
    var totalAmount = taxableAmount + totalGST;
    
    // Payment calculation
    var paidAmount = parseFloat($('#paid_amount').val()) || 0;
    var balanceAmount = totalAmount - paidAmount;
    
    // Update payment status badge
    var statusBadge = $('#payment_status');
    if (paidAmount <= 0) {
        statusBadge.text('Pending').removeClass().addClass('badge bg-secondary');
    } else if (paidAmount < totalAmount) {
        statusBadge.text('Partial').removeClass().addClass('badge bg-warning text-dark');
    } else {
        statusBadge.text('Paid').removeClass().addClass('badge bg-success');
    }
    
    // Update fields
    $('#subtotal').val(subtotal.toFixed(2));
    $('#taxable_amount').val(taxableAmount.toFixed(2));
    $('#metal_gst').val(metalGST.toFixed(2));
    $('#making_gst').val(makingGST.toFixed(2));
    $('#cgst_amount').val(cgst.toFixed(2));
    $('#sgst_amount').val(sgst.toFixed(2));
    $('#total_amount').val(totalAmount.toFixed(2));
    $('#balance_amount').val(balanceAmount.toFixed(2));
}

/**
 * Add new invoice item row
 */
function addInvoiceItem() {
    var itemCount = $('.invoice-item-row').length + 1;
    
    $.ajax({
        url: '/ajax/get-item-row.php',
        type: 'POST',
        data: { item_no: itemCount },
        success: function(html) {
            $('#invoice-items-container').append(html);
        }
    });
}

/**
 * Remove invoice item row
 */
function removeInvoiceItem(btn) {
    if ($('.invoice-item-row').length > 1) {
        $(btn).closest('.invoice-item-row').remove();
        calculateInvoiceTotals();
        
        // Renumber items
        $('.invoice-item-row').each(function(index) {
            $(this).find('.item-number').text(index + 1);
        });
    } else {
        alert('At least one item is required.');
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Format weight
 */
function formatWeight(weight) {
    return parseFloat(weight).toFixed(3) + ' gram';
}

/**
 * Show loading overlay
 */
function showLoading() {
    $('body').append('<div class="spinner-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    $('.spinner-overlay').remove();
}

/**
 * Validate form
 */
function validateForm(formId) {
    var form = document.getElementById(formId);
    if (form.checkValidity() === false) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
    return form.checkValidity();
}

/**
 * Search customer
 */
function searchCustomer(query) {
    if (query.length < 2) return;
    
    $.ajax({
        url: '/ajax/search-customer.php',
        type: 'POST',
        data: { query: query },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.customers.length > 0) {
                var html = '<ul class="list-group">';
                response.customers.forEach(function(customer) {
                    html += '<li class="list-group-item list-group-item-action" onclick="selectCustomer(' + customer.id + ', \'' + customer.business_name + '\', ' + customer.current_balance + ')">';
                    html += '<strong>' + customer.business_name + '</strong><br>';
                    html += '<small>' + customer.phone + ' | Balance: ₹' + customer.current_balance + '</small>';
                    html += '</li>';
                });
                html += '</ul>';
                $('#customer-search-results').html(html).show();
            } else {
                $('#customer-search-results').hide();
            }
        }
    });
}

/**
 * Select customer from search
 */
function selectCustomer(id, name, balance) {
    $('#customer_id').val(id);
    $('#customer_name').val(name);
    $('#customer_balance').text('Balance: ₹' + balance.toFixed(2));
    $('#customer-search-results').hide();
}

/**
 * Calculate customer balance
 */
function calculateCustomerBalance() {
    var openingBalance = parseFloat($('#opening_balance').val()) || 0;
    var creditLimit = parseFloat($('#credit_limit').val()) || 0;
    
    $('#balance_info').text('Opening Balance: ₹' + openingBalance.toFixed(2));
}

/**
 * Export table to Excel
 */
function exportToExcel(tableId, filename) {
    var table = document.getElementById(tableId);
    var html = table.outerHTML;
    
    var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    var downloadLink = document.createElement('a');
    downloadLink.href = url;
    downloadLink.download = filename + '.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

/**
 * Print report
 */
function printReport() {
    window.print();
}
