<?php

namespace emspaypaynow\Components\emspaypaynow;

class PaymentResponse
{
    /**
     * @var int
     */
    public $transactionId;

    /**
     * @var string
     */
    public $token;

    /**
     * @var string
     */
    public $status;
}
