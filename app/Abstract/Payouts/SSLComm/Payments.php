<?php

namespace App\Abstract\Payouts\SSLComm;

use App\Abstract\Payouts\SSLComm\Contracts\PaymentsContracts as SSLPayment;
use App\Abstract\Payouts\SSLComm\Contracts\SessionContracts as SSLSession;
use Psr\Http\Client\ClientInterface;
use Illuminate\Support\Arr;
use GuzzleHttp\Client;

class Payments
{
    protected string $mode = 'sandbox';
    protected string $sandboxUrl = 'https://sandbox.sslcommerz.com';
    protected string $liveUrl = 'https://securepay.sslcommerz.com';

    protected ClientInterface $httpClient;

    public function __construct(protected SSLSession $session)
    {
        $this->httpClient = new Client();
    }

    /**
     * @throws \Exception
     */
    public function create(Customer $customer, SSLPayment $paymentItems)
    {
        $data = array_merge($this->session->toArray(), $customer->toArray(), $paymentItems->toArray());
        return ($this->httpClient->post($this->getPaymentURL(), ['form_params' => $data])->getBody()->getContents());
    }

    public function setMode(string $mode): static
    {
        if (!in_array($mode, ['sandbox', 'live'])) {
            throw new \Exception('Invalid Mode');
        }

        $this->mode = $mode;
        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getUrl(): string
    {
        return $this->mode === 'sandbox' ? $this->sandboxUrl : $this->liveUrl;
    }

    public function getPaymentURL(): string
    {
        return $this->getUrl() . '/gwprocess/v4/api.php';
    }

    public function getValidationURL(): string
    {
        return $this->getUrl() . '/validator/api/validationserverAPI.php?';
    }


    public function validate($paymentId)
    {
        $storeParams = array_merge($this->session->toArray(), ['format' => 'json']);

        $url = "{$this->getValidationURL()}val_id={$paymentId}&store_id={$storeParams['store_id']}&store_passwd={$storeParams['store_passwd']}&format=json";

        $response = $this->httpClient->get($url);

        if (!Arr::get(json_decode($response->getBody()->getContents(), true), 'status') === 'VALID') {
            throw new \Exception('Invalid Transaction');
        }

        $response = (string)$response->getBody();
        return json_decode($response, true);
    }
}
