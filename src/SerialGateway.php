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

use Luminova\ExtraUtils\Sms\Exceptions\SmsException;

class SerialGateway
{
    /**
     * @var int SERIAL_DEVICE_UNSET 
    */
    private const SERIAL_DEVICE_UNSET = 0;

    /**
     * @var int SERIAL_DEVICE_SET 
    */
    private const SERIAL_DEVICE_SET = 1; 

    /**
     * @var int SERIAL_DEVICE_OPENED 
    */
    private const SERIAL_DEVICE_OPENED = 2;

    /**
     * @var int SERIAL_DEVICE_CLOSED 
    */
    private const SERIAL_DEVICE_CLOSED = 3;

    /**
     * @var string $device 
    */
    private ?string $device = '';

    /**
     * @var string $winDevice 
    */
    private ?string $winDevice = '';

    /**
     * @var resource|bool $deviceHandle 
    */
    private mixed $deviceHandle = null;

    /**
     * @var int $deviceState 
    */
    private int $deviceState = 0;

    /**
     * @var string $buffer 
    */
    private string $buffer = '';

    /**
     * @var string $os 
    */
    private string $os = '';

    /**
     * @var bool $autoFlush 
    */
    private bool $autoFlush = true;

    /**
     * @var bool $isDebug 
    */
    private bool $isDebug = true;

    /**
     * Initializes the constructor
     * 
     * @param string $device name/address.
     * @param string $local system local information
    */
    public function __construct(string $device = 'COM4', string $local = 'en_US')
    {
        if(!$this->initializeOSSystem($local)){
            throw new SmsException('Unable to initialize OS serai');
        }

        if(!$this->setDevice($device)){
            throw new SmsException('Unable to set device');
        }
    }

    /**
     * Set enable Log error, warning message
     * 
     * @param bool $enable 
     * 
     * @return self 
    */
    private function enableLog(bool $enable): self 
    {
        $this->isDebug = $enable;

        return $this;
    }

    /**
     * Log error, warning message
     * 
     * @param string $message 
     * @param int $level error level 
     * 
     * @return void 
    */
    private function log(string $message, int $level = E_USER_WARNING): void 
    {
        if($this->isDebug){
            trigger_error($message, $level);
        }
    }

    /**
     * Set the device name/address.
     * -> linux : use the device address, like /dev/ttyS0
     * -> osx : use the device address, like /dev/tty.serial
     * -> windows : use the COMxx device name, like COM1 (can also be used with linux)
     * 
     * @param string $device the name of the device to be used
     * 
     * @return bool
     */
    public function setDevice(string $device): bool
    {
        if ($this->deviceState === self::SERIAL_DEVICE_OPENED)
        {
            $this->log("The device is already opened", E_USER_NOTICE);
            return true;
        }

        $commands = [
            "linux" => "stty -F ",
            "osx" => "stty -f ",
            "windows" => "mode "
        ];

        $command = '';

        if ($this->os === "linux") {
            if (preg_match("@^COM(\d+):?$@i", $device, $matches)){
                $device = "/dev/ttyS" . ($matches[1] - 1);
            }
            $command = $commands[$this->os] . $device;
        }
        elseif ($this->os === "osx"){
            $command = $commands[$this->os] . $device;
        }
        elseif ($this->os === "windows"){
            if (preg_match("@^COM(\d+):?$@i", $device, $matches)){
                $this->winDevice = "COM" . $matches[1];
                $device = "\\.\com" . $matches[1];
                $command = $commands[$this->os] . $this->winDevice . " xon=on BAUD=9600";
                //$command = exec( $command );
            }
        }

        if (!empty($command) && $this->executeCommand($command) === 0){
            $this->device = $device;
            $this->deviceState = self::SERIAL_DEVICE_SET;

            return true;
        }

        $this->log("Specified serial port is not valid");
        return false;
    }


