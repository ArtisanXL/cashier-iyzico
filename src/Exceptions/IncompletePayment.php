<?php

namespace ArtisanXL\CashierIyzico\Exceptions;

use ArtisanXL\CashierIyzico\Payment;

class IncompletePayment extends IyzicoException
{
    public function __construct(private readonly Payment $payment)
    {
        parent::__construct('The payment ['.($payment->paymentId() ?? 'unknown').'] was not successful (status: '.$payment->status().').');
    }

    public function payment(): Payment
    {
        return $this->payment;
    }
}
