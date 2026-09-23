<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Channel::query()->firstOrCreate(
            ['slug' => 'hidden-relationship-patterns'],
            [
                'name' => 'Hidden Relationship Patterns',
                'niche' => 'Behaviour-focused relationship education for men',
                'mission' => 'Help men recognise unhealthy patterns without hostility or gender generalisation.',
                'audience_summary' => 'Adults seeking calm, practical relationship insight.',
                'default_timezone' => 'Europe/London',
                'operating_mode' => 'guarded',
                'settings' => [
                    'max_posts_per_day' => 2,
                    'require_approval' => true,
                ],
            ],
        );
    }
}
