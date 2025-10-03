
<?php
// Configuration for different income line forms
return [
    'General' => [
        'fields' => ['customer_name', 'description'],
        'has_fixed_price' => false,
        'required_fields' => ['customer_name'],
        'field_types' => [
            'customer_name' => 'text',
            'description' => 'textarea'
        ]
    ],
    'Shop Rent' => [
        'fields' => ['shop_id', 'shop_no', 'shop_size', 'start_date', 'end_date'],
        'has_fixed_price' => true,
        'prices' => [5000, 7500, 10000, 15000, 20000],
        'required_fields' => ['shop_id', 'shop_no'],
        'field_types' => [
            'shop_id' => 'text',
            'shop_no' => 'text',
            'shop_size' => 'select',
            'start_date' => 'date',
            'end_date' => 'date'
        ],
        'select_options' => [
            'shop_size' => ['Small', 'Medium', 'Large', 'Extra Large']
        ]
    ],
    'Service Charge' => [
        'fields' => ['shop_id', 'shop_no', 'month_year'],
        'has_fixed_price' => true,
        'prices' => [500, 750, 1000],
        'required_fields' => ['shop_id', 'shop_no', 'month_year'],
        'field_types' => [
            'shop_id' => 'text',
            'shop_no' => 'text',
            'month_year' => 'month'
        ]
    ],
    'Car Loading' => [
        'fields' => ['no_of_tickets', 'transaction_descr', 'remitting_staff', 'remit_id'],
        'has_fixed_price' => true,
        'prices' => [200, 300, 500],
        'required_fields' => ['no_of_tickets', 'remitting_staff'],
        'field_types' => [
            'no_of_tickets' => 'number',
            'transaction_descr' => 'text',
            'remitting_staff' => 'select',
            'remit_id' => 'select'
        ],
        'readonly_fields' => ['transaction_descr', 'amount_paid'],
        'select_options' => [
            'remitting_staff' => 'dynamic_staff', // This will be populated dynamically
            'remit_id' => 'dynamic_remittance' // This will be populated dynamically
        ]
    ],
    'Car Park Ticket' => [
        'fields' => ['plate_no', 'no_of_tickets'],
        'has_fixed_price' => true,
        'prices' => [100, 200],
        'required_fields' => ['no_of_tickets'],
        'field_types' => [
            'plate_no' => 'text',
            'no_of_tickets' => 'number'
        ]
    ],
    'Hawkers Ticket' => [
        'fields' => ['no_of_tickets', 'location'],
        'has_fixed_price' => true,
        'prices' => [50, 100],
        'required_fields' => ['no_of_tickets'],
        'field_types' => [
            'no_of_tickets' => 'number',
            'location' => 'text'
        ]
    ],
    'WheelBarrow Ticket' => [
        'fields' => ['no_of_tickets'],
        'has_fixed_price' => true,
        'prices' => [50],
        'required_fields' => ['no_of_tickets'],
        'field_types' => [
            'no_of_tickets' => 'number'
        ]
    ],
    'Daily Trade' => [
        'fields' => ['no_of_tickets', 'trade_type'],
        'has_fixed_price' => true,
        'prices' => [100, 200, 300],
        'required_fields' => ['no_of_tickets', 'trade_type'],
        'field_types' => [
            'no_of_tickets' => 'number',
            'trade_type' => 'select'
        ],
        'select_options' => [
            'trade_type' => ['Food Items', 'Clothing', 'Electronics', 'General Goods']
        ]
    ],
    'Abattoir' => [
        'fields' => ['animal_type', 'no_of_animals'],
        'has_fixed_price' => true,
        'prices' => [500, 1000, 1500],
        'required_fields' => ['animal_type', 'no_of_animals'],
        'field_types' => [
            'animal_type' => 'select',
            'no_of_animals' => 'number'
        ],
        'select_options' => [
            'animal_type' => ['Cow', 'Goat', 'Sheep', 'Chicken']
        ]
    ]
];
?>
