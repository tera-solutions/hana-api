<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class BusinessLocation extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'business_locations';
  protected $guarded = ['id'];
  protected $fillable = [
    'id',
    'business_id',
    'location_id',
    'parent_id',
    'name',
    'landmark',
    'ward',
    'state',
    'city',
    'country',
    'zip_code',
    'invoice_scheme_id',
    'invoice_layout_id',
    'selling_price_group_id',
    'print_receipt_on_invoice',
    'receipt_printer_type',
    'printer_id',
    'mobile',
    'alternate_number',
    'email',
    'website',
    'featured_products',
    'is_active',
    'is_default',
    'is_delete',
    'is_new_address',
    'default_payment_accounts',
    'custom_field1',
    'custom_field2',
    'custom_field3',
    'custom_field4',
    'deleted_at',
    'created_at',
    'updated_at',
    'email',
    'address',
    'employee_size',
    'payment_methods'
  ];

  protected $appends = ['active_text', 'address'];

  public function getActiveTextAttribute()
  {
    if(!$this->is_active) return [];
    $text = [
      0 => [
        'color' => '#8c8c8c',
        'text' => 'Ngừng hoạt động'
      ],
      1 => [
        'color' => '#33cc33',
        'text' => 'Hoạt động'
      ]
    ];

    return $text[$this->is_active];
  }

  public function getAddressAttribute()
  {
    $newAddress = [
      $this->landmark,
      $this->city,
      $this->state
    ];

    $oldAddress = [
      $this->landmark,
      $this->ward,
      $this->city,
      $this->state
    ];

    if ($this->is_new_address)
      return implode(', ', array_filter($newAddress));


    return implode(', ', array_filter($oldAddress));
  }
}
