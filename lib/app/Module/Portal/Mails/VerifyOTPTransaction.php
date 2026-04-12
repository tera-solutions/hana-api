<?php

namespace App\Module\Portal\Mails;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyOTPTransaction extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  protected $user;
  protected $otp;
  public function __construct(User $user, $otp)
  {
    $this->user = $user;
    $this->otp = $otp;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    return $this->view('mails.OTPTransaction')
      ->subject('Yêu cầu xác thực giao dịch')
      ->with([
        'name' => $this->user->name,
        'otp' => $this->otp
      ]);;
  }
}
