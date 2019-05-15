<?php


namespace Cbr;

class Api
{
    const CONTENT_TYPE_JOSN = 'application/json;charset=UTF-8';
    const CONTENT_TYPE_FORM = 'multipart/form-data';

    private $_host;
    private $_ch;
    private $_token = null;
    private $_defaultHeaders = [];

    private $_defaultContentType = self::CONTENT_TYPE_JOSN;

    public function __construct($host, $options = [])
    {
        $this->_host = $host;
        $this->_ch = curl_init();
        foreach ($options as $key => $val) {
            curl_setopt($this->_ch, $key, $val);
        }
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, 200);
    }

    public function setAuthToken($token)
    {
        $this->_token = $token;
    }

    public function __destruct()
    {
        curl_close($this->_ch);
    }

    private function _request($url, $type, $data = null, $requireAuth = true, $contentType = null)
    {
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        if ($type == 'POST' || $type == 'PUT') {
            curl_setopt($this->_ch, CURLOPT_POST, true);
        }
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $type);

        $contentType = $contentType ? $contentType : $this->_defaultContentType;
        if ($data) {
            curl_setopt($this->_ch, CURLOPT_POSTFIELDS,
                $contentType == self::CONTENT_TYPE_JOSN ? json_encode($data) : $data);
        }

        $headers = $this->_defaultHeaders;
        $headers [] = 'Content-Type: ' . $contentType;
        if ($requireAuth) {
            $headers []= 'Authorization: Bearer ' . $this->_token;
        }
//        print_r($headers);
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($this->_ch);
        $httpCode = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
        $error = curl_error($this->_ch);
        $errno = curl_errno($this->_ch);
//        curl_setopt($this->_ch, CURLOPT_VERBOSE, true);

        if ($errno !== 0) {
            //print_r($error);
            throw new \Exception($error, $errno);
        }
// 		print_r($response);
//
        return [
            'code' => $httpCode,
            'body' => json_decode($response, true)
        ];
    }

    /**
     * Make query params string from associative array
     *
     */
    private function _queryParams($params)
    {
        $queryParams = [];
        foreach ($params as $k => $v) {
            $queryParams [] = $k . '=' . $v;
        }

        return implode('&', $queryParams);
    }

    public function auth($login, $password)
    {
        return $this->_request($this->_host . '/api/rest.php/auth/session', 'POST', [
            "email" => $login,
            "password" => $password
        ], false);
    }

    public function import($userInfo)
    {
        return $this->_request($this->_host . '/api/rest.php/imports-user?action=single-import', 'POST', $userInfo);
    }

    public function importFromCsv($file)
    {
        if (!file_exists($file)) {
            throw new \Exception('Unable to open file ' . $file);
        }

        return $this->_request($this->_host . '/api/rest.php/imports-user?action=import', 'POST', [
            'file' => curl_file_create($file)
        ], true, self::CONTENT_TYPE_FORM);
    }

    public function blockUser($userId, $blockMessage = '')
    {
        return $this->_request($this->_host . '/api/rest.php/auth/users/' . $userId . '?action=disable', 'PUT', [
            'block_message' => $blockMessage
        ]);
    }

    public function unBlockUser($userId)
    {
        return $this->_request($this->_host . '/api/rest.php/auth/users/' . $userId . '?action=enable', 'PUT');
    }

    public function setUserRating($uid, $ratingValue, $date)
    {
        return $this->_request($this->_host . '/api/rest.php/user-rating?action=set-external-user-rating', 'POST', [
            'uid' => $uid,
            'rating' => $ratingValue,
            'date' => $date
        ]);
    }

    public function getUsers($params)
    {
        return $this->_request($this->_host . '/api/rest.php/auth/users?' . $this->_queryParams($params), 'GET');
    }

    public function getUserTasks($uid, $params)
    {
        return $this->_request($this->_host . '/api/rest.php/tasks?action=my&uid=' . $uid . '&' . $this->_queryParams($params), 'GET');
    }

    public function getUserNotices($uid)
    {
        return $this->_request($this->_host . '/api/rest.php/notices?action=get-last-actual&uid=' . $uid, 'GET');
    }

    public function getNews()
    {
        return $this->_request($this->_host . '/api/rest.php/news?action=get-all-public-new', 'GET');
    }
}
