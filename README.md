## PHP SMS GATEWAYS

PHP class for sms gateways, using same codebase and implementation to send sms in Vonage & ClickSend  sms gateways.

### AVAILABLE SMS PROVIDERS

- Vonage: To use Vonage as provider, install the library `composer require vonage/client`
- ClickSend To use ClickSend as provider, install the library `composer require clicksend/clicksend-php`

Installation Guide via Composer:

```bash
composer require nanoblocktech/php-sms-gateways
```

### Usages 

Initialize classes 

```php
use \Luminova\ExtraUtils\Sms\Gateway;
use \Luminova\ExtraUtils\Sms\Providers\Vonage;
use \Luminova\ExtraUtils\Sms\Providers\ClickSend;
use \Luminova\ExtraUtils\Sms\Exceptions\SmsException;
```

Initialize SMS Client using `Vonage`

```php
use Luminova\ExtraUtils\Sms\Providers\Vonage;

$client = new Vonage("KEY", "SECRETE");
```

Initialize SMS Client using `ClickSend`

```php
use Luminova\ExtraUtils\Sms\Providers\ClickSend;

$client = new ClickSend("USERNAME", "KEY");
```

Initialize SMS gateway

```php
$gateway = new Gateway($client);

$gateway->setPhone('000000000');
$gateway->setFrom('000000000');
$gateway->setMessage('Hello your verification code is 1234');
try {
    if($gateway->send()){
        echo "Message sent successfully";
    }else{
        $response = $gateway->getResponse();
        echo $response->getError();
    }
} catch (SmsException $e){
    echo $e->getMessage();
}
```

Response methods 

```php
 $response = $gateway->getResponse();

/**
 * Get response status 
*/
$response->getStatus();

/**
 * Get gateways response status 
*/
$response->getOriginalStatus();

/**
 * Check if sms message was sent
*/
$response->isSuccess();

/**
 * Get api response body
*/
$response->getContent();

/**
 * Get error if any 
*/
$response->getError();
```
