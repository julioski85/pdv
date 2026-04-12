<?php

namespace biomaussanpos\Sms\Gateways;

interface SmsGatewayInterface
{
    public function getUrl();
    public function sendSms($mobile, $message);
}
