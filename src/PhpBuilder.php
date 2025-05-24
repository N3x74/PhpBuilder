<?php

namespace N3x74;

class PhpBuilder
{
    private $inputUrl;
    private $inputMethod;
    private $inputPayloadType;
    private $inputPayload;
    private $inputHeaders = [];
    private $inputTimeout = 0;
    private $inputConnectTimeout = 0;
    private const ALLOW_METHODS = [
        'GET' => ['NONE', 'URL-ENCODE'],
        'POST' => ['NONE', 'URL-ENCODE', 'JSON', 'XML', 'MULTIPART', 'TEXT', 'BINARY', 'CUSTOM', 'GRAPHQL', 'YAML', 'HTML'],
        'PUT' => ['NONE', 'URL-ENCODE', 'JSON', 'XML', 'TEXT', 'BINARY', 'CUSTOM', 'GRAPHQL', 'YAML', 'HTML'],
        'DELETE' => ['NONE', 'URL-ENCODE', 'JSON', 'XML', 'TEXT', 'BINARY', 'CUSTOM', 'GRAPHQL', 'YAML', 'HTML'],
        'HEAD' => ['NONE'],
        'OPTIONS' => ['NONE', 'XML', 'JSON'],
        'PATCH' => ['NONE', 'URL-ENCODE', 'JSON', 'YAML'],
        'CUSTOM' => ['OPTIONAL']
    ];

    public function __construct(string $url, string $method = 'GET')
    {
        $method = strtoupper($method);
        if (array_key_exists($method, self::ALLOW_METHODS)) {
            if ($this->isValidUrl($url)) {
                $this->inputUrl = $url;
                $this->inputMethod = $method;
            } else {
                throw new \InvalidArgumentException("Invalid URL provided");
            }
        } else {
            throw new \InvalidArgumentException("Invalid Method provided");
        }
    }
    private function isValidUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        $host   = $parts['host'];

        return (bool) filter_var("$scheme://$host", FILTER_VALIDATE_URL);
    }

    public function setPayloads(string $type, $payload): void
    {
        $type = strtoupper($type);
        if (in_array($type, self::ALLOW_METHODS[$this->inputMethod])) {
            $this->inputPayloadType = $type;
            switch ($type) {
                case 'URL-ENCODE':
                    $this->inputPayload = http_build_query($payload);
                    break;
                case 'JSON':
                    $json = json_encode($payload);
                    $this->inputPayload = str_replace("\"", "\\\"", $json);
                    break;
                case 'NONE':
                    $this->inputPayload = [];
                    break;
                default:
                    $this->inputPayload = $payload;
            }
        } else {
            throw new \InvalidArgumentException("it is not possible to use {$type} payload in {$this->inputMethod} method");
        }
    }

    public function setHeaders(array $headers): void
    {
        if (!is_array($headers) || array_values($headers) === $headers) {
            throw new \InvalidArgumentException("Headers must be an associative array of strings");
        }
        
        $this->inputHeaders = $headers;
    }

    public function setTimeout(int $timeout = 30, int $ConnectTimeout = 10): void {
        $this->inputTimeout = $timeout;
        $this->inputConnectTimeout = $ConnectTimeout;
    }

    public function fetchCode(int $webView = 0): string 
    {
        $curlCode  = "<?php\n" . PHP_EOL;
        if (!empty($this->inputPayload) && $this->inputMethod != 'GET') {
            $curlCode .= "\$payloads = \"{$this->inputPayload}\";\n\n";
        }
        
        if (!empty($this->inputPayload) && $this->inputMethod == 'GET') {
            $param = "";
            parse_str($this->inputPayload, $array);
            $lastKey = array_key_last($array);
            foreach ($array as $key => $value) {
                $param .= "    '" . $key . "' => '" . $value . "'";
                if ($key !== $lastKey) {
                    $param .= "," . PHP_EOL;
                }
            }
            $curlCode .= "\$payloads = http_build_query([\n{$param}\n]);\n\n";
        }

        if (!empty($this->inputHeaders)) {
            $headers = '';
            foreach ($this->inputHeaders as $key => $value) {
                $key = str_replace("\"", "\\\"", $key);
                $value = str_replace("\"", "\\\"", $value);
                $headers .= "\"$key: $value\",\n    ";
            }

            $curlCode .= "\$headers = [\n    {$headers}];\n\n";
        }
        
        $curlCode .= "\$ch = curl_init();\n\n";

        if ($this->inputMethod == 'GET') {
            if ($this->inputPayload) {
                $url = urldecode($this->inputUrl);
                $pay = "?\" . \$payloads);";
            } else {
                $url = $this->inputUrl;
                $pay = "\");";
            }
            $curlCode .= "curl_setopt(\$ch, CURLOPT_URL, \"$url$pay\n";
        } else {
            $curlCode .= "curl_setopt(\$ch, CURLOPT_URL, \"{$this->inputUrl}\");\n";
        }

        switch ($this->inputMethod) {
            case "GET":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_HTTPGET, true);\n";
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "POST":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_POST, true);\n";
                $curlCode .= (!empty($this->inputPayload)) ? "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payloads);\n" : null;
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "PUT":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"PUT\");\n";
                $curlCode .= (!empty($this->inputPayload)) ? "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payloads);\n" : null;
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "DELETE":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"DELETE\");\n";
                $curlCode .= (!empty($this->inputPayload)) ? "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payloads);\n" : null;
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "PATCH":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"PATCH\");\n";
                $curlCode .= (!empty($this->inputPayload)) ? "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payloads);\n" : null;
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "OPTIONS":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"OPTIONS\");\n";
                $curlCode .= (!empty($this->inputPayload)) ? "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payloads);\n" : null;
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "HEAD":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_NOBODY, true);\n";
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "TRACE":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"TRACE\");\n";
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
                break;
            case "CUSTOM":
                $curlCode .= "curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"{$this->inputMethod}\");\n";
                $curlCode .= (!empty($this->inputPayload)) ? "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payloads);\n" : null;
                $curlCode .= (!empty($this->inputHeaders)) ? "curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);\n" : null;
        }
        
        $curlCode .= "curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
        $curlCode .= ($this->inputTimeout > 0) ? "curl_setopt(\$ch, CURLOPT_TIMEOUT, {$this->inputTimeout});\n" : null;
        $curlCode .= ($this->inputConnectTimeout > 0) ? "curl_setopt(\$ch, CURLOPT_CONNECTTIMEOUT, {$this->inputConnectTimeout});\n" : null;
        $curlCode .= "\$response = curl_exec(\$ch);\n\n";
        $curlCode .= "curl_close(\$ch);\n\n";
        $curlCode .= "echo \$response;";

        $result = match ($webView) {
            0 => $curlCode,
            1 => '<pre>' . htmlspecialchars($curlCode) . '</pre>',
            2 => highlight_string($curlCode)
        };

        return $result;
    }
}
