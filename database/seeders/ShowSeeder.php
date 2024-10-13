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
            ['code' => 'GA', 'title' => 'Grey\'s Anatomy', 'color' => 'bg-slate-500'],
            ['code' => 'GA:BT', 'title' => 'Grey\'s Anatomy: B-Team', 'color' => 'bg-neutral-700'],
            ['code' => 'PP', 'title' => 'Private Practice', 'color' => 'bg-orange-500'],
            ['code' => 'S19', 'title' => 'Station 19', 'color' => 'bg-purple-300'],
            ['code' => 'SG:OC', 'title' => 'Seattle Grace: On Call', 'color' => 'bg-neutral-700'],
            ['code' => 'SG:MOH', 'title' => 'Seattle Grace: Message of Hope', 'color' => 'bg-neutral-700'],
        )->create();
    }
}
