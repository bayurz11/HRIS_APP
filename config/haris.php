<?php

return [
    'name' => env('APP_NAME', 'HARIS'),

    'locales' => [
        'id' => [
            'label' => 'Bahasa Indonesia',
            'short_label' => 'ID',
        ],
        'en' => [
            'label' => 'English',
            'short_label' => 'EN',
        ],
    ],

    'navigation' => [
        'primary' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'active' => 'dashboard',
                'icon' => 'home',
                'admin_only' => false,
            ],
            [
                'label' => 'Organization',
                'route' => 'organization.index',
                'active' => 'organization.*',
                'icon' => 'building-office-2',
                'roles' => ['Administrator'],
            ],
            [
                'label' => 'Employees',
                'route' => 'users.index',
                'active' => 'users.*',
                'icon' => 'users',
                'roles' => ['Administrator'],
            ],
            [
                'label' => 'Payroll',
                'route' => 'payroll.index',
                'active' => 'payroll.*',
                'icon' => 'banknotes',
                'roles' => ['Administrator', 'Payroll Officer', 'Payroll Approver', 'Finance Approver'],
            ],
            [
                'label' => 'Workflows',
                'route' => 'workflows.index',
                'active' => 'workflows.*',
                'icon' => 'arrows-right-left',
                'roles' => ['Administrator', 'Payroll Officer', 'Payroll Approver', 'Finance Approver'],
            ],
            [
                'label' => 'Documents',
                'route' => 'documents.index',
                'active' => 'documents.*',
                'icon' => 'document-text',
                'roles' => ['Administrator'],
            ],
            [
                'label' => 'Reports',
                'route' => 'reports.index',
                'active' => 'reports.*',
                'icon' => 'chart-bar',
                'roles' => ['Administrator'],
            ],
            [
                'label' => 'Notifications',
                'route' => 'notifications.index',
                'active' => 'notifications.*',
                'icon' => 'bell-alert',
            ],
            [
                'label' => 'Audit Trail',
                'route' => 'audit-trail.index',
                'active' => 'audit-trail.*',
                'icon' => 'shield-check',
                'roles' => ['Administrator', 'Payroll Officer', 'Payroll Approver', 'Finance Approver'],
            ],
        ],
        'secondary' => [
            [
                'label' => 'Settings',
                'route' => 'profile.edit',
                'active' => 'profile.*',
                'icon' => 'cog-6-tooth',
            ],
            [
                'label' => 'My Payslips',
                'route' => 'self-service.payslips.index',
                'active' => 'self-service.payslips.*',
                'icon' => 'document-currency-dollar',
                'employee_only' => true,
            ],
        ],
    ],

    'module_pages' => [
        'organization' => [
            'title' => 'Organization',
            'description' => 'Manage company structure, departments, positions, and approval hierarchy.',
            'focus' => [
                'Organization structure and work units',
                'Department, position, and employee relationships',
                'Organization-based approval mapping',
            ],
        ],
        'users' => [
            'title' => 'Employees',
            'description' => 'Manage accounts, employee profiles, active status, and granular access control.',
            'focus' => [
                'User account lifecycle',
                'Account-to-employee relationship',
                'Module-specific roles and permissions',
            ],
        ],
        'workflows' => [
            'title' => 'Workflows',
            'description' => 'Multi-level approvals, SLA, assignments, and cross-module decision history.',
            'focus' => [
                'Approval flow templates',
                'Task assignments and reminders',
                'Status logs and escalation trails',
            ],
        ],
        'documents' => [
            'title' => 'Documents',
            'description' => 'Handle file uploads, versioning, document validation, and permission-based access control.',
            'focus' => [
                'Transaction attachments and archives',
                'File validation and storage strategy',
                'Retention and document access audits',
            ],
        ],
        'reports' => [
            'title' => 'Reports',
            'description' => 'Generate tabular reports, exports, snapshots, and queued reporting jobs.',
            'focus' => [
                'Report queries separated from controllers',
                'Excel/PDF exports and snapshots',
                'Scheduled report distribution',
            ],
        ],
        'notifications' => [
            'title' => 'Notifications',
            'description' => 'Database notifications, emails, approval reminders, and important application events.',
            'focus' => [
                'Domain notification templates',
                'Approval reminders and queue jobs',
                'Delivery tracking and read status',
            ],
        ],
        'audit-trail' => [
            'title' => 'Audit Trail',
            'description' => 'Track user activity, data changes, approval history, and anomaly logs.',
            'focus' => [
                'Before and after states for critical data',
                'Filters by user, module, and date',
                'Payroll and configuration change audits',
            ],
        ],
    ],
];
