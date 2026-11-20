<?php

namespace Database\Factories;

use App\Models\System\Outlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outlet>
 */
class OutletFactory extends Factory
{
    protected $model = Outlet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outlet_name'                => fake()->company() . ' Konveksi',
            'outlet_code'                => strtoupper(fake()->unique()->bothify('OUT-####')),
            'slug'                       => fake()->unique()->slug(),
            'phone_wa'                   => fake()->phoneNumber(),
            'address'                    => fake()->address(),
            'description'                => fake()->sentence(),
            'logo_path'                  => null,
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ];
    }
}
