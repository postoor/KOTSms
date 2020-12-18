<?php

namespace postoor\KOTSms;

use GUMP;
use GuzzleHttp\Client;

class Sms
{
    protected $username;

    protected $password;

    protected $url;

    protected $smsUri = '/kotsmsapi-1.php';

    protected $pointUri = '/memberpoint.php';

    protected $statusUri = '/msgstatus.php';

    public function __construct(
        $username,
        $password,
        $url = 'https://api2.kotsms.com.tw'
    ) {
        $this->username = $username;

        $this->password = $password;

        $this->url = $url;
    }

    /**
     * Send SMS.
     *
     * @param string $target
     * @param string $message
     * @param array  $options
     *
     * @return int
     */
    public function send($target, $message, $options = [], array &$errorMessages = [])
    {
        $defaultParams = [
            'dstaddr' => null,
            'smbody' => null,
            'dlvtime' => null,
            'vldtime' => null,
            'response' => null,
            'Ftcode' => null,
        ];

        $params = array_merge($defaultParams, $options, ['username' => $this->username, 'password' => $this->password]);
        $params['dstaddr'] = $target;
        $params['smbody'] = mb_convert_encoding($message, 'BIG5', 'auto');

        $validate = $this->validate($params);
        if ($validate !== true) {
            $errorMessages = $validate;
            throw new \Exception('Validate Failed');
        }

        foreach ($params as $key => $value) {
            if ($value == null) {
                unset($params[$key]);
                continue;
            }
        }

        $client = new Client();
        $response = $client->request(
            'GET',
            $this->url.$this->smsUri.'?'.http_build_query($params)
        );

        if ($response->getStatusCode() != 200) {
            throw new \Exception('Connected Failed');
        }
        parse_str(trim($response->getBody()), $responseData);

        if (!isset($responseData['kmsgid'])) {
            throw new \Exception('No Response');
        }

        if ($responseData['kmsgid'] < 0) {
            $errorMessages[] = $this->getMessageByCode($responseData['kmsgid']);
        }

        return (int) $responseData['kmsgid'];
    }

    /**
     * Validate Data.
     *
     * @return GUMP
     */
    private function validate(array $data)
    {
        return GUMP::is_valid($data, [
            'dstaddr' => 'required|phone_number',
            'smbody' => 'required|max_len,160',
            'dlvtime' => 'date,Y/m/d H:i:s',
            'vldtime' => 'integer|min_numeric,1800|max_numeric,28800',
            'response' => 'valid_url',
            'Ftcode' => 'max_len,36',
        ]);
    }

    /**
     * Get Message By error code.
     *
     * @param string $code
     *
     * @return string
     */
    private function getMessageByCode($code)
    {
        $codeMsgMap = [
            '-1' => 'CGI string error ，系統維護中或其他錯誤 ,帶入的參數異常,伺服器異常',
            '-2' => '授權錯誤(帳號/密碼錯誤)',
            '-4' => 'A Number違反規則 發送端 870短碼VCSN 設定異常',
            '-5' => 'B Number違反規則 接收端 門號錯誤',
            '-6' => 'Closed User 接收端的門號停話異常090 094 099 付費代號等',
            '-20' => 'Schedule Time錯誤 預約時間錯誤 或時間已過',
            '-21' => 'Valid Time錯誤 有效時間錯誤',
            '-1000' => '發送內容違反NCC規範',
            '-59999' => '帳務系統異常 簡訊無法扣款送出',
            '-60002' => '您帳戶中的點數不足',
            '-60014' => '該用戶已申請 拒收簡訊平台之簡訊 ( 2010 NCC新規)',
            '-999949999' => '境外IP限制(只接受台灣IP發送，欲申請過濾請洽簡訊王客服)',
            '-999959999' => '在12 小時內，相同容錯機制碼',
            '-999969999' => '同秒, 同門號, 同內容簡訊',
            '-999979999' => '鎖定來源IP',
            '-999989999' => '簡訊為空',
        ];

        return $codeMsgMap[$code] ?? '未定義錯誤';
    }

    public function getPoint()
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            $this->url.$this->pointUri.'?'.http_build_query(['username' => $this->username, 'password' => $this->password])
        );

        if ($response->getStatusCode() != 200) {
            throw new \Exception('Connected Failed');
        }
        $point = (int) trim($response->getBody());

        if ($point === null) {
            throw new \Exception('No Response');
        }

        if ($point < 0) {
            throw new \Exception($this->getMessageByCode($point));
        }

        return $point;
    }

    /**
     * Check SMS Status.
     */
    public function getSMSStatus($kmsgid)
    {
        $client = new Client();
        $response = $client->request(
            'GET',
            $this->url.$this->pointUri.'?'.http_build_query(['kmsgid' => $kmsgid, 'username' => $this->username, 'password' => $this->password])
        );

        if ($response->getStatusCode() != 200) {
            throw new \Exception('Connected Failed');
        }
        $body = (string) $response->getBody();
        if (is_numeric($body)) {
            if ($body == 0) {
                throw new \Exception('SMS not Exists');
            }

            throw new \Exception($this->getMessageByCode($body));
        }

        parse_str(trim($body), $responseData);

        if (!isset($responseData['statusstr'])) {
            throw new \Exception('Bad Response');
        }

        return $responseData['statusstr'];
    }
}
