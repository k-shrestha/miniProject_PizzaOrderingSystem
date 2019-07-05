<?php

use Faker\Factory;
use Illuminate\Database\Seeder;

class ToppingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();

        for ($i = 0; $i < 5; $i++) { //Create 5 dummy toppings in the database
        	factory(App\Topping::class)->create();
        }
    }
}
