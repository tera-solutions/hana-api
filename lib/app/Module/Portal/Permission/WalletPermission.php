<?php

namespace App\Module\Portal\Permission;

use Package\Permission\AbstractPermission;
use Package\Permission\PermissionInterface;

class WalletPermission extends AbstractPermission  implements PermissionInterface
{

  /**
   * Permission list for collection
   *
   * @var array
   */
  protected $keys =  [
    "recharge" => "portal_wallet_recharge",
    "withdrawal" => "portal_wallet_withdrawal",
    "createTransactionSession" => "portal_wallet_createTransactionSession",
    "resendOTP" => "portal_wallet_resendOTP",
    "verifyOTP" => "portal_wallet_verifyOTP",
    "getQR" => "portal_wallet_getQR",
    "getAmount" => "portal_wallet_getAmount"
  ];

  protected $messages = [
    "portal_wallet_recharge" => "Bạn không có quyền xem danh sách người dùng!",
    "portal_wallet_withdrawal" => "Bạn không có quyền xem chi tiết người dùng!",
    "portal_wallet_createTransactionSession" => "Bạn không có quyền tạo người dùng!",
    "portal_wallet_resendOTP" => "Bạn không có quyền cập nhật người dùng!",
    "portal_wallet_verifyOTP" => "Bạn không có quyền xoá người dùng!",
    "portal_wallet_getQR" => "Bạn không có quyền cập nhật người dùng!",
    "portal_wallet_getAmount" => "Bạn không có quyền xoá người dùng!",
  ];
}
