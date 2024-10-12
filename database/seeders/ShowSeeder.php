<?php

namespace Database\Seeders;

use App\Models\Show;
use Illuminate\Database\Seeder;

class ShowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Show::factory(6)->sequence(
            ['code' => 'GA', 'title' => 'Grey\'s Anatomy', 'color' => 'slate-500'],
            ['code' => 'GA:BT', 'title' => 'Grey\'s Anatomy: B-Team', 'color' => 'slate-50'],
            ['code' => 'PP', 'title' => 'Private Practice', 'color' => 'orange-500'],
            ['code' => 'S19', 'title' => 'Station 19', 'color' => 'purple-300'],
            ['code' => 'SG:OC', 'title' => 'Seattle Grace: On Call', 'color' => 'neutral-50'],
            ['code' => 'SG:MOH', 'title' => 'Seattle Grace: Message of Hope', 'color' => 'neutral-50'],
        )->create();
    }
}
