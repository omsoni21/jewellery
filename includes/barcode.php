<?php
/**
 * Barcode Generation and Scanning Utilities
 * Supports Code128, EAN-13, and QR Code formats
 */

/**
 * Generate a barcode for a product
 * @param string $data The data to encode (usually product code or SKU)
 * @param string $type Barcode type: 'code128', 'ean13', 'qrcode'
 * @param int $width Width of the barcode image
 * @param int $height Height of the barcode image
 * @return string Base64 encoded image data
 */
function generateBarcode($data, $type = 'code128', $width = 200, $height = 80) {
    // Use a simple barcode generation approach with HTML5 Canvas via JavaScript
    // This returns HTML/JS that will render the barcode client-side
    
    $barcodeId = 'barcode_' . uniqid();
    
    $html = '<canvas id="' . $barcodeId . '" width="' . $width . '" height="' . $height . '"></canvas>';
    $html .= '<script>';
    $html .= '(function() {';
    $html .= 'var canvas = document.getElementById("' . $barcodeId . '");';
    $html .= 'var ctx = canvas.getContext("2d");';
    $html .= 'ctx.fillStyle = "white"; ctx.fillRect(0, 0, ' . $width . ', ' . $height . ');';
    $html .= 'ctx.fillStyle = "black";';
    $html .= 'ctx.font = "12px Arial"; ctx.textAlign = "center";';
    $html .= 'ctx.fillText("' . addslashes($data) . '", ' . ($width/2) . ', ' . ($height-10) . ');';
    
    // Simple Code128 barcode pattern generation
    if ($type === 'code128') {
        $html .= generateCode128JS($data, $width, $height);
    }
    
    $html .= '})();';
    $html .= '</script>';
    
    return $html;
}

/**
 * Generate JavaScript for Code128 barcode
 */
function generateCode128JS($data, $width, $height) {
    // Code128 patterns (simplified B set)
    $patterns = [
        ' ' => '11011001100', '!' => '11001101100', '"' => '11001100110',
        '#' => '10010011000', '$' => '10010001100', '%' => '10001001100',
        '&' => '10011001000', '\'' => '10011000100', '(' => '10001100100',
        ')' => '11001001000', '*' => '11001000100', '+' => '11000100100',
        ',' => '10110011100', '-' => '10011011100', '.' => '10011001110',
        '/' => '10111001100', '0' => '10011101100', '1' => '10011100110',
        '2' => '11001110010', '3' => '11001011100', '4' => '11001001110',
        '5' => '11011100100', '6' => '11001110100', '7' => '11101101110',
        '8' => '11101001100', '9' => '11100101100', ':' => '11100100110',
        ';' => '11101100100', '<' => '11100110100', '=' => '11100110010',
        '>' => '11011011000', '?' => '11011000110', '@' => '11000110110',
        'A' => '10100011000', 'B' => '10001011000', 'C' => '10001000110',
        'D' => '10110001000', 'E' => '10001101000', 'F' => '10001100010',
        'G' => '11010001000', 'H' => '11000101000', 'I' => '11000100010',
        'J' => '10110111000', 'K' => '10110001110', 'L' => '10001101110',
        'M' => '10111011000', 'N' => '10111000110', 'O' => '10001110110',
        'P' => '11101110110', 'Q' => '11010001110', 'R' => '11000101110',
        'S' => '11011101000', 'T' => '11011100010', 'U' => '11011101110',
        'V' => '11101011000', 'W' => '11101000110', 'X' => '11100010110',
        'Y' => '11101101000', 'Z' => '11101100010', '[' => '11100011010',
        '\\' => '11101111010', ']' => '11001000010', '^' => '11110001010',
        '_' => '10100110000', '`' => '10100001100', 'a' => '10010110000',
        'b' => '10010000110', 'c' => '10000101100', 'd' => '10000100110',
        'e' => '10110010000', 'f' => '10110000100', 'g' => '10011010000',
        'h' => '10011000010', 'i' => '10000110100', 'j' => '10000110010',
        'k' => '11000010010', 'l' => '11001010000', 'm' => '11110111010',
        'n' => '11000010100', 'o' => '10001111010', 'p' => '10100111100',
        'q' => '10010111100', 'r' => '10010011110', 's' => '10111100100',
        't' => '10011110100', 'u' => '10011110010', 'v' => '11110100100',
        'w' => '11110010100', 'x' => '11110010010', 'y' => '11011011110',
        'z' => '11011110110', '{' => '11110110110', '|' => '10101111000',
        '}' => '10100011110', '~' => '10001011110'
    ];
    
    // Start code B
    $startCode = '11010010000';
    // Stop code
    $stopCode = '1100011101011';
    
    $js = 'var patterns = {};';
    foreach ($patterns as $char => $pattern) {
        $js .= 'patterns["' . $char . '"] = "' . $pattern . '";';
    }
    
    $js .= 'var data = "' . addslashes($data) . '";';
    $js .= 'var x = 10; var barHeight = ' . ($height - 25) . ';';
    $js .= 'var barWidth = 2;';
    $js .= 'var fullPattern = "' . $startCode . '";';
    $js .= 'for (var i = 0; i < data.length; i++) {';
    $js .= '  var char = data.charAt(i);';
    $js .= '  if (patterns[char]) fullPattern += patterns[char];';
    $js .= '}';
    $js .= 'fullPattern += "' . $stopCode . '";';
    $js .= 'for (var i = 0; i < fullPattern.length; i++) {';
    $js .= '  if (fullPattern.charAt(i) === "1") {';
    $js .= '    ctx.fillRect(x, 10, barWidth, barHeight);';
    $js .= '  }';
    $js .= '  x += barWidth;';
    $js .= '}';
    
    return $js;
}

