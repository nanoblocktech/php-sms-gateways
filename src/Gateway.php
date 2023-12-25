<?php
/**
 * Luminova Framework
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\ExtraUtils\Sms;

use Luminova\ExtraUtils\Sms\Interface\ProviderInterface;
use Luminova\ExtraUtils\Sms\Response;
use Luminova\ExtraUtils\Sms\Exceptions\SmsException;
use \GuzzleHttp\Client as GuzzleHttpClient;
use \Exception;

class Gateway
{
    /**
     * Sms singleton instance
     * 
     * @var Gateway $instance
    */
    private static ?self $instance = null;

    /**
     * Client instance
     * 
     * @var ProviderInterface $client
    */
    private ProviderInterface $client;

    /**
     * Network instance
     * 
     * @var object $network
    */
    private ?object $network = null;

    /**
     * Phone number
     * 
     * @var string $phone
    */
    public string $phone = '';

    /**
     * From number
     * 
     * @var string $fromNumber
    */
    public string $fromNumber = '';

    /**
     * Message body
     * 
     * @var Response $response
    */
    private Response $response;

    /**
     * Message body
     * 
     * @var string $message
    */
    public string $message = '';

    /**
     * Gateway constructor.
     *
     * @param ProviderInterface $client The sms client instance to use.
     * @param object $network Http network client instance to use 
    */
    private function __construct(ProviderInterface $client, ?object $network = null)
    {
        $this->client = $client;
        $this->network = $network;
    }

    /**
     * Get the Sms singleton instance.
     * 
     * @param ProviderInterface $client The sms client instance to use.
     * @param object $network Http network client instance to use 
     *
     * @return self The ProviderInterface instance.
     */
    public static function getInstance(ProviderInterface $client, ?object $network = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($client, $network);
        }

        return self::$instance;
    }

    /**
     * Set phone number
     *
     * @param string $phone The phone number to send message to
     *
     * @return self $this
     */
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Set from number
     *
     * @param string $from The sender number
     *
     * @return self $this
     */
    public function setFrom(string $from): self
    {
        $this->fromNumber = $from;

        return $this;
    }

    /**
     * Set Message
     *
     * @param string $message The message to send
     *
     * @return self $this
    */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get response instance
     *
     * @return Response $this->response
    */
    public function getResponse(): Response 
    {
        return $this->response;
    }

    /**
     * Send sms.
     *
     * @return bool True if the message was sent successfully, false otherwise.
     * @throws SmsException
    */
    public function send(): bool
    {
        $config = $this->client->getConfig();
        $provider = $this->client->getProvider();
        $instance = null;

        if($provider === 'vonage'){
            $instance = $this->client::getInstance(null, $config);
        }elseif($provider === 'clicksend'){
           
            if ($this->network === null && class_exists(GuzzleHttpClient::class)) {
                $this->network = new GuzzleHttpClient();
            }            

            if($this->network === null){
                throw new SmsException('ClickSend requires a http network client class, install "\GuzzleHttp\Client" to use ClickSend');
            }

            $instance = $this->client::getInstance($this->network, $config);
        }else{
            throw new SmsException('No sms client instance found for ' . get_class($this->client));
        }

        try{
            $surface =  $this->client->messageSurface($this->phone, $this->fromNumber, $this->message);
        
            $this->response = $this->client->send($instance, $surface);

            if($this->response->isSuccess()){
                return true;
            }
        }catch(Exception $e){
            $data = (object) [
                'status' => 0,
                'originalStatus' => $e->getCode(),
                'success' => false,
                'response' => null,
                'error' => $e->getMessage()
            ];
            $this->response = new Response($data);
        }

        return false;
    }

}