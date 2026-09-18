<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The first account is the instance owner and the only one allowed to
     * create additional accounts. Credentials come from the OWNER_* env
     * variables (see config/opencal.php).
     */
    public function run(): void
    {
        $owner = config('opencal.owner');

        $existing = User::where('email', $owner['email'])->first();

        if ($existing !== null) {
            $existing->forceFill(['is_owner' => true])->save();

            return;
        }

        User::factory()->owner()->create([
            'name' => $owner['name'],
            'email' => $owner['email'],
            'password' => $owner['password'],
        ]);
    }
}
