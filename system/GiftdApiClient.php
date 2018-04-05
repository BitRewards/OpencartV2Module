<?php

class Giftd_Client
{
    private $userId;
    private $apiKey;
    private $baseUrl;

    const RESPONSE_TYPE_DATA = 'data';
    const RESPONSE_TYPE_ERROR = 'error';

    const ERROR_NETWORK_ERROR = "networkError";
    const ERROR_TOKEN_NOT_FOUND = "tokenNotFound";
    const ERROR_EXTERNAL_ID_NOT_FOUND = "externalIdNotFound";
    const ERROR_DUPLICATE_EXTERNAL_ID = "duplicateExternalId";
    const ERROR_TOKEN_ALREADY_USED = "tokenAlreadyUsed";
    const ERROR_YOUR_ACCOUNT_IS_BANNED = "yourAccountIsBanned";

    public function __construct($userId, $apiKey)
    {
        $this->userId = $userId;
        $this->apiKey = $apiKey;
        $this->baseUrl = (defined('DEBUG') && DEBUG) ? "https://api.giftd.local/v1/" : "https://api.giftd.ru/v1/";
    }

    private function httpPostCurl($url, array $params)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => 1,
        ));
        if (defined('DEBUG') && DEBUG) {
            curl_setopt_array($ch, array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ));
        }
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Giftd_NetworkException("HTTP POST to $url failed: " . curl_error($ch));
        }
        return $result;
    }

    private function httpPostFileGetContents($url, array $params)
    {
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            )
        );
        $context  = stream_context_create($opts);

        $result = @file_get_contents($url, false, $context);
        if (!$result) {
            throw new Giftd_NetworkException("HTTP POST to $url failed or returned empty result");
        }
        return $result;
    }

    private function httpPost($url, array $params)
    {
        if (function_exists('curl_init')) {
            $rawResult = $this->httpPostCurl($url, $params);
        } else {
            $rawResult = $this->httpPostFileGetContents($url, $params);
        }

        if (!($result = json_decode($rawResult, true))) {
            throw new Giftd_Exception("Giftd API returned malformed JSON, unable to decode it");
        }

        return $result;
    }

    public function query($method, $params = array(), $suppressExceptions = false)
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $params['client_ip'] = $_SERVER['REMOTE_ADDR'];
        }

        $params['signature'] = $this->calculateSignature($method, $params);
        $params['user_id'] = $this->userId;

        $result = $this->httpPost($this->baseUrl . $method, $params);
        if (empty($result['type'])) {
            throw new Giftd_Exception("Giftd API returned response without type field, unable to decode it");
        }
        if (!$suppressExceptions && $result['type'] == static::RESPONSE_TYPE_ERROR) {
            $this->throwException($result);
        }
        return $result;
    }

    private function throwException(array $rawResponse)
    {
        throw new Giftd_Exception($rawResponse['data'], $rawResponse['code']);
    }

    private function constructGiftCard(array $rawData, $token)
    {
        $card = new Giftd_Card();
        foreach ($rawData as $key => $value) {
            $card->$key = $value;
        }
        $card->token = $token;
        if ($card->charge_details) {
            $chargeDetails = new Giftd_ChargeDetails();
            foreach ($card->charge_details as $key => $value) {
                $chargeDetails->$key = $value;
            }
            $card->charge_details = $chargeDetails;
        }
        return $card;
    }

    /**
     * @param null $token
     * @param null $external_id
     * @return Giftd_Card|null
     * @throws Giftd_Exception
     */
    public function check($token = null, $external_id = null)
    {
        $response = $this->query('gift/check', array(
            'token' => $token,
            'external_id' => $external_id
        ), true);
        switch ($response['type']) {
            case static::RESPONSE_TYPE_ERROR:
                switch ($response['code']) {
                    case static::ERROR_TOKEN_NOT_FOUND:
                    case static::ERROR_EXTERNAL_ID_NOT_FOUND:
                        return null;
                    default:
                        $this->throwException($response);
                }
                break;
            case static::RESPONSE_TYPE_DATA:
                return $this->constructGiftCard($response['data'], $token);
            default:
                throw new Giftd_Exception("Unknown response type {$response['type']}");
        }
    }

    /**
     * @param $externalId
     * @return Giftd_Card|null
     * @throws Giftd_Exception
     */
    public function checkByExternalId($externalId)
    {
        return $this->check(null, $externalId);
    }

    /**
     * @param $token
     * @return Giftd_Card|null
     * @throws Giftd_Exception
     */
    public function checkByToken($token)
    {
        return $this->check($token, null);
    }

    /**
     * @param $token
     * @param $amount
     * @param null $amountTotal
     * @param null $externalId
     * @param null $comment
     * @return Giftd_Card
     * @throws Giftd_Exception
     */
    public function charge($token, $amount, $amountTotal = null, $externalId = null, $comment = null)
    {
        $result = $this->query('gift/charge', array(
            'token' => $token,
            'amount' => $amount,
            'amount_total' => $amountTotal,
            'external_id' => $externalId,
            'comment' => $comment
        ));

        return $this->constructGiftCard($result['data'], $token);
    }

    private function calculateSignature($method, array $params)
    {
        $signatureBase = $method . "," . $this->userId. ",";
        unset($params['user_id'], $params['signature'], $params['api_key']);
        ksort($params);
        foreach ($params as $key => $value) {
            $signatureBase .= $key . "=" . $value . ",";
        }
        $signatureBase .= $this->apiKey;
        return sha1($signatureBase);
    }
}

