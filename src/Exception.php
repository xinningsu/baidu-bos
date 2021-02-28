<?php

namespace Sulao\BaiduBos;

class Exception extends \Exception
{
    public $requestId = null;
    public $bosCode = null;

    /*' GuzzleException)
    └── RequestException
        ├── BadResponseException
        │   ├── ServerException
        │   └── ClientException
        ├── ConnectException
        └── TooManyRedirectsException'*/
}
