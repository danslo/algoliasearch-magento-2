<?php

namespace Algolia\AlgoliaSearch\Helper;

class SupportHelper
{
    const INTERNAL_API_PROXY_URL = 'https://lj1hut7upg.execute-api.us-east-2.amazonaws.com/dev/';

    /** @var ConfigHelper */
    private $configHelper;

    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /** @return bool */
    public function isExtensionSupportEnabled()
    {
        $appId = $this->configHelper->getApplicationID();
        $apiKey = $this->configHelper->getAPIKey();

        $token = $appId . ':' . $apiKey;
        $token = base64_encode($token);
        $token = str_replace(["\n", '='], '', $token);

        $params = [
            'appId' => $appId,
            'token' => $token,
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::INTERNAL_API_PROXY_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($ch);

        curl_close ($ch);

        if ($res === 'true') {
            return true;
        }

        return false;
    }
}
