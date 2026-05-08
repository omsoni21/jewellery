<?php

/**
 * Add 300 Jewellery Products (Gold & Silver) to database
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Adding 300 jewellery products...\n\n";

try {
  $db->beginTransaction();

  // Gold Products - 200 items
  $goldProducts = [
    // Rings (20 items)
    ['Gold Ring 22K Simple', 'gold', '22K', 1],
    ['Gold Ring 22K Designer', 'gold', '22K', 1],
    ['Gold Ring 22K Wedding', 'gold', '22K', 1],
    ['Gold Ring 22K Stone', 'gold', '22K', 1],
    ['Gold Ring 22K Plain', 'gold', '22K', 1],
    ['Gold Ring 22K Antique', 'gold', '22K', 1],
    ['Gold Ring 22K Bridal', 'gold', '22K', 1],
    ['Gold Ring 22K Traditional', 'gold', '22K', 1],
    ['Gold Ring 22K Modern', 'gold', '22K', 1],
    ['Gold Ring 22K Temple', 'gold', '22K', 1],
    ['Gold Ring 18K Diamond Cut', 'gold', '18K', 1],
    ['Gold Ring 18K Solitaire', 'gold', '18K', 1],
    ['Gold Ring 18K Eternity', 'gold', '18K', 1],
    ['Gold Ring 18K Cocktail', 'gold', '18K', 1],
    ['Gold Ring 18K Promise', 'gold', '18K', 1],
    ['Gold Ring 24K Plain', 'gold', '24K', 1],
    ['Gold Ring 24K Embossed', 'gold', '24K', 1],
    ['Gold Ring 24K High Polish', 'gold', '24K', 1],
    ['Gold Ring 24K Matte Finish', 'gold', '24K', 1],
    ['Gold Ring 24K Textured', 'gold', '24K', 1],

    // Chains (25 items)
    ['Gold Chain 22K Simple', 'gold', '22K', 2],
    ['Gold Chain 22K Rope', 'gold', '22K', 2],
    ['Gold Chain 22K Figaro', 'gold', '22K', 2],
    ['Gold Chain 22K Cuban Link', 'gold', '22K', 2],
    ['Gold Chain 22K Snake', 'gold', '22K', 2],
    ['Gold Chain 22K Box', 'gold', '22K', 2],
    ['Gold Chain 22K Wheat', 'gold', '22K', 2],
    ['Gold Chain 22K Herringbone', 'gold', '22K', 2],
    ['Gold Chain 22K Franco', 'gold', '22K', 2],
    ['Gold Chain 22K Singapore', 'gold', '22K', 2],
    ['Gold Chain 22K Byzantine', 'gold', '22K', 2],
    ['Gold Chain 22K Anchor', 'gold', '22K', 2],
    ['Gold Chain 22K Cable', 'gold', '22K', 2],
    ['Gold Chain 22K Curb', 'gold', '22K', 2],
    ['Gold Chain 22K Mariner', 'gold', '22K', 2],
    ['Gold Chain 18K Diamond Cut', 'gold', '18K', 2],
    ['Gold Chain 18K Thin', 'gold', '18K', 2],
    ['Gold Chain 18K Medium', 'gold', '18K', 2],
    ['Gold Chain 18K Thick', 'gold', '18K', 2],
    ['Gold Chain 18K Designer', 'gold', '18K', 2],
    ['Gold Chain 24K Plain', 'gold', '24K', 2],
    ['Gold Chain 24K Heavy', 'gold', '24K', 2],
    ['Gold Chain 24K Light', 'gold', '24K', 2],
    ['Gold Chain 24K Machine Made', 'gold', '24K', 2],
    ['Gold Chain 24K Handmade', 'gold', '24K', 2],

    // Bangles (20 items)
    ['Gold Bangle 22K Simple', 'gold', '22K', 3],
    ['Gold Bangle 22K Designer', 'gold', '22K', 3],
    ['Gold Bangle 22K Antique', 'gold', '22K', 3],
    ['Gold Bangle 22K Bridal Set', 'gold', '22K', 3],
    ['Gold Bangle 22K Plain', 'gold', '22K', 3],
    ['Gold Bangle 22K Stone Work', 'gold', '22K', 3],
    ['Gold Bangle 22K Enamel', 'gold', '22K', 3],
    ['Gold Bangle 22K Screw', 'gold', '22K', 3],
    ['Gold Bangle 22K Hinge', 'gold', '22K', 3],
    ['Gold Bangle 22K Flexible', 'gold', '22K', 3],
    ['Gold Bangle 22K Cuff', 'gold', '22K', 3],
    ['Gold Bangle 22K Temple', 'gold', '22K', 3],
    ['Gold Bangle 22K Modern', 'gold', '22K', 3],
    ['Gold Bangle 22K Traditional', 'gold', '22K', 3],
    ['Gold Bangle 18K Diamond', 'gold', '18K', 3],
    ['Gold Bangle 18K Slim', 'gold', '18K', 3],
    ['Gold Bangle 18K Wide', 'gold', '18K', 3],
    ['Gold Bangle 24K Plain', 'gold', '24K', 3],
    ['Gold Bangle 24K Embossed', 'gold', '24K', 3],
    ['Gold Bangle 24K High Polish', 'gold', '24K', 3],

    // Necklaces (25 items)
    ['Gold Necklace 22K Simple', 'gold', '22K', 6],
    ['Gold Necklace 22K Designer', 'gold', '22K', 6],
    ['Gold Necklace 22K Bridal', 'gold', '22K', 6],
    ['Gold Necklace 22K Long Haram', 'gold', '22K', 6],
    ['Gold Necklace 22K Choker', 'gold', '22K', 6],
    ['Gold Necklace 22K Layered', 'gold', '22K', 6],
    ['Gold Necklace 22K Temple', 'gold', '22K', 6],
    ['Gold Necklace 22K Antique', 'gold', '22K', 6],
    ['Gold Necklace 22K Stone', 'gold', '22K', 6],
    ['Gold Necklace 22K Pearl', 'gold', '22K', 6],
    ['Gold Necklace 22K Beaded', 'gold', '22K', 6],
    ['Gold Necklace 22K Mesh', 'gold', '22K', 6],
    ['Gold Necklace 22K Coin', 'gold', '22K', 6],
    ['Gold Necklace 22K Rani Haram', 'gold', '22K', 6],
    ['Gold Necklace 22K Short', 'gold', '22K', 6],
    ['Gold Necklace 18K Diamond', 'gold', '18K', 6],
    ['Gold Necklace 18K Pendant Chain', 'gold', '18K', 6],
    ['Gold Necklace 18K Thin', 'gold', '18K', 6],
    ['Gold Necklace 18K Statement', 'gold', '18K', 6],
    ['Gold Necklace 18K Choker', 'gold', '18K', 6],
    ['Gold Necklace 24K Plain', 'gold', '24K', 6],
    ['Gold Necklace 24K Heavy', 'gold', '24K', 6],
    ['Gold Necklace 24K Light', 'gold', '24K', 6],
    ['Gold Necklace 24K Traditional', 'gold', '24K', 6],
    ['Gold Necklace 24K Modern', 'gold', '24K', 6],

    // Earrings (25 items)
    ['Gold Earrings 22K Studs', 'gold', '22K', 5],
    ['Gold Earrings 22K Jhumkas', 'gold', '22K', 5],
    ['Gold Earrings 22K Drops', 'gold', '22K', 5],
    ['Gold Earrings 22K Hoops', 'gold', '22K', 5],
    ['Gold Earrings 22K Chandbali', 'gold', '22K', 5],
    ['Gold Earrings 22K Temple', 'gold', '22K', 5],
    ['Gold Earrings 22K Antique', 'gold', '22K', 5],
    ['Gold Earrings 22K Stone', 'gold', '22K', 5],
    ['Gold Earrings 22K Pearl', 'gold', '22K', 5],
    ['Gold Earrings 22K Long', 'gold', '22K', 5],
    ['Gold Earrings 22K Short', 'gold', '22K', 5],
    ['Gold Earrings 22K Designer', 'gold', '22K', 5],
    ['Gold Earrings 22K Bridal', 'gold', '22K', 5],
    ['Gold Earrings 22K Traditional', 'gold', '22K', 5],
    ['Gold Earrings 22K Modern', 'gold', '22K', 5],
    ['Gold Earrings 18K Diamond Studs', 'gold', '18K', 5],
    ['Gold Earrings 18K Drops', 'gold', '18K', 5],
    ['Gold Earrings 18K Hoops', 'gold', '18K', 5],
    ['Gold Earrings 18K Huggies', 'gold', '18K', 5],
    ['Gold Earrings 18K Cluster', 'gold', '18K', 5],
    ['Gold Earrings 24K Plain', 'gold', '24K', 5],
    ['Gold Earrings 24K Ball', 'gold', '24K', 5],
    ['Gold Earrings 24K Button', 'gold', '24K', 5],
    ['Gold Earrings 24K Coin', 'gold', '24K', 5],
    ['Gold Earrings 24K Floral', 'gold', '24K', 5],

    // Pendants (20 items)
    ['Gold Pendant 22K Simple', 'gold', '22K', 6],
    ['Gold Pendant 22K Designer', 'gold', '22K', 6],
    ['Gold Pendant 22K Religious', 'gold', '22K', 6],
    ['Gold Pendant 22K Initial', 'gold', '22K', 6],
    ['Gold Pendant 22K Heart', 'gold', '22K', 6],
    ['Gold Pendant 22K Flower', 'gold', '22K', 6],
    ['Gold Pendant 22K Star', 'gold', '22K', 6],
    ['Gold Pendant 22K Cross', 'gold', '22K', 6],
    ['Gold Pendant 22K Oval', 'gold', '22K', 6],
    ['Gold Pendant 22K Round', 'gold', '22K', 6],
    ['Gold Pendant 22K Square', 'gold', '22K', 6],
    ['Gold Pendant 22K Teardrop', 'gold', '22K', 6],
    ['Gold Pendant 22K Antique', 'gold', '22K', 6],
    ['Gold Pendant 22K Stone', 'gold', '22K', 6],
    ['Gold Pendant 22K Pearl', 'gold', '22K', 6],
    ['Gold Pendant 18K Diamond', 'gold', '18K', 6],
    ['Gold Pendant 18K Solitaire', 'gold', '18K', 6],
    ['Gold Pendant 18K Minimal', 'gold', '18K', 6],
    ['Gold Pendant 24K Plain', 'gold', '24K', 6],
    ['Gold Pendant 24K Embossed', 'gold', '24K', 6],

    // Bracelets (15 items)
    ['Gold Bracelet 22K Simple', 'gold', '22K', 7],
    ['Gold Bracelet 22K Chain', 'gold', '22K', 7],
    ['Gold Bracelet 22K Bangle', 'gold', '22K', 7],
    ['Gold Bracelet 22K Cuff', 'gold', '22K', 7],
    ['Gold Bracelet 22K Tennis', 'gold', '22K', 7],
    ['Gold Bracelet 22K Charm', 'gold', '22K', 7],
    ['Gold Bracelet 22K Link', 'gold', '22K', 7],
    ['Gold Bracelet 22K Designer', 'gold', '22K', 7],
    ['Gold Bracelet 22K Antique', 'gold', '22K', 7],
    ['Gold Bracelet 22K Stone', 'gold', '22K', 7],
    ['Gold Bracelet 18K Diamond', 'gold', '18K', 7],
    ['Gold Bracelet 18K Slim', 'gold', '18K', 7],
    ['Gold Bracelet 18K Chain', 'gold', '18K', 7],
    ['Gold Bracelet 24K Plain', 'gold', '24K', 7],
    ['Gold Bracelet 24K Heavy', 'gold', '24K', 7],

    // Mangalsutra (15 items)
    ['Gold Mangalsutra 22K Simple', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Designer', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Long', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Short', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Black Beads', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Diamond', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Stone', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Traditional', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Modern', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Bridal', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Antique', 'gold', '22K', 8],
    ['Gold Mangalsutra 22K Temple', 'gold', '22K', 8],
    ['Gold Mangalsutra 18K Diamond', 'gold', '18K', 8],
    ['Gold Mangalsutra 24K Plain', 'gold', '24K', 8],
    ['Gold Mangalsutra 24K Heavy', 'gold', '24K', 8],

    // Nose Pin (10 items)
    ['Gold Nose Pin 22K Simple', 'gold', '22K', 9],
    ['Gold Nose Pin 22K Stone', 'gold', '22K', 9],
    ['Gold Nose Pin 22K Diamond', 'gold', '22K', 9],
    ['Gold Nose Pin 22K Floral', 'gold', '22K', 9],
    ['Gold Nose Pin 22K Traditional', 'gold', '22K', 9],
    ['Gold Nose Pin 22K Modern', 'gold', '22K', 9],
    ['Gold Nose Pin 22K L-Shaped', 'gold', '22K', 9],
    ['Gold Nose Pin 22K Screw', 'gold', '22K', 9],
    ['Gold Nose Pin 18K Diamond', 'gold', '18K', 9],
    ['Gold Nose Pin 24K Plain', 'gold', '24K', 9],

    // Anklet (10 items)
    ['Gold Anklet 22K Simple', 'gold', '22K', 10],
    ['Gold Anklet 22K Chain', 'gold', '22K', 10],
    ['Gold Anklet 22K Designer', 'gold', '22K', 10],
    ['Gold Anklet 22K Heavy', 'gold', '22K', 10],
    ['Gold Anklet 22K Light', 'gold', '22K', 10],
    ['Gold Anklet 22K Bell', 'gold', '22K', 10],
    ['Gold Anklet 22K Traditional', 'gold', '22K', 10],
    ['Gold Anklet 22K Modern', 'gold', '22K', 10],
    ['Gold Anklet 24K Plain', 'gold', '24K', 10],
    ['Gold Anklet 24K Heavy', 'gold', '24K', 10],

    // Coins & Bars (15 items)
    ['Gold Coin 24K 5g', 'gold', '24K', 12],
    ['Gold Coin 24K 10g', 'gold', '24K', 12],
    ['Gold Coin 24K 20g', 'gold', '24K', 12],
    ['Gold Coin 24K 50g', 'gold', '24K', 12],
    ['Gold Coin 24K 100g', 'gold', '24K', 12],
    ['Gold Bar 24K 10g', 'gold', '24K', 12],
    ['Gold Bar 24K 20g', 'gold', '24K', 12],
    ['Gold Bar 24K 50g', 'gold', '24K', 12],
    ['Gold Bar 24K 100g', 'gold', '24K', 12],
    ['Gold Bar 24K 250g', 'gold', '24K', 12],
    ['Gold Coin 24K Lakshmi', 'gold', '24K', 12],
    ['Gold Coin 24K Ganesh', 'gold', '24K', 12],
    ['Gold Coin 24K Diwali', 'gold', '24K', 12],
    ['Gold Coin 24K Akshaya Tritiya', 'gold', '24K', 12],
    ['Gold Coin 24K Wedding Special', 'gold', '24K', 12],
  ];

  // Silver Products - 100 items
  $silverProducts = [
    // Silver Anklets (15 items)
    ['Silver Anklet 925 Simple', 'silver', '925', 10],
    ['Silver Anklet 925 Designer', 'silver', '925', 10],
    ['Silver Anklet 925 Heavy', 'silver', '925', 10],
    ['Silver Anklet 925 Light', 'silver', '925', 10],
    ['Silver Anklet 925 Bell', 'silver', '925', 10],
    ['Silver Anklet 925 Chain', 'silver', '925', 10],
    ['Silver Anklet 925 Traditional', 'silver', '925', 10],
    ['Silver Anklet 925 Modern', 'silver', '925', 10],
    ['Silver Anklet 925 Antique', 'silver', '925', 10],
    ['Silver Anklet 925 Stone', 'silver', '925', 10],
    ['Silver Anklet 925 Oxidized', 'silver', '925', 10],
    ['Silver Anklet 925 Payal', 'silver', '925', 10],
    ['Silver Anklet 999 Plain', 'silver', '999', 10],
    ['Silver Anklet 999 Heavy', 'silver', '999', 10],
    ['Silver Anklet 999 Simple', 'silver', '999', 10],

    // Silver Chains (15 items)
    ['Silver Chain 925 Simple', 'silver', '925', 2],
    ['Silver Chain 925 Rope', 'silver', '925', 2],
    ['Silver Chain 925 Figaro', 'silver', '925', 2],
    ['Silver Chain 925 Box', 'silver', '925', 2],
    ['Silver Chain 925 Snake', 'silver', '925', 2],
    ['Silver Chain 925 Wheat', 'silver', '925', 2],
    ['Silver Chain 925 Cable', 'silver', '925', 2],
    ['Silver Chain 925 Curb', 'silver', '925', 2],
    ['Silver Chain 925 Designer', 'silver', '925', 2],
    ['Silver Chain 925 Thick', 'silver', '925', 2],
    ['Silver Chain 925 Thin', 'silver', '925', 2],
    ['Silver Chain 925 Long', 'silver', '925', 2],
    ['Silver Chain 925 Short', 'silver', '925', 2],
    ['Silver Chain 999 Plain', 'silver', '999', 2],
    ['Silver Chain 999 Heavy', 'silver', '999', 2],

    // Silver Rings (15 items)
    ['Silver Ring 925 Simple', 'silver', '925', 1],
    ['Silver Ring 925 Designer', 'silver', '925', 1],
    ['Silver Ring 925 Stone', 'silver', '925', 1],
    ['Silver Ring 925 Antique', 'silver', '925', 1],
    ['Silver Ring 925 Oxidized', 'silver', '925', 1],
    ['Silver Ring 925 Wedding', 'silver', '925', 1],
    ['Silver Ring 925 Signet', 'silver', '925', 1],
    ['Silver Ring 925 Band', 'silver', '925', 1],
    ['Silver Ring 925 Statement', 'silver', '925', 1],
    ['Silver Ring 925 Minimal', 'silver', '925', 1],
    ['Silver Ring 925 Floral', 'silver', '925', 1],
    ['Silver Ring 925 Geometric', 'silver', '925', 1],
    ['Silver Ring 999 Plain', 'silver', '999', 1],
    ['Silver Ring 999 Simple', 'silver', '999', 1],
    ['Silver Ring 999 Band', 'silver', '999', 1],

    // Silver Bracelets (10 items)
    ['Silver Bracelet 925 Simple', 'silver', '925', 7],
    ['Silver Bracelet 925 Chain', 'silver', '925', 7],
    ['Silver Bracelet 925 Cuff', 'silver', '925', 7],
    ['Silver Bracelet 925 Bangle', 'silver', '925', 7],
    ['Silver Bracelet 925 Tennis', 'silver', '925', 7],
    ['Silver Bracelet 925 Charm', 'silver', '925', 7],
    ['Silver Bracelet 925 Designer', 'silver', '925', 7],
    ['Silver Bracelet 925 Antique', 'silver', '925', 7],
    ['Silver Bracelet 925 Oxidized', 'silver', '925', 7],
    ['Silver Bracelet 999 Plain', 'silver', '999', 7],

    // Silver Earrings (15 items)
    ['Silver Earrings 925 Studs', 'silver', '925', 5],
    ['Silver Earrings 925 Jhumkas', 'silver', '925', 5],
    ['Silver Earrings 925 Drops', 'silver', '925', 5],
    ['Silver Earrings 925 Hoops', 'silver', '925', 5],
    ['Silver Earrings 925 Chandbali', 'silver', '925', 5],
    ['Silver Earrings 925 Designer', 'silver', '925', 5],
    ['Silver Earrings 925 Stone', 'silver', '925', 5],
    ['Silver Earrings 925 Antique', 'silver', '925', 5],
    ['Silver Earrings 925 Oxidized', 'silver', '925', 5],
    ['Silver Earrings 925 Long', 'silver', '925', 5],
    ['Silver Earrings 925 Short', 'silver', '925', 5],
    ['Silver Earrings 925 Traditional', 'silver', '925', 5],
    ['Silver Earrings 925 Modern', 'silver', '925', 5],
    ['Silver Earrings 999 Simple', 'silver', '999', 5],
    ['Silver Earrings 999 Plain', 'silver', '999', 5],

    // Silver Pendants (10 items)
    ['Silver Pendant 925 Simple', 'silver', '925', 6],
    ['Silver Pendant 925 Designer', 'silver', '925', 6],
    ['Silver Pendant 925 Religious', 'silver', '925', 6],
    ['Silver Pendant 925 Heart', 'silver', '925', 6],
    ['Silver Pendant 925 Flower', 'silver', '925', 6],
    ['Silver Pendant 925 Stone', 'silver', '925', 6],
    ['Silver Pendant 925 Antique', 'silver', '925', 6],
    ['Silver Pendant 925 Oxidized', 'silver', '925', 6],
    ['Silver Pendant 999 Plain', 'silver', '999', 6],
    ['Silver Pendant 999 Simple', 'silver', '999', 6],

    // Silver Coins & Bars (10 items)
    ['Silver Coin 999 10g', 'silver', '999', 12],
    ['Silver Coin 999 25g', 'silver', '999', 12],
    ['Silver Coin 999 50g', 'silver', '999', 12],
    ['Silver Coin 999 100g', 'silver', '999', 12],
    ['Silver Coin 999 Lakshmi', 'silver', '999', 12],
    ['Silver Coin 999 Ganesh', 'silver', '999', 12],
    ['Silver Bar 999 50g', 'silver', '999', 12],
    ['Silver Bar 999 100g', 'silver', '999', 12],
    ['Silver Bar 999 250g', 'silver', '999', 12],
    ['Silver Bar 999 500g', 'silver', '999', 12],

    // Silver Toe Rings (10 items)
    ['Silver Toe Ring 925 Simple', 'silver', '925', 11],
    ['Silver Toe Ring 925 Designer', 'silver', '925', 11],
    ['Silver Toe Ring 925 Stone', 'silver', '925', 11],
    ['Silver Toe Ring 925 Pair', 'silver', '925', 11],
    ['Silver Toe Ring 925 Plain', 'silver', '925', 11],
    ['Silver Toe Ring 925 Antique', 'silver', '925', 11],
    ['Silver Toe Ring 925 Oxidized', 'silver', '925', 11],
    ['Silver Toe Ring 999 Simple', 'silver', '999', 11],
    ['Silver Toe Ring 999 Plain', 'silver', '999', 11],
    ['Silver Toe Ring 999 Pair', 'silver', '999', 11],
  ];

  // Insert gold products
  $goldCount = 0;
  $stmt = $db->prepare("INSERT INTO products (product_code, name, metal_type, purity, category_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");

  foreach ($goldProducts as $index => $product) {
    $productCode = 'GP' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
    $stmt->execute([
      $productCode,
      $product[0],
      $product[1],
      $product[2],
      $product[3]
    ]);
    $goldCount++;
  }

  echo "✓ Added $goldCount gold products\n";

  // Insert silver products
  $silverCount = 0;
  foreach ($silverProducts as $index => $product) {
    $productCode = 'SP' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
    $stmt->execute([
      $productCode,
      $product[0],
      $product[1],
      $product[2],
      $product[3]
    ]);
    $silverCount++;
  }

  echo "✓ Added $silverCount silver products\n";

  $db->commit();

  $total = $goldCount + $silverCount;
  echo "\n✅ Total Products Added: $total\n";
  echo "  - Gold Products: $goldCount\n";
  echo "  - Silver Products: $silverCount\n";
  echo "\nProducts are now available in billing autocomplete!\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "Error: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
