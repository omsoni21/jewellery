<?php
/**
 * PDF Invoice Generator
 * Uses simple HTML output that can be printed to PDF
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$invoiceId) {
    header("Location: " . BASE_URL . "/billing/list.php");
    exit();
}

$db = getDBConnection();

// Get invoice details
$stmt = $db->prepare("SELECT i.*, c.business_name, c.contact_person, c.address_line1, c.address_line2, c.city, c.state, c.pincode, c.gst_number as customer_gst, c.phone, c.email FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.id = ?");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: " . BASE_URL . "/billing/list.php");
    exit();
}

// Get invoice items
$stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoiceId]);
$items = $stmt->fetchAll();

// Get company settings
$stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
$company = $stmt->fetch();

// Set headers for PDF-like outputs
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_no']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            background: white;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #d4af37;
            padding: 30px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 3px solid #d4af37;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .company-info h2 {
            color: #d4af37;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .company-info p {
            color: #666;
            font-size: 11px;
            line-height: 1.6;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h1 {
            color: #1a1a2e;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .invoice-title .invoice-no {
            font-size: 14px;
            color: #666;
            font-weight: bold;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .bill-to, .invoice-meta {
            width: 48%;
        }
        
        .bill-to h3, .invoice-meta h3 {
            background: #d4af37;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .bill-to p {
            line-height: 1.8;
        }
        
        .bill-to .customer-name {
            font-weight: bold;
            font-size: 14px;
            color: #1a1a2e;
        }
        
        .invoice-meta table {
            width: 100%;
        }
        
        .invoice-meta td {
            padding: 5px 0;
        }
        
        .invoice-meta td:first-child {
            font-weight: bold;
            width: 40%;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        
        .items-table th {
            background: #1a1a2e;
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
        }
        
        .items-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        
        .items-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .items-table th:nth-child(1), .items-table td:nth-child(1) { width: 5%; }
        .items-table th:nth-child(2), .items-table td:nth-child(2) { width: 25%; text-align: left; }
        .items-table th:nth-child(3), .items-table td:nth-child(3) { width: 12%; }
        .items-table th:nth-child(4), .items-table td:nth-child(4) { width: 8%; }
        .items-table th:nth-child(5), .items-table td:nth-child(5) { width: 10%; }
        .items-table th:nth-child(6), .items-table td:nth-child(6) { width: 10%; }
        .items-table th:nth-child(7), .items-table td:nth-child(7) { width: 12%; }
        .items-table th:nth-child(8), .items-table td:nth-child(8) { width: 18%; text-align: right; }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        
        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
        }
        
        .totals-table td:first-child {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-table .grand-total {
            background: #d4af37;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }
        
        .bank-details {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 30px;
            background: #f9f9f9;
        }
        
        .bank-details h4 {
            color: #1a1a2e;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .bank-details p {
            font-size: 11px;
            line-height: 1.8;
        }
        
        .terms {
            margin-bottom: 30px;
        }
        
        .terms h4 {
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        
        .terms ul {
            margin-left: 20px;
            font-size: 11px;
            line-height: 1.8;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 11px;
        }
        
        .gst-notice {
            text-align: center;
            margin-top: 30px;
            padding: 10px;
            background: #f0f0f0;
            font-size: 11px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #d4af37;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        
        .print-button:hover {
            background: #b8941f;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            
            body {
                padding: 0;
            }
            
            .invoice-container {
                border: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        <i class="bi bi-printer"></i> Print / Save as PDF
    </button>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-info">
                <h2><?php echo $company ? htmlspecialchars($company['company_name']) : 'Your Company Name'; ?></h2>
                <p>
                    <?php if ($company): ?>
                        <?php echo htmlspecialchars($company['address_line1']); ?><br>
                        <?php if ($company['address_line2']) echo htmlspecialchars($company['address_line2']) . '<br>'; ?>
                        <?php echo htmlspecialchars($company['city'] . ', ' . $company['state'] . ' - ' . $company['pincode']); ?><br>
                        Phone: <?php echo htmlspecialchars($company['phone']); ?><br>
                        Email: <?php echo htmlspecialchars($company['email']); ?><br>
                        GST: <?php echo htmlspecialchars($company['gst_number']); ?>
                    <?php else: ?>
                        Company Address<br>
                        Phone: +91 XXXXX XXXXX<br>
                        Email: company@example.com
                    <?php endif; ?>
                </p>
            </div>
            <div class="invoice-title">
                <h1>TAX INVOICE</h1>
                <div class="invoice-no">Invoice No: <?php echo htmlspecialchars($invoice['invoice_no']); ?></div>
            </div>
        </div>
        
        <div class="invoice-details">
            <div class="bill-to">
                <h3>BILL TO</h3>
                <p>
                    <span class="customer-name"><?php echo htmlspecialchars($invoice['business_name']); ?></span><br>
                    Attn: <?php echo htmlspecialchars($invoice['contact_person']); ?><br>
                    <?php echo htmlspecialchars($invoice['address_line1']); ?><br>
                    <?php if ($invoice['address_line2']) echo htmlspecialchars($invoice['address_line2']) . '<br>'; ?>
                    <?php echo htmlspecialchars($invoice['city'] . ', ' . $invoice['state'] . ' - ' . $invoice['pincode']); ?><br>
                    Phone: <?php echo htmlspecialchars($invoice['phone']); ?><br>
                    GST: <?php echo htmlspecialchars($invoice['customer_gst'] ?: 'N/A'); ?>
                </p>
            </div>
            <div class="invoice-meta">
                <h3>INVOICE DETAILS</h3>
                <table>
                    <tr>
                        <td>Invoice Date:</td>
                        <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                    </tr>
                    <tr>
                        <td>Due Date:</td>
                        <td><?php echo formatDate($invoice['due_date']); ?></td>
                    </tr>
                    <tr>
                        <td>Payment Status:</td>
                        <td><?php echo ucfirst($invoice['payment_status']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Metal</th>
                    <th>Qty</th>
                    <th>Net Wt</th>
                    <th>Wastage</th>
                    <th>Rate/g</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td style="text-align: left;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo ucfirst($item['metal_type']) . ' ' . $item['purity']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['net_weight'], 2) . 'g'; ?></td>
                    <td><?php echo number_format($item['wastage_percent'], 1) . '%'; ?></td>
                    <td>₹<?php echo number_format($item['rate_per_gram'], 2); ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($item['item_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td><?php echo formatCurrency($invoice['subtotal']); ?></td>
                </tr>
                <tr>
                    <td>CGST (1.5%):</td>
                    <td><?php echo formatCurrency($invoice['cgst_amount']); ?></td>
                </tr>
                <tr>
                    <td>SGST (1.5%):</td>
                    <td><?php echo formatCurrency($invoice['sgst_amount']); ?></td>
                </tr>
                <tr class="grand-total">
                    <td>Grand Total:</td>
                    <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                </tr>
            </table>
        </div>
        
        <?php if ($company && ($company['bank_name'] || $company['bank_account_no'])): ?>
        <div class="bank-details">
            <h4>BANK DETAILS</h4>
            <p>
                <strong>Bank Name:</strong> <?php echo htmlspecialchars($company['bank_name']); ?><br>
                <strong>Account No:</strong> <?php echo htmlspecialchars($company['bank_account_no']); ?><br>
                <strong>IFSC Code:</strong> <?php echo htmlspecialchars($company['bank_ifsc']); ?><br>
                <strong>Branch:</strong> <?php echo htmlspecialchars($company['bank_branch']); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="terms">
            <h4>TERMS & CONDITIONS</h4>
            <ul>
                <li>Payment is due within 30 days from the invoice date.</li>
                <li>Interest will be charged at 18% per annum on overdue amounts.</li>
                <li>Goods once sold cannot be returned or exchanged.</li>
                <li>All disputes are subject to <?php echo $company ? htmlspecialchars($company['city']) : 'local'; ?> jurisdiction.</li>
            </ul>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Customer Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
        
        <div class="gst-notice">
            <strong>Thank you for your business!</strong><br>
            This is a computer-generated invoice and does not require a physical signature.
        </div>
    </div>
</body>
</html>
