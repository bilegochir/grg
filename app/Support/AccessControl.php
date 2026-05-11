<?php

namespace App\Support;

class AccessControl
{
    public static function permissions(): array
    {
        return [
            ['name' => 'dashboard.view', 'group_name' => 'dashboard', 'label' => 'View dashboard'],
            ['name' => 'leads.view', 'group_name' => 'leads', 'label' => 'View leads'],
            ['name' => 'leads.create', 'group_name' => 'leads', 'label' => 'Create leads'],
            ['name' => 'leads.update', 'group_name' => 'leads', 'label' => 'Update leads'],
            ['name' => 'leads.convert', 'group_name' => 'leads', 'label' => 'Convert leads'],
            ['name' => 'applicants.view', 'group_name' => 'applicants', 'label' => 'View applicants'],
            ['name' => 'applicants.update', 'group_name' => 'applicants', 'label' => 'Update applicants'],
            ['name' => 'cases.view', 'group_name' => 'cases', 'label' => 'View cases'],
            ['name' => 'cases.create', 'group_name' => 'cases', 'label' => 'Create cases'],
            ['name' => 'cases.update', 'group_name' => 'cases', 'label' => 'Update cases'],
            ['name' => 'cases.assign', 'group_name' => 'cases', 'label' => 'Assign cases'],
            ['name' => 'documents.review', 'group_name' => 'documents', 'label' => 'Review documents'],
            ['name' => 'communications.manage', 'group_name' => 'communications', 'label' => 'Manage communications'],
            ['name' => 'finance.view', 'group_name' => 'finance', 'label' => 'View finance'],
            ['name' => 'settings.manage', 'group_name' => 'admin', 'label' => 'Manage settings'],
            ['name' => 'staff.manage', 'group_name' => 'admin', 'label' => 'Manage staff and roles'],
        ];
    }

    public static function defaultRoles(): array
    {
        return [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full visibility and control across the CRM.',
                'permissions' => collect(self::permissions())->pluck('name')->all(),
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Owns operations, staffing, and performance.',
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'leads.create',
                    'leads.update',
                    'leads.convert',
                    'applicants.view',
                    'applicants.update',
                    'cases.view',
                    'cases.create',
                    'cases.update',
                    'cases.assign',
                    'documents.review',
                    'communications.manage',
                    'staff.manage',
                ],
            ],
            [
                'name' => 'Agent',
                'slug' => 'agent',
                'description' => 'Works leads, applicants, cases, and document review.',
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'leads.create',
                    'leads.update',
                    'leads.convert',
                    'applicants.view',
                    'applicants.update',
                    'cases.view',
                    'cases.create',
                    'cases.update',
                    'documents.review',
                    'communications.manage',
                ],
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'description' => 'Handles applicant follow-up and documentation support.',
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'applicants.view',
                    'applicants.update',
                    'cases.view',
                    'documents.review',
                    'communications.manage',
                ],
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Tracks financial obligations and reminders.',
                'permissions' => [
                    'dashboard.view',
                    'cases.view',
                    'finance.view',
                    'communications.manage',
                ],
            ],
        ];
    }
}
