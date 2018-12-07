<?php

namespace Wuunder\Api;


class Environment
{
    const STAGING_ENV_BASE_URL = "https://api-staging.wearewuunder.com/api";
    const PRODUCTION_ENV_BASE_URL = "https://api.wearewuunder.com/api";

    private $stageBaseUrl;

    public function __construct($stage)
    {
        if ($stage === "staging") {
            $this->stageBaseUrl = self::STAGING_ENV_BASE_URL;
        } else  if ($stage === "production") {
            $this->stageBaseUrl = self::PRODUCTION_ENV_BASE_URL;
        } else {
            throw new Exception('Unknown stage');
        }
    }

    /**
     * Returns stage base url
     *
     * @return string
     */
    public function getStageBaseUrl()
    {
        return $this->stageBaseUrl;
    }
}
