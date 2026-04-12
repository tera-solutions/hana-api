<?php

namespace App\Module\Portal\Model;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $connection = 'mysql';
  protected $table = 'business';
  protected $guarded = ['id'];
  protected $fillable = [
    'id',
    'name',
    'currency_id',
    'start_date',
    'limit_location',
    'tax_number_1',
    'tax_label_1',
    'tax_number_2',
    'tax_label_2',
    'default_sales_tax',
    'default_profit_percent',
    'owner_id',
    'time_zone',
    'fy_start_month',
    'accounting_method',
    'default_sales_discount',
    'sell_price_tax',
    'logo',
    'sku_prefix',
    'enable_product_expiry',
    'expiry_type',
    'on_product_expiry',
    'stop_selling_before',
    'enable_tooltip',
    'purchase_in_diff_currency',
    'purchase_currency_id',
    'p_exchange_rate',
    'transaction_edit_days',
    'stock_expiry_alert_days',
    'keyboard_shortcuts',
    'pos_settings',
    'weighing_scale_setting',
    'enable_brand',
    'enable_category',
    'enable_sub_category',
    'enable_price_tax',
    'enable_purchase_status',
    'enable_lot_number',
    'default_unit',
    'enable_sub_units',
    'enable_racks',
    'enable_row',
    'enable_position',
    'enable_editing_product_from_purchase',
    'sales_cmsn_agnt',
    'item_addition_method',
    'enable_inline_tax',
    'currency_symbol_placement',
    'enabled_modules',
    'date_format',
    'time_format',
    'ref_no_prefixes',
    'theme_color',
    'enable_rp',
    'rp_name',
    'amount_for_unit_rp',
    'min_order_total_for_rp',
    'max_rp_per_order',
    'redeem_amount_per_unit_rp',
    'min_order_total_for_redeem',
    'min_redeem_point',
    'max_redeem_point',
    'rp_expiry_period',
    'rp_expiry_type',
    'email_settings',
    'sms_settings',
    'custom_labels',
    'common_settings',
    'is_active',
    'created_by',
    'created_at',
    'updated_at',
    'owner_name',
    'owner_email',
    'owner_job_title',
    'owner_department',
    'owner_phone',
    'email',
    'address',
    'employee_size',
    'payment_methods',
    'status',
    'register_time',
    'trial_time',
    'expiration_time',
    'website',
    'instagram',
    'linkedin',
    'facebook',
    'intro',
    'name_registration',
    'tiktok',
  ];

  protected $appends = ['location_count'];

  public function owner()
  {
    return $this->belongsTo(User::class, 'owner_id');
  }

  public function roles()
  {
    return $this->hasMany(Role::class, 'business_id');
  }

  public function locations()
  {
    return $this->hasMany(BusinessLocation::class, 'business_id');
  }

  public function getLocationCountAttribute()
  {
    return $this->hasMany(BusinessLocation::class, 'business_id')->where("is_delete", 0)->count();
  }
}
