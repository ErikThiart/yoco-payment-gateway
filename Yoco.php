<?php
namespace App\Classes;

use App\Classes\Config\Config;

class Yoco
{
    private $id; // not always needed
    private $failureUrl; // yoco sends the customer there on failure
    private $redirectUrl; // yoco sends this to you to send the customer to
    private $successUrl; // yoco sends the customer there on success
    private $cancelUrl; // yoco sends the customer there when they cancel
    private $amount; // cents
    private $currency; / ZAR
    private $metadata = []; // undocumented, but it's an array map of info you can send them, unsure where they display it.
    private $totalDiscount; // display purposes only
    /**
     * Array of line items for the checkout.
     *
     * This property holds an array of line items that provide additional details
     * for the items purchased during the checkout process. Each line item should
     * be an associative array with the following structure:
     *
     * [
     *     "displayName" => "Product Name",
     *     "description" => "Product Description",
     *     "quantity" => 1, // Quantity of the product
     *     "pricingDetails" => [
     *         "price" => 100, // Price of the product in cents
     *     ],
     * ]
     *
     * @var array
     */
    private $lineItems = [];
    private $totalTaxAmount; // display purposes only
    private $subtotalAmount; // not always needed
    private $yocoConfig; // my config for this payment gateway

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(5));
        $this->currency = 'ZAR';
        $this->totalDiscount = 0;
        $this->yocoConfig = Config::get('payment_gateway')['yoco'];
    }

    public function setUrls($successUrl, $cancelUrl, $failureUrl = '')
    {
        $this->successUrl = $successUrl;
        $this->cancelUrl = $cancelUrl;
        $this->failureUrl = $failureUrl;
    }

    public function addItem($item)
    {
        if ($this->validateInvoiceLineItem($item)) {
            $this->lineItems[] = $item;
            return true; // Item added successfully
        } else {
            return false; // Validation failed
        }
    }

    private function validateInvoiceLineItem($item)
    {
        // Define the required fields and their types
        $requiredFields = [
            "displayName" => "string",
            "description" => "string",
            "quantity" => "integer",
            "pricingDetails" => "array"
        ];

        // Check if all required fields are present and have the correct type
        foreach ($requiredFields as $field => $type) {
            if (!array_key_exists($field, $item) || gettype($item[$field]) !== $type) {
                return false;
            }
        }

        // Check the pricingDetails structure if it's an array
        if (isset($item["pricingDetails"]) && !is_array($item["pricingDetails"])) {
            return false;
        }

        return true; // All validation checks passed
    }

    public function getItems() {

        if(empty($this->lineItems)) {
            return 'No items has been added yet, please add items first.';
        }

        return $this->lineItems;
    }

    public function checkout($amount, $note = '', $additionalNote = '', $transactionId = '')
    {

        // Construct checkout data
        $this->amount = $amount;
        $this->totalTaxAmount = $this->amount - ($this->amount) / 1.15; // show the tax separately - 99 - (99/(1.15)) = R12.91 VAT

        if (!$this->lineItems) {
            echo 'No items have been added yet, please add items first.';
            return false;
        }

        $data = array(
            "amount" => $this->amount,
            "totalTaxAmount" => $this->totalTaxAmount,
            "currency" => $this->currency,
            "totalDiscount" => $this->totalDiscount,
            "successUrl" => $this->successUrl,
            "cancelUrl" => $this->cancelUrl,
            "failureUrl" => $this->failureUrl,
            "metadata" => array(
                "billNote" => $note, // optional 
                "additionalNote" => $additionalNote, // optional 
                "transactionId" => $transactionId, // optional 
            ),
            "lineItems" => $this->getItems()
        );

        // Perform the API request
        $response = $this->sendCheckoutRequest($data);

        if ($response === false) {
            return false; // Failed to perform API request
        }

        return $response;
    }

    private function sendCheckoutRequest($data)
    {
        $payload = json_encode($data);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://payments.yoco.com/api/checkouts",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer ".$this->yocoConfig['xxxx_api_key'],
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $err =  "cURL Error #:" . $err;
            return false;
        } else {
            return $response;
        }
    }
}
