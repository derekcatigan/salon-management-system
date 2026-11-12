<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            /* ================= Hair Services ================= */
            ['Haircut', 'Hair', 80, 30],
            ['Blow Dry w/ Shampoo', 'Hair', 100, 30],
            ['Hair Iron', 'Hair', 300, 45],
            ['Hot Oil Treatment', 'Hair', 200, 45],
            ['Hair Spa Aroma', 'Hair', 300, 60],
            ['Hair Detox', 'Hair', 400, 60],
            ['Hair Butter Treatment', 'Hair', 500, 60],
            ['Hair Reborn (2 in 1)', 'Hair', 500, 90],
            ['Morocco Argan Oil', 'Hair', 500, 60],
            ['I-Cure Hair Mask', 'Hair', 500, 60],
            ['Semi de Lino', 'Hair', 500, 60],
            ['Rejuvenating Cellophane', 'Hair', 500, 90],

            /* ================= Rebonding Services (by length) ================= */
            ['Rebond', 'Hair', 1000, 180],
            ['Premium Rebond', 'Hair', 1500, 210],
            ['Loreal Rebond', 'Hair', 2000, 240],
            ['Rebond w/ Cellophane', 'Hair', 1500, 210],
            ['Rebond w/ Hairmask', 'Hair', 1500, 210],
            ['Rebond Brazilian Botox', 'Hair', 2500, 240],

            /* ================= Hair Services by Length ================= */
            ['Hair Color', 'Hair', 500, 120, 'Starting price (by length).'],
            ['Hi-Lites', 'Hair', 500, 120, 'Starting price (by length).'],
            ['Cold Wave', 'Hair', 500, 180, 'Starting price (by length).'],

            /* ================= Brazilian Treatment ================= */
            ['Brazilian Blowout', 'Hair', 1000, 120],
            ['Keratin Blowout', 'Hair', 1300, 150],
            ['Brazilian H-Botox w/ Collagen', 'Hair', 1500, 150],

            /* ================= Paraffin Services ================= */
            ['Hand Paraffin', 'Spa', 150, 30],
            ['Hand to Elbow Paraffin', 'Spa', 200, 40],
            ['Foot Paraffin', 'Spa', 200, 40],
            ['Foot to Knee Paraffin', 'Spa', 250, 50],

            /* ================= Nail Services ================= */
            ['Manicure', 'Nail', 70, 40],
            ['Pedicure', 'Nail', 80, 45],
            ['Nail Art', 'Nail', 150, 30],
            ['Gel Polish', 'Nail', 400, 60],
            ['Softgel Extensions', 'Nail', 1200, 120],
            ['Polygel with Gel Polish', 'Nail', 1000, 120],
            ['Gel Polish Removal', 'Nail', 150, 20],

            /* ================= Spa Services ================= */
            ['Basic Foot Spa', 'Spa', 200, 45],
            ['Magic Foot Spa', 'Spa', 250, 60],
            ['Hand Whitening Mud', 'Spa', 150, 30],
            ['Hand to Shoulder Massage', 'Spa', 250, 45],
            ['Foot Whitening Mud', 'Spa', 150, 30],
            ['Foot to Knee Massage', 'Spa', 250, 45],

            /* ================= Hair & Make-Up ================= */
            ['Hair-Do', 'Hair', 350, 45],
            ['Make-Up', 'Facial', 350, 45],
            ['Hair and Make Up', 'Hair', 600, 90],
            ['Eyelash Extension', 'Facial', 500, 90, 'Starting price.'],
            ['Eyelash Perm', 'Facial', 500, 60],

            /* ================= Waxing Services ================= */
            ['Underarm Waxing', 'Waxing', 399, 15],
            ['Full Leg Waxing', 'Waxing', 799, 45],
            ['Half Leg Waxing', 'Waxing', 499, 30],
        ];

        foreach ($services as $row) {
            [$name, $category, $price, $duration] = $row;
            $description = $row[4] ?? null;

            Service::updateOrCreate(
                ['name' => $name, 'category' => $category],
                [
                    'price' => $price,
                    'duration_minutes' => $duration,
                    'description' => $description,
                    'status' => 'active',
                ],
            );
        }
    }
}
