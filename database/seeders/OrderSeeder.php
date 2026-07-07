<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderPlayer;
use App\Models\OrderStageLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

// class OrderSeeder extends Seeder
// {
//     /**
//      * Seed realistic demo orders covering all stages and priorities.
//      * Useful for development and UI testing.
//      */
//     public function run(): void
//     {
//         $manager = User::where('role', 'pipeline_manager')->firstOrFail();

//         $orders = [
//             [
//                 'customer_name'  => 'FC United Academy',
//                 'customer_phone' => '+60 12-345 6789',
//                 'quantity'       => 22,
//                 'product_type'   => 'jersey',
//                 'order_date'     => now()->subDays(5),
//                 'delivery_date'  => now()->addDays(2),
//                 'priority'       => 'critical',
//                 'stage'          => 'design',
//                 'status'         => 'in_progress',
//                 'details'        => '<p>Home kit: red body, white sleeves. Sponsor: <strong>TechCorp</strong> on chest. Player names on back. Font: bold block.</p>',
//                 'players'        => [
//                     ['player_name' => 'Ahmad Razif',    'jersey_number' => '1',  'size' => 'L'],
//                     ['player_name' => 'Khairul Azlan',  'jersey_number' => '5',  'size' => 'M'],
//                     ['player_name' => 'Mohamad Farid',  'jersey_number' => '10', 'size' => 'XL'],
//                     ['player_name' => 'Izzatul Hakim',  'jersey_number' => '7',  'size' => 'M'],
//                     ['player_name' => 'Syafiq Rahman',  'jersey_number' => '11', 'size' => 'L'],
//                 ],
//             ],
//             [
//                 'customer_name'  => 'Kelab Bola Sepak Wira',
//                 'customer_phone' => '+60 11-876 5432',
//                 'quantity'       => 35,
//                 'product_type'   => 'jersey',
//                 'order_date'     => now()->subDays(3),
//                 'delivery_date'  => now()->addDays(4),
//                 'priority'       => 'rush',
//                 'stage'          => 'print',
//                 'status'         => 'in_progress',
//                 'details'        => '<p>Away kit: all black with gold collar trim. Sublimation print. No sponsor.</p>',
//                 'players'        => [
//                     ['player_name' => 'Zulkifli Said',   'jersey_number' => '3',  'size' => 'L'],
//                     ['player_name' => 'Hafizuddin Noor',  'jersey_number' => '8',  'size' => 'XL'],
//                     ['player_name' => 'Redzuan Malik',    'jersey_number' => '14', 'size' => 'M'],
//                 ],
//             ],
//             [
//                 'customer_name'  => 'Sekolah Menengah Sains Alam',
//                 'customer_phone' => '+60 13-222 3344',
//                 'quantity'       => 60,
//                 'product_type'   => 'tracksuit',
//                 'order_date'     => now()->subDays(10),
//                 'delivery_date'  => now()->addDays(3),
//                 'priority'       => 'normal',
//                 'stage'          => 'sew',
//                 'status'         => 'in_progress',
//                 'details'        => '<p>School tracksuit. Navy blue with white stripe. Embroidered school crest on chest. Sizes mixed.</p>',
//                 'players'        => [],
//             ],
//             [
//                 'customer_name'  => 'Persatuan Futsal Taman Maju',
//                 'customer_phone' => '+60 16-555 9900',
//                 'quantity'       => 12,
//                 'product_type'   => 'jersey',
//                 'order_date'     => now()->subDays(14),
//                 'delivery_date'  => now()->addDays(1),
//                 'priority'       => 'normal',
//                 'stage'          => 'ready',
//                 'status'         => 'completed',
//                 'details'        => '<p>Futsal set: orange jerseys with black trim. Full sublimation. Names and numbers required.</p>',
//                 'players'        => [
//                     ['player_name' => 'Azlan Bin Noor',   'jersey_number' => '10', 'size' => 'M'],
//                     ['player_name' => 'Faisal Hashim',     'jersey_number' => '7',  'size' => 'L'],
//                     ['player_name' => 'Rizwan Ismail',     'jersey_number' => '99', 'size' => 'XL'],
//                 ],
//             ],
//             [
//                 'customer_name'  => 'JDT Fan Club Chapter 3',
//                 'customer_phone' => '+60 17-100 2020',
//                 'quantity'       => 50,
//                 'product_type'   => 'polo_shirt',
//                 'order_date'     => now()->subDays(20),
//                 'delivery_date'  => now()->subDays(2),
//                 'priority'       => 'rush',
//                 'stage'          => 'design',
//                 'status'         => 'on_hold',
//                 'details'        => '<p>Fan polo shirt: yellow with blue club crest. Awaiting final crest artwork. HOLD until artwork received.</p>',
//                 'players'        => [],
//             ],
//             [
//                 'customer_name'  => 'Running Club KL',
//                 'customer_phone' => '+60 18-777 4455',
//                 'quantity'       => 100,
//                 'product_type'   => 'jersey',
//                 'order_date'     => now()->subDays(1),
//                 'delivery_date'  => now()->addDays(10),
//                 'priority'       => 'normal',
//                 'stage'          => 'design',
//                 'status'         => 'pending',
//                 'details'        => '<p>Marathon jersey. Dry-fit fabric. Green/white gradient sublimation. Club logo on chest, sponsor on sleeve.</p>',
//                 'players'        => [],
//             ],
//             [
//                 'customer_name'  => 'SMK Taman Damai',
//                 'customer_phone' => '+60 12-001 8899',
//                 'quantity'       => 30,
//                 'product_type'   => 'jersey',
//                 'order_date'     => now()->subDays(21),
//                 'delivery_date'  => now()->subDays(3),
//                 'priority'       => 'normal',
//                 'stage'          => 'delivered',
//                 'status'         => 'completed',
//                 'details'        => '<p>School rugby jersey. Maroon with silver trim. No names, numbers 1–30.</p>',
//                 'players'        => [],
//             ],
//             [
//                 'customer_name'  => 'Kelab Badminton Jaya',
//                 'customer_phone' => '+60 19-333 7788',
//                 'quantity'       => 40,
//                 'product_type'   => 'tracksuit',
//                 'order_date'     => now()->subDays(2),
//                 'delivery_date'  => now()->addDays(5),
//                 'priority'       => 'normal',
//                 'stage'          => 'sew',
//                 'status'         => 'in_progress',
//                 'details'        => '<p>Badminton tracksuit. Royal blue with white piping. Club logo embroidery. Adult and junior sizes.</p>',
//                 'players'        => [],
//             ],
//         ];

//         foreach ($orders as $data) {
//             $players = $data['players'];
//             unset($data['players']);

//             $order = Order::create(array_merge($data, [
//                 'order_date'    => Carbon::parse($data['order_date'])->toDateString(),
//                 'delivery_date' => Carbon::parse($data['delivery_date'])->toDateString(),
//                 'product_type'  => $data['product_type'] ?? 'jersey',
//                 'created_by'    => $manager->id,
//             ]));

//             // Seed players
//             foreach ($players as $index => $playerData) {
//                 OrderPlayer::create(array_merge($playerData, [
//                     'order_id'   => $order->id,
//                     'sort_order' => $index,
//                 ]));
//             }

//             // Seed initial stage log entry
//             OrderStageLog::create([
//                 'order_id'   => $order->id,
//                 'from_stage' => null,
//                 'to_stage'   => $order->stage,
//                 'from_status'=> null,
//                 'to_status'  => $order->status,
//                 'changed_by' => $manager->id,
//                 'notes'      => 'Order created.',
//             ]);
//         }
//     }
// }
