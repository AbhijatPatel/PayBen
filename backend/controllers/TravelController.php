<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/middleware/auth.php';

class TravelController {

    /**
     * Train Ticket Search (Sandbox Demonstration + IRCTC Official Access)
     */
    public static function searchTrains() {
        $from = trim($_GET['from'] ?? 'NDLS - New Delhi');
        $to = trim($_GET['to'] ?? 'BSB - Varanasi Junction');
        $date = trim($_GET['date'] ?? date('Y-m-d', strtotime('+1 day')));
        $quota = trim($_GET['quota'] ?? 'GN');

        $catalog = [
            [
                'train_number' => '22436',
                'train_name' => 'Vande Bharat Express',
                'departure_time' => '06:00',
                'arrival_time' => '14:00',
                'duration' => '8h 00m',
                'from_station' => $from,
                'to_station' => $to,
                'running_days' => ['Mon', 'Tue', 'Wed', 'Fri', 'Sat', 'Sun'],
                'classes' => [
                    ['code' => 'CC', 'name' => 'AC Chair Car', 'fare' => 1750.00, 'availability' => 'AVAILABLE 42', 'status_type' => 'CONFIRMED'],
                    ['code' => 'EC', 'name' => 'Exec. Chair Car', 'fare' => 3300.00, 'availability' => 'AVAILABLE 14', 'status_type' => 'CONFIRMED']
                ]
            ],
            [
                'train_number' => '12424',
                'train_name' => 'Rajdhani Express',
                'departure_time' => '16:10',
                'arrival_time' => '01:55',
                'duration' => '9h 45m',
                'from_station' => $from,
                'to_station' => $to,
                'running_days' => ['Daily'],
                'classes' => [
                    ['code' => '3A', 'name' => 'AC 3 Tier', 'fare' => 1480.00, 'availability' => 'RAC 12', 'status_type' => 'RAC'],
                    ['code' => '2A', 'name' => 'AC 2 Tier', 'fare' => 2120.00, 'availability' => 'AVAILABLE 08', 'status_type' => 'CONFIRMED'],
                    ['code' => '1A', 'name' => 'AC 1st Class', 'fare' => 3560.00, 'availability' => 'WL 4', 'status_type' => 'WL']
                ]
            ],
            [
                'train_number' => '12560',
                'train_name' => 'Shiv Ganga Superfast Express',
                'departure_time' => '20:05',
                'arrival_time' => '06:10',
                'duration' => '10h 05m',
                'from_station' => $from,
                'to_station' => $to,
                'running_days' => ['Daily'],
                'classes' => [
                    ['code' => 'SL', 'name' => 'Sleeper Class', 'fare' => 440.00, 'availability' => 'AVAILABLE 86', 'status_type' => 'CONFIRMED'],
                    ['code' => '3A', 'name' => 'AC 3 Tier', 'fare' => 1175.00, 'availability' => 'AVAILABLE 23', 'status_type' => 'CONFIRMED'],
                    ['code' => '2A', 'name' => 'AC 2 Tier', 'fare' => 1680.00, 'availability' => 'AVAILABLE 06', 'status_type' => 'CONFIRMED']
                ]
            ],
            [
                'train_number' => '12582',
                'train_name' => 'Banaras Superfast Express',
                'departure_time' => '22:50',
                'arrival_time' => '10:00',
                'duration' => '11h 10m',
                'from_station' => $from,
                'to_station' => $to,
                'running_days' => ['Daily'],
                'classes' => [
                    ['code' => 'SL', 'name' => 'Sleeper Class', 'fare' => 415.00, 'availability' => 'AVAILABLE 112', 'status_type' => 'CONFIRMED'],
                    ['code' => '3E', 'name' => '3 AC Economy', 'fare' => 995.00, 'availability' => 'AVAILABLE 34', 'status_type' => 'CONFIRMED'],
                    ['code' => '3A', 'name' => 'AC 3 Tier', 'fare' => 1120.00, 'availability' => 'AVAILABLE 18', 'status_type' => 'CONFIRMED']
                ]
            ]
        ];

        jsonResponse([
            'success' => true,
            'source' => $from,
            'destination' => $to,
            'travel_date' => $date,
            'quota' => $quota,
            'is_sandbox' => true,
            'official_irctc_url' => 'https://www.irctc.co.in/nget/train-search',
            'trains' => $catalog
        ]);
    }

