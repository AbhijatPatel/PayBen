<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';

class MobilityController {

    /**
     * Get estimated ride options for Uber and Rapido
     */
    public static function estimate() {
        $pickup = trim($_GET['pickup'] ?? 'Connaught Place, New Delhi');
        $drop = trim($_GET['drop'] ?? 'Indira Gandhi International Airport, Terminal 3');

        // Estimate distance baseline (~16 km)
        $distanceKm = 16.5;

        $rides = [
            'uber' => [
                'provider' => 'Uber',
                'logo' => 'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?w=80&auto=format&fit=crop&q=80',
                'categories' => [
                    [
                        'id' => 'uber_go',
                        'name' => 'Uber Go',
                        'desc' => 'Affordable, compact rides with top-rated drivers',
                        'capacity' => 4,
                        'eta' => '4 mins away',
                        'estimated_fare' => 380.00,
                        'deep_link' => 'https://m.uber.com/ul/?action=setPickup&pickup[my_location]=true'
                    ],
                    [
                        'id' => 'uber_auto',
                        'name' => 'Uber Auto',
                        'desc' => 'Reliable, metered auto rickshaws at your doorstep',
                        'capacity' => 3,
                        'eta' => '2 mins away',
                        'estimated_fare' => 195.00,
                        'deep_link' => 'https://m.uber.com/ul/?action=setPickup&pickup[my_location]=true'
                    ],
                    [
                        'id' => 'uber_moto',
                        'name' => 'Uber Moto',
                        'desc' => 'Fast, affordable bike rides through heavy city traffic',
                        'capacity' => 1,
                        'eta' => '3 mins away',
                        'estimated_fare' => 110.00,
                        'deep_link' => 'https://m.uber.com/ul/?action=setPickup&pickup[my_location]=true'
                    ],
                    [
                        'id' => 'uber_premier',
                        'name' => 'Uber Premier',
                        'desc' => 'Premium sedans with top-rated driver partners',
                        'capacity' => 4,
                        'eta' => '6 mins away',
                        'estimated_fare' => 520.00,
                        'deep_link' => 'https://m.uber.com/ul/?action=setPickup&pickup[my_location]=true'
                    ]
                ]
            ],
            'rapido' => [
                'provider' => 'Rapido',
                'logo' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=80&auto=format&fit=crop&q=80',
                'categories' => [
                    [
                        'id' => 'rapido_bike',
                        'name' => 'Rapido Bike Taxi',
                        'desc' => 'Fastest way to beat city jams with helmet provided',
                        'capacity' => 1,
                        'eta' => '2 mins away',
                        'estimated_fare' => 95.00,
                        'deep_link' => 'https://www.rapido.bike'
                    ],
                    [
                        'id' => 'rapido_auto',
                        'name' => 'Rapido Auto',
                        'desc' => 'Hassle-free auto rides with zero bargaining',
                        'capacity' => 3,
                        'eta' => '3 mins away',
                        'estimated_fare' => 180.00,
                        'deep_link' => 'https://www.rapido.bike'
                    ],
                    [
                        'id' => 'rapido_cab',
                        'name' => 'Rapido Cab Economy',
                        'desc' => 'Air-conditioned compact cab for pocket-friendly travel',
                        'capacity' => 4,
                        'eta' => '5 mins away',
                        'estimated_fare' => 350.00,
                        'deep_link' => 'https://www.rapido.bike'
                    ]
                ]
            ]
        ];

        jsonResponse([
            'success' => true,
            'pickup' => $pickup,
            'drop' => $drop,
            'estimated_distance_km' => $distanceKm,
            'disclaimer' => 'Fare estimates are approximate. Booking completes on the official provider application.',
            'mobility' => $rides
        ]);
    }

    /**
     * Log ride redirect and return official deep link
     */
    public static function logRedirect() {
        $body = getJsonBody();
        $provider = strtolower(trim($body['provider'] ?? 'uber'));
        $rideType = trim($body['ride_type'] ?? 'uber_go');
        $pickup = trim($body['pickup'] ?? 'Current Location');
        $drop = trim($body['drop'] ?? 'Destination');
        $fare = (float)($body['estimated_fare'] ?? 0);
        $officialUrl = $provider === 'rapido' ? 'https://www.rapido.bike' : 'https://m.uber.com';

        $userId = null;
        try {
            $user = optionalAuth();
            if ($user) $userId = (int)$user['id'];
        } catch (\Throwable $tb) {
            // guest allowed
        }

        $pdo = getDBConnection();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO mobility_redirect_logs 
                (user_id, provider, ride_type, pickup_location, destination_location, estimated_fare, official_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $provider === 'rapido' ? 'rapido' : 'uber',
                $rideType,
                $pickup,
                $drop,
                $fare,
                $officialUrl
            ]);
        } catch (\Throwable $e) {
            // logged gracefully
        }

        jsonResponse([
            'success' => true,
            'provider' => ucfirst($provider),
            'ride_type' => $rideType,
            'official_url' => $officialUrl,
            'message' => "Redirecting to official {$provider} application."
        ]);
    }
}
