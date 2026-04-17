<?php
/**
 * GST API Integration Module
 * Supports GSTIN verification and GST return filing
 */

/**
 * GST API Configuration
 */
define('GST_API_BASE_URL', 'https://api.gst.gov.in/v1'); // Production URL
define('GST_API_SANDBOX_URL', 'https://api-sandbox.gst.gov.in/v1'); // Sandbox URL
define('GST_API_MODE', 'sandbox'); // 'sandbox' or 'production'

/**
 * Get GST API base URL based on mode
 * @return string API base URL
 */
function getGstApiBaseUrl() {
    return GST_API_MODE === 'production' ? GST_API_BASE_URL : GST_API_SANDBOX_URL;
}

/**
 * Verify GSTIN (GST Number)
 * Uses public GST API or fallback verification
 * 
 * @param string $gstin GST number to verify
 * @return array Verification result with status and details
 */
function verifyGSTIN($gstin) {
    // Basic format validation first
    if (!validateGSTINFormat($gstin)) {
        return [
            'success' => false,
            'error' => 'Invalid GSTIN format',
            'details' => null
        ];
    }
    
    // In production, you would call the actual GST API
    // For now, we'll simulate the verification
    
    // Check if GSTIN exists in our database first
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM customers WHERE gst_number = ? LIMIT 1");
    $stmt->execute([$gstin]);
    $existingCustomer = $stmt->fetch();
    
    if ($existingCustomer) {
        return [
            'success' => true,
            'verified' => true,
            'source' => 'database',
            'details' => [
                'gstin' => $gstin,
                'business_name' => $existingCustomer['business_name'],
                'address' => $existingCustomer['address_line1'] . ' ' . $existingCustomer['city'],
                'state' => $existingCustomer['state'],
                'status' => 'Active'
            ]
        ];
    }
    
    // Simulate API verification (replace with actual API call)
    $apiResult = simulateGstApiVerification($gstin);
    
    return $apiResult;
}

/**
 * Validate GSTIN format
 * @param string $gstin GST number
 * @return bool True if format is valid
 */
function validateGSTINFormat($gstin) {
    // GSTIN format: 2 digits (state code) + 10 chars (PAN) + 1 char (entity) + 1 char (checksum) + 1 char (Z)
    // Total: 15 characters
    
    if (strlen($gstin) !== 15) {
        return false;
    }
    
    // Check if alphanumeric
    if (!preg_match('/^[0-9A-Z]{15}$/', $gstin)) {
        return false;
    }
    
    // State code validation (01-37)
    $stateCode = substr($gstin, 0, 2);
    if (!preg_match('/^(0[1-9]|[1-3][0-7])$/', $stateCode)) {
        return false;
    }
    
    // PAN format validation (3 letters + 4 digits + 1 letter)
    $panPart = substr($gstin, 2, 10);
    if (!preg_match('/^[A-Z]{3}[0-9]{4}[A-Z]{1}$/', $panPart)) {
        return false;
    }
    
    // Last character should be Z for regular taxpayers
    $lastChar = substr($gstin, -1);
    if ($lastChar !== 'Z' && !preg_match('/[0-9A-Y]/', $lastChar)) {
        return false;
    }
    
    return true;
}

/**
 * Simulate GST API verification
 * In production, replace this with actual API call
 */
function simulateGstApiVerification($gstin) {
    // State codes mapping
    $stateCodes = [
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab',
        '04' => 'Chandigarh', '05' => 'Uttarakhand', '06' => 'Haryana',
        '07' => 'Delhi', '08' => 'Rajasthan', '09' => 'Uttar Pradesh',
        '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram',
        '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam',
        '19' => 'West Bengal', '20' => 'Jharkhand', '21' => 'Odisha',
        '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
        '25' => 'Daman and Diu', '26' => 'Dadra and Nagar Haveli', '27' => 'Maharashtra',
        '28' => 'Andhra Pradesh', '29' => 'Karnataka', '30' => 'Goa',
        '31' => 'Lakshadweep', '32' => 'Kerala', '33' => 'Tamil Nadu',
        '34' => 'Puducherry', '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana',
        '37' => 'Andhra Pradesh (New)'
    ];
    
    $stateCode = substr($gstin, 0, 2);
    $stateName = $stateCodes[$stateCode] ?? 'Unknown State';
    
    // Simulate API delay
    usleep(500000); // 500ms delay
    
    // For demo purposes, we'll return success for valid format GSTINs
    // In production, this would be an actual API response
    return [
        'success' => true,
        'verified' => true,
        'source' => 'api_simulation',
        'details' => [
            'gstin' => $gstin,
            'business_name' => 'Verified Business ' . substr($gstin, -6),
            'address' => 'Sample Address, ' . $stateName,
            'state' => $stateName,
            'status' => 'Active',
            'registration_date' => date('Y-m-d', strtotime('-2 years')),
            'filing_status' => 'Regular'
        ],
        'api_response' => [
            'code' => 200,
            'message' => 'GSTIN verified successfully'
        ]
    ];
}

