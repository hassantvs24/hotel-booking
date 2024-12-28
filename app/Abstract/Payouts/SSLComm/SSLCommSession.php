<?php

namespace App\Abstract\Payouts\SSLComm;

class SSLCommSession implements Contracts\SessionContracts
{
    public function __construct(protected string $storeId, protected string $storePassword, protected string $successUrl, protected string $failUrl, protected string $cancelUrl, protected string $currency = 'BDT')
    {
    }

    public static function create($data = []): static
    {
        return new static(
            $data['store_id'],
            $data['store_password'],
            $data['success_url'],
            $data['fail_url'],
            $data['cancel_url'],
            $data['currency']
        );
    }

    public function toArray(): array
    {
        return [
            'store_id'     => $this->storeId,
            'store_passwd' => $this->storePassword,
            'success_url'  => $this->successUrl,
            'fail_url'     => $this->failUrl,
            'cancel_url'   => $this->cancelUrl,
            'currency'     => $this->currency,
        ];
    }
}
