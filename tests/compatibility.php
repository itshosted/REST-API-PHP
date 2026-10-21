<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require __DIR__ . '/../src/Icepay/API/Resources/BaseApi.php';
require __DIR__ . '/../src/Icepay/API/Resources/Payment.php';
require __DIR__ . '/../src/Icepay/API/Resources/Refund.php';
require __DIR__ . '/../src/Icepay/API/Client.php';

function checkSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

class RecordingClient extends Icepay\API\Client
{
    public function request($method, $api_method, $body, $checksum)
    {
        checkSame('POST', $method, 'Unexpected HTTP method');
        checkSame(
            hash('sha256', $this->api_endpoint . $api_method . $method .
                $this->api_key . $this->api_secret . json_encode($body)),
            $checksum,
            'Invalid request checksum'
        );
        checkSame(true, isset($body['Timestamp']), 'Missing timestamp');

        return array('endpoint' => $api_method, 'body' => $body);
    }
}

$client = new RecordingClient();
checkSame(true, $client->payment instanceof Icepay\API\Resources\Payment, 'Missing payment resource');
checkSame(true, $client->refund instanceof Icepay\API\Resources\Refund, 'Missing refund resource');
checkSame($client, $client->payment->client, 'Payment resource has the wrong client');
checkSame($client, $client->refund->client, 'Refund resource has the wrong client');
checkSame(Icepay\API\Client::getInstance(), Icepay\API\Client::getInstance(), 'Singleton changed');

$client->setApiKey(' merchant ');
$client->setApiSecret(' secret ');
$client->setCompletedURL(' https://example.test/completed ');
$client->setErrorURL(' https://example.test/error ');
checkSame('merchant', $client->api_key, 'API key was not trimmed');
checkSame('secret', $client->api_secret, 'API secret was not trimmed');
checkSame('https://example.test/completed', $client->api_completed_url, 'Completed URL was not trimmed');
checkSame('https://example.test/error', $client->api_error_url, 'Error URL was not trimmed');

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
checkSame('127.0.0.1', $client->payment->getClientIpFromServerVar(), 'Unexpected client IP');

$data = array(
    'Amount' => 1000,
    'Country' => 'NL',
    'Currency' => 'EUR',
    'Description' => 'Compatibility test',
    'Issuer' => '',
    'Language' => 'NL',
    'OrderID' => 'order-1',
    'Paymentmethod' => 'PAYSAFECARD',
    'Reference' => 'reference-1',
    'CustomerID' => 'customer-1',
    'MerchantClientID' => 'merchant-client-1',
    'PaymentID' => 123,
    'RefundID' => 456
);

$checkoutMethods = array(
    'checkOut' => 'payment/checkout',
    'vaultCheckout' => 'payment/vaultcheckout',
    'autoCheckout' => 'payment/automaticcheckout'
);
foreach ($checkoutMethods as $method => $endpoint) {
    $result = $client->payment->$method($data);
    checkSame($endpoint, $result['endpoint'], 'Unexpected checkout endpoint');
    checkSame(1000, $result['body']['Amount'], 'Unexpected checkout amount');
    checkSame('PAYSAFECARD', $result['body']['PaymentMethod'], 'Unexpected payment method');
    checkSame($client->api_completed_url, $result['body']['URLCompleted'], 'Unexpected completed URL');
    checkSame($client->api_error_url, $result['body']['URLError'], 'Unexpected error URL');
    if ($method === 'checkOut') {
        checkSame('merchant-client-1', $result['body']['MerchantClientID'], 'Missing Paysafecard client ID');
    } else {
        checkSame('customer-1', $result['body']['CustomerID'], 'Missing recurring customer ID');
    }
}

unset($data['MerchantClientID']);
$result = $client->payment->checkOut($data);
checkSame(false, isset($result['body']['MerchantClientID']), 'Unexpected optional client ID');
$result = $client->payment->getMyPaymentMethods();
checkSame('payment/getmypaymentmethods', $result['endpoint'], 'Unexpected payment methods endpoint');
$result = $client->payment->getPayment($data);
checkSame('payment/getpayment', $result['endpoint'], 'Unexpected payment lookup endpoint');
checkSame(123, $result['body']['PaymentID'], 'Unexpected payment ID');

$refundMethods = array(
    'startRefund' => 'refund/requestrefund',
    'cancelRefund' => 'refund/cancelrefund',
    'getRefundStatus' => 'refund/getpaymentrefunds'
);
foreach ($refundMethods as $method => $endpoint) {
    $result = $client->refund->$method($data);
    checkSame($endpoint, $result['endpoint'], 'Unexpected refund endpoint');
    checkSame(123, $result['body']['PaymentID'], 'Unexpected refund payment ID');
    if ($method === 'startRefund') {
        checkSame(1000, $result['body']['RefundAmount'], 'Unexpected refund amount');
        checkSame('EUR', $result['body']['RefundCurrency'], 'Unexpected refund currency');
    } elseif ($method === 'cancelRefund') {
        checkSame(456, $result['body']['RefundID'], 'Unexpected refund ID');
    }
}

restore_error_handler();
echo 'Compatibility checks passed on PHP ' . PHP_VERSION . PHP_EOL;