/**
 * Make actual GST API call
 * @param string $endpoint API endpoint
 * @param array $params Request parameters
 * @param string $method HTTP method
 * @return array API response
 */
function callGstApi($endpoint, $params = [], $method = 'GET') {
    $baseUrl = getGstApiBaseUrl();
    $url = $baseUrl . $endpoint;
    
    // Get API credentials from settings
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    $apiKey = $settings['gst_api_key'] ?? '';
    $apiSecret = $settings['gst_api_secret'] ?? '';
    
    if (empty($apiKey) || empty($apiSecret)) {
        return [
            'success' => false,
            'error' => 'GST API credentials not configured'
        ];
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($apiKey . ':' . $apiSecret),
        'x-api-version: 1.0'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'API connection error: ' . $error
        ];
    }
    
    $data = json_decode($response, true);
    
    return [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'data' => $data
    ];
}

/**
 * Get GST return filing status
 * @param string $gstin GST number
 * @param string $returnType Return type (GSTR1, GSTR3B, etc.)
 * @param string $period Return period (MMYYYY)
 * @return array Filing status
 */
function getGstReturnStatus($gstin, $returnType = 'GSTR3B', $period = null) {
    if (!$period) {
        $period = date('mY'); // Current month
    }
    
    // In production, call actual API
    // For now, return simulated data
    
    return [
        'success' => true,
        'gstin' => $gstin,
        'return_type' => $returnType,
        'period' => $period,
        'status' => 'Filed',
        'filing_date' => date('Y-m-d', strtotime('-5 days')),
        'due_date' => date('Y-m-d', strtotime('+20 days')),
        'mode' => 'Online'
    ];
}

/**
 * Calculate GST breakdown for API
 * @param float $amount Taxable amount
 * @param float $gstRate GST rate (default 3% for jewellery)
 * @param string $customerState Customer state
 * @param string $companyState Company state
 * @return array GST breakdown
 */
function calculateGSTForAPI($amount, $gstRate = 3, $customerState = '', $companyState = '') {
    $gstAmount = ($amount * $gstRate) / 100;
    
    // Check if IGST applies (inter-state)
    $isInterState = $customerState && $companyState && $customerState !== $companyState;
    
    if ($isInterState) {
        // IGST for inter-state
        return [
            'taxable_amount' => $amount,
            'gst_rate' => $gstRate,
            'cgst_amount' => 0,
            'sgst_amount' => 0,
            'igst_amount' => $gstAmount,
            'total_gst' => $gstAmount,
            'total_amount' => $amount + $gstAmount,
            'type' => 'IGST'
        ];
    } else {
        // CGST + SGST for intra-state
        $halfGst = $gstAmount / 2;
        return [
            'taxable_amount' => $amount,
            'gst_rate' => $gstRate,
            'cgst_amount' => $halfGst,
            'sgst_amount' => $halfGst,
            'igst_amount' => 0,
            'total_gst' => $gstAmount,
            'total_amount' => $amount + $gstAmount,
            'type' => 'CGST/SGST'
        ];
    }
}

/**
 * Generate GSTR-1 data for filing
 * @param PDO $db Database connection
 * @param string $period Filing period (MMYYYY)
 * @return array GSTR-1 data
 */
