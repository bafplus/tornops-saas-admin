<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['name' => 'All-Rounder', 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => false],
            ['name' => 'Strength Focus', 'str_percent' => 40, 'def_percent' => 20, 'spd_percent' => 20, 'dex_percent' => 20, 'is_custom' => false],
            ['name' => 'Defense Focus', 'str_percent' => 20, 'def_percent' => 40, 'spd_percent' => 20, 'dex_percent' => 20, 'is_custom' => false],
            ['name' => 'Speed Focus', 'str_percent' => 20, 'def_percent' => 20, 'spd_percent' => 40, 'dex_percent' => 20, 'is_custom' => false],
            ['name' => 'Dexterity Focus', 'str_percent' => 20, 'def_percent' => 20, 'spd_percent' => 20, 'dex_percent' => 40, 'is_custom' => false],
            ['name' => 'STR/DEF Build', 'str_percent' => 35, 'def_percent' => 35, 'spd_percent' => 15, 'dex_percent' => 15, 'is_custom' => false],
            ['name' => 'SPD/DEX Build', 'str_percent' => 15, 'def_percent' => 15, 'spd_percent' => 35, 'dex_percent' => 35, 'is_custom' => false],
            ['name' => 'Melee Special', 'str_percent' => 45, 'def_percent' => 25, 'spd_percent' => 15, 'dex_percent' => 15, 'is_custom' => false],
            ['name' => "Hank's Ratio", 'str_percent' => 28, 'def_percent' => 35, 'spd_percent' => 28, 'dex_percent' => 10, 'is_custom' => false],
            ['name' => "Balder's Ratio", 'str_percent' => 30, 'def_percent' => 30, 'spd_percent' => 20, 'dex_percent' => 20, 'is_custom' => false],
            ["Duce's Ratio", 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => false],
            ['name' => 'Custom', 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => true],
        ];

        foreach ($programs as $program) {
            DB::table('training_programs')->insert($program);
        }
    }
}
