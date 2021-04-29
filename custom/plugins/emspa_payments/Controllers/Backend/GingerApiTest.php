<?php
use emspa_payments\Components\emspay\Helper;
use Monolog\Logger;
use Shopware\Components\HttpClient\HttpClientInterface;
use Shopware\Components\HttpClient\RequestException;
use Symfony\Component\HttpFoundation\Response;

class Shopware_Controllers_Backend_GingerApiTest extends \Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Helper;
     */
    private $helper;
    /**
     * @var \Ginger\ApiClient
     */
    private $ems;

    public function __construct()
    {
        $this->helper = Shopware()->Container()->get('emspa_payments.helper');
        parent::__construct();
    }

    public function testAction()
    {
        //Create EMS
        try {
            $this->ems = $this->helper->getClient(Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('emspa_payments'));
            if (is_array($this->ems->getIdealIssuers())) {
                $this->response->setStatusCode(200);
                $this->View()->assign('response', 'Access data is valid');
            } else {
                $this->response->setStatusCode(300);
                $this->View()->assign('response', 'Access data is not valid');
            }
        } catch (\Exception $exception)
        {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->View()->assign('response', 'Access data is not valid');
        }
    }
}