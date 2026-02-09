<?php

namespace Database\Seeders;

use App\Models\RiskTheme;
use App\Models\RiskCategory;
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
                'description' => 'Risques liés à la conformité réglementaire et aux obligations légales',
                'board_appetite' => 2,
                'order' => 1,
            ],
            [
                'code' => 'GOV',
                'name' => 'Governance',
                'description' => 'Risques liés à la gouvernance d\'entreprise et aux processus décisionnels',
                'board_appetite' => 2,
                'order' => 2,
            ],
            [
                'code' => 'OPS',
                'name' => 'Operational',
                'description' => 'Risques opérationnels liés aux processus, personnes et systèmes',
                'board_appetite' => 3,
                'order' => 3,
            ],
            [
                'code' => 'BUS',
                'name' => 'Business',
                'description' => 'Risques commerciaux et stratégiques',
                'board_appetite' => 3,
                'order' => 4,
            ],
            [
                'code' => 'CAP',
                'name' => 'Capital',
                'description' => 'Risques liés au capital, à la liquidité et aux ressources financières',
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
                ['name' => 'Regulatory Compliance', 'description' => 'Conformité aux exigences réglementaires'],
                ['name' => 'Licensing', 'description' => 'Risques liés aux licences et autorisations'],
                ['name' => 'Reporting', 'description' => 'Obligations de reporting réglementaire'],
            ],
            'GOV' => [
                ['name' => 'Board Oversight', 'description' => 'Supervision par le conseil d\'administration'],
                ['name' => 'Policies & Procedures', 'description' => 'Politiques et procédures internes'],
                ['name' => 'Conflicts of Interest', 'description' => 'Gestion des conflits d\'intérêts'],
            ],
            'OPS' => [
                ['name' => 'Process Risk', 'description' => 'Risques liés aux processus opérationnels'],
                ['name' => 'Technology Risk', 'description' => 'Risques technologiques et cybersécurité'],
                ['name' => 'People Risk', 'description' => 'Risques liés aux ressources humaines'],
                ['name' => 'Third Party Risk', 'description' => 'Risques liés aux tiers et fournisseurs'],
            ],
            'BUS' => [
                ['name' => 'Market Risk', 'description' => 'Risques de marché'],
                ['name' => 'Client Risk', 'description' => 'Risques liés aux clients'],
                ['name' => 'Strategic Risk', 'description' => 'Risques stratégiques'],
                ['name' => 'Reputation Risk', 'description' => 'Risques de réputation'],
            ],
            'CAP' => [
                ['name' => 'Capital Adequacy', 'description' => 'Adéquation des fonds propres'],
                ['name' => 'Liquidity Risk', 'description' => 'Risques de liquidité'],
                ['name' => 'Credit Risk', 'description' => 'Risques de crédit'],
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
