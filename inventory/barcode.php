<?php

/**
 * Barcode Management Page
 * Generate and print barcodes for products
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/barcode.php';
requireAuth();

$pageTitle = 'Barcode Management';
$db = getDBConnection();

// Get all products
$stmt = $db->query("
    SELECT p.*, s.quantity as stock_quantity 
    FROM products p 
    LEFT JOIN stock s ON p.id = s.product_id 
    WHERE p.is_active = 1 
    ORDER BY p.name
");
$products = $stmt->fetchAll();

// Handle barcode generation request
$selectedProduct = null;
$barcodeData = '';
if (isset($_GET['product_id'])) {
    $productId = intval($_GET['product_id']);
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $selectedProduct = $stmt->fetch();
    if ($selectedProduct) {
        $barcodeData = generateProductBarcodeData($productId, $selectedProduct['metal_type']);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-upc"></i> Barcode Management</h2>
    <a href="<?php echo BASE_URL; ?>/inventory/stock.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Stock
    </a>
</div>

<div class="row">
    <!-- Product Selection -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-search"></i> Select Product</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Search Product</label>
                    <input type="text" class="form-control" id="productSearch" placeholder="Type to search...">
                </div>

                <div class="list-group" id="productList" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($products as $product): ?>
                        <a href="?product_id=<?php echo $product['id']; ?>"
                            class="list-group-item list-group-item-action <?php echo ($selectedProduct && $selectedProduct['id'] == $product['id']) ? 'active' : ''; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small><?php echo ucfirst($product['metal_type']) . ' ' . $product['purity']; ?></small>
                            </div>
                            <small class="text-muted">
                                Stock: <?php echo $product['stock_quantity'] ?? 0; ?> pcs |
                                ID: <?php echo $product['id']; ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Barcode Scanner -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-upc-scan"></i> Scan Barcode</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Scan or Enter Barcode</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="scanBarcode" placeholder="Scan barcode here...">
                        <button class="btn btn-outline-primary" type="button" onclick="startScanner()">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>
                </div>
                <div id="scannerContainer" style="display: none;">
                    <div id="interactive" class="viewport"></div>
                    <button class="btn btn-sm btn-secondary mt-2" onclick="stopScanner()">Close Scanner</button>
                </div>
                <div id="scanResult" class="mt-2"></div>
                <div id="scanProductDetails" class="mt-3" style="display: none;">
                    <div class="card border-success">
                        <div class="card-body">
                            <h6 class="card-title text-success"><i class="bi bi-check-circle"></i> Product Found!</h6>
                            <div id="scanProductInfo"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barcode Preview -->
    <div class="col-md-7">
        <?php if ($selectedProduct): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-upc"></i> Barcode Preview</h5>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-3"><?php echo htmlspecialchars($selectedProduct['name']); ?></h4>
                    <p class="text-muted">
                        <?php echo ucfirst($selectedProduct['metal_type']) . ' ' . $selectedProduct['purity']; ?> |
                        ID: <?php echo $selectedProduct['id']; ?>
                    </p>

                    <!-- Barcode Display -->
                    <div class="barcode-display my-4 p-4 bg-white border rounded">
                        <div id="barcodeLoading" class="text-center text-muted">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <small class="d-block mt-2">Loading barcode...</small>
                        </div>
                        <svg id="barcode" style="display: none;"></svg>
                        <div class="mt-2">
                            <strong><?php echo $barcodeData; ?></strong>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="qrcode-display my-4">
                        <h6>QR Code</h6>
                        <div id="qrcode"></div>
                        <small class="text-muted">Scan for product details</small>
                    </div>

                    <!-- Print Options -->
                    <div class="print-options mt-4">
                        <h6>Print Labels</h6>
                        <div class="row justify-content-center">
                            <div class="col-auto">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="printQty" value="1" min="1" max="100" style="width: 80px;">
                            </div>
                            <div class="col-auto">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary d-block" onclick="printBarcode()">
                                    <i class="bi bi-printer"></i> Print Barcode
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-upc" style="font-size: 64px; color: #dee2e6;"></i>
                    <h5 class="mt-3 text-muted">Select a product to generate barcode</h5>
                    <p class="text-muted">Choose a product from the list on the left</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print Template (Hidden) -->
<div id="printTemplate" style="display: none;">
    <div class="barcode-label">
        <div class="label-header"><?php echo APP_NAME; ?></div>
        <div class="label-product" id="printProductName"></div>
        <div class="label-barcode">
            <svg id="printBarcode"></svg>
        </div>
        <div class="label-code" id="printBarcodeData"></div>
        <div class="label-price" id="printPrice"></div>
    </div>
</div>

<style>
    .barcode-label {
        width: 2in;
        height: 1.2in;
        padding: 0.08in;
        border: 1px solid #000;
        text-align: center;
        font-family: Arial, sans-serif;
        page-break-inside: avoid;
        margin: 0.03in;
        display: inline-block;
        overflow: hidden;
        box-sizing: border-box;
    }

    .label-header {
        font-size: 7px;
        font-weight: bold;
        color: #666;
        margin-bottom: 1px;
        height: 10px;
        line-height: 10px;
    }

    .label-product {
        font-size: 9px;
        font-weight: bold;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 14px;
        line-height: 14px;
    }

    .label-barcode {
        height: 0.45in;
        width: 100%;
        overflow: hidden;
    }

    .label-barcode svg {
        max-width: 100%;
        height: 100%;
    }

    .label-code {
        font-size: 8px;
        font-family: monospace;
        margin-top: 1px;
        height: 12px;
        line-height: 12px;
    }

    .label-price {
        font-size: 10px;
        font-weight: bold;
        color: #d4af37;
        margin-top: 1px;
        height: 14px;
        line-height: 14px;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #printTemplate,
        #printTemplate * {
            visibility: visible;
        }

        #printTemplate {
            display: block !important;
            position: absolute;
            left: 0;
            top: 0;
        }

        .barcode-label {
            border: 1px solid #000 !important;
        }
    }
</style>

<!-- Include Barcode Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/simple-barcode.js"></script>

<script>
    // Wait for libraries to load with timeout
    var libraryCheckInterval;
    var libraryCheckCount = 0;

    function checkLibrariesLoaded() {
        libraryCheckCount++;

        if (typeof JsBarcode !== 'undefined') {
            console.log('✓ JsBarcode loaded');
            clearInterval(libraryCheckInterval);
            generateBarcode();
        } else if (libraryCheckCount > 20) {
            // After 10 seconds, use fallback
            console.warn('✗ JsBarcode failed to load, using simple barcode generator');
            clearInterval(libraryCheckInterval);
            useSimpleBarcode();
        } else {
            console.log('Waiting for JsBarcode... (' + libraryCheckCount + ')');
        }
    }

    function generateBarcode() {
        try {
            JsBarcode("#barcode", "<?php echo $barcodeData; ?>", {
                format: "CODE128",
                width: 2,
                height: 80,
                displayValue: true,
                fontSize: 14,
                font: "monospace",
                margin: 10
            });
            $('#barcodeLoading').hide();
            $('#barcode').show();
            console.log('✓ Barcode generated with JsBarcode');
        } catch (e) {
            console.error('Barcode generation error:', e);
            useSimpleBarcode();
        }
    }

    function useSimpleBarcode() {
        $('#barcodeLoading').hide();
        // Use simple canvas-based barcode
        var canvas = document.createElement('canvas');
        canvas.id = 'simpleBarcode';
        canvas.width = 400;
        canvas.height = 120;
        canvas.style.display = 'block';
        canvas.style.margin = '0 auto';

        $('#barcode').parent().append(canvas);
        $('#barcode').hide();

        generateCanvasBarcode('simpleBarcode', "<?php echo $barcodeData; ?>");
        console.log('✓ Simple barcode generated');
    }

    // Start checking when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        libraryCheckInterval = setInterval(checkLibrariesLoaded, 500);
    });
</script>

<!-- Fallback for library load errors -->
<script>
    window.onerror = function(msg, url, line) {
        if (msg.includes('JsBarcode') || msg.includes('QRCode')) {
            console.error('Barcode library failed to load:', msg);
            document.addEventListener('DOMContentLoaded', function() {
                var barcodeEl = document.getElementById('barcode');
                var qrcodeEl = document.getElementById('qrcode');
                if (barcodeEl) {
                    barcodeEl.parentElement.innerHTML = '<div class="alert alert-warning">Barcode library failed to load. Please check your internet connection.</div>';
                }
                if (qrcodeEl) {
                    qrcodeEl.innerHTML = '<div class="alert alert-warning">QR Code library failed to load.</div>';
                }
            });
        }
    };
</script>

<script>
    // Product search
    $('#productSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#productList a').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    <?php if ($selectedProduct): ?>
        // Barcode will be generated automatically by the library checker above
        // QR Code generation
        $(document).ready(function() {
            setTimeout(function() {
                try {
                    if (typeof QRCode !== 'undefined') {
                        new QRCode(document.getElementById("qrcode"), {
                            text: "<?php echo $barcodeData; ?>",
                            width: 128,
                            height: 128,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                        console.log('✓ QR Code generated successfully');
                    } else {
                        console.warn('✗ QRCode library not loaded');
                        $('#qrcode').html('<div class="alert alert-warning">QR Code library not loaded</div>');
                    }
                } catch (e) {
                    console.error('QR Code generation error:', e);
                    $('#qrcode').html('<div class="alert alert-danger">Error generating QR code</div>');
                }
            }, 1000);
        });

        // Print barcode function
        function printBarcode() {
            var qty = parseInt($('#printQty').val()) || 1;
            var productName = "<?php echo addslashes($selectedProduct['name']); ?>";
            var barcodeData = "<?php echo $barcodeData; ?>";

            // Create print content
            var printContent = '';
            for (var i = 0; i < qty; i++) {
                printContent += `
            <div class="barcode-label">
                <div class="label-header"><?php echo APP_NAME; ?></div>
                <div class="label-product">${productName}</div>
                <div class="label-barcode">
                    <svg class="print-barcode-${i}"></svg>
                </div>
                <div class="label-code">${barcodeData}</div>
            </div>
        `;
            }

            // Open print window
            var printWindow = window.open('', '_blank');
            printWindow.document.write(`
        <html>
        <head>
            <title>Print Barcodes</title>
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
            <style>
                body { margin: 0.1in; }
                .barcode-label {
                    width: 2in;
                    height: 1.2in;
                    padding: 0.08in;
                    border: 1px solid #000;
                    text-align: center;
                    font-family: Arial, sans-serif;
                    page-break-inside: avoid;
                    margin: 0.03in;
                    display: inline-block;
                    overflow: hidden;
                    box-sizing: border-box;
                }
                .label-header { font-size: 7px; font-weight: bold; color: #666; margin-bottom: 1px; height: 10px; line-height: 10px; }
                .label-product { font-size: 9px; font-weight: bold; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; height: 14px; line-height: 14px; }
                .label-barcode { height: 0.45in; width: 100%; overflow: hidden; }
                .label-barcode svg { max-width: 100%; height: 100%; }
                .label-code { font-size: 8px; font-family: monospace; margin-top: 1px; height: 12px; line-height: 12px; }
            </style>
        </head>
        <body>
            ${printContent}
            <script>
                window.onload = function() {
                    for (var i = 0; i < ${qty}; i++) {
                        JsBarcode(".print-barcode-" + i, "${barcodeData}", {
                            format: "CODE128",
                            width: 1.5,
                            height: 40,
                            displayValue: false,
                            margin: 0
                        });
                    }
                    setTimeout(function() { window.print(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
            printWindow.document.close();
        }
    <?php endif; ?>

    // Barcode Scanner
    var scannerActive = false;

    function startScanner() {
        $('#scannerContainer').show();
        scannerActive = true;

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector("#interactive"),
                constraints: {
                    width: 400,
                    height: 300,
                    facingMode: "environment"
                }
            },
            decoder: {
                readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"]
            }
        }, function(err) {
            if (err) {
                console.error(err);
                $('#scanResult').html('<span class="text-danger">Error starting scanner</span>');
                return;
            }
            Quagga.start();
        });

        Quagga.onDetected(function(result) {
            var code = result.codeResult.code;
            $('#scanBarcode').val(code);
            searchProductByBarcode(code);
            stopScanner();
        });
    }

    function stopScanner() {
        if (scannerActive) {
            Quagga.stop();
            scannerActive = false;
        }
        $('#scannerContainer').hide();
    }

    function searchProductByBarcode(barcode) {
        $('#scanResult').html('<span class="text-info"><i class="bi bi-hourglass-split"></i> Searching...</span>');
        $('#scanProductDetails').hide();

        $.ajax({
            url: '<?php echo BASE_URL; ?>/ajax/search_barcode.php',
            type: 'GET',
            data: {
                barcode: barcode
            },
            success: function(response) {
                if (response.success && response.product) {
                    $('#scanResult').html('<span class="text-success"><i class="bi bi-check-circle-fill"></i> Product Found!</span>');

                    // Show product details
                    var productHtml = '<div class="mb-2">';
                    productHtml += '<strong>Product Name:</strong> ' + response.product.name + '<br>';
                    productHtml += '<strong>Metal:</strong> ' + response.product.metal_type.charAt(0).toUpperCase() + response.product.metal_type.slice(1) + ' ' + response.product.purity + '<br>';
                    productHtml += '<strong>Stock:</strong> ' + response.product.stock_quantity + ' pcs';
                    if (response.product.stock_weight > 0) {
                        productHtml += ' (' + response.product.stock_weight + 'g)';
                    }
                    productHtml += '</div>';
                    productHtml += '<a href="?product_id=' + response.product.id + '" class="btn btn-sm btn-success">';
                    productHtml += '<i class="bi bi-upc"></i> View & Generate Barcode';
                    productHtml += '</a>';

                    $('#scanProductInfo').html(productHtml);
                    $('#scanProductDetails').show();

                    // Auto-redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = '?product_id=' + response.product.id;
                    }, 2000);
                } else {
                    $('#scanResult').html('<span class="text-danger"><i class="bi bi-x-circle-fill"></i> Product not found</span>');
                    $('#scanProductDetails').hide();
                }
            },
            error: function() {
                $('#scanResult').html('<span class="text-danger"><i class="bi bi-x-circle-fill"></i> Search failed</span>');
                $('#scanProductDetails').hide();
            }
        });
    }

    // Handle manual barcode entry
    $('#scanBarcode').on('keypress', function(e) {
        if (e.which === 13) {
            searchProductByBarcode($(this).val());
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
