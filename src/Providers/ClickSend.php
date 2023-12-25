<?php
/**
 * Luminova Framework
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\ExtraUtils\Sms\Providers;

use Luminova\ExtraUtils\Sms\Interface\ProviderInterface;
use Luminova\ExtraUtils\Sms\Surface\MessageSurface;
use Luminova\ExtraUtils\Sms\Response;
use Luminova\ExtraUtils\Sms\Exceptions\SmsException;
use \ClickSend\Configuration;
use \ClickSend\Api\SMSApi as SmsClient;
use \ClickSend\Model\SmsMessage;
use \ClickSend\Model\SmsMessageCollection;

class ClickSend implements ProviderInterface
{
    /**
     * Provider name 
     * 
     * @var string $provider
    */
    private string $provider = 'clickend';

    /**
     * Configurations
     * 
     * @var object $config
    */
    private $config;

    /**
     * Sms client instance 
     * 
     * @var ?SmsClient $instance
    */
    private static ?SmsClient $instance = null;

    /**
     * Initialize ClickSend constructor
     * 
     * @param string $user Api username 
     * @param string $key Api key / password
     * 
     * @throws SmsException
    */
    public function __construct($user, $key)
    {
        if (!class_exists(SmsClient::class)) {
            throw new SmsException('To use clickSend api, you need to first install the library by running [composer require clicksend/clicksend-php]');
        }
        
        $this->config = Configuration::getDefaultConfiguration()->setUsername($user)->setPassword($key);
    }

    /**
     * Get sms client configuration instance 
     * 
     * @return object $this->config
    */
    public function getConfig(): object 
    {
        return $this->config;
    }

    /**
     * Get sms provider name
     * 
     * @return string
    */
    public function getProvider(): string 
    {
        return $this->provider;
    }

    /**
     * Get sms client instance
     * 
     * @param ?object $network Http network client instance
     * @param object $config Sms client configuration instance
     * 
     * @return object $this->config
    */
    public static function getInstance(?object $network, object $config): object
    {
        if(self::$instance === null){
            self::$instance = new SmsClient($network, $config);
        }

        return self::$instance;
    }

    /**
     * Create message model and return SendSurface instance 
     * 
     * @param string $to send message to number 
     * @param ?string $from Send message from number
     * @param string $message Message body to send 
     * 
     * @return MessageSurface 
    */
    public function messageSurface(string $to, ?string $from, string $message): MessageSurface
    {
        $model = new SmsMessage();
        $model->setBody($message);
        $model->setTo($to);
        $model->setSource("php-sdk");

        $collection = new SmsMessageCollection(); 
        $collection->setMessages([$model]);

        $clone = clone $collection;

        return new MessageSurface( $clone );
    }

    /**
     * Send sms 
     * 
     * @param object $instance client Instance
     * @param MessageSurface $surface client message class instance  
     * 
     * @return Response 
    */
    public function send(object $instance, MessageSurface $surface): Response 
    {
        //$response = json_decode($instance->smsPricePost($sendSurface->getCloned()));
        $result = $instance->smsSendPost($surface->getCloned());
        $response = json_decode($result);

        $success = false;
        $status = -0;
        $originalStatus = $response->response_code ?? 'FAILED';

        if($response->http_code == 200 && $originalStatus === 'SUCCESS'){
            $success = true;
            $status = 200;
        }

        $data = (object) [
            'status' => $status,
            'originalStatus' => $originalStatus,
            'success' => $success,
            'response' => $response,
            'error' => null
        ];

        return new Response($data);
    }
}
