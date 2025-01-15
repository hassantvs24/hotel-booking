<?php

namespace App\Abstract\Payouts\SSLComm;

class Customer implements Contracts\CustomerContracts
{
    protected array $customer = [
        'cus_name'     => null,
        'cus_email'    => null,
        'cus_add1'     => null,
        'cus_add2'     => null,
        'cus_city'     => null,
        'cus_state'    => null,
        'cus_postcode' => null,
        'cus_country'  => null,
        'cus_phone'    => null,
        'cus_fax'      => null,
    ];

    protected array $customerMap = [
        'name'     => 'cus_name',
        'email'    => 'cus_email',
        'phone'    => 'cus_phone',
        'address1' => 'cus_add1',
        'address2' => 'cus_add2',
        'city'     => 'cus_city',
        'state'    => 'cus_state',
        'postcode' => 'cus_postcode',
        'country'  => 'cus_country',
        'fax'      => 'cus_fax',
    ];

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {

            if (array_key_exists($key, $this->customer)) {
                $this->customer[$key] = $value;
            }

            if (array_key_exists($key, $this->customerMap)) {
                $this->customer[$this->customerMap[$key]] = $value;
            }
        }
    }

    public static function createFromArray($data = []): static
    {
        return new static($data);
    }

    public function toArray(): array
    {
        return array_filter($this->customer);
    }
}
