<?php
/**
 * Sample Data Generator
 * Run this once to populate the database with test data
 */

require_once __DIR__ . '/includes/functions.php';
requireAuth();

// Only allow admin to run this
if (!hasRole(ROLE_ADMIN)) {
    die('Access denied. Only admin can add sample data.');
}

$db = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // 1. Add Sample Customers (15 total)
        $customers = [
            ['business_name' => 'Sharma Jewellers', 'contact_person' => 'Rajesh Sharma', 'phone' => '9876543210', 'email' => 'rajesh@sharmajewellers.com', 'gst_number' => '27AABCS1234A1Z5', 'address_line1' => '123 MG Road', 'address_line2' => 'Near Railway Station', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400001', 'opening_balance' => 50000],
            ['business_name' => 'Gold Star Traders', 'contact_person' => 'Priya Patel', 'phone' => '9876543211', 'email' => 'priya@goldstar.com', 'gst_number' => '24AABCP5678B1Z2', 'address_line1' => '456 Ring Road', 'address_line2' => 'Opposite City Mall', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'pincode' => '380001', 'opening_balance' => 75000],
            ['business_name' => 'Diamond House', 'contact_person' => 'Kumar Reddy', 'phone' => '9876543212', 'email' => 'kumar@diamondhouse.com', 'gst_number' => '36AABCD9012C1Z8', 'address_line1' => '789 Jubilee Hills', 'address_line2' => 'Road No. 36', 'city' => 'Hyderabad', 'state' => 'Telangana', 'pincode' => '500033', 'opening_balance' => 100000],
            ['business_name' => 'Silver Palace', 'contact_person' => 'Anita Gupta', 'phone' => '9876543213', 'email' => 'anita@silverpalace.com', 'gst_number' => '07AABCE3456D1Z1', 'address_line1' => '321 Chandni Chowk', 'address_line2' => 'Main Market', 'city' => 'Delhi', 'state' => 'Delhi', 'pincode' => '110006', 'opening_balance' => 25000],
            ['business_name' => 'Royal Ornaments', 'contact_person' => 'Suresh Kumar', 'phone' => '9876543214', 'email' => 'suresh@royalornaments.com', 'gst_number' => '29AABCF7890E1Z3', 'address_line1' => '555 Commercial Street', 'address_line2' => 'Shopping Complex', 'city' => 'Bangalore', 'state' => 'Karnataka', 'pincode' => '560001', 'opening_balance' => 80000],
            ['business_name' => 'Mumbai Gold Hub', 'contact_person' => 'Amit Shah', 'phone' => '9876543215', 'email' => 'amit@mumbaigold.com', 'gst_number' => '27AABCG1234H1Z1', 'address_line1' => '88 Zaveri Bazaar', 'address_line2' => 'Kalbadevi', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'pincode' => '400002', 'opening_balance' => 150000],
            ['business_name' => 'Chennai Jewels', 'contact_person' => 'Lakshmi Narayan', 'phone' => '9876543216', 'email' => 'lakshmi@chennaijewels.com', 'gst_number' => '33AABCJ5678K1L1', 'address_line1' => '42 T Nagar', 'address_line2' => 'Near Temple', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'pincode' => '600017', 'opening_balance' => 95000],
            ['business_name' => 'Jaipur Gems', 'contact_person' => 'Vikram Singh', 'phone' => '9876543217', 'email' => 'vikram@jaipurgems.com', 'gst_number' => '08AABCM9012N1P1', 'address_line1' => '77 Johari Bazaar', 'address_line2' => 'Pink City', 'city' => 'Jaipur', 'state' => 'Rajasthan', 'pincode' => '302003', 'opening_balance' => 120000],
            ['business_name' => 'Kolkata Bullion', 'contact_person' => 'Rahul Banerjee', 'phone' => '9876543218', 'email' => 'rahul@kolkatabullion.com', 'gst_number' => '19AABCB3456C1D1', 'address_line1' => '15 Bow Bazaar', 'address_line2' => 'Central Kolkata', 'city' => 'Kolkata', 'state' => 'West Bengal', 'pincode' => '700012', 'opening_balance' => 180000],
            ['business_name' => 'Pune Precious', 'contact_person' => 'Neha Joshi', 'phone' => '9876543219', 'email' => 'neha@puneprecious.com', 'gst_number' => '27AABCP7890Q1R1', 'address_line1' => '33 Laxmi Road', 'address_line2' => 'Pune Camp', 'city' => 'Pune', 'state' => 'Maharashtra', 'pincode' => '411001', 'opening_balance' => 65000],
            ['business_name' => 'Surat Diamond', 'contact_person' => 'Mahesh Patel', 'phone' => '9876543220', 'email' => 'mahesh@suratdiamond.com', 'gst_number' => '24AABCS1234T1U1', 'address_line1' => '101 Varachha Road', 'address_line2' => 'Diamond Market', 'city' => 'Surat', 'state' => 'Gujarat', 'pincode' => '395006', 'opening_balance' => 250000],
            ['business_name' => 'Lucknow Jewellers', 'contact_person' => 'Fatima Khan', 'phone' => '9876543221', 'email' => 'fatima@lucknowjewellers.com', 'gst_number' => '09AABCL5678M1N1', 'address_line1' => '22 Hazratganj', 'address_line2' => 'Near GPO', 'city' => 'Lucknow', 'state' => 'Uttar Pradesh', 'pincode' => '226001', 'opening_balance' => 45000],
            ['business_name' => 'Indore Gold Center', 'contact_person' => 'Sanjay Verma', 'phone' => '9876543222', 'email' => 'sanjay@indoregold.com', 'gst_number' => '23AABCI9012J1K1', 'address_line1' => '66 MG Road', 'address_line2' => 'Sarafa Bazaar', 'city' => 'Indore', 'state' => 'Madhya Pradesh', 'pincode' => '452002', 'opening_balance' => 85000],
            ['business_name' => 'Nagpur Silver House', 'contact_person' => 'Pooja Sharma', 'phone' => '9876543223', 'email' => 'pooja@nagpursilver.com', 'gst_number' => '27AABCN3456O1P1', 'address_line1' => '44 Itwari', 'address_line2' => 'Main Road', 'city' => 'Nagpur', 'state' => 'Maharashtra', 'pincode' => '440002', 'opening_balance' => 35000],
            ['business_name' => 'Kochi Gold Palace', 'contact_person' => 'Thomas Mathew', 'phone' => '9876543224', 'email' => 'thomas@kochigold.com', 'gst_number' => '32AABCK7890L1M1', 'address_line1' => '55 MG Road', 'address_line2' => 'Ernakulam', 'city' => 'Kochi', 'state' => 'Kerala', 'pincode' => '682011', 'opening_balance' => 110000],
        ];
        
        $stmt = $db->prepare("INSERT INTO customers (business_name, contact_person, phone, email, gst_number, address_line1, address_line2, city, state, pincode, opening_balance, current_balance, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        
        foreach ($customers as $customer) {
            $stmt->execute([
                $customer['business_name'],
                $customer['contact_person'],
                $customer['phone'],
                $customer['email'],
                $customer['gst_number'],
                $customer['address_line1'],
                $customer['address_line2'],
                $customer['city'],
                $customer['state'],
                $customer['pincode'],
                $customer['opening_balance'],
                $customer['opening_balance']
            ]);
        }
        
        // 2. Add Today's Metal Rates (skip if already exist)
        $today = date('Y-m-d');
        $rates = [
            ['metal_type' => 'gold', 'purity' => '24K', 'rate_per_gram' => 6250.00],
            ['metal_type' => 'gold', 'purity' => '22K', 'rate_per_gram' => 5730.00],
            ['metal_type' => 'gold', 'purity' => '18K', 'rate_per_gram' => 4687.50],
            ['metal_type' => 'silver', 'purity' => '999', 'rate_per_gram' => 72.50],
            ['metal_type' => 'silver', 'purity' => '925', 'rate_per_gram' => 67.00],
        ];
        
        $stmt = $db->prepare("INSERT INTO metal_rates (metal_type, purity, rate_per_gram, rate_date, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        foreach ($rates as $rate) {
            try {
                $stmt->execute([
                    $rate['metal_type'],
                    $rate['purity'],
                    $rate['rate_per_gram'],
                    $today,
                    $_SESSION['user_id']
                ]);
            } catch (PDOException $e) {
                // Rate already exists for today, skip
                if ($e->getCode() == 23000) {
                    continue;
                }
                throw $e;
            }
        }
        
        // 3. Add Sample Products (12 total)
        $products = [
            ['name' => 'Gold Chain 22K', 'description' => '22 Karat Gold Chain', 'metal_type' => 'gold', 'purity' => '22K'],
            ['name' => 'Gold Ring 18K', 'description' => '18 Karat Gold Ring with design', 'metal_type' => 'gold', 'purity' => '18K'],
            ['name' => 'Silver Bracelet 925', 'description' => '925 Sterling Silver Bracelet', 'metal_type' => 'silver', 'purity' => '925'],
            ['name' => 'Gold Necklace Set', 'description' => '24K Gold Necklace with earrings', 'metal_type' => 'gold', 'purity' => '24K'],
            ['name' => 'Silver Anklets', 'description' => 'Traditional Silver Anklets', 'metal_type' => 'silver', 'purity' => '999'],
            ['name' => 'Gold Bangles 22K', 'description' => 'Traditional 22K Gold Bangles Pair', 'metal_type' => 'gold', 'purity' => '22K'],
            ['name' => 'Gold Earrings 18K', 'description' => 'Designer 18K Gold Earrings', 'metal_type' => 'gold', 'purity' => '18K'],
            ['name' => 'Silver Necklace 925', 'description' => '925 Silver Necklace with Pendant', 'metal_type' => 'silver', 'purity' => '925'],
            ['name' => 'Gold Mangalsutra 22K', 'description' => 'Traditional 22K Gold Mangalsutra', 'metal_type' => 'gold', 'purity' => '22K'],
            ['name' => 'Silver Toe Rings', 'description' => '925 Sterling Silver Toe Rings Pair', 'metal_type' => 'silver', 'purity' => '925'],
            ['name' => 'Gold Pendant 18K', 'description' => '18K Gold Pendant with Diamond', 'metal_type' => 'gold', 'purity' => '18K'],
            ['name' => 'Silver Coins 999', 'description' => 'Pure 999 Silver Coins', 'metal_type' => 'silver', 'purity' => '999'],
        ];
        
        $stmt = $db->prepare("INSERT INTO products (name, description, metal_type, purity, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
        
        foreach ($products as $product) {
            $stmt->execute([
                $product['name'],
                $product['description'],
                $product['metal_type'],
                $product['purity']
            ]);
        }
        
        // 4. Add Stock for Products
        $productIds = [];
        $result = $db->query("SELECT id FROM products ORDER BY id");
        while ($row = $result->fetch()) {
            $productIds[] = $row['id'];
        }
        
        $stockData = [
            ['product_id' => $productIds[0], 'quantity' => 15, 'gross_weight' => 450.00, 'net_weight' => 445.50],
            ['product_id' => $productIds[1], 'quantity' => 25, 'gross_weight' => 125.00, 'net_weight' => 123.75],
            ['product_id' => $productIds[2], 'quantity' => 30, 'gross_weight' => 900.00, 'net_weight' => 891.00],
            ['product_id' => $productIds[3], 'quantity' => 8, 'gross_weight' => 240.00, 'net_weight' => 238.40],
            ['product_id' => $productIds[4], 'quantity' => 20, 'gross_weight' => 600.00, 'net_weight' => 594.00],
            ['product_id' => $productIds[5], 'quantity' => 12, 'gross_weight' => 360.00, 'net_weight' => 356.40],
            ['product_id' => $productIds[6], 'quantity' => 40, 'gross_weight' => 80.00, 'net_weight' => 79.20],
            ['product_id' => $productIds[7], 'quantity' => 18, 'gross_weight' => 540.00, 'net_weight' => 534.60],
            ['product_id' => $productIds[8], 'quantity' => 10, 'gross_weight' => 150.00, 'net_weight' => 148.50],
            ['product_id' => $productIds[9], 'quantity' => 50, 'gross_weight' => 100.00, 'net_weight' => 99.00],
            ['product_id' => $productIds[10], 'quantity' => 35, 'gross_weight' => 70.00, 'net_weight' => 69.30],
            ['product_id' => $productIds[11], 'quantity' => 100, 'gross_weight' => 1000.00, 'net_weight' => 990.00],
        ];
        
        $stmt = $db->prepare("INSERT INTO stock (product_id, quantity, gross_weight, net_weight, last_updated) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($stockData as $stock) {
            try {
                $stmt->execute([
                    $stock['product_id'],
                    $stock['quantity'],
                    $stock['gross_weight'],
                    $stock['net_weight']
                ]);
            } catch (PDOException $e) {
                // Stock already exists for this product, skip
                if ($e->getCode() == 23000) {
                    continue;
                }
                throw $e;
            }
        }
        
        // 5. Add Sample Invoices (15 total)
        $customerIds = [];
        $result = $db->query("SELECT id FROM customers ORDER BY id");
        while ($row = $result->fetch()) {
            $customerIds[] = $row['id'];
        }
        
        // Get the next invoice number
        $result = $db->query("SELECT invoice_no FROM invoices ORDER BY id DESC LIMIT 1");
        $lastInvoice = $result->fetch();
        if ($lastInvoice) {
            // Extract number from INV-2024-XXXX format
            $parts = explode('-', $lastInvoice['invoice_no']);
            $lastNum = intval(end($parts));
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }
        
        $invoices = [
            [
                'customer_id' => $customerIds[0],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-45 days')),
                'due_date' => date('Y-m-d', strtotime('-15 days')),
                'items' => [
                    ['product_id' => $productIds[0], 'quantity' => 2, 'gross_weight' => 60.00, 'net_weight' => 59.40, 'wastage_percent' => 8, 'making_charge' => 2500, 'rate_per_gram' => 5730],
                ],
                'subtotal' => 365466.20,
                'taxable_amount' => 365466.20,
                'cgst_amount' => 5481.99,
                'sgst_amount' => 5481.99,
                'total_amount' => 376430.18,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[1],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-38 days')),
                'due_date' => date('Y-m-d', strtotime('-8 days')),
                'items' => [
                    ['product_id' => $productIds[1], 'quantity' => 5, 'gross_weight' => 25.00, 'net_weight' => 24.75, 'wastage_percent' => 10, 'making_charge' => 1500, 'rate_per_gram' => 4687.50],
                ],
                'subtotal' => 136237.50,
                'taxable_amount' => 136237.50,
                'cgst_amount' => 2043.56,
                'sgst_amount' => 2043.56,
                'total_amount' => 140324.62,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[2],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-32 days')),
                'due_date' => date('Y-m-d', strtotime('-2 days')),
                'items' => [
                    ['product_id' => $productIds[2], 'quantity' => 10, 'gross_weight' => 300.00, 'net_weight' => 297.00, 'wastage_percent' => 5, 'making_charge' => 800, 'rate_per_gram' => 67],
                ],
                'subtotal' => 27899.00,
                'taxable_amount' => 27899.00,
                'cgst_amount' => 418.49,
                'sgst_amount' => 418.49,
                'total_amount' => 28735.98,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[3],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-28 days')),
                'due_date' => date('Y-m-d', strtotime('+2 days')),
                'items' => [
                    ['product_id' => $productIds[3], 'quantity' => 3, 'gross_weight' => 90.00, 'net_weight' => 89.10, 'wastage_percent' => 6, 'making_charge' => 3500, 'rate_per_gram' => 6250],
                ],
                'subtotal' => 591937.50,
                'taxable_amount' => 591937.50,
                'cgst_amount' => 8879.06,
                'sgst_amount' => 8879.06,
                'total_amount' => 609695.62,
                'balance_amount' => 609695.62,
                'payment_status' => 'pending'
            ],
            [
                'customer_id' => $customerIds[4],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-25 days')),
                'due_date' => date('Y-m-d', strtotime('+5 days')),
                'items' => [
                    ['product_id' => $productIds[4], 'quantity' => 15, 'gross_weight' => 450.00, 'net_weight' => 445.50, 'wastage_percent' => 4, 'making_charge' => 600, 'rate_per_gram' => 72.50],
                ],
                'subtotal' => 41623.50,
                'taxable_amount' => 41623.50,
                'cgst_amount' => 624.35,
                'sgst_amount' => 624.35,
                'total_amount' => 42872.20,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[5],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-22 days')),
                'due_date' => date('Y-m-d', strtotime('+8 days')),
                'items' => [
                    ['product_id' => $productIds[5], 'quantity' => 4, 'gross_weight' => 120.00, 'net_weight' => 118.80, 'wastage_percent' => 8, 'making_charge' => 2800, 'rate_per_gram' => 5730],
                ],
                'subtotal' => 731933.20,
                'taxable_amount' => 731933.20,
                'cgst_amount' => 10979.00,
                'sgst_amount' => 10979.00,
                'total_amount' => 753891.20,
                'balance_amount' => 253891.20,
                'payment_status' => 'partial'
            ],
            [
                'customer_id' => $customerIds[6],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-18 days')),
                'due_date' => date('Y-m-d', strtotime('+12 days')),
                'items' => [
                    ['product_id' => $productIds[6], 'quantity' => 8, 'gross_weight' => 16.00, 'net_weight' => 15.84, 'wastage_percent' => 12, 'making_charge' => 1800, 'rate_per_gram' => 4687.50],
                ],
                'subtotal' => 98940.00,
                'taxable_amount' => 98940.00,
                'cgst_amount' => 1484.10,
                'sgst_amount' => 1484.10,
                'total_amount' => 101908.20,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[7],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-15 days')),
                'due_date' => date('Y-m-d', strtotime('+15 days')),
                'items' => [
                    ['product_id' => $productIds[7], 'quantity' => 6, 'gross_weight' => 180.00, 'net_weight' => 178.20, 'wastage_percent' => 5, 'making_charge' => 950, 'rate_per_gram' => 67],
                ],
                'subtotal' => 13729.40,
                'taxable_amount' => 13729.40,
                'cgst_amount' => 205.94,
                'sgst_amount' => 205.94,
                'total_amount' => 14141.28,
                'balance_amount' => 14141.28,
                'payment_status' => 'pending'
            ],
            [
                'customer_id' => $customerIds[8],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-12 days')),
                'due_date' => date('Y-m-d', strtotime('+18 days')),
                'items' => [
                    ['product_id' => $productIds[8], 'quantity' => 5, 'gross_weight' => 75.00, 'net_weight' => 74.25, 'wastage_percent' => 7, 'making_charge' => 3200, 'rate_per_gram' => 5730],
                ],
                'subtotal' => 456233.25,
                'taxable_amount' => 456233.25,
                'cgst_amount' => 6843.50,
                'sgst_amount' => 6843.50,
                'total_amount' => 469920.25,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[9],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-10 days')),
                'due_date' => date('Y-m-d', strtotime('+20 days')),
                'items' => [
                    ['product_id' => $productIds[9], 'quantity' => 25, 'gross_weight' => 50.00, 'net_weight' => 49.50, 'wastage_percent' => 3, 'making_charge' => 450, 'rate_per_gram' => 67],
                ],
                'subtotal' => 3763.50,
                'taxable_amount' => 3763.50,
                'cgst_amount' => 56.45,
                'sgst_amount' => 56.45,
                'total_amount' => 3876.40,
                'balance_amount' => 3876.40,
                'payment_status' => 'pending'
            ],
            [
                'customer_id' => $customerIds[10],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-7 days')),
                'due_date' => date('Y-m-d', strtotime('+23 days')),
                'items' => [
                    ['product_id' => $productIds[10], 'quantity' => 12, 'gross_weight' => 24.00, 'net_weight' => 23.76, 'wastage_percent' => 10, 'making_charge' => 2200, 'rate_per_gram' => 4687.50],
                ],
                'subtotal' => 135910.00,
                'taxable_amount' => 135910.00,
                'cgst_amount' => 2038.65,
                'sgst_amount' => 2038.65,
                'total_amount' => 139987.30,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[11],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-5 days')),
                'due_date' => date('Y-m-d', strtotime('+25 days')),
                'items' => [
                    ['product_id' => $productIds[11], 'quantity' => 50, 'gross_weight' => 500.00, 'net_weight' => 495.00, 'wastage_percent' => 2, 'making_charge' => 150, 'rate_per_gram' => 72.50],
                ],
                'subtotal' => 42975.00,
                'taxable_amount' => 42975.00,
                'cgst_amount' => 644.63,
                'sgst_amount' => 644.63,
                'total_amount' => 44264.26,
                'balance_amount' => 44264.26,
                'payment_status' => 'pending'
            ],
            [
                'customer_id' => $customerIds[12],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-3 days')),
                'due_date' => date('Y-m-d', strtotime('+27 days')),
                'items' => [
                    ['product_id' => $productIds[0], 'quantity' => 3, 'gross_weight' => 90.00, 'net_weight' => 89.10, 'wastage_percent' => 8, 'making_charge' => 2500, 'rate_per_gram' => 5730],
                ],
                'subtotal' => 548199.30,
                'taxable_amount' => 548199.30,
                'cgst_amount' => 8222.99,
                'sgst_amount' => 8222.99,
                'total_amount' => 564645.28,
                'balance_amount' => 564645.28,
                'payment_status' => 'pending'
            ],
            [
                'customer_id' => $customerIds[13],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-2 days')),
                'due_date' => date('Y-m-d', strtotime('+28 days')),
                'items' => [
                    ['product_id' => $productIds[2], 'quantity' => 20, 'gross_weight' => 600.00, 'net_weight' => 594.00, 'wastage_percent' => 5, 'making_charge' => 800, 'rate_per_gram' => 67],
                ],
                'subtotal' => 55798.00,
                'taxable_amount' => 55798.00,
                'cgst_amount' => 836.97,
                'sgst_amount' => 836.97,
                'total_amount' => 57471.94,
                'balance_amount' => 0,
                'payment_status' => 'paid'
            ],
            [
                'customer_id' => $customerIds[14],
                'invoice_no' => 'INV-2024-' . sprintf('%04d', $nextNum++),
                'invoice_date' => date('Y-m-d', strtotime('-1 day')),
                'due_date' => date('Y-m-d', strtotime('+29 days')),
                'items' => [
                    ['product_id' => $productIds[5], 'quantity' => 6, 'gross_weight' => 180.00, 'net_weight' => 178.20, 'wastage_percent' => 8, 'making_charge' => 2800, 'rate_per_gram' => 5730],
                ],
                'subtotal' => 1097899.80,
                'taxable_amount' => 1097899.80,
                'cgst_amount' => 16468.50,
                'sgst_amount' => 16468.50,
                'total_amount' => 1130836.80,
                'balance_amount' => 630836.80,
                'payment_status' => 'partial'
            ],
        ];
        
        $stmtInvoice = $db->prepare("INSERT INTO invoices (customer_id, invoice_no, invoice_date, due_date, subtotal, taxable_amount, cgst_amount, sgst_amount, total_amount, balance_amount, payment_status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmtItem = $db->prepare("INSERT INTO invoice_items (invoice_id, product_id, item_name, metal_type, purity, quantity, gross_weight, net_weight, wastage_percent, wastage_weight, total_weight, rate_per_gram, metal_amount, making_charge_amount, item_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($invoices as $invoice) {
            $stmtInvoice->execute([
                $invoice['customer_id'],
                $invoice['invoice_no'],
                $invoice['invoice_date'],
                $invoice['due_date'],
                $invoice['subtotal'],
                $invoice['taxable_amount'],
                $invoice['cgst_amount'],
                $invoice['sgst_amount'],
                $invoice['total_amount'],
                $invoice['balance_amount'],
                $invoice['payment_status'],
                $_SESSION['user_id']
            ]);
            
            $invoiceId = $db->lastInsertId();
            
            foreach ($invoice['items'] as $item) {
                // Get product details
                $stmtProd = $db->prepare("SELECT name, metal_type, purity FROM products WHERE id = ?");
                $stmtProd->execute([$item['product_id']]);
                $product = $stmtProd->fetch();
                
                $wastageWeight = $item['net_weight'] * ($item['wastage_percent'] / 100);
                $totalWeight = $item['net_weight'] + $wastageWeight;
                $metalAmount = $totalWeight * $item['rate_per_gram'];
                $makingChargeAmount = $item['making_charge'];
                $itemTotal = $metalAmount + $makingChargeAmount;
                
                $stmtItem->execute([
                    $invoiceId,
                    $item['product_id'],
                    $product['name'],
                    $product['metal_type'],
                    $product['purity'],
                    $item['quantity'],
                    $item['gross_weight'],
                    $item['net_weight'],
                    $item['wastage_percent'],
                    $wastageWeight,
                    $totalWeight,
                    $item['rate_per_gram'],
                    $metalAmount,
                    $makingChargeAmount,
                    $itemTotal
                ]);
            }
        }
        
        // 6. Add Sample Payments (10 payments)
        $payments = [
            ['customer_id' => $customerIds[0], 'amount' => 376430.18, 'payment_method' => 'bank', 'reference_no' => 'NEFT-001', 'notes' => 'Full payment for INV-2024-0001'],
            ['customer_id' => $customerIds[1], 'amount' => 140324.62, 'payment_method' => 'bank', 'reference_no' => 'RTGS-002', 'notes' => 'Full payment for INV-2024-0002'],
            ['customer_id' => $customerIds[2], 'amount' => 28735.98, 'payment_method' => 'cash', 'reference_no' => '', 'notes' => 'Full payment for INV-2024-0003'],
            ['customer_id' => $customerIds[4], 'amount' => 42872.20, 'payment_method' => 'upi', 'reference_no' => 'UPI-003', 'notes' => 'Full payment for INV-2024-0005'],
            ['customer_id' => $customerIds[5], 'amount' => 500000, 'payment_method' => 'bank', 'reference_no' => 'NEFT-004', 'notes' => 'Partial payment for INV-2024-0006'],
            ['customer_id' => $customerIds[6], 'amount' => 101908.20, 'payment_method' => 'cheque', 'reference_no' => 'CHQ-005', 'notes' => 'Full payment for INV-2024-0007'],
            ['customer_id' => $customerIds[8], 'amount' => 469920.25, 'payment_method' => 'bank', 'reference_no' => 'RTGS-006', 'notes' => 'Full payment for INV-2024-0009'],
            ['customer_id' => $customerIds[10], 'amount' => 139987.30, 'payment_method' => 'upi', 'reference_no' => 'UPI-007', 'notes' => 'Full payment for INV-2024-0011'],
            ['customer_id' => $customerIds[13], 'amount' => 57471.94, 'payment_method' => 'cash', 'reference_no' => '', 'notes' => 'Full payment for INV-2024-0014'],
            ['customer_id' => $customerIds[14], 'amount' => 500000, 'payment_method' => 'bank', 'reference_no' => 'NEFT-008', 'notes' => 'Partial payment for INV-2024-0015'],
        ];
        
        $stmtPayment = $db->prepare("INSERT INTO payments (customer_id, invoice_id, amount, payment_method, reference_no, payment_date, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($payments as $payment) {
            // Find the invoice for this customer
            $stmt = $db->prepare("SELECT id FROM invoices WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$payment['customer_id']]);
            $invoiceId = $stmt->fetch()['id'] ?? null;
            
            $stmtPayment->execute([
                $payment['customer_id'],
                $invoiceId,
                $payment['amount'],
                $payment['payment_method'],
                $payment['reference_no'],
                date('Y-m-d'),
                $payment['notes'],
                $_SESSION['user_id']
            ]);
        }
        
        // Update customer balances based on payments
        $db->query("UPDATE customers SET current_balance = opening_balance + (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE customer_id = customers.id) - (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = customers.id)");
        
        $db->commit();
        $message = 'Sample data added successfully!';
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Error: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-database-add"></i> Add Sample Data</h2>
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?php echo strpos($message, 'Error') === false ? 'success' : 'danger'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Sample Data to be Added</h5>
        </div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="bi bi-people text-primary"></i> 
                    <strong>15 Customers</strong> - Jewellers from Mumbai, Delhi, Bangalore, Chennai, Kolkata & more
                </li>
                <li class="list-group-item">
                    <i class="bi bi-currency-exchange text-warning"></i> 
                    <strong>5 Metal Rates</strong> - Gold (24K, 22K, 18K) and Silver (999, 925) for today
                </li>
                <li class="list-group-item">
                    <i class="bi bi-box text-success"></i> 
                    <strong>12 Products</strong> - Gold & Silver items: Chains, Rings, Bangles, Necklaces, Earrings, etc.
                </li>
                <li class="list-group-item">
                    <i class="bi bi-box-seam text-info"></i> 
                    <strong>Stock Entries</strong> - Initial inventory for all 12 products
                </li>
                <li class="list-group-item">
                    <i class="bi bi-receipt text-danger"></i> 
                    <strong>15 Invoices</strong> - Mix of Pending, Partial, and Paid invoices
                </li>
                <li class="list-group-item">
                    <i class="bi bi-cash-coin text-success"></i> 
                    <strong>10 Payments</strong> - Bank, Cash, UPI, and Cheque payments
                </li>
            </ul>
            
            <?php if (!$message || strpos($message, 'Error') !== false): ?>
            <form method="POST" action="" class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('This will add sample data to the database. Continue?')">
                    <i class="bi bi-database-add"></i> Add Sample Data
                </button>
            </form>
            <?php else: ?>
            <div class="mt-4">
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-success">
                    <i class="bi bi-speedometer2"></i> Go to Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>/customers/list.php" class="btn btn-primary">
                    <i class="bi bi-people"></i> View Customers
                </a>
                <a href="<?php echo BASE_URL; ?>/billing/list.php" class="btn btn-info">
                    <i class="bi bi-receipt"></i> View Invoices
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
