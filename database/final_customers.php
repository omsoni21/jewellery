<?php
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

// Get the max customer number
$stmt = $db->query("SELECT MAX(CAST(SUBSTRING(customer_code, 5) AS UNSIGNED)) as max_num FROM customers");
$max_num = $stmt->fetch()['max_num'];

echo "Max customer number: $max_num\n";

$start_from = $max_num + 1;
$need_to_add = 250 - $max_num;

echo "Starting from: CUST" . str_pad($start_from, 3, '0', STR_PAD_LEFT) . "\n";
echo "Need to add: $need_to_add customers\n";

if ($need_to_add <= 0) {
  echo "We already have 250 or more customers!\n";
  exit;
}

// Data arrays
$states_cities = [
  'Maharashtra' => ['Mumbai|400001', 'Pune|411001', 'Nagpur|440001', 'Thane|400601', 'Nashik|422001'],
  'Gujarat' => ['Surat|395001', 'Ahmedabad|380001', 'Vadodara|390001', 'Rajkot|360001', 'Bhavnagar|364001'],
  'Rajasthan' => ['Jaipur|302001', 'Jodhpur|342001', 'Udaipur|313001', 'Ajmer|305001', 'Kota|324001'],
  'Tamil Nadu' => ['Chennai|600001', 'Coimbatore|641001', 'Madurai|625001', 'Trichy|620001', 'Salem|636001'],
  'Karnataka' => ['Bangalore|560001', 'Mysore|570001', 'Mangalore|575001', 'Hubli|580001', 'Belgaum|590001'],
  'West Bengal' => ['Kolkata|700001', 'Howrah|711101', 'Durgapur|713201', 'Asansol|713301', 'Siliguri|734001'],
  'Telangana' => ['Hyderabad|500001', 'Warangal|506001', 'Nizamabad|503001', 'Karimnagar|505001', 'Khammam|507001'],
  'Kerala' => ['Kochi|682001', 'Trivandrum|695001', 'Kozhikode|673001', 'Thrissur|680001', 'Kollam|691001'],
];

$name_prefixes = ['Shree', 'Gold', 'Diamond', 'Silver', 'Royal', 'Kundan', 'Temple', 'Bridal', 'Elite', 'Pearl', 'Platinum', 'Jadau', 'Polki', 'Meenakari', 'Navratna', 'Antique', 'Modern', 'Traditional', 'Heritage', 'Lakshmi', 'Swarna', 'Kemp', 'Filigree', 'Thewa', 'Rudraksha'];
$name_suffixes = ['Jewellers', 'Gold House', 'Diamond Palace', 'Silver Works', 'Ornaments', 'Jewels', 'Gold Mart', 'Pearl Traders', 'Diamond House', 'Silver Crafts', 'Gold Traders', 'Jewellery Mart', 'Gold Palace', 'Silver Mart', 'Diamond Traders'];
$first_names = ['Rajesh', 'Suresh', 'Amit', 'Priya', 'Vikram', 'Lakshmi', 'Mahesh', 'Kavita', 'Ravi', 'Neha', 'Sanjay', 'Anita', 'Rahul', 'Pooja', 'Kiran', 'Meena', 'Ashok', 'Deepa', 'Arjun', 'Geeta', 'Manish', 'Rekha', 'Shankar', 'Rajiv', 'Farhan', 'Subrata', 'Anjali', 'Nitin', 'Mohammed', 'Harpreet'];
$last_names = ['Kumar', 'Patel', 'Shah', 'Sharma', 'Singh', 'Devi', 'Agarwal', 'Reddy', 'Verma', 'Gupta', 'Mehta', 'Joshi', 'Nair', 'Rathore', 'Desai', 'Kumari', 'Malhotra', 'Iyer', 'Rao', 'Das'];
$gst_codes = ['Maharashtra' => '27', 'Gujarat' => '24', 'Rajasthan' => '08', 'Tamil Nadu' => '33', 'Karnataka' => '29', 'West Bengal' => '19', 'Telangana' => '36', 'Kerala' => '32'];
$streets = ['Main Market', 'Ring Road', 'Gold Market', 'Silver Market', 'Diamond Street', 'Jewelry Lane', 'Commercial Street', 'Market Complex', 'Business District', 'Temple Road'];
$landmarks = ['Near City Center', 'Opposite Bus Stand', 'Sector ', 'Phase ', 'Market Road', 'Old City', 'New Market Area', 'Business Hub', 'Craftsmen Area', 'Commercial Complex'];

$customers = [];
$added = 0;
$current_num = $start_from;

while ($added < $need_to_add) {
  foreach ($states_cities as $state => $cities) {
    foreach ($cities as $city_data) {
      if ($added >= $need_to_add) break 2;

      list($city, $pincode) = explode('|', $city_data);
      $prefix = $name_prefixes[array_rand($name_prefixes)];
      $suffix = $name_suffixes[array_rand($name_suffixes)];
      $business_name = $prefix . ' ' . $suffix;
      $first_name = $first_names[array_rand($first_names)];
      $last_name = $last_names[array_rand($last_names)];
      $contact_person = $first_name . ' ' . $last_name;
      $phone = '9' . rand(8000000000, 9999999999);
      $email = strtolower(str_replace(' ', '', $first_name)) . $current_num . '@' . strtolower(str_replace(' ', '', $business_name)) . '.com';
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
      $customer_code = 'CUST' . str_pad($current_num, 3, '0', STR_PAD_LEFT);

      $customers[] = "('$customer_code', '$business_name', '$contact_person', '$phone', '$email', '$gst_number', '$pan', '$address_line1', '$address_line2', '$city', '$state', '$pincode', $credit_limit, $payment_terms, $opening_balance, $current_balance, 1)";

      $current_num++;
      $added++;
    }
  }
}

$values = implode(",\n", $customers);
$sql = "INSERT INTO customers (customer_code, business_name, contact_person, phone, email, gst_number, pan_number, address_line1, address_line2, city, state, pincode, credit_limit, payment_terms, opening_balance, current_balance, is_active) VALUES\n$values";

try {
  $db->exec($sql);
  echo "Successfully inserted $added customers\n";
  echo "Total customers now: " . ($max_num + $added) . "\n";
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage() . "\n";
}

echo "Done!\n";
