<?php

namespace SzamlaAgent;

class SzamlaAgentException extends \Exception
{

    const SYSTEM_DOWN                            = 'The site is currently under maintenance. Please check back in a few minutes.';
    const REQUEST_TYPE_NOT_EXISTS                = 'The request type does not exist';
    const RESPONSE_TYPE_NOT_EXISTS               = 'The response type does not exist';
    const CALL_TYPE_NOT_EXISTS                   = 'Nonexistent call type';
    const XML_SCHEMA_TYPE_NOT_EXISTS             = 'The XML schema type does not exist';
    const XML_KEY_NOT_EXISTS                     = 'XML key does not exist';
    const XML_NOT_VALID                          = 'The compiled XML is not valid';
    const XML_DATA_NOT_AVAILABLE                 = 'An error occurred while compiling the XML data: no data.';
    const XML_DATA_BUILD_FAILED                  = 'Compilation of XML data failed';
    const FIELDS_CHECK_ERROR                     = 'Error checking fields';
    const CONNECTION_METHOD_CANNOT_BE_DETERMINED = 'The type of connection method cannot be determined';
    const DATE_FORMAT_NOT_EXISTS                 = 'There is no such date format';
    const NO_AGENT_INSTANCE_WITH_USERNAME        = 'There is no Account Agent instantiated with this username!';
    const NO_AGENT_INSTANCE_WITH_APIKEY          = 'There is no Account Agent instantiated with such a key!';
    const NO_SZLAHU_KEY_IN_HEADER                = 'Invalid answer!';
    const DOCUMENT_DATA_IS_MISSING               = 'The PDF data of the receipt is missing!';
    const PDF_FILE_SAVE_SUCCESS                  = 'PDF file saved successfully';
    const PDF_FILE_SAVE_FAILED                   = 'Failed to save PDF file';
    const AGENT_RESPONSE_NO_CONTENT              = 'There is no content in the Account Agent\'s response!';
    const AGENT_RESPONSE_NO_HEADER               = 'The Account Agent\'s response does not contain a header!';
    const AGENT_RESPONSE_IS_EMPTY                = 'The Account Agent\'s answer cannot be empty!';
    const AGENT_ERROR                            = 'Agent error';
    const FILE_CREATION_FAILED                   = 'Failed to create file.';
    const ATTACHMENT_NOT_EXISTS                  = 'The file to attach does not exist';
    const SENDING_ATTACHMENT_NOT_ALLOWED         = 'Attaching an invoice attachment is only supported in the case of a CURL request!';
    const INVOICE_NOTIFICATION_SEND_FAILED       = 'Invoice delivery failed';
    const INVALID_JSON                           = 'Invalid JSON';
    const INVOICE_EXTERNAL_ID_IS_EMPTY           = 'The external account ID is empty';

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