/**
 * Generate barcode using external library (JsBarcode)
 * @param string $data Data to encode
 * @param string $format Barcode format (CODE128, EAN13, UPC, etc.)
 * @param array $options Additional options
 * @return string HTML/JS code
 */
function generateBarcodeJsBarcode($data, $format = 'CODE128', $options = []) {
    $defaultOptions = [
        'width' => 2,
        'height' => 80,
        'displayValue' => true,
        'fontSize' => 14,
        'font' => 'monospace',
        'textMargin' => 8,
        'background' => '#ffffff',
        'lineColor' => '#000000'
    ];
    
    $options = array_merge($defaultOptions, $options);
    $barcodeId = 'barcode_' . uniqid();
    
    $html = '<svg id="' . $barcodeId . '"></svg>';
    $html .= '<script>';
    $html .= 'if (typeof JsBarcode !== "undefined") {';
    $html .= '  JsBarcode("#' . $barcodeId . '", "' . addslashes($data) . '", {';
    $html .= '    format: "' . $format . '",';
    foreach ($options as $key => $value) {
        if (is_bool($value)) {
            $html .= '    ' . $key . ': ' . ($value ? 'true' : 'false') . ',';
        } elseif (is_numeric($value)) {
            $html .= '    ' . $key . ': ' . $value . ',';
        } else {
            $html .= '    ' . $key . ': "' . $value . '",';
        }
    }
    $html .= '  });';
    $html .= '}';
    $html .= '</script>';
    
    return $html;
}

/**
 * Generate QR Code
 * @param string $data Data to encode
 * @param int $size QR code size in pixels
 * @return string HTML/JS code
 */
function generateQRCode($data, $size = 150) {
    $qrId = 'qrcode_' . uniqid();
    
    $html = '<div id="' . $qrId . '"></div>';
    $html .= '<script>';
    $html .= 'if (typeof QRCode !== "undefined") {';
    $html .= '  new QRCode(document.getElementById("' . $qrId . '"), {';
    $html .= '    text: "' . addslashes($data) . '",';
    $html .= '    width: ' . $size . ',';
    $html .= '    height: ' . $size . ',';
    $html .= '    colorDark: "#000000",';
    $html .= '    colorLight: "#ffffff",';
    $html .= '    correctLevel: QRCode.CorrectLevel.M';
    $html .= '  });';
    $html .= '}';
    $html .= '</script>';
    
    return $html;
}

/**
 * Generate product barcode data
 * Format: PRD-{product_id}-{category_code}
 * @param int $productId Product ID
 * @param string $categoryCode Category code (optional)
 * @return string Barcode data string
 */