function generateGSTR1Data($db, $period) {
    $year = substr($period, 2, 4);
    $month = substr($period, 0, 2);
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    // Get all invoices for the period
    $stmt = $db->prepare("
        SELECT i.*, c.business_name, c.gst_number as customer_gst, c.state as customer_state
        FROM invoices i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.invoice_date BETWEEN ? AND ?
        ORDER BY i.invoice_date
    ");
    $stmt->execute([$startDate, $endDate]);
    $invoices = $stmt->fetchAll();
    
    $b2b = []; // Business to Business
    $b2cs = []; // Business to Consumer Small
    $b2cl = []; // Business to Consumer Large
    
    foreach ($invoices as $invoice) {
        $invoiceData = [
            'inum' => $invoice['invoice_no'],
            'idt' => $invoice['invoice_date'],
            'val' => $invoice['total_amount'],
            'pos' => getStateCode($invoice['customer_state']),
            'rchrg' => 'N',
            'inv_typ' => 'R',
            'items' => [
                [
                    'num' => 1,
                    'itm_det' => [
                        'txval' => $invoice['taxable_amount'],
                        'rt' => 3,
                        'camt' => $invoice['cgst_amount'],
                        'samt' => $invoice['sgst_amount'],
                        'iamt' => $invoice['igst_amount']
                    ]
                ]
            ]
        ];
        
        if (!empty($invoice['customer_gst'])) {
            // B2B invoice
            $ctin = $invoice['customer_gst'];
            if (!isset($b2b[$ctin])) {
                $b2b[$ctin] = ['ctin' => $ctin, 'inv' => []];
            }
            $b2b[$ctin]['inv'][] = $invoiceData;
        } elseif ($invoice['total_amount'] > 250000) {
            // B2CL invoice (large)
            $b2cl[] = $invoiceData;
        } else {
            // B2CS invoice (small)
            $b2cs[] = $invoiceData;
        }
    }
    
    return [
        'success' => true,
        'period' => $period,
        'gstin' => '', // Will be filled from company settings
        'b2b' => array_values($b2b),
        'b2cs' => $b2cs,
        'b2cl' => $b2cl,
        'total_invoices' => count($invoices),
        'total_taxable_value' => array_sum(array_column($invoices, 'taxable_amount')),
        'total_igst' => array_sum(array_column($invoices, 'igst_amount')),
        'total_cgst' => array_sum(array_column($invoices, 'cgst_amount')),
        'total_sgst' => array_sum(array_column($invoices, 'sgst_amount'))
    ];
}

/**
 * Get state code from state name
 * @param string $stateName State name
 * @return string State code (2 digits)
 */
function getStateCode($stateName) {
    $stateCodes = [
        'Jammu and Kashmir' => '01', 'Himachal Pradesh' => '02', 'Punjab' => '03',
        'Chandigarh' => '04', 'Uttarakhand' => '05', 'Haryana' => '06',
        'Delhi' => '07', 'Rajasthan' => '08', 'Uttar Pradesh' => '09',
        'Bihar' => '10', 'Sikkim' => '11', 'Arunachal Pradesh' => '12',
        'Nagaland' => '13', 'Manipur' => '14', 'Mizoram' => '15',
        'Tripura' => '16', 'Meghalaya' => '17', 'Assam' => '18',
        'West Bengal' => '19', 'Jharkhand' => '20', 'Odisha' => '21',
        'Chhattisgarh' => '22', 'Madhya Pradesh' => '23', 'Gujarat' => '24',
        'Daman and Diu' => '25', 'Dadra and Nagar Haveli' => '26', 'Maharashtra' => '27',
        'Andhra Pradesh' => '28', 'Karnataka' => '29', 'Goa' => '30',
        'Lakshadweep' => '31', 'Kerala' => '32', 'Tamil Nadu' => '33',
        'Puducherry' => '34', 'Andaman and Nicobar Islands' => '35', 'Telangana' => '36'
    ];
    
    return $stateCodes[$stateName] ?? '00';
}

/**
 * Get GSTIN verification widget HTML
 * @param string $inputId Input field ID for GSTIN
 * @param array $targetFields Fields to auto-fill with verified data
 * @return string HTML/JS code
 */
function getGSTINVerificationWidget($inputId, $targetFields = []) {
    $widgetId = 'gst_verify_' . uniqid();
    
    $html = '<div class="input-group">';
    $html .= '<input type="text" class="form-control" id="' . $inputId . '" name="' . $inputId . '" placeholder="Enter GSTIN" maxlength="15">';
    $html .= '<button type="button" class="btn btn-outline-primary" onclick="verifyGSTIN(\'' . $inputId . '\', \'' . $widgetId . '\')">';
    $html .= '<i class="bi bi-check-circle"></i> Verify';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '<div id="' . $widgetId . '_result" class="mt-2"></div>';
    
    $html .= '<script>';
    $html .= 'function verifyGSTIN(inputId, widgetId) {';
    $html .= '  var gstin = document.getElementById(inputId).value.trim();';
    $html .= '  var resultDiv = document.getElementById(widgetId + "_result");';
    $html .= '  if (!gstin) { resultDiv.innerHTML = \'<span class="text-danger">Please enter GSTIN</span>\'; return; }';
    $html .= '  resultDiv.innerHTML = \'<span class="text-info"><i class="bi bi-hourglass-split"></i> Verifying...</span>\';';
    $html .= '  fetch("' . BASE_URL . '/ajax/verify_gstin.php?gstin=" + encodeURIComponent(gstin))';
    $html .= '    .then(response => response.json())';
    $html .= '    .then(data => {';
    $html .= '      if (data.success && data.verified) {';
    $html .= '        resultDiv.innerHTML = \'<span class="text-success"><i class="bi bi-check-circle-fill"></i> Verified: \' + data.details.business_name + \'</span>\';';
    foreach ($targetFields as $field => $dataKey) {
        $html .= '        if (document.getElementById("' . $field . '") && data.details.' . $dataKey . ') {';
        $html .= '          document.getElementById("' . $field . '").value = data.details.' . $dataKey . ';';
        $html .= '        }';
    }
    $html .= '      } else {';
    $html .= '        resultDiv.innerHTML = \'<span class="text-danger"><i class="bi bi-x-circle-fill"></i> \' + (data.error || "Verification failed") + \'</span>\';';
    $html .= '      }';
    $html .= '    })';
    $html .= '    .catch(error => {';
    $html .= '      resultDiv.innerHTML = \'<span class="text-danger">Error: \' + error.message + \'</span>\';';
    $html .= '    });';
    $html .= '}';
    $html .= '</script>';
    
    return $html;
}
