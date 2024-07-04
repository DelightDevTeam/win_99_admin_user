<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BankTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'KBZ Pay',
                
            ],
            [
                'name' => 'Wave Pay',
               
            ],
            [
                'name' => 'AYA Pay',
               
            ],
            [
                'name' => 'AYA Bank',
                
            ],
            [
                'name' => 'CB Bank',
                
            ],
            [
                'name' => 'CB Pay',
                
            ],
            [
                'name' => 'MAB Bank',
               
            ],
            [
                'name' => 'UAB Bank',
                
            ],
            [
                'name' => 'UAB Pay',
                
            ],
            [
                'name' => 'Yoma Bank',
                
            ]
        ];

        DB::table('banks')->insert($types);
    }
}
