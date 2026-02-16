<?php

namespace Database\Seeders;

use App\Models\RiskCategory;
use App\Models\RiskTheme;
use Illuminate\Database\Seeder;

class RiskThemeSeeder extends Seeder
{
    public function run(): void
    {
        // L1 - Risk Themes
        $themes = [
            [
                'code' => 'REG',
                'name' => 'Regulatory',
                'description' => 'Risks related to regulatory compliance and legal obligations',
                'board_appetite' => 2,
                'order' => 1,
            ],
            [
                'code' => 'GOV',
                'name' => 'Governance',
                'description' => 'Risks related to corporate governance and decision-making processes',
                'board_appetite' => 2,
                'order' => 2,
            ],
            [
                'code' => 'OPS',
                'name' => 'Operational',
                'description' => 'Operational risks related to processes, people and systems',
                'board_appetite' => 3,
                'order' => 3,
            ],
            [
                'code' => 'BUS',
                'name' => 'Business',
                'description' => 'Commercial and strategic risks',
                'board_appetite' => 3,
                'order' => 4,
            ],
            [
                'code' => 'CAP',
                'name' => 'Capital',
                'description' => 'Risks related to capital, liquidity and financial resources',
                'board_appetite' => 2,
                'order' => 5,
            ],
        ];

        foreach ($themes as $themeData) {
            $theme = RiskTheme::firstOrCreate(
                ['code' => $themeData['code']],
                $themeData
            );

            // Create default L2 categories for each theme
            $this->createDefaultCategories($theme);
        }

        $this->command->info('Risk themes and categories seeded successfully');
    }

    private function createDefaultCategories(RiskTheme $theme): void
    {
        $categories = match ($theme->code) {
            'REG' => [
                ['name' => 'Regulatory Compliance', 'description' => 'Compliance with regulatory requirements'],
                ['name' => 'Licensing', 'description' => 'Risks related to licences and authorisations'],
                ['name' => 'Reporting', 'description' => 'Regulatory reporting obligations'],
            ],
            'GOV' => [
                ['name' => 'Board Oversight', 'description' => 'Board of directors oversight and supervision'],
                ['name' => 'Policies & Procedures', 'description' => 'Internal policies and procedures'],
                ['name' => 'Conflicts of Interest', 'description' => 'Conflicts of interest management'],
            ],
            'OPS' => [
                ['name' => 'Process Risk', 'description' => 'Operational process risks'],
                ['name' => 'Technology Risk', 'description' => 'Technology and cybersecurity risks'],
                ['name' => 'People Risk', 'description' => 'Human resources risks'],
                ['name' => 'Third Party Risk', 'description' => 'Third party and supplier risks'],
            ],
            'BUS' => [
                ['name' => 'Market Risk', 'description' => 'Market risks'],
                ['name' => 'Client Risk', 'description' => 'Client-related risks'],
                ['name' => 'Strategic Risk', 'description' => 'Strategic risks'],
                ['name' => 'Reputation Risk', 'description' => 'Reputational risks'],
            ],
            'CAP' => [
                ['name' => 'Capital Adequacy', 'description' => 'Capital adequacy and own funds'],
                ['name' => 'Liquidity Risk', 'description' => 'Liquidity risks'],
                ['name' => 'Credit Risk', 'description' => 'Credit risks'],
            ],
            default => [],
        };

        foreach ($categories as $order => $categoryData) {
            $code = sprintf('P-%s-%02d', $theme->code, $order + 1);

            RiskCategory::firstOrCreate(
                ['code' => $code],
                [
                    'theme_id' => $theme->id,
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }
}
