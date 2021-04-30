<?php

namespace emspa_payments\Components\emspay;

class Service
{
    /**
     * @param $request \Enlight_Controller_Request_Request
     * @return Response
     */
    public function createPaymentResponse(\Enlight_Controller_Request_Request $request)
    {
        $response = new Response();
        $response->transactionId = $request->getParam('transactionId', null);
        $response->status = $request->getParam('status', null);
        $response->token = $request->getParam('token', null);

        return $response;
    }

    /**
     * @param Response $response
     * @param string $token
     * @return bool
     */
    public function isValidToken(Response $response, $token)
    {
        return hash_equals($token, $response->token);
    }

    /**
     * @param float $amount
     * @param int $customerId
     * @return string
     */
    public function createPaymentToken($amount, $customerId)
    {
        return md5(implode('|', [$amount, $customerId]));
    }
}
