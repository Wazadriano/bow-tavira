<?php

namespace Database\Seeders;

use App\Models\RiskTheme;
use App\Models\RiskThemePermission;
use App\Models\User;
use App\Models\UserDepartmentPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RealUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('bow2026!');

        $users = [
            // Admins
            ['username' => 'dev', 'full_name' => 'Dev Admin', 'email' => 'dev@tavira-bow.local', 'role' => 'admin', 'primary_department' => 'Corporate Governance'],
            ['username' => 'mark.griffiths', 'full_name' => 'Mark Griffiths', 'email' => 'mark.griffiths@tavira-bow.local', 'role' => 'admin', 'primary_department' => 'Corporate Governance'],
            ['username' => 'simon.mason', 'full_name' => 'Simon Mason', 'email' => 'simon.mason@tavira-bow.local', 'role' => 'admin', 'primary_department' => 'Compliance'],
            ['username' => 'ranjit.gursahani', 'full_name' => 'Ranjit Gursahani', 'email' => 'ranjit.gursahani@tavira-bow.local', 'role' => 'admin', 'primary_department' => 'Operations'],
            // Members
            ['username' => 'andy.webster', 'full_name' => 'Andy Webster', 'email' => 'andy.webster@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Finance'],
            ['username' => 'will.moody', 'full_name' => 'Will Moody', 'email' => 'will.moody@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Operations'],
            ['username' => 'rebecca.reffell', 'full_name' => 'Rebecca Reffell', 'email' => 'rebecca.reffell@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Finance'],
            ['username' => 'lisa.scott', 'full_name' => 'Lisa Scott', 'email' => 'lisa.scott@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Corporate Governance'],
            ['username' => 'john.halliday', 'full_name' => 'John Halliday', 'email' => 'john.halliday@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Corporate Governance'],
            ['username' => 'olivier.dupont', 'full_name' => 'Olivier Dupont', 'email' => 'olivier.dupont@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Technology'],
            ['username' => 'myles.wood-mcgrath', 'full_name' => 'Myles Wood-Mcgrath', 'email' => 'myles.wood-mcgrath@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Product'],
            ['username' => 'rabia.khan', 'full_name' => 'Rabia Khan', 'email' => 'rabia.khan@tavira-bow.local', 'role' => 'member', 'primary_department' => 'HR'],
            ['username' => 'colin.bugler', 'full_name' => 'Colin Bugler', 'email' => 'colin.bugler@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Finance'],
            ['username' => 'monte.wren', 'full_name' => 'Monte Wren', 'email' => 'monte.wren@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Product'],
            ['username' => 'remy.alexander', 'full_name' => 'Remy Alexander', 'email' => 'remy.alexander@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Corporate Governance'],
            ['username' => 'harrison.rudd', 'full_name' => 'Harrison Rudd', 'email' => 'harrison.rudd@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Compliance'],
            ['username' => 'aamir.khalid', 'full_name' => 'Aamir Khalid', 'email' => 'aamir.khalid@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Technology'],
            ['username' => 'darren.bird', 'full_name' => 'Darren Bird', 'email' => 'darren.bird@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Finance'],
            ['username' => 'elizabeth.canning', 'full_name' => 'Elizabeth Canning', 'email' => 'elizabeth.canning@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Compliance'],
            ['username' => 'morgan.boyd', 'full_name' => 'Morgan Boyd', 'email' => 'morgan.boyd@tavira-bow.local', 'role' => 'member', 'primary_department' => 'Technology'],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                array_merge($userData, ['password' => $password, 'is_active' => true])
            );
        }

        $this->command->info('Users: '.count($users).' created/updated');

        $this->seedDepartmentPermissions();
        $this->seedRiskThemePermissions();
    }

    private function seedDepartmentPermissions(): void
    {
        $allDepts = [
            'Corporate Governance', 'Finance', 'Technology', 'Operations',
            'Compliance', 'Risk Management', 'Human Resources', 'Legal',
            'Product', 'HR', 'Marketing', 'Risk & Credit', 'Real Estate',
        ];

        // Permissions par utilisateur: [dept => [can_view, can_edit_status, can_create_tasks, can_edit_all]]
        $permissions = [
            // Admins: full access on all departments
            'dev' => $this->fullAccessAll($allDepts),
            'mark.griffiths' => $this->fullAccessAll($allDepts),
            'simon.mason' => $this->fullAccessAll($allDepts),
            'ranjit.gursahani' => $this->fullAccessAll($allDepts),
            // Managers with edit_all on specific depts + view on others
            'andy.webster' => array_merge(
                $this->editAll(['Compliance', 'Finance']),
                $this->viewOnly(['Operations', 'Technology'])
            ),
            'will.moody' => array_merge(
                $this->editAll(['Operations']),
                $this->viewOnly(['Finance', 'Technology'])
            ),
            'rebecca.reffell' => array_merge(
                $this->editAll(['Finance']),
                $this->viewOnly(['Compliance'])
            ),
            'lisa.scott' => array_merge(
                $this->editStatus(['Corporate Governance']),
                $this->viewOnly(['HR'])
            ),
            'john.halliday' => $this->viewOnly(['Corporate Governance']),
            'olivier.dupont' => array_merge(
                $this->editAll(['Technology']),
                $this->viewOnly(['Operations'])
            ),
            'myles.wood-mcgrath' => $this->viewOnly(['Marketing', 'Product']),
            'rabia.khan' => $this->editStatus(['HR', 'Operations']),
            'colin.bugler' => $this->viewOnly(['Finance']),
            'monte.wren' => $this->viewOnly(['Product', 'Operations']),
            'remy.alexander' => $this->editAll(['Corporate Governance']),
            'harrison.rudd' => $this->viewOnly(['Corporate Governance', 'Finance']),
            'aamir.khalid' => $this->viewOnly(['Technology']),
            'darren.bird' => $this->viewOnly(['Finance', 'Operations']),
            'elizabeth.canning' => $this->viewOnly(['Compliance']),
            'morgan.boyd' => $this->viewOnly(['Technology', 'Operations']),
        ];

        $count = 0;
        foreach ($permissions as $username => $deptPerms) {
            $user = User::where('username', $username)->first();
            if (! $user) {
                continue;
            }

            foreach ($deptPerms as $dept => $perms) {
                UserDepartmentPermission::updateOrCreate(
                    ['user_id' => $user->id, 'department' => $dept],
                    [
                        'can_view' => $perms[0],
                        'can_edit_status' => $perms[1],
                        'can_create_tasks' => $perms[2],
                        'can_edit_all' => $perms[3],
                    ]
                );
                $count++;
            }
        }

        $this->command->info('Department Permissions: '.$count.' created');
    }

    private function seedRiskThemePermissions(): void
    {
        $themes = RiskTheme::all();
        if ($themes->isEmpty()) {
            $this->command->warn('No risk themes found, skipping risk theme permissions');

            return;
        }

        $themeCodes = $themes->pluck('id', 'code')->toArray();

        // Permissions par username: [theme_code => [can_view, can_edit, can_create, can_delete]]
        $permissions = [
            // Admins: full access all themes
            'dev' => $this->riskFullAll($themeCodes),
            'mark.griffiths' => $this->riskFullAll($themeCodes),
            'simon.mason' => $this->riskFullAll($themeCodes),
            'ranjit.gursahani' => $this->riskFullAll($themeCodes),
            // Managers: view + edit on 2-3 themes
            'andy.webster' => $this->riskEdit($themeCodes, ['REG', 'GOV'])
                + $this->riskView($themeCodes, ['OPS']),
            'will.moody' => $this->riskEdit($themeCodes, ['OPS'])
                + $this->riskView($themeCodes, ['BUS', 'CAP']),
            'rebecca.reffell' => $this->riskEdit($themeCodes, ['CAP'])
                + $this->riskView($themeCodes, ['REG']),
            'olivier.dupont' => $this->riskEdit($themeCodes, ['OPS'])
                + $this->riskView($themeCodes, ['BUS']),
            'remy.alexander' => $this->riskEdit($themeCodes, ['GOV'])
                + $this->riskView($themeCodes, ['REG']),
            // Members: view only on 1-2 themes
            'lisa.scott' => $this->riskView($themeCodes, ['GOV']),
            'john.halliday' => $this->riskView($themeCodes, ['GOV']),
            'myles.wood-mcgrath' => $this->riskView($themeCodes, ['BUS']),
            'rabia.khan' => $this->riskView($themeCodes, ['OPS']),
            'colin.bugler' => $this->riskView($themeCodes, ['CAP']),
            'monte.wren' => $this->riskView($themeCodes, ['BUS']),
            'harrison.rudd' => $this->riskView($themeCodes, ['GOV', 'CAP']),
            'aamir.khalid' => $this->riskView($themeCodes, ['OPS']),
            'darren.bird' => $this->riskView($themeCodes, ['CAP', 'OPS']),
            'elizabeth.canning' => $this->riskView($themeCodes, ['REG']),
            'morgan.boyd' => $this->riskView($themeCodes, ['OPS']),
        ];

        $count = 0;
        foreach ($permissions as $username => $themePerms) {
            $user = User::where('username', $username)->first();
            if (! $user) {
                continue;
            }

            foreach ($themePerms as $themeId => $perms) {
                RiskThemePermission::updateOrCreate(
                    ['user_id' => $user->id, 'theme_id' => $themeId],
                    [
                        'can_view' => $perms[0],
                        'can_edit' => $perms[1],
                        'can_create' => $perms[2],
                        'can_delete' => $perms[3],
                    ]
                );
                $count++;
            }
        }

        $this->command->info('Risk Theme Permissions: '.$count.' created');
    }

    // -- Helper methods --

    /** @return array<string, array{bool, bool, bool, bool}> */
    private function fullAccessAll(array $depts): array
    {
        $result = [];
        foreach ($depts as $dept) {
            $result[$dept] = [true, true, true, true];
        }

        return $result;
    }

    /** @return array<string, array{bool, bool, bool, bool}> */
    private function editAll(array $depts): array
    {
        $result = [];
        foreach ($depts as $dept) {
            $result[$dept] = [true, true, true, true];
        }

        return $result;
    }

    /** @return array<string, array{bool, bool, bool, bool}> */
    private function editStatus(array $depts): array
    {
        $result = [];
        foreach ($depts as $dept) {
            $result[$dept] = [true, true, false, false];
        }

        return $result;
    }

    /** @return array<string, array{bool, bool, bool, bool}> */
    private function viewOnly(array $depts): array
    {
        $result = [];
        foreach ($depts as $dept) {
            $result[$dept] = [true, false, false, false];
        }

        return $result;
    }

    /** @return array<int, array{bool, bool, bool, bool}> */
    private function riskFullAll(array $themeCodes): array
    {
        $result = [];
        foreach ($themeCodes as $id) {
            $result[$id] = [true, true, true, true];
        }

        return $result;
    }

    /** @return array<int, array{bool, bool, bool, bool}> */
    private function riskEdit(array $themeCodes, array $codes): array
    {
        $result = [];
        foreach ($codes as $code) {
            if (isset($themeCodes[$code])) {
                $result[$themeCodes[$code]] = [true, true, true, false];
            }
        }

        return $result;
    }

    /** @return array<int, array{bool, bool, bool, bool}> */
    private function riskView(array $themeCodes, array $codes): array
    {
        $result = [];
        foreach ($codes as $code) {
            if (isset($themeCodes[$code])) {
                $result[$themeCodes[$code]] = [true, false, false, false];
            }
        }

        return $result;
    }
}
