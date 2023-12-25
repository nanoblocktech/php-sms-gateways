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
use \Vonage\Client\Credentials\Basic;
use \Vonage\Client as SmsClient;
use \Vonage\SMS\Message\SMS as SmsMessage;

class Vonage implements ProviderInterface
{
    /**
     * Provider name 
     * 
     * @var string $provider
    */
    private string $provider = 'vonage';

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
     * @param string $key Api key 
     * @param string $secret Api secret key
    */
    public function __construct($key, $secret)
    {
        $this->config = new Basic($key, $secret);    
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
            self::$instance = new SmsClient($config);
        }

        return self::$instance;
    }

    /**
     * Create message model and return MessageSurface instance 
     * 
     * @param string $to send message to number 
     * @param ?string $from Send message from number
     * @param string $message Message body to send 
     * 
     * @return MessageSurface 
    */
    public function messageSurface(string $to, ?string $from, string $message): MessageSurface
    {
        $sms = new SmsMessage($to, $from, $message);
        $sms->setClientRef('test-message');

        $clone = clone $sms;

        return new MessageSurface( $clone );
    }

    /**
     * Send sms 
     * 
     * @param object $instance client Instance
     * @param MessageSurface $message client message class instance  
     * 
     * @return Response 
    */
    public function send(object $instance, MessageSurface $surface): Response 
    {
        $response = $instance->sms()->send($surface->getCloned());

    
        $current = $response->current();
        $success = false;
        $status = -0;
        $originalStatus = $current->getStatus();
        if ($originalStatus == 0) {
            $success = true;
            $status = 200;
        }
        
        $data = (object) [
            'status' => $status,
            'originalStatus' => $originalStatus,
            'success' => $success,
            'response' => $current,
            'error' => null
        ];

        return new Response($data);
    }
}