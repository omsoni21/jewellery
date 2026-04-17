<?php

/**
 * Script to insert 250 wholesale jewellery customers
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

// Indian states and cities data
$states_cities = [
  'Maharashtra' => [['Mumbai', '400001'], ['Pune', '411001'], ['Nagpur', '440001'], ['Thane', '400601'], ['Nashik', '422001']],
  'Gujarat' => [['Surat', '395001'], ['Ahmedabad', '380001'], ['Vadodara', '390001'], ['Rajkot', '360001'], ['Bhavnagar', '364001']],
  'Rajasthan' => [['Jaipur', '302001'], ['Jodhpur', '342001'], ['Udaipur', '313001'], ['Ajmer', '305001'], ['Kota', '324001']],
  'Tamil Nadu' => [['Chennai', '600001'], ['Coimbatore', '641001'], ['Madurai', '625001'], ['Tiruchirappalli', '620001'], ['Salem', '636001']],
  'Karnataka' => [['Bangalore', '560001'], ['Mysore', '570001'], ['Mangalore', '575001'], ['Hubli', '580001'], ['Belgaum', '590001']],
  'West Bengal' => [['Kolkata', '700001'], ['Howrah', '711101'], ['Durgapur', '713201'], ['Asansol', '713301'], ['Siliguri', '734001']],
  'Telangana' => [['Hyderabad', '500001'], ['Warangal', '506001'], ['Nizamabad', '503001'], ['Karimnagar', '505001'], ['Khammam', '507001']],
  'Kerala' => [['Kochi', '682001'], ['Thiruvananthapuram', '695001'], ['Kozhikode', '673001'], ['Thrissur', '680001'], ['Kollam', '691001']],
  'Delhi' => [['Delhi', '110001'], ['New Delhi', '110011'], ['Dwarka', '110075'], ['Rohini', '110085'], ['Noida', '201301']],
  'Punjab' => [['Amritsar', '143001'], ['Ludhiana', '141001'], ['Jalandhar', '144001'], ['Patiala', '147001'], ['Bathinda', '151001']],
  'Uttar Pradesh' => [['Lucknow', '226001'], ['Kanpur', '208001'], ['Varanasi', '221001'], ['Agra', '282001'], ['Meerut', '250001']],
  'Madhya Pradesh' => [['Indore', '452001'], ['Bhopal', '462001'], ['Jabalpur', '482001'], ['Gwalior', '474001'], ['Ujjain', '456001']],
  'Andhra Pradesh' => [['Vijayawada', '520001'], ['Visakhapatnam', '530001'], ['Guntur', '522001'], ['Tirupati', '517501'], ['Nellore', '524001']],
  'Odisha' => [['Bhubaneswar', '751001'], ['Cuttack', '753001'], ['Rourkela', '769001'], ['Puri', '752001'], ['Sambalpur', '768001']],
  'Bihar' => [['Patna', '800001'], ['Gaya', '823001'], ['Bhagalpur', '812001'], ['Muzaffarpur', '842001'], ['Darbhanga', '846001']],
  'Jharkhand' => [['Ranchi', '834001'], ['Jamshedpur', '831001'], ['Dhanbad', '826001'], ['Bokaro', '827001'], ['Deoghar', '814112']],
  'Assam' => [['Guwahati', '781001'], ['Dibrugarh', '786001'], ['Jorhat', '785001'], ['Silchar', '788001'], ['Nagaon', '782001']],
  'Chandigarh' => [['Chandigarh', '160001'], ['Chandigarh', '160002'], ['Chandigarh', '160003'], ['Chandigarh', '160004'], ['Chandigarh', '160005']],
  'Goa' => [['Panaji', '403001'], ['Margao', '403601'], ['Vasco da Gama', '403802'], ['Mapusa', '403507'], ['Ponda', '403401']],
  'Himachal Pradesh' => [['Shimla', '171001'], ['Dharamshala', '176215'], ['Manali', '175131'], ['Solan', '173212'], ['Mandi', '175001']],
  'Uttarakhand' => [['Dehradun', '248001'], ['Haridwar', '249401'], ['Roorkee', '247667'], ['Haldwani', '263139'], ['Rishikesh', '249201']],
  'Chhattisgarh' => [['Raipur', '492001'], ['Bhilai', '490001'], ['Bilaspur', '495001'], ['Korba', '495677'], ['Durg', '491001']],
  'Jammu & Kashmir' => [['Srinagar', '190001'], ['Jammu', '180001'], ['Anantnag', '192101'], ['Baramulla', '193101'], ['Udhampur', '182101']],
];

// Business name prefixes and suffixes
$name_prefixes = ['Shree', 'Gold', 'Diamond', 'Silver', 'Royal', 'Kundan', 'Temple', 'Bridal', 'Elite', 'Pearl', 'Platinum', 'Jadau', 'Polki', 'Meenakari', 'Navratna', 'Antique', 'Modern', 'Traditional', 'Heritage', 'Lakshmi', 'Swarna', 'Kemp', 'Filigree', 'Thewa', 'Rudraksha'];
$name_suffixes = ['Jewellers', 'Gold House', 'Diamond Palace', 'Silver Works', 'Ornaments', 'Jewels', 'Gold Mart', 'Pearl Traders', 'Diamond House', 'Silver Crafts', 'Gold Traders', 'Jewellery Mart', 'Gold Palace', 'Silver Mart', 'Diamond Traders'];

// Contact person names
$first_names = ['Rajesh', 'Suresh', 'Amit', 'Priya', 'Vikram', 'Lakshmi', 'Mahesh', 'Kavita', 'Ravi', 'Neha', 'Sanjay', 'Anita', 'Rahul', 'Pooja', 'Kiran', 'Meena', 'Ashok', 'Deepa', 'Arjun', 'Geeta', 'Manish', 'Rekha', 'Shankar', 'Rajiv', 'Farhan', 'Subrata', 'Anjali', 'Nitin', 'Mohammed', 'Harpreet', 'Kamla', 'Ramesh', 'Pankaj', 'Venkat', 'Vishal', 'Francis', 'Suresh', 'Ranjan', 'Anandhan', 'Mohan', 'Kamlesh', 'Venkata', 'Bhaben', 'Thoibi', 'Bishu'];
$last_names = ['Kumar', 'Patel', 'Shah', 'Sharma', 'Singh', 'Devi', 'Agarwal', 'Reddy', 'Verma', 'Gupta', 'Mehta', 'Joshi', 'Nair', 'Rathore', 'Desai', 'Kumari', 'Malhotra', 'Iyer', 'Singh', 'Rao', 'Das', 'Reddy', 'Rajput', 'Agarwal', 'Nair', 'Sheikh', 'Ghosh', 'Sharma', 'Patel', 'Singh', 'Devi', 'Menon', 'Gupta', 'Krishnan', 'Tiwari', 'D Souza', 'Thakur', 'Kumar', 'Murugan', 'Singh', 'Sahu', 'Rao', 'Baruah', 'Singh', 'Debbarma'];

// GST state codes
$gst_codes = [
  'Maharashtra' => '27',
  'Gujarat' => '24',
  'Rajasthan' => '08',
  'Tamil Nadu' => '33',
  'Karnataka' => '29',
  'West Bengal' => '19',
  'Telangana' => '36',
  'Kerala' => '32',
  'Delhi' => '07',
  'Punjab' => '03',
  'Uttar Pradesh' => '09',
  'Madhya Pradesh' => '23',
  'Andhra Pradesh' => '37',
  'Odisha' => '21',
  'Bihar' => '10',
  'Jharkhand' => '20',
  'Assam' => '18',
  'Chandigarh' => '06',
  'Goa' => '30',
  'Himachal Pradesh' => '02',
  'Uttarakhand' => '05',
  'Chhattisgarh' => '22',
  'Jammu & Kashmir' => '01'
];

$customers = [];
$customer_count = 1;

// Generate 250 customers
foreach ($states_cities as $state => $cities) {
  foreach ($cities as $city_data) {
    if ($customer_count > 250) break 2;

    list($city, $pincode) = $city_data;

    // Generate customer data
    $prefix = $name_prefixes[array_rand($name_prefixes)];
    $suffix = $name_suffixes[array_rand($name_suffixes)];
    $business_name = $prefix . ' ' . $suffix;

    $first_name = $first_names[array_rand($first_names)];
    $last_name = $last_names[array_rand($last_names)];
    $contact_person = $first_name . ' ' . $last_name;

    $phone = '9' . rand(8000000000, 9999999999);
    $email = strtolower(str_replace(' ', '', $first_name)) . $customer_count . '@' . strtolower(str_replace(' ', '', $business_name)) . '.com';

    // Generate GST number
    $gst_code = $gst_codes[$state];
    $pan = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5)) . rand(1000, 9999) . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1));
    $gst_number = $gst_code . substr($pan, 0, 10) . '1Z' . rand(1, 9);

    // Generate address
    $address_line1 = rand(100, 999) . ' ' . ['Main Market', 'Ring Road', 'Gold Market', 'Silver Market', 'Diamond Street', 'Jewelry Lane', 'Commercial Street', 'Market Complex', 'Business District', 'Temple Road'][array_rand(['Main Market', 'Ring Road', 'Gold Market', 'Silver Market', 'Diamond Street', 'Jewelry Lane', 'Commercial Street', 'Market Complex', 'Business District', 'Temple Road'])];
    $address_line2 = ['Near City Center', 'Opposite Bus Stand', 'Sector ' . rand(1, 50), 'Phase ' . rand(1, 5), 'Market Road', 'Old City', 'New Market Area', 'Business Hub', 'Craftsmen Area', 'Commercial Complex'][array_rand(['Near City Center', 'Opposite Bus Stand', 'Sector ' . rand(1, 50), 'Phase ' . rand(1, 5), 'Market Road', 'Old City', 'New Market Area', 'Business Hub', 'Craftsmen Area', 'Commercial Complex'])];

    $credit_limit = [200000, 250000, 300000, 350000, 400000, 450000, 500000, 550000, 600000, 650000, 700000, 750000, 800000, 850000, 900000, 950000, 1000000, 1100000, 1200000][array_rand([200000, 250000, 300000, 350000, 400000, 450000, 500000, 550000, 600000, 650000, 700000, 750000, 800000, 850000, 900000, 950000, 1000000, 1100000, 1200000])];
    $payment_terms = [15, 30, 45, 60][array_rand([15, 30, 45, 60])];
    $opening_balance = rand(-50000, 50000);
    $current_balance = $opening_balance + rand(0, 30000);

    $customer_code = 'CUST' . str_pad($customer_count, 3, '0', STR_PAD_LEFT);

    $customers[] = "('$customer_code', '$business_name', '$contact_person', '$phone', '$email', '$gst_number', '$pan', '$address_line1', '$address_line2', '$city', '$state', '$pincode', $credit_limit, $payment_terms, $opening_balance, $current_balance, 1)";

    $customer_count++;
  }
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