     /**
     * Opens the device for reading and/or writing.
     *
     * @param string $mode Opening mode, same parameter as fopen mode
     * 
     * @return bool
     */
    public function openDevice(string $mode = "r+b"): bool
    {
        if ($this->deviceState === self::SERIAL_DEVICE_OPENED)
        {
            $this->log("The device is already opened", E_USER_NOTICE);
            return true;
        }

        if ($this->deviceState === self::SERIAL_DEVICE_UNSET)
        {
            $this->log("The device must be set before to be open");
            return false;
        }

        if (!preg_match("@^[raw]\+?b?$@", $mode))
        {
            $this->log("Invalid opening mode : ".$mode.". Use fopen() modes.");
            return false;
        }

        $this->deviceHandle = fopen($this->device, $mode);

        if ($this->deviceHandle !== false)
        {
            stream_set_blocking($this->deviceHandle, 0);
            $this->deviceState = self::SERIAL_DEVICE_OPENED;
            return true;
        }

        $this->deviceHandle = null;
        $this->log("Unable to open the device");
        return false;
    }

    /**
     * Closes the device
     *
     * @return bool
    */
    public function closeDevice(): bool
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_OPENED)
        {
            return true;
        }

        if (fclose($this->deviceHandle)){
            $this->deviceHandle = null;
            $this->deviceState = self::SERIAL_DEVICE_SET;
            return true;
        }

        $this->log("Unable to close the device", E_USER_ERROR);
        return false;
    }

    /**
     * Set the Baud Rate
     * Possible rates: 110, 150, 300, 600, 1200, 2400, 4800, 9600, 38400,
     * 57600, and 115200.
     *
     * @param int $rate the rate to set the port in
     * 
     * @return bool
    */
    public function setBaudRate(int $rate): bool 
    {
        $validBauds = [
            110 => 11,
            150 => 15,
            300 => 30,
            600 => 60,
            1200 => 12,
            2400 => 24,
            4800 => 48,
            9600 => 96,
            19200 => 19,
            38400 => 38400,
            57600 => 57600,
            115200 => 115200
        ];

        if ($this->deviceState !== self::SERIAL_DEVICE_SET) {
            $this->log("Unable to set the baud rate: the device is either not set or opened");
            return false;
        }

        if (!isset($validBauds[$rate])) {
            $this->log("Invalid baud rate specified");
            return false;
        }

        $command = "";

        if ($this->os === "linux") {
            $command = "stty -F {$this->device} {$rate}";
        } elseif ($this->os === "darwin") {
            $command = "stty -f {$this->device} {$rate}";
        } elseif ($this->os === "windows") {
            $command = "mode {$this->winDevice} BAUD=" . $validBauds[$rate];
        } else {
            return false;
        }

        $ret = $this->executeCommand($command, $out);

        if ($ret !== 0) {
            $this->log("Unable to set baud rate: {$out[1]}");
            return false;
        }

        return true;
    }


    /**
     * Set parity.
     * Available modes : odd, even, none
     *
     * @param string $parity one of the modes
     * 
     * @return bool
    */
    public function setParity(string $parity): bool 
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_SET){
            $this->log("Unable to set parity : the device is either not set or opened");
            return false;
        }

        $args = [
            "none" => "-parenb",
            "odd"  => "parenb parodd",
            "even" => "parenb -parodd",
        ];

        if (!isset($args[$parity])){
            $this->log("Parity mode not supported");
            return false;
        }

        if ($this->os === "linux"){
            $ret = $this->executeCommand("stty -F " . $this->device . " " . $args[$parity], $out);
        }
        else{
            $ret = $this->executeCommand("mode " . $this->winDevice . " PARITY=" . $parity[0], $out);
        }

        if ($ret === 0){
            return true;
        }

        $this->log("Unable to set parity : " . $out[1]);

        return false;
    }

    /**
     * Sets the length of a character.
     * Ensure $length is between 5 and 8
     *
     * @param int $int length of a character
     * 
     * @return bool
    */
    public function setCharacterLength(int $length): bool
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_SET)
        {
            $this->log("Unable to set the length of a character: the device is either not set or opened");
            return false;
        }

        $length = max(5, min(8, $length));

        $command = ($this->os === "linux") ? "stty -F {$this->device} cs{$length}" : "mode {$this->winDevice} DATA={$length}";

        $ret = $this->executeCommand($command, $out);

        if ($ret === 0)
        {
            return true;
        }

        $this->log("Unable to set character length: {$out[1]}");
        return false;
    }


    /**
     * Sets the length of stop bits.
     *
     * @param float $length the length of a stop bit. It must be either 1,
     * 1.5 or 2. 1.5 is not supported under linux and on some computers.
     * 
     * @return bool
    */
    public function setStopBits(float $length): bool 
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_SET)
        {
            $this->log("Unable to set the length of a stop bit: the device is either not set or opened");
            return false;
        }

        $validLengths = [1, 1.5, 2];
        if (!in_array($length, $validLengths) || ($length == 1.5 && $this->os !== "linux"))
        {
            $this->log("Specified stop bit length is invalid");
            return false;
        }

        $commendArg = ($length == 1 ? '-' : '');
        $command = ($this->os === "linux") ? "stty -F {$this->device} {$commendArg}cstopb" : "mode {$this->winDevice} STOP={$length}";

        $ret = $this->executeCommand($command, $out);

        if ($ret === 0)
        {
            return true;
        }

        $this->log("Unable to set stop bit length: {$out[1]}");
        return false;
    }


    /**
     * Set the flow control
     *
     * @param string $mode Set the flow control mode. Available modes
     *      -> "none" : No flow control
     *      -> "rts/cts" : Use RTS/CTS handshaking
     *      -> "xon/xoff" : Use XON/XOFF protocol
     * 
     * @return bool
     */
    public function setFlowControl(string $mode): bool 
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_SET)
        {
            $this->log("Unable to set flow control mode : the device is either not set or opened");
            return false;
        }

        if(!in_array($mode, ['none', 'rts/cts', 'xon/xoff'])){
            $this->log("Invalid flow control mode specified", E_USER_ERROR);

            return false;
        }

        $linuxModes = [
            "none"     => "clocal -crtscts -ixon -ixoff",
            "rts/cts"  => "-clocal crtscts -ixon -ixoff",
            "xon/xoff" => "-clocal -crtscts ixon ixoff"
        ];
        $windowsModes = [
            "none"     => "xon=off octs=off rts=on",
            "rts/cts"  => "xon=off octs=on rts=hs",
            "xon/xoff" => "xon=on octs=off rts=on",
        ];

        if ($this->os === "linux"){
            $ret = $this->executeCommand("stty -F " . $this->device . " " . $linuxModes[$mode], $out);
        } else{
            $ret = $this->executeCommand("mode " . $this->winDevice . " " . $windowsModes[$mode], $out);
        }

        if ($ret === 0){
            return true;
        }
            
        $this->log("Unable to set flow control : " . $out[1], E_USER_ERROR);
        return false;
    }

    /**
     * Sets a set serial parameter (cf man set serial)
     * set serial is no longer supported
     * Only use it if you need it
     *
     * @param string $param parameter name
     * @param string $argument parameter value
     * 
     * @return bool
     */
    public function setSerialFlag (string $param, string $argument = ''): bool 
    {
        if (!$this->isOpen()) {
            return false;
        }

        $return = exec ("setserial " . $this->device . " " . $param . " " . $argument . " 2>&1");

        if ($return[0] === "I") {
            $this->log("setSerialFlag: Invalid flag");

            return false;
        } elseif ($return[0] === "/") {
            $this->log("setSerialFlag: Error with device file");

            return false;
        }

        return true;
    }

     /**
     * Sends a string to the device
     *
     * @param string $body string to be sent to the device
     * @param float $waitForReply time to wait for the reply (in seconds)
     * 
     * @return void 
     */
    public function sendMessage(string $body, float $waitForReply = 0.1): void 
    {
        $this->buffer .= $body;
        if ($this->autoFlush === true){
            $this->serialFlush();
        }

        usleep((int) ($waitForReply * 1000000));
    }

    /**
     * Reads the port until no new data are available, then return the content.
     *
     * @param int $count number of characters to be read (will stop before if less characters are in the buffer)
     * 
     * @return mixed
    */
    public function readPort($count = 0): mixed 
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_OPENED)
        {
            $this->log("Device must be opened to read it");
            return null;
        }

        if ($this->os === "linux" || $this->os === "osx") {
            // Behavior in OSX isn't to wait for new data to recover, but just grabs what's there!
            // Doesn't always work perfectly for me in OSX
            $content = ""; 
            $i = 0;

            if ($count !== 0) {
                do {
                    if ($i > $count) $content .= fread($this->deviceHandle, ($count - $i));
                    else $content .= fread($this->deviceHandle, 128);
                } while (($i += 128) === strlen($content));
            }
            else {
                do {
                    $content .= fread($this->deviceHandle, 128);
                } while (($i += 128) === strlen($content));
            }

            return $content;
        }
        elseif ($this->os === "windows"){
            $content = "";
            if ($count > 0){
                    $content = fread($this->deviceHandle, $count);
            }
            return $content;
        }


        return null;
    }

    /**
     * Flush butter 
     * 
     * @return bool 
    */
    public function serialFlush(): bool 
    {
        if (!$this->isOpen()){
            return false;
        } 

        if (fwrite($this->deviceHandle, $this->buffer) !== false){
            $this->buffer = '';
            return true;
        }
   
        $this->buffer = '';
        $this->log("Error while sending message");
        return false;
    }

    
    /**
     * Check if device is open
     * 
     * @return bool 
    */
    private function isOpen(): bool 
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_OPENED)
        {
            $this->log("Device must be opened");
            return false;
        }
        return true;
    }

    /**
     * Check if device is closed
     * 
     * @return bool 
    */
    private function isClosed(): bool 
    {
        if ($this->deviceState !== self::SERIAL_DEVICE_CLOSED){
            $this->log("Device must be closed");
            return false;
        }
        return true;
    }

    /**
     * Execute command 
     * @param string|array $command Command to execute
     * @param ?array $out return output
     * 
     * @return int
     *
    */
    private function executeCommand(string|array $command, ?array &$out = null): int
    {
        $desc = [
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $proc = proc_open($command, $desc, $pipes);

        $ret = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $retVal = proc_close($proc);

        if (func_num_args() == 2) {
            $out = [$ret, $err];
        }

        return $retVal;
    }


    /**
     * Constructor. Perform some checks about the OS and set serial
     * @param string $local Set locale information
     * 
     * @return bool
     *
    */
    private function initializeOSSystem(string $local): bool 
    {
        setlocale(LC_ALL, $local);
        $systemName = php_uname();
        $error = "Host OS is neither osx, linux nor windows, unable to run.";
        if (substr($systemName, 0, 5) === "Linux") {
            $this->os = "linux";

            if ($this->executeCommand("stty --version") === 0) {
                register_shutdown_function([$this, "closeDevice"]);
                return true;
            }
            $error = "No stty available, unable to run.";
        } elseif (substr($systemName, 0, 6) === "Darwin") {
            $this->os = "osx";
            if ($this->executeCommand("stty") === 0) {
                register_shutdown_function([$this, "closeDevice"]);
                return true;
            }
        } elseif (substr($systemName, 0, 7) === "Windows") {
            $this->os = "windows";
            register_shutdown_function([$this, "closeDevice"]);
            return true;
        }

        $this->log($error, E_USER_ERROR);

        return false;
    }

    /**
     * Send your message 
     * 
     * @param string $to Phone number
     * @param string $message Message text
     * @param callable $callback Optional callback function
     * 
     * @return void
     *
    */
    public function send(string $to, string $message, ?callable $callback = null): void 
    {
        $config = (object) [
            'baudRate' => 9600,
            'parity' => 'none',
            'characterLength' => 8,
            'stopBits' => 1,
            'flowControl' => 'none',
            'sleep' => 7
        ];
        $this->setBaudRate($config->baudRate);
        $this->setParity($config->parity);
        $this->setCharacterLength($config->characterLength);
        $this->setStopBits($config->stopBits);
        $this->setFlowControl($config->flowControl);
        if($this->openDevice()){
            $this->sendMessage("AT+CMGF=1\n\r"); 
            $this->sendMessage("AT+cmgs=\"{$to}\"\n\r");
            $this->sendMessage("{$message}\n\r");
            $this->sendMessage(chr(26));
            sleep($config->sleep);
            $read = $this->readPort();
            $this->closeDevice();
            if($callback !== null){
                $callback($read);
                return;
            }
            echo "Message was sent successfully";
        }
    }
}
