<?php
return [
    'system' => [
        'prefix' => 'sys',
        'module' => 'System',
        'features' => [
            'User',
            'Role',
            'Permission',
            'Business',
            'Branch',
            'Setting',
        ]
    ],

    'crm' => [
        'prefix' => 'crm',
        'module' => 'CRM',
        'features' => [
            'Lead',
            'Parent',
            'ParentStudent',
        ]
    ],

    'education' => [
        'prefix' => 'edu',
        'module' => 'Education',
        'features' => [
            'Student',
            'Course',
            'ClassRoom',
            'Enrollment',
            'LessonPlan',
            'Lesson',
            'Attendance',
            'Evaluation',
            'StudentLevel',
        ]
    ],

    'finance' => [
        'prefix' => 'fin',
        'module' => 'Finance',
        'features' => [
            'Invoice',
            'Payment',
            'Refund',
            'Debt',
            'Discount',
        ]
    ],

    'wallet' => [
        'prefix' => 'fin',
        'module' => 'Wallet',
        'features' => [
            'Wallet',
            'WalletTransaction',
        ]
    ],

    'hr' => [
        'prefix' => 'hr',
        'module' => 'HR',
        'features' => [
            'Teacher',
            'Staff',
        ]
    ],

    'notification' => [
        'prefix' => 'sys',
        'module' => 'Notification',
        'features' => [
            'Notification',
            'Template',
        ]
    ],
];