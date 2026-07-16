<?php

namespace App\Modules\System\Onboarding\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $business = $this->resource['business'];
        $user = $this->resource['user'];

        return [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'email' => $business->email,
            ],
            'user' => [
                'id' => $user->id,
                'code' => $user->code,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ],
            // The owner is active and can log in immediately; no OTP/activation step.
            'is_verify' => 1,
        ];
    }
}
