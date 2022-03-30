<?php

namespace Asiabill\Payment\Model;

class PaymentLogger extends \Magento\Payment\Model\Method\Logger
{
    const PATH = BP.'/var/log/asiabill_log';
    const FILE = 'payment.log';
    static $systemLogger = null;
    static $isLogger = false;

    public function startLogger($start_log = 0){
        if( $start_log == 1 )
            self::$isLogger = true;
    }

    public function addLog($obj){

        if( !self::$isLogger ) return;

        $data = self::getPrintableObject($obj);

        if( class_exists('\Zend\Log\Writer\Stream') && class_exists('\Zend\Log\Logger') ){
            $dir = self::PATH;
            $file = self::FILE;

            if(!is_dir($dir)){
                mkdir($dir,0755);
            }

            $path = explode('.', $file);
            $path = $path[0];
            $file = $path."_".date('Ymd',time()).'.'.pathinfo($file)['extension'];

            $writer = new \Zend\Log\Writer\Stream($dir.'/'.$file);
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($data,true));

        }else{
            $this->addBug($data);
        }
    }

    public function addBug($data){
        if( method_exists(self::$systemLogger,'addDebug') ){
            self::$systemLogger->addDebug(print_r($data, true));
        }
    }

    public function getPrintableObject($obj){

        if (!self::$systemLogger)
            self::$systemLogger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);

        if (is_object($obj))
        {
            if (method_exists($obj, 'debug'))
                $data = $obj->debug();
            else if (method_exists($obj, 'getData'))
                $data = $obj->getData();
            else
                $data = $obj;
        }
        else if (is_array($obj))
            $data = json_encode($obj);
        else
            $data = $obj;

        return $data;
    }

}