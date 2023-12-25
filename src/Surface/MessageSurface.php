<?php
/**
 * Luminova Framework
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\ExtraUtils\Sms\Surface;

class MessageSurface
{
    /**
     * Cloned 
     * 
     * @var object $object
    */
    private $object;

    /**
     * Initialize MessageSurface constructor 
     * 
     * @param object $object cloned object
    */
    public function __construct(object $object){
        $this->object = $object;
    }

    /**
     * Get cloned object 
     * 
     * @return object $this->object
    */
    public function getCloned(): object 
    {
        return $this->object;
    }
}