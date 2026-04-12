<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class MailConfig extends Model
{
  protected $connection = 'admin';
  protected $table = 'ad_mail_configs';
}
