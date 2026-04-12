<?php

namespace App\Module\Portal\Jobs;

use App\Mail\VerifyOTP;
use App\Module\Portal\Mails\VerifyOTPTransaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailVerifyOTPTransaction implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  protected $user;
  protected $otp;
  protected $config;
  public function __construct(User $user, $otp, $mailConfig)
  {
    $this->user = $user;
    $this->otp = $otp;
    $this->config = $mailConfig;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    config([
      'mail.driver' => $this->config->driver,
      'mail.host' => $this->config->host,
      'mail.port' => $this->config->port,
      'mail.from.address' => $this->config->from_address,
      'mail.from.name' => $this->config->from_name,
      'mail.username' => $this->config->username,
      'mail.password' => $this->config->password,
      'mail.encryption' => ($this->config->encryption == 1) ? 'ssl' : 'tls'
    ]);
    Mail::to($this->user->email)->send(new VerifyOTPTransaction($this->user, $this->otp));
  }
}