    /**
     * Bus Ticket Search
     */
    public static function searchBuses() {
        $from = trim($_GET['from'] ?? 'Delhi');
        $to = trim($_GET['to'] ?? 'Jaipur');
        $date = trim($_GET['date'] ?? date('Y-m-d', strtotime('+1 day')));

        $buses = [
            [
                'bus_id' => 'BUS-INT-101',
                'operator_name' => 'IntrCity SmartBus',
                'bus_type' => 'Bharat Benz AC Sleeper (2+1)',
                'departure_time' => '21:30',
                'arrival_time' => '03:15',
                'duration' => '5h 45m',
                'rating' => '4.8',
                'ratings_count' => 1420,
                'fare' => 749.00,
                'seats_available' => 18,
                'boarding_points' => ['ISBT Kashmiri Gate (21:30)', 'Dhaula Kuan (22:00)', 'IFFCO Chowk Gurgaon (22:45)'],
                'dropping_points' => ['Sindhi Camp (03:15)', 'Narayan Singh Circle (03:30)']
            ],
            [
                'bus_id' => 'BUS-ZNG-204',
                'operator_name' => 'Zingbus Electric Lounge',
                'bus_type' => 'Premium Multi-Axle Volvo AC (2+2)',
                'departure_time' => '07:00',
                'arrival_time' => '12:30',
                'duration' => '5h 30m',
                'rating' => '4.7',
                'ratings_count' => 980,
                'fare' => 620.00,
                'seats_available' => 24,
                'boarding_points' => ['Majnu Ka Tilla (07:00)', 'Akshardham (07:35)', 'Mahipalpur (08:15)'],
                'dropping_points' => ['200 Ft Bypass (12:15)', 'Polo Victory (12:30)']
            ],
            [
                'bus_id' => 'BUS-VRL-309',
                'operator_name' => 'VRL Travels',
                'bus_type' => 'I-Shift Multi-Axle Volvo AC Sleeper',
                'departure_time' => '23:00',
                'arrival_time' => '04:45',
                'duration' => '5h 45m',
                'rating' => '4.6',
                'ratings_count' => 2100,
                'fare' => 890.00,
                'seats_available' => 11,
                'boarding_points' => ['Kashmiri Gate (23:00)', 'Karol Bagh (23:30)'],
                'dropping_points' => ['Sindhi Camp Bus Stand (04:45)']
            ]
        ];

        jsonResponse([
            'success' => true,
            'source' => $from,
            'destination' => $to,
            'travel_date' => $date,
            'is_sandbox' => true,
            'official_redbus_url' => 'https://www.redbus.in',
            'buses' => $buses
        ]);
    }

