<?php

namespace Database\Seeders;

use Database\Seeders\Cefr\A1PathSeeder;
use Database\Seeders\Cefr\A2PathSeeder;
use Database\Seeders\Cefr\B1PathSeeder;
use Database\Seeders\Cefr\B2PathSeeder;
use Database\Seeders\Cefr\C1PathSeeder;
use Database\Seeders\Cefr\C2PathSeeder;
use Illuminate\Database\Seeder;

class CefrLearningPathsSeeder extends Seeder
{
    /**
     * Seeds one path per CEFR level, A1 to C2, lowest first so the catalog's
     * id order matches the level order.
     */
    public function run(): void
    {
        $this->call([
            A1PathSeeder::class,
            A2PathSeeder::class,
            B1PathSeeder::class,
            B2PathSeeder::class,
            C1PathSeeder::class,
            C2PathSeeder::class,
        ]);
    }
}