/**
 * @property integer $id
 * @property string $status
 * @property string $status_str
 * @property float $amount_available
 * @property integer $card_id,
 * @property string $card_title
 * @property string $owner_owner_name
 * @property string $owner_gender
 * @property bool $amount_total_required
 * @property float|null $min_amount_total
 * @property string $charge_type
 * @property integer $created
 * @property integer $expires
 * @property string $token_status
 * @property Giftd_ChargeDetails|null $charge_details
 */
class Giftd_Card
{
    const CHARGE_TYPE_ONETIME = 'onetime';
    const CHARGE_TYPE_MULTIPLE = 'multiple';

    const TOKEN_STATUS_OK = 'ok';
    const TOKEN_STATUS_USED = 'used';

    public $id;
    public $status;
    public $status_str;
    public $amount_available;
    public $card_id;
    public $card_title;
    public $owner_name;
    public $owner_gender;
    public $amount_total_required;
    public $min_amount_total;
    public $charge_type;
    public $created;
    public $expires;
    public $token_status;
    public $charge_details;
    public $token;
    public $cannot_be_used_on_discounted_items;
}

/**
 * @property string $token
 * @property string $external_id
 * @property integer $time
 * @property float $amount
 * @property float $amount_total
 * @property float $amount_left
 * @property string $type
 * @property string $comment
 */
class Giftd_ChargeDetails
{
    const TYPE_MANUAL = 'manual';
    const TYPE_API = 'api';

    public $token;
    public $external_id;
    public $time;
    public $amount;
    public $amount_total;
    public $amount_left;
    public $type;
    public $comment;
}

class Giftd_Exception extends Exception
{
    public $code;
    public $data;

    protected function _stringifyData($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        $result = array();
        foreach ($data as $key => $value)
        {
            if (is_array($value)) {
                $result[] = implode(", ", $value);
            } else {
                $result[] = $value;
            }
        }
        return implode(", ", $result);
    }

    public function __construct($data, $code = null)
    {
        parent::__construct($this->_stringifyData($data));
        $this->code = $code;
        $this->data = $data;
    }

    public function __toString()
    {
        return parent::__toString() . ($this->code ? "; code = " . $this->code : null);
    }
}

class Giftd_NetworkException extends Giftd_Exception
{

}
