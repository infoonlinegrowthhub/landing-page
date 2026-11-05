<?php
/**
 * Database Connection & Auto-Initializer / Seeder
 * Dynamic E-Commerce Promotional Landing Page (India / INR Edition)
 */

$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalEnv = (
    stripos($httpHost, 'localhost') !== false ||
    stripos($httpHost, '127.0.0.1') !== false ||
    (PHP_SAPI === 'cli' && DIRECTORY_SEPARATOR === '\\')
);

define('IS_LOCAL_ENV', $isLocalEnv);

if (IS_LOCAL_ENV) {
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'ecom_adminlte_landing_db');
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u521707801_landingpage');
    define('DB_PASS', 'omUyl+5W');
    define('DB_NAME', 'u521707801_landingpage');
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (IS_LOCAL_ENV) {
            $dsnServer = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            $pdo = new PDO($dsnServer, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");
        } else {
            $dsnDb = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsnDb, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        $stmt = $pdo->query("SHOW TABLES LIKE 'site_settings'");
        if ($stmt->rowCount() === 0) {
            initializeDatabaseAndSeed($pdo);
        }

        return $pdo;
    } catch (PDOException $e) {
        die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
    }
}

function initializeDatabaseAndSeed(PDO $pdo): void {
    $schemaPath = __DIR__ . '/../database/schema.sql';
    if (file_exists($schemaPath)) {
        $sql = file_get_contents($schemaPath);
        $lines = preg_replace('/^\s*--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $lines)));
        foreach ($statements as $stmtSql) {
            if ($stmtSql !== '') {
                $pdo->exec($stmtSql);
            }
        }
    }

    // 1. Seed default Admin (admin / admin123)
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ((int)$checkAdmin === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password_hash, full_name, role, avatar) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'admin@novapulse.in',
            $hash,
            'Aarav Sharma',
            'Store Administrator',
            '../adminlte/dist/assets/img/user2-160x160.jpg'
        ]);
    }

    // 2. Seed Site Settings (India / INR)
    $checkSettings = $pdo->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
    if ((int)$checkSettings === 0) {
        $defaultSettings = [
            'site_name'            => 'NovaPulse',
            'legal_entity_name'    => 'NovaPulse Technologies India Pvt. Ltd.',
            'canonical_domain'     => 'landingpage.buildmyngo.online',
            'site_tagline'         => 'Premium Smart Tech & Audio Gear in India',
            'logo_text'            => 'Nova<span>Pulse</span>',
            'currency_symbol'      => '₹',
            'primary_color'        => '#4f46e5',
            'accent_color'         => '#f97316',
            'announcement_enabled' => '1',
            'announcement_text'    => '🔥 FESTIVE LAUNCH SALE: Save up to 50% + Free Pan-India Express Delivery!',
            'countdown_end'        => date('Y-m-d H:i:s', strtotime('+5 days')),
            'whatsapp_number'      => '919876543210',
            'contact_email'        => 'support@novapulse.in',
            'contact_phone'        => '+91 98765 43210',
            'contact_address'      => 'Cyber Hub, DLF Phase 2, Gurugram, Haryana 122002',
            'facebook_url'         => 'https://facebook.com',
            'instagram_url'        => 'https://instagram.com',
            'twitter_url'          => 'https://x.com',
            'youtube_url'          => 'https://youtube.com',
            'meta_title'           => 'NovaPulse India — Official Product Showcase & Instant Checkout',
            'meta_description'     => 'Shop high-performance smart audio, wearables, and desk accessories in India. Click Buy Now for instant verified checkout.',
            'footer_disclaimer'    => 'All orders are securely processed through our verified payment links (Razorpay / UPI) and official marketplace partners (Amazon.in & Flipkart).',
            'sticky_bar_enabled'   => '1',
            'custom_head_scripts'  => ''
        ];

        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaultSettings as $k => $v) {
            $stmt->execute([$k, $v]);
        }
    }

    // 3. Seed Categories
    $checkCats = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ((int)$checkCats === 0) {
        $categories = [
            ['Smart Audio', 'smart-audio', 'bi-headphones', 'Studio-grade wireless earbuds and ANC headphones', 1, 1],
            ['Smartwatches', 'wearables', 'bi-smartwatch', 'AMOLED health, fitness, and calling smartwatches', 2, 1],
            ['Power & GaN', 'power-charging', 'bi-lightning-charge-fill', 'Fast wireless chargers and magnetic power banks', 3, 1],
            ['Desk Setup', 'desk-setup', 'bi-laptop', 'Ergonomic workspace lighting and keyboards', 4, 1],
        ];
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($categories as $c) {
            $stmt->execute($c);
        }
    }

    // 4. Seed Products (INR Pricing)
    $checkProds = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ((int)$checkProds === 0) {
        $products = [
            [
                'category_id'       => 1,
                'name'              => 'AeroSonic Pro ANC Spatial Headphones',
                'slug'              => 'aerosonic-pro-anc-headphones',
                'subtitle'          => '45dB Hybrid ANC • 60-Hour Battery • Hi-Res LDAC Audio',
                'short_description' => 'Experience studio-grade acoustics with adaptive 45dB noise cancellation, memory-foam ear cushions, and ultra-low latency Bluetooth 5.4.',
                'full_description'  => "Engineered for audiophiles, remote professionals, and commuters, the AeroSonic Pro ANC combines custom 40mm titanium-coated drivers with real-time acoustic calibration and 6-mic AI clear calling.",
                'price'             => 2499.00,
                'compare_at_price'  => 4999.00,
                'discount_badge'    => '50% OFF',
                'main_image'        => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1000&q=85',
                'gallery_images'    => json_encode([
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1000&q=85',
                    'https://images.unsplash.com/photo-1583394838336-acd977736f90?auto=format&fit=crop&w=1000&q=85',
                    'https://images.unsplash.com/photo-1484704849700-f032a568e944?auto=format&fit=crop&w=1000&q=85'
                ]),
                'features_json'     => json_encode([
                    '45dB Adaptive Hybrid Active Noise Cancellation + Transparency Mode',
                    'Up to 60 Hours Playback (10-min Type-C Fast Charge = 8 Hours)',
                    'Hi-Res Wireless Audio with Spatial Head Tracking & Deep Bass',
                    'Dual-Device Multipoint Connection (Laptop + Smartphone)',
                    '1-Year Doorstep Warranty + Free Carry Case Included'
                ]),
                'external_buy_url'  => 'https://rzp.io/l/aerosonic-pro-anc',
                'buy_button_text'   => 'Buy Now — ₹2,499',
                'platform_label'    => 'Razorpay / UPI Verified',
                'open_in_new_tab'   => 1,
                'rating'            => 4.9,
                'reviews_count'     => 1420,
                'stock_status'      => 'In Stock',
                'is_featured'       => 1,
                'is_spotlight'      => 1,
                'is_active'         => 1,
                'sort_order'        => 1,
                'clicks_count'      => 184
            ],
            [
                'category_id'       => 2,
                'name'              => 'ChronoVibe Ultra AMOLED Smartwatch',
                'slug'              => 'chronovibe-ultra-titanium-smartwatch',
                'subtitle'          => '1.95" Sapphire AMOLED • Bluetooth Calling • 14-Day Battery',
                'short_description' => 'Titanium-alloy bezel with 2000-nit Always-On AMOLED screen, continuous SpO2 & heart rate tracking, and 100+ sports modes.',
                'full_description'  => "Built to handle workouts and boardroom meetings alike. ChronoVibe Ultra tracks heart rate, blood oxygen, sleep stages, and supports crystal-clear Bluetooth calling directly from your wrist.",
                'price'             => 3499.00,
                'compare_at_price'  => 5999.00,
                'discount_badge'    => 'BEST SELLER',
                'main_image'        => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=85',
                'gallery_images'    => json_encode([
                    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=85',
                    'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?auto=format&fit=crop&w=900&q=85'
                ]),
                'features_json'     => json_encode([
                    '1.95" Always-On Super AMOLED Display (2000 Nits Brightness)',
                    'Single-Chip Bluetooth 5.3 Calling with AI Noise Reduction',
                    '24/7 Heart Rate, Blood Oxygen (SpO2), Stress & Sleep Monitor',
                    'IP68 Water Resistant with 14-Day Battery Life'
                ]),
                'external_buy_url'  => 'https://www.amazon.in/dp/B0EXAMPLE01',
                'buy_button_text'   => 'Buy on Amazon.in',
                'platform_label'    => 'Amazon.in Prime',
                'open_in_new_tab'   => 1,
                'rating'            => 4.8,
                'reviews_count'     => 875,
                'stock_status'      => 'In Stock',
                'is_featured'       => 1,
                'is_spotlight'      => 0,
                'is_active'         => 1,
                'sort_order'        => 2,
                'clicks_count'      => 129
            ],
            [
                'category_id'       => 3,
                'name'              => 'MagVault 3-in-1 Qi2 Fast Wireless Charger',
                'slug'              => 'magvault-qi2-charging-station',
                'subtitle'          => '15W Fast Magnetic Wireless • Active Cooling • Foldable Stand',
                'short_description' => 'Charge your smartphone, smartwatch, and earbuds simultaneously at full 15W speed with built-in thermal cooling.',
                'full_description'  => "Keep your desk and bedside table completely wire-free. MagVault snaps magnetically in portrait or landscape mode while protecting battery health.",
                'price'             => 1499.00,
                'compare_at_price'  => 2499.00,
                'discount_badge'    => '40% OFF',
                'main_image'        => 'https://images.unsplash.com/photo-1585338107529-13afc5f02586?auto=format&fit=crop&w=900&q=85',
                'gallery_images'    => json_encode([
                    'https://images.unsplash.com/photo-1585338107529-13afc5f02586?auto=format&fit=crop&w=900&q=85'
                ]),
                'features_json'     => json_encode([
                    'Certified 15W Fast Magnetic Wireless Alignment',
                    'Charges Phone + Watch + Earbuds Simultaneously',
                    'Foldable Pocket-Ready Design for Travel',
                    'Includes 33W Type-C PD Fast Adapter & Braided Cable'
                ]),
                'external_buy_url'  => 'https://rzp.io/l/magvault-qi2-india',
                'buy_button_text'   => 'Pay via UPI / Card',
                'platform_label'    => 'Razorpay Direct Link',
                'open_in_new_tab'   => 1,
                'rating'            => 4.9,
                'reviews_count'     => 530,
                'stock_status'      => 'Limited Stock',
                'is_featured'       => 1,
                'is_spotlight'      => 0,
                'is_active'         => 1,
                'sort_order'        => 3,
                'clicks_count'      => 96
            ],
            [
                'category_id'       => 4,
                'name'              => 'LumiBar Pro Asymmetric Monitor Light Bar',
                'slug'              => 'lumibar-pro-monitor-light-bar',
                'subtitle'          => 'Zero Screen Glare • Wireless Control Dial • Eye-Care LEDs',
                'short_description' => 'Eliminate eye strain during late-night work or gaming with asymmetric optics, stepless dimming, and RGB ambient backlight.',
                'full_description'  => "Designed for developers, designers, and creators. LumiBar Pro illuminates your desk workspace evenly without any reflection on your monitor.",
                'price'             => 1799.00,
                'compare_at_price'  => 2999.00,
                'discount_badge'    => 'HOT DEAL',
                'main_image'        => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=900&q=85',
                'gallery_images'    => json_encode([
                    'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=900&q=85'
                ]),
                'features_json'     => json_encode([
                    'Asymmetric Optical Design — Zero Screen Glare',
                    'Wireless Aluminum Desktop Control Puck Included',
                    'CRI Ra>98 True Color + Dual Ambient Backlight',
                    'Universal Counterweighted Clamp Fits All Monitors'
                ]),
                'external_buy_url'  => 'https://www.flipkart.com/',
                'buy_button_text'   => 'Buy on Flipkart',
                'platform_label'    => 'Flipkart Assured',
                'open_in_new_tab'   => 1,
                'rating'            => 4.8,
                'reviews_count'     => 412,
                'stock_status'      => 'In Stock',
                'is_featured'       => 1,
                'is_spotlight'      => 0,
                'is_active'         => 1,
                'sort_order'        => 4,
                'clicks_count'      => 74
            ],
            [
                'category_id'       => 1,
                'name'              => 'PulsePods Air Open-Ear Sport Earbuds',
                'slug'              => 'pulsepods-air-open-ear-earbuds',
                'subtitle'          => 'Featherlight Earhooks • IP68 Sweatproof • Deep Bass',
                'short_description' => 'Stay aware of traffic and surroundings while enjoying rich 16.2mm dynamic bass with zero ear-canal pain.',
                'full_description'  => "Ideal for running, cycling, and all-day office calls. PulsePods Air rest comfortably outside the ear canal with secure memory-titanium hooks.",
                'price'             => 1299.00,
                'compare_at_price'  => 2199.00,
                'discount_badge'    => 'NEW ARRIVAL',
                'main_image'        => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=900&q=85',
                'gallery_images'    => json_encode([
                    'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=900&q=85'
                ]),
                'features_json'     => json_encode([
                    'Open-Ear Comfort with Zero Ear Canal Fatigue',
                    '16.2mm Dynamic Drivers with Directional Audio',
                    'IP68 Sweat & Dust Resistance for Workouts',
                    '42 Hours Total Playback with Fast Type-C Case'
                ]),
                'external_buy_url'  => 'https://rzp.io/l/pulsepods-air',
                'buy_button_text'   => 'Buy Now — ₹1,299',
                'platform_label'    => 'Official Checkout Link',
                'open_in_new_tab'   => 1,
                'rating'            => 4.7,
                'reviews_count'     => 318,
                'stock_status'      => 'In Stock',
                'is_featured'       => 1,
                'is_spotlight'      => 0,
                'is_active'         => 1,
                'sort_order'        => 5,
                'clicks_count'      => 61
            ],
            [
                'category_id'       => 4,
                'name'              => 'KeyChronos K8 Pro Wireless Mechanical Keyboard',
                'slug'              => 'keychronos-k8-pro-mechanical-keyboard',
                'subtitle'          => 'CNC Aluminum Frame • Hot-Swappable • Tri-Mode Wireless',
                'short_description' => 'Ultra-slim gasket-mounted wireless mechanical keyboard for Mac & Windows with PBT keycaps and 3-device Bluetooth + 2.4GHz.',
                'full_description'  => "Crafted from solid CNC aluminum, the KeyChronos K8 Pro delivers a premium tactile typing experience with 180 hours of battery life.",
                'price'             => 2999.00,
                'compare_at_price'  => 4499.00,
                'discount_badge'    => 'SAVE ₹1,500',
                'main_image'        => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=85',
                'gallery_images'    => json_encode([
                    'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=85'
                ]),
                'features_json'     => json_encode([
                    'Tri-Mode: 2.4GHz Wireless, Bluetooth 5.3 & Wired Type-C',
                    'Hot-Swappable Tactile Mechanical Switches',
                    'Double-Shot PBT Keycaps (Windows & Mac Keys Included)',
                    '4000mAh Battery for up to 180 Hours of Work'
                ]),
                'external_buy_url'  => 'https://www.amazon.in/dp/B0EXAMPLE02',
                'buy_button_text'   => 'Buy Now — ₹2,999',
                'platform_label'    => 'Amazon.in Choice',
                'open_in_new_tab'   => 1,
                'rating'            => 4.9,
                'reviews_count'     => 645,
                'stock_status'      => 'In Stock',
                'is_featured'       => 1,
                'is_spotlight'      => 0,
                'is_active'         => 1,
                'sort_order'        => 6,
                'clicks_count'      => 88
            ]
        ];

        $sql = "INSERT INTO products (
            category_id, name, slug, subtitle, short_description, full_description,
            price, compare_at_price, discount_badge, main_image, gallery_images,
            features_json, external_buy_url, buy_button_text, platform_label,
            open_in_new_tab, rating, reviews_count, stock_status,
            is_featured, is_spotlight, is_active, sort_order, clicks_count
        ) VALUES (
            :category_id, :name, :slug, :subtitle, :short_description, :full_description,
            :price, :compare_at_price, :discount_badge, :main_image, :gallery_images,
            :features_json, :external_buy_url, :buy_button_text, :platform_label,
            :open_in_new_tab, :rating, :reviews_count, :stock_status,
            :is_featured, :is_spotlight, :is_active, :sort_order, :clicks_count
        )";
        $stmt = $pdo->prepare($sql);
        foreach ($products as $p) {
            $stmt->execute($p);
        }
    }

    // 5. Seed Hero Banner (INR)
    $checkHero = $pdo->query("SELECT COUNT(*) FROM hero_banners")->fetchColumn();
    if ((int)$checkHero === 0) {
        $stmt = $pdo->prepare("INSERT INTO hero_banners (
            badge_text, title, highlight_text, subtitle, price_tag, compare_price_tag,
            cta_text, cta_external_url, secondary_cta_text, secondary_cta_url,
            hero_image, trust_text, product_id, is_active, sort_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'LAUNCH OFFER • FLAT 50% OFF',
            'Pure Studio Sound.',
            'Zero Distractions.',
            'Experience the AeroSonic Pro ANC with 45dB active noise cancellation, spatial audio, and 60-hour battery life. Click Buy Now for instant UPI, Card, or Partner Checkout.',
            '₹2,499',
            '₹4,999',
            'Buy Now — ₹2,499',
            'https://rzp.io/l/aerosonic-pro-anc',
            'View All Products',
            '#products',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1100&q=85',
            '4.9/5 Rated by 1,400+ Indian Buyers • Free Express Delivery Across India',
            1,
            1,
            1
        ]);
    }

    // 6. Seed Features / Benefits
    $checkFeat = $pdo->query("SELECT COUNT(*) FROM features")->fetchColumn();
    if ((int)$checkFeat === 0) {
        $features = [
            ['bi-shield-check', '100% Verified Checkout Links', 'Every Buy Now button connects directly to our official Razorpay/UPI payment links or verified Amazon.in & Flipkart stores.', 'Secure', 1, 1],
            ['bi-truck', 'Free Pan-India Express Delivery', 'Fast 24-hour dispatch from our Indian fulfillment centers with live BlueDart & Delhivery tracking.', 'Fast Shipping', 2, 1],
            ['bi-award', '1-Year Doorstep Warranty', 'All products include 12-month official India warranty with hassle-free doorstep pickup and replacement.', 'Warranty', 3, 1],
            ['bi-arrow-repeat', '7-Day Easy Replacement', 'Received a defective unit? Enjoy no-questions-asked 7-day instant replacement across all pin codes.', 'Assured', 4, 1],
        ];
        $stmt = $pdo->prepare("INSERT INTO features (icon, title, description, badge, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($features as $f) {
            $stmt->execute($f);
        }
    }

    // 7. Seed Testimonials (Indian Buyers)
    $checkTesti = $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
    if ((int)$checkTesti === 0) {
        $testimonials = [
            [
                'Rohan Mehta',
                'Music Producer, Mumbai',
                'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=250&q=80',
                5,
                'Unbeatable ANC quality at ₹2,499!',
                'Ordered the AeroSonic Pro via the Razorpay link using UPI. Delivery to Mumbai took just 2 days, and the noise cancellation easily beats headphones costing ₹8,000+.',
                'AeroSonic Pro ANC Spatial Headphones',
                1, 1, 1
            ],
            [
                'Ananya Nair',
                'UX Designer, Bengaluru',
                'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=250&q=80',
                5,
                'Super smooth checkout & premium build',
                'Loved how quick the direct payment link was. The MagVault 3-in-1 wireless charger keeps my work desk in Bengaluru completely clutter-free.',
                'MagVault 3-in-1 Qi2 Fast Wireless Charger',
                1, 1, 2
            ],
            [
                'Vikramaditya Rao',
                'Software Architect, Hyderabad',
                'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=250&q=80',
                5,
                'Zero eye strain during late-night coding',
                'The LumiBar Pro completely eliminated glare on my monitor. The wireless rotary dial feels super premium. Highly recommended!',
                'LumiBar Pro Monitor Light Bar',
                1, 1, 3
            ]
        ];
        $stmt = $pdo->prepare("INSERT INTO testimonials (customer_name, customer_role, customer_avatar, rating, review_title, review_text, product_name, is_verified, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($testimonials as $t) {
            $stmt->execute($t);
        }
    }

    // 8. Seed FAQs
    $checkFaq = $pdo->query("SELECT COUNT(*) FROM faqs")->fetchColumn();
    if ((int)$checkFaq === 0) {
        $faqs = [
            [
                'How does the "Buy Now" checkout process work?',
                'When you click any "Buy Now" button on this page, you are securely redirected to our official payment link (Razorpay UPI/Card/NetBanking) or verified marketplace listing (Amazon.in / Flipkart) to complete your order.',
                'Ordering', 1, 1
            ],
            [
                'Can I pay via UPI (GPay, PhonePe, Paytm) or Cards?',
                'Yes! Our external payment links support all UPI apps (Google Pay, PhonePe, Paytm, CRED), Credit/Debit Cards, NetBanking, and No-Cost EMI.',
                'Payment', 2, 1
            ],
            [
                'How long does delivery take across India?',
                'Orders are dispatched within 24 hours via BlueDart/Delhivery. Metro cities receive delivery in 2–3 days, and rest of India within 3–5 business days.',
                'Shipping', 3, 1
            ],
            [
                'How do I claim warranty or support?',
                'Every product is backed by a 1-Year Official India Doorstep Warranty. Simply contact us via WhatsApp (+91 98765 43210) or the inquiry form below with your order receipt.',
                'Warranty', 4, 1
            ]
        ];
        $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
        foreach ($faqs as $q) {
            $stmt->execute($q);
        }
    }

    // 9. Seed Sample Click Logs
    $checkClicks = $pdo->query("SELECT COUNT(*) FROM click_logs")->fetchColumn();
    if ((int)$checkClicks === 0) {
        $placements = ['hero', 'spotlight', 'card', 'modal', 'sticky_bar'];
        $stmt = $pdo->prepare("INSERT INTO click_logs (product_id, target_url, placement, ip_address, user_agent, referrer, clicked_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        for ($d = 6; $d >= 0; $d--) {
            $dailyCount = rand(8, 22);
            for ($i = 0; $i < $dailyCount; $i++) {
                $pid = rand(1, 6);
                $placement = $placements[array_rand($placements)];
                $time = date('Y-m-d H:i:s', strtotime("-$d days -" . rand(0, 18) . " hours -" . rand(0, 55) . " minutes"));
                $stmt->execute([
                    $pid,
                    'https://rzp.io/l/product_' . $pid,
                    $placement,
                    '103.21.124.' . rand(10, 240),
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/134.0',
                    'https://google.co.in',
                    $time
                ]);
            }
        }
    }

    // 10. Seed Sample Inquiries
    $checkInq = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
    if ((int)$checkInq === 0) {
        $inquiries = [
            ['Priya Verma', 'priya.verma@example.in', '+91 98201 44510', 'AeroSonic Pro ANC Spatial Headphones', 'Corporate bulk order for 15 units', 'Hi team, we want to order 15 units of the AeroSonic Pro with GST invoice for our Bengaluru office. Please share a bulk payment link.', 'Unread'],
            ['Karthik Iyer', 'karthik.iyer@example.in', '+91 98402 11920', 'MagVault 3-in-1 Qi2 Fast Wireless Charger', 'Compatibility with iPhone 16 & Samsung S24', 'Does the MagVault 3-in-1 charger include the 33W Indian pin adapter in the box?', 'Read'],
        ];
        $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, phone, product_interest, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($inquiries as $inq) {
            $stmt->execute($inq);
        }
    }
}
