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

class Response
{
    /**
     * Response data
     * 
     * @var object $object 
    */
    private $object;

    /**
     * Initialize response constructor
     * 
     * @param object $object 
    */
    public function __construct(object $object){
        $this->object = $object;
    }

    /**
     * Get response status 
     * 
     * @return mixed 
    */
    public function getStatus(): int 
    {
        return $this->object->status ?? 0;
    }

    /**
     * Get original response status 
     * 
     * @return mixed 
    */
    public function getOriginalStatus(): mixed 
    {
        return $this->object->originalStatus ?? -0;
    }

    /**
     * Check if sms was send successfully
     * 
     * @return bool 
    */
    public function isSuccess(): bool 
    {
        return $this->object->success ?? false;
    }

    /**
     * Get response content 
     * 
     * @return mixed 
    */
    public function getContent(): mixed 
    {
        return $this->object->response ?? null;
    }

    /**
     * Get error message 
     * 
     * @return string|null
    */
    public function getError(): ?string 
    {
        return $this->object->error ?? null;
    }
}