    /**
     * Flight Ticket Search
     */
    public static function searchFlights() {
        $from = trim($_GET['from'] ?? 'DEL - New Delhi');
        $to = trim($_GET['to'] ?? 'BOM - Mumbai');
        $date = trim($_GET['date'] ?? date('Y-m-d', strtotime('+3 days')));

        $flights = [
            [
                'flight_id' => 'FL-6E-204',
                'airline' => 'IndiGo',
                'flight_number' => '6E-204',
                'aircraft' => 'Airbus A321neo',
                'departure_time' => '07:15',
                'arrival_time' => '09:25',
                'duration' => '2h 10m',
                'stops' => 'Non-stop',
                'fare' => 4550.00,
                'seats_left' => 9,
                'cabin_baggage' => '7 kg',
                'checkin_baggage' => '15 kg',
                'refundable' => true
            ],
            [
                'flight_id' => 'FL-AI-865',
                'airline' => 'Air India',
                'flight_number' => 'AI-865',
                'aircraft' => 'Boeing 787-8 Dreamliner',
                'departure_time' => '10:00',
                'arrival_time' => '12:15',
                'duration' => '2h 15m',
                'stops' => 'Non-stop',
                'fare' => 5200.00,
                'seats_left' => 5,
                'cabin_baggage' => '7 kg',
                'checkin_baggage' => '20 kg (Complimentary Meal)',
                'refundable' => true
            ],
            [
                'flight_id' => 'FL-UK-995',
                'airline' => 'Vistara',
                'flight_number' => 'UK-995',
                'aircraft' => 'Airbus A320neo',
                'departure_time' => '18:30',
                'arrival_time' => '20:45',
                'duration' => '2h 15m',
                'stops' => 'Non-stop',
                'fare' => 5650.00,
                'seats_left' => 4,
                'cabin_baggage' => '7 kg',
                'checkin_baggage' => '15 kg',
                'refundable' => true
            ],
            [
                'flight_id' => 'FL-QP-1301',
                'airline' => 'Akasa Air',
                'flight_number' => 'QP-1301',
                'aircraft' => 'Boeing 737 MAX 8',
                'departure_time' => '21:40',
                'arrival_time' => '23:55',
                'duration' => '2h 15m',
                'stops' => 'Non-stop',
                'fare' => 3999.00,
                'seats_left' => 12,
                'cabin_baggage' => '7 kg',
                'checkin_baggage' => '15 kg',
                'refundable' => false
            ]
        ];

        jsonResponse([
            'success' => true,
            'source' => $from,
            'destination' => $to,
            'departure_date' => $date,
            'is_sandbox' => true,
            'flights' => $flights
        ]);
    }

    /**
     * Hotel Search
     */
    public static function searchHotels() {
        $city = trim($_GET['city'] ?? 'Goa');
        $checkin = trim($_GET['checkin'] ?? date('Y-m-d', strtotime('+2 days')));
        $checkout = trim($_GET['checkout'] ?? date('Y-m-d', strtotime('+4 days')));

        $hotels = [
            [
                'hotel_id' => 'HTL-GOA-01',
                'name' => 'The Grand Palm Resort & Spa',
                'location' => 'Calangute, North Goa (250m from Beach)',
                'star_rating' => 5,
                'user_rating' => 4.8,
                'reviews_count' => 1840,
                'price_per_night' => 4200.00,
                'amenities' => ['Infinity Pool', 'Free Breakfast', 'High-Speed WiFi', 'Beach Access', 'AC'],
                'room_type' => 'Deluxe Garden View Room'
            ],
            [
                'hotel_id' => 'HTL-GOA-02',
                'name' => 'Ocean Breeze Heritage Boutique',
                'location' => 'Candolim, North Goa',
                'star_rating' => 4,
                'user_rating' => 4.6,
                'reviews_count' => 920,
                'price_per_night' => 2650.00,
                'amenities' => ['Swimming Pool', 'Free WiFi', 'Restaurant & Bar', 'Air Conditioning'],
                'room_type' => 'Superior Pool Facing Suite'
            ],
            [
                'hotel_id' => 'HTL-GOA-03',
                'name' => 'Blue Lagoon Coastal Villa',
                'location' => 'Benaulim, South Goa',
                'star_rating' => 4,
                'user_rating' => 4.7,
                'reviews_count' => 640,
                'price_per_night' => 3100.00,
                'amenities' => ['Private Balcony', 'Free Breakfast', 'Ayurvedic Spa', 'Free Parking'],
                'room_type' => 'Executive Coastal Balcony Room'
            ]
        ];

        jsonResponse([
            'success' => true,
            'city' => $city,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'is_sandbox' => true,
            'hotels' => $hotels
        ]);
    }
}
