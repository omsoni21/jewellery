<?php

/**
 * Script to insert 250 wholesale jewellery customers
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

// Clear existing customers (except if you want to keep them, comment this out)
// $db->exec("DELETE FROM customers");

$states_cities = [
  'Maharashtra' => ['Mumbai|400001', 'Pune|411001', 'Nagpur|440001', 'Thane|400601', 'Nashik|422001', 'Aurangabad|431001', 'Solapur|413001', 'Kolhapur|416001', 'Amravati|444601', 'Nanded|431601'],
  'Gujarat' => ['Surat|395001', 'Ahmedabad|380001', 'Vadodara|390001', 'Rajkot|360001', 'Bhavnagar|364001', 'Jamnagar|361001', 'Junagadh|362001', 'Gandhinagar|382001', 'Anand|388001', 'Morbi|363641'],
  'Rajasthan' => ['Jaipur|302001', 'Jodhpur|342001', 'Udaipur|313001', 'Ajmer|305001', 'Kota|324001', 'Bikaner|334001', 'Alwar|301001', 'Bharatpur|321001', 'Sikar|332001', 'Pali|306401'],
  'Tamil Nadu' => ['Chennai|600001', 'Coimbatore|641001', 'Madurai|625001', 'Trichy|620001', 'Salem|636001', 'Tirunelveli|627001', 'Tiruppur|641601', 'Vellore|632001', 'Erode|638001', 'Thoothukudi|628001'],
  'Karnataka' => ['Bangalore|560001', 'Mysore|570001', 'Mangalore|575001', 'Hubli|580001', 'Belgaum|590001', 'Gulbarga|585101', 'Davangere|577001', 'Bellary|583101', 'Bijapur|586101', 'Shimoga|577201'],
  'West Bengal' => ['Kolkata|700001', 'Howrah|711101', 'Durgapur|713201', 'Asansol|713301', 'Siliguri|734001', 'Malda|732101', 'Bardhaman|713101', 'Baharampur|742101', 'Habra|743263', 'Kharagpur|721301'],
  'Telangana' => ['Hyderabad|500001', 'Warangal|506001', 'Nizamabad|503001', 'Karimnagar|505001', 'Khammam|507001', 'Ramagundam|505208', 'Mahbubnagar|509001', 'Nalgonda|508001', 'Adilabad|504001', 'Suryapet|508213'],
  'Kerala' => ['Kochi|682001', 'Trivandrum|695001', 'Kozhikode|673001', 'Thrissur|680001', 'Kollam|691001', 'Palakkad|678001', 'Alappuzha|688001', 'Malappuram|676501', 'Kannur|670001', 'Kottayam|686001'],
];

$name_prefixes = ['Shree', 'Gold', 'Diamond', 'Silver', 'Royal', 'Kundan', 'Temple', 'Bridal', 'Elite', 'Pearl', 'Platinum', 'Jadau', 'Polki', 'Meenakari', 'Navratna', 'Antique', 'Modern', 'Traditional', 'Heritage', 'Lakshmi', 'Swarna', 'Kemp', 'Filigree', 'Thewa', 'Rudraksha'];
$name_suffixes = ['Jewellers', 'Gold House', 'Diamond Palace', 'Silver Works', 'Ornaments', 'Jewels', 'Gold Mart', 'Pearl Traders', 'Diamond House', 'Silver Crafts', 'Gold Traders', 'Jewellery Mart', 'Gold Palace', 'Silver Mart', 'Diamond Traders'];

$first_names = ['Rajesh', 'Suresh', 'Amit', 'Priya', 'Vikram', 'Lakshmi', 'Mahesh', 'Kavita', 'Ravi', 'Neha', 'Sanjay', 'Anita', 'Rahul', 'Pooja', 'Kiran', 'Meena', 'Ashok', 'Deepa', 'Arjun', 'Geeta', 'Manish', 'Rekha', 'Shankar', 'Rajiv', 'Farhan', 'Subrata', 'Anjali', 'Nitin', 'Mohammed', 'Harpreet', 'Kamla', 'Ramesh', 'Pankaj', 'Venkat', 'Vishal', 'Francis', 'Suresh', 'Ranjan', 'Anandhan', 'Mohan', 'Kamlesh', 'Venkata', 'Bhaben', 'Thoibi', 'Bishu'];
$last_names = ['Kumar', 'Patel', 'Shah', 'Sharma', 'Singh', 'Devi', 'Agarwal', 'Reddy', 'Verma', 'Gupta', 'Mehta', 'Joshi', 'Nair', 'Rathore', 'Desai', 'Kumari', 'Malhotra', 'Iyer', 'Singh', 'Rao', 'Das', 'Reddy', 'Rajput', 'Agarwal', 'Nair', 'Sheikh', 'Ghosh', 'Sharma', 'Patel', 'Singh', 'Devi', 'Menon', 'Gupta', 'Krishnan', 'Tiwari', 'D Souza', 'Thakur', 'Kumar', 'Murugan', 'Singh', 'Sahu', 'Rao', 'Baruah', 'Singh', 'Debbarma'];

$gst_codes = [
  'Maharashtra' => '27',
  'Gujarat' => '24',
  'Rajasthan' => '08',
  'Tamil Nadu' => '33',
  'Karnataka' => '29',
  'West Bengal' => '19',
  'Telangana' => '36',
  'Kerala' => '32',
  'Madhya Pradesh' => '23',
  'Delhi' => '07',
  'Uttar Pradesh' => '09',
];

$streets = ['Main Market', 'Ring Road', 'Gold Market', 'Silver Market', 'Diamond Street', 'Jewelry Lane', 'Commercial Street', 'Market Complex', 'Business District', 'Temple Road'];
$landmarks = ['Near City Center', 'Opposite Bus Stand', 'Sector ', 'Phase ', 'Market Road', 'Old City', 'New Market Area', 'Business Hub', 'Craftsmen Area', 'Commercial Complex'];

$customers = [];
$customer_count = 1;
$max_customers = 500;

// Generate 250 customers
while ($customer_count <= $max_customers) {
  foreach ($states_cities as $state => $cities) {
    foreach ($cities as $city_data) {
     if ($customer_count > $max_customers) break 2;

      list($city, $pincode) = explode('|', $city_data);

      $prefix = $name_prefixes[array_rand($name_prefixes)];
      $suffix = $name_suffixes[array_rand($name_suffixes)];
      $business_name = $prefix . ' ' . $suffix . ' ' . ($customer_count > 100 ? '' : '');

      $first_name = $first_names[array_rand($first_names)];
      $last_name = $last_names[array_rand($last_names)];
      $contact_person = $first_name . ' ' . $last_name;

      $phone = '9' . rand(8000000000, 9999999999);
      $email = strtolower(str_replace(' ', '', $first_name)) . $customer_count . '@' . strtolower(str_replace(' ', '', $business_name)) . '.com';

      $gst_code = $gst_codes[$state];
      $pan = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5)) . rand(1000, 9999) . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1));
      $gst_number = $gst_code . substr($pan, 0, 10) . '1Z' . rand(1, 9);

      $address_line1 = rand(100, 999) . ' ' . $streets[array_rand($streets)];
      $landmark = $landmarks[array_rand($landmarks)];
      $address_line2 = strpos($landmark, 'Sector') !== false ? $landmark . rand(1, 50) : (strpos($landmark, 'Phase') !== false ? $landmark . rand(1, 5) : $landmark);

      $credit_limit = [200000, 250000, 300000, 350000, 400000, 450000, 500000, 550000, 600000, 650000, 700000, 750000, 800000, 850000, 900000, 950000, 1000000, 1100000, 1200000][array_rand([200000, 250000, 300000, 350000, 400000, 450000, 500000, 550000, 600000, 650000, 700000, 750000, 800000, 850000, 900000, 950000, 1000000, 1100000, 1200000])];
      $payment_terms = [15, 30, 45, 60][array_rand([15, 30, 45, 60])];
      $opening_balance = rand(-50000, 50000);
      $current_balance = $opening_balance + rand(0, 30000);

      $customer_code = 'CUST' . str_pad($customer_count, 3, '0', STR_PAD_LEFT);

      $customers[] = "('$customer_code', '$business_name', '$contact_person', '$phone', '$email', '$gst_number', '$pan', '$address_line1', '$address_line2', '$city', '$state', '$pincode', $credit_limit, $payment_terms, $opening_balance, $current_balance, 1)";

      $customer_count++;
    }
  }
  if ($customer_count <= $max_customers) {
    continue;
  }
  break;
}

// Insert in batches of 50
$batches = array_chunk($customers, 50);
$total_inserted = 0;

foreach ($batches as $batch_num => $batch) {
  $values = implode(",\n", $batch);
  $sql = "INSERT INTO customers (customer_code, business_name, contact_person, phone, email, gst_number, pan_number, address_line1, address_line2, city, state, pincode, credit_limit, payment_terms, opening_balance, current_balance, is_active) VALUES\n$values";

  try {
    $db->exec($sql);
    $total_inserted += count($batch);
    echo "Batch " . ($batch_num + 1) . " inserted: " . count($batch) . " customers\n";
  } catch (PDOException $e) {
    echo "Error in batch " . ($batch_num + 1) . ": " . $e->getMessage() . "\n";
  }
}

echo "\nTotal customers inserted: $total_inserted\n";
echo "Done!\n";
