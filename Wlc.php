<?php
require_once __DIR__ . "/AESGCM.php";

class Wlc
{
    public $app_id;
    public $secret_key;
    public $biz_id;

    public $headers;
    public $body;
    public $params = [];

    public function __construct($app_id, $secret_key, $biz_id)
    {
        $this->app_id = $app_id;
        $this->secret_key = $secret_key;
        $this->biz_id = $biz_id;

        $time = $this->mtime();
        $this->headers = [
            "appId" => $this->app_id,
            "bizId" => $this->biz_id,
            "timestamps" => "$time",
        ];
        return $this;
    }

    /**
     * 获取毫秒
     * @return float
     */
    protected function mtime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $mtime = round(round($sec . substr($msec, 2, 3)));
        return $mtime;
    }

    /**
     * 加密请求体
     * @param $text
     * @return string
     */
    protected function encrypt($text)
    {
        $key = hex2bin($this->secret_key);
        $cipher = "aes-128-gcm";

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

        //php7.1以上可直接使用以下代码
       if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
           $encrypt = openssl_encrypt($text, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
           return base64_encode($iv . $encrypt . $tag);
       }

        //php5.6-7.0使用以下代码
        list($encrypt, $tag) = AESGCM::encrypt($key, $iv, $text);
        $str = bin2hex($iv) . bin2hex($encrypt) . bin2hex($tag);
        return base64_encode(hex2bin($str));
    }

    /**
     * 签名
     * @param $body
     * @return string
     */
    protected function sign($body)
    {
        $data = array_merge($this->headers, $this->params);
        ksort($data);
        $sign_str = '';
        foreach ($data as $k => $v) {
            $sign_str .= "{$k}{$v}";
        }
        $sign_str = $this->secret_key . $sign_str . $body;
        $sign = hash("sha256", $sign_str);
        return $sign;
    }

    /**
     * 发送请求
     * @param $url
     * @param $method
     * @param $headers
     * @param $body
     * @return mixed
     */
    protected function request($url, $method, $headers, $body)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        unset($ch);

        return json_decode($response, true);
    }

    /**
     * 设置url 请求参数
     * @param $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * 设置请求体
     * @param $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function check()
    {
        $url = "https://api.wlc.nppa.gov.cn/idcard/authentication/check";
        //加密请求体
        $body = json_encode([
            "data" => $this->encrypt(json_encode($this->body, JSON_UNESCAPED_UNICODE)),
        ]);

        //签名
        $sign = $this->sign($body);
        $headers[] = "sign:$sign";
        $headers[] = "Content-Type:application/json; charset=utf-8";
        foreach ($this->headers as $k => $v) {
            $headers[] = "{$k}:{$v}";
        }

        return $this->request($url, "POST", $headers, $body);
    }

    public function query()
    {
        $url = "http://api2.wlc.nppa.gov.cn/idcard/authentication/query";
        //设置url参数
        $params = '';
        if (!empty($this->params)) {
            $params = http_build_query($this->params);
            $url .= "?$params";
        }

        $body = '';
        $sign = $this->sign($body);
        $headers[] = "sign:$sign";
        foreach ($this->headers as $k => $v) {
            $headers[] = "{$k}:{$v}";
        }

        return $this->request($url, "GET", $headers, $body);
    }

    public function loginout()
    {
        $url = "http://api2.wlc.nppa.gov.cn/behavior/collection/loginout";
        //加密请求体
        $body = json_encode([
            "data" => $this->encrypt(json_encode($this->body, JSON_UNESCAPED_UNICODE)),
        ]);

        //签名
        $sign = $this->sign($body);
        $headers[] = "sign:$sign";
        $headers[] = "Content-Type:application/json; charset=utf-8";
        foreach ($this->headers as $k => $v) {
            $headers[] = "{$k}:{$v}";
        }

        return $this->request($url, "POST", $headers, $body);
    }


}
