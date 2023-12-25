<?php
/**
 * Luminova Framework
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\ExtraUtils\Sms\Interface;

use Luminova\ExtraUtils\Sms\Surface\MessageSurface;
use Luminova\ExtraUtils\Sms\Response;

interface ProviderInterface
{
    /**
     * Get sms client configuration instance 
     * 
     * @return object $this->config
    */
    public function getConfig(): object;

    /**
     * Get sms provider name
     * 
     * @return string
    */
    public function getProvider(): string;

    /**
     * Get sms client instance
     * 
     * @param ?object $network Http network client instance
     * @param object $config Sms client configuration instance
     * 
     * @return object $this->config
    */
    public static function getInstance(?object $network, object $config): object;

    /**
     * Create message model and return MessageSurface instance 
     * 
     * @param string $to send message to number 
     * @param ?string $from Send message from number
     * @param string $message Message body to send 
     * 
     * @return MessageSurface 
    */
    public function messageSurface(string $to, ?string $from, string $message): MessageSurface;

    /**
     * Send sms 
     * 
     * @param object $instance client Instance
     * @param MessageSurface $surface client message class instance  
     * 
     * @return Response 
    */
    public function send(object $instance, MessageSurface $surface): Response;
}