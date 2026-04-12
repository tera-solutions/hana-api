<?php

namespace App\Module\Portal\Model;

use App\Module\Portal\Model\Wallet\Transaction;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillTransaction extends Model
{
  protected $table = 'sys_bill_transactions';

  protected $fillable = [
    'id',
    'bill_id',
    'transaction_id',
    'created_at',
    'updated_at'
  ];

  public function transaction()
  {
    return $this->belongsTo(Transaction::class, 'transaction_id');
  }
}
