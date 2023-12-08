<?php

namespace SzamlaAgent;

use SzamlaAgent\Response\SzamlaAgentResponse;

class SzamlaAgentAPI extends SzamlaAgent
{
    public static function create($apiKey, $downloadPdf = true, $logLevel = Log::LOG_LEVEL_DEBUG, $responseType = SzamlaAgentResponse::RESULT_AS_TEXT, $aggregator = '')
    {
        return resolve(static::class, [null, null, $apiKey, $downloadPdf, $logLevel, $responseType, $aggregator]);
    }
}