function generateProductBarcodeData($productId, $categoryCode = '') {
    $prefix = 'PRD';
    $category = $categoryCode ? strtoupper(substr($categoryCode, 0, 3)) : '000';
    $id = str_pad($productId, 6, '0', STR_PAD_LEFT);
    return $prefix . $category . $id;
}

/**
 * Generate invoice barcode data
 * Format: INV-{invoice_id}-{year}
 * @param string $invoiceNo Invoice number
 * @return string Barcode data string
 */
function generateInvoiceBarcodeData($invoiceNo) {
    return str_replace('-', '', $invoiceNo);
}

/**
 * Initialize barcode scanner
 * @return string HTML/JS for barcode scanner
 */
function initBarcodeScanner($inputId, $callback = null) {
    $scannerId = 'scanner_' . uniqid();
    
    $html = '<div id="' . $scannerId . '" class="barcode-scanner" style="display:none;">';
    $html .= '<video id="' . $scannerId . '_video" style="width:100%;max-width:400px;"></video>';
    $html .= '<button type="button" class="btn btn-secondary btn-sm" onclick="closeBarcodeScanner(\'' . $scannerId . '\')">Close Scanner</button>';
    $html .= '</div>';
    $html .= '<button type="button" class="btn btn-outline-primary btn-sm" onclick="startBarcodeScanner(\'' . $scannerId . '\', \'' . $inputId . '\')">';
    $html .= '<i class="bi bi-upc-scan"></i> Scan Barcode';
    $html .= '</button>';
    
    return $html;
}

/**
 * Get barcode scanner JavaScript
 * @return string JavaScript code for scanner functionality
 */
function getBarcodeScannerJS() {
    return '
<script>
// Barcode Scanner functionality using QuaggaJS
var activeScanner = null;

function startBarcodeScanner(scannerId, inputId) {
    var scannerDiv = document.getElementById(scannerId);
    var videoElement = document.getElementById(scannerId + "_video");
    
    scannerDiv.style.display = "block";
    
    if (typeof Quagga !== "undefined") {
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: videoElement,
                constraints: {
                    width: 400,
                    height: 300,
                    facingMode: "environment"
                }
            },
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader",
                    "ean_8_reader",
                    "code_39_reader",
                    "upc_reader"
                ]
            }
        }, function(err) {
            if (err) {
                console.error("Scanner init error:", err);
                alert("Could not start barcode scanner. Please ensure camera access is allowed.");
                return;
            }
            Quagga.start();
            activeScanner = scannerId;
        });
        
        Quagga.onDetected(function(result) {
            var code = result.codeResult.code;
            document.getElementById(inputId).value = code;
            closeBarcodeScanner(scannerId);
        });
    } else {
        alert("Barcode scanner library not loaded. Please check your internet connection.");
    }
}

function closeBarcodeScanner(scannerId) {
    if (typeof Quagga !== "undefined") {
        Quagga.stop();
    }
    document.getElementById(scannerId).style.display = "none";
    activeScanner = null;
}
</script>
    ';
}

/**
 * Search product by barcode
 * @param PDO $db Database connection
 * @param string $barcode Barcode data
 * @return array|false Product data or false if not found
 */
function searchProductByBarcode($db, $barcode) {
    // Try to extract product ID from barcode format PRDXXX######
    if (preg_match('/PRD[A-Z0-9]{3}(\d{6})/', $barcode, $matches)) {
        $productId = intval($matches[1]);
        
        $stmt = $db->prepare("
            SELECT p.*, s.quantity as stock_quantity, s.net_weight as stock_weight 
            FROM products p 
            LEFT JOIN stock s ON p.id = s.product_id 
            WHERE p.id = ? AND p.is_active = 1
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }
    
    // Fallback: search by product code if stored
    $stmt = $db->prepare("
        SELECT p.*, s.quantity as stock_quantity, s.net_weight as stock_weight 
        FROM products p 
        LEFT JOIN stock s ON p.id = s.product_id 
        WHERE p.product_code = ? AND p.is_active = 1
    ");
    $stmt->execute([$barcode]);
    return $stmt->fetch();
}
