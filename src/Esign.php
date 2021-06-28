<?php

/**
 * Esign e签宝操作类
 * @authors linxue (599562695@qq.com)
 * @date    2020-11-19 10:43:26
 */

namespace Esign;

require_once 'Uri.php';
require_once 'Utility.php';


class Esign
{

    ## appid secret
    private static $appid = '';
    private static $secret = '';
    ## 存储一次token
    private static $stoken = '';
    ## e签宝的接口地址(正式环境\沙箱测试环境)
    private static $apiHost = '';

    /**
     * @name 1. 初始化
     */
    public static function init(string $appid = '', string $secret = '', string $environ = 'develop')
    {
        self::$appid = $appid;
        self::$secret = $secret;

        ## environ: 非product 则都是develop
        switch ($environ) {
            case 'product':
                self::$apiHost = Uri::EURI_HOST;
                break;

            default:
                self::$apiHost = Uri::EURI_HOST_DEBUG;
                break;
        }

        ## 获取 stoken
        return self::$stoken = self::getToken();
    }

    /**
     * @name 1.2 获取鉴权token
     */
    public static function getToken(): string
    {
        $res = '';
        if (self::$appid && self::$secret) {
            $baseUri = self::$apiHost . Uri::EURI_TOKEN;
            $requestParameterArr = [
                "appId" => self::$appid,
                "secret" => self::$secret,
                "grantType" => 'client_credentials',
            ];
            $uriAddition = http_build_query($requestParameterArr);
            $apiUrl = $baseUri . "?" . $uriAddition;
            $apiResult = Utility::doGet($apiUrl);
            ## deal return data
            if ($apiResult) {
                $data2 = $apiResult['data'] ?? [];
                $res = isset($data2['token']) && $data2['token'] ? $data2['token'] : '';
            }
        }
        return $res;
    }

    /**
     * @name 2. 创建账户
     */
    private static function _addUser(string $apiUrl, array $userData = [], $debug = false): array
    {
        $res = [];
        if (self::$appid) {
            if (self::$stoken) {
                $apiResult = Utility::doPost($apiUrl, $userData, self::$appid, self::$stoken);
                if ($apiResult) {
                    $res = isset($apiResult['data']) ? $apiResult['data'] : [];
                }
            }
        }
        return $res;
    }

    /**
     * @name 2.1 创建个人账户
     */
    public static function addPerson(array $personalData = [], $debug = false): array
    {
        ## 检验
        if (!(isset($personalData['id']) && $personalData['id'])) {
            $msg = '唯一id不能为空';
            exit($msg);
        }
        if (!(isset($personalData['name']) && $personalData['name'])) {
            $msg = 'name不能为空';
            exit($msg);
        }
        if (!(isset($personalData['mobile']) && $personalData['mobile'])) {
            $msg = 'mobile不能为空';
            exit($msg);
        }
        if (!(isset($personalData['idcard']) && $personalData['idcard'])) {
            $msg = 'idcard不能为空';
            exit($msg);
        }
        $apiUrl = self::$apiHost . Uri::EURI_ADD_PERSON;
        ## 个人账户
        $idType = 'CRED_PSN_CH_IDCARD';
        $requestParameterArr = [
            'thirdPartyUserId' => $personalData['id'],
            'name' => $personalData['name'],
            'mobile' => $personalData['mobile'],
            'idNumber' => $personalData['idcard'],
            'idType' => $personalData['idtype'],
        ];
        return self::_addUser($apiUrl, $requestParameterArr, $debug);
    }

    /**
     * @name 2.2 创建企业账户
     */
    public static function addCompany(array $companyData = []): array
    {
        $res = [
            'success' => false,
            'err_code' => -1,
            'err_msg' => 'error',
            'data' => [],
        ];
        ## 检验
        if (!(isset($companyData['id']) && $companyData['id'])) {
            $msg = 'E签宝:创建企业账户的字段id不能为空';
            $res['err_msg'] = $msg;
            return $res;
        }
        if (!(isset($companyData['creator']) && $companyData['creator'])) {
            $msg = 'E签宝:创建企业账户的字段creator不能为空';
            $res['err_msg'] = $msg;
            return $res;
        }
        if (!(isset($companyData['name']) && $companyData['name'])) {
            $msg = 'E签宝:创建企业账户的字段name不能为空';
            $res['err_msg'] = $msg;
            return $res;
        }
        $orgLegalIdNumber = isset($companyData['orgLegalIdNumber']) ? trim($companyData['orgLegalIdNumber']) : '';
        $orgLegalName = isset($companyData['orgLegalName']) ? trim($companyData['orgLegalName']) : '';
        $apiUrl = self::$apiHost . Uri::EURI_ADD_COMPANY;
        ## 企业账户
        $idType = 'CRED_ORG_USCC';
        $requestParameterArr = [
            'thirdPartyUserId' => $companyData['id'] ?? "",
            'creator' => $companyData['creator'] ?? "",
            'name' => $companyData['name'] ?? "",
            'idType' => $idType,
            'idNumber' => $companyData['registration'] ?? "", // 机构证件号,应该是 营业执照统一注册号
            // 'orgLegalIdNumber' => $orgLegalIdNumber,  ## 法人身份证号
            // 'orgLegalName' => $orgLegalName, ## 法人姓名
        ];
        $userRes = self::_addUser($apiUrl, $requestParameterArr);
        if($userRes){
            $res['success'] = true;
            $res['err_code'] = 0;
            $res['err_msg'] = 'ok';
            $res['data'] = $userRes;
        }else{
            $res['err_msg'] = 'E签宝:无法创建企业账户';
        }
        return $res;
    }

    /**
     * @name 注销账户
     */
    private static function _deleteUser(string $apiUrl, string $userId = '')
    {
        $res = false;
        if (self::$stoken) {
            ## 校验
            if (!$userId) {
                $msg = 'userId不能为空';
                exit($msg);
            }
            $apiUrl .= "?thirdPartyUserId=" . $userId;
            $res = Utility::sendHttpDELETE($apiUrl, [], self::$appid, self::$stoken, false);
        }
        return $res;
    }

    /**
     * @name 注销个人账户
     */
    public static function deletePerson(string $userId = '')
    {
        $apiUrl = self::$apiHost . Uri::EURI_DELETE_PERSON;
        return self::_deleteUser($apiUrl, $userId);
    }

    /**
     * @name 注销企业账户
     */
    public static function deleteCompany(string $userId = '')
    {
        $apiUrl = self::$apiHost . Uri::EURI_DELETE_COMPANY;
        return self::_deleteUser($apiUrl, $userId);
    }

    /**
     * @name 查询账户
     */
    private static function _getUser(string $baseUri, string $userId = ''): array
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$userId) {
                $msg = 'userId不能为空';
                exit($msg);
            }
            $requestParameterArr = [
                "thirdPartyUserId" => $userId,
            ];
            $uriAddition = http_build_query($requestParameterArr);
            $apiUrl = $baseUri . "?" . $uriAddition;
            $res = Utility::doGetWithToken($apiUrl, [], self::$appid, self::$stoken, true);
        }
        return $res;
    }

    /**
     * @name 查询个人账户
     */
    public static function getPerson(string $userId = ''): array
    {
        $apiUrl = self::$apiHost . Uri::EURI_GET_PERSON;
        return self::_getUser($apiUrl, $userId);
    }

    /**
     * @name 查询企业账户
     */
    public static function getCompany(string $userId = ''): array
    {
        $apiUrl = self::$apiHost . Uri::EURI_GET_COMPANY;
        return self::_getUser($apiUrl, $userId);
    }

    /**
     * @name 创建签署流程
     * @param $scene 业务场景介绍: 内容必填
     * @param $noticeUrl 推送的回调地址
     * @param $redirectUrl 签署后的跳转地址
     */
    public static function addFlow(string $scene = '', string $noticeUrl = '', string $redirectUrl = '')
    {
        $success = false;
        $errCode = -1;
        $errMsg = 'error';
        $flowId = '';
        ## 校验
        if (self::$appid && self::$secret && $scene) {
            $apiUrl = self::$apiHost . Uri::EURI_ADD_FLOW;
            ## autoArchive 自动归档 : 当所有签署人签署完毕
            ## noticeType 通知类型 : 1 短信
            $autoArchive = true;
            $autoArchive = false;
            $requestParameterArr = [
                'businessScene' => $scene,
                'autoArchive' => $autoArchive,
                'configInfo' => [
                    'noticeType' => 1,
                    'noticeDeveloperUrl' => $noticeUrl,
                    'redirectUrl' => $redirectUrl,
                    'orgAvailableAuthTypes' => [ "ORG_BANK_TRANSFER" , "ORG_LEGAL_AUTHORIZE" ],
                ]
            ];
            $apiResult = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken);
            if ( $apiResult ) {
                $code = isset($apiResult['code']) ? intval($apiResult['code']) : -1;
                $flowId = '';
                if( $code ===0 ){
                    $data2 = $apiResult['data'] ?? [];
                    $flowId = isset($data2['flowId']) && $data2['flowId'] ? $data2['flowId'] : '';
                    $errCode = 0;
                    $errMsg = '';
                    $success = true;
                }else{
                    $errMsg = isset($apiResult['message']) ? trim($apiResult['message']) : '';
                    $success = false;
                    $errCode = -1;
                }
            }else{
                $msg = 'E签宝平台服务器出错了';
            }
        }else{
            $msg = '配置信息不完整';
        }
        $res = [
            'success' => $success,
            'err_code' => $errCode,
            'err_msg' => $errMsg,
            'flow_id' => $flowId,
        ];
        return $res;
    }

    /**
     * @name 流程文档添加
     */
    public static function addDocumnet(string $flowId = '', string $fileId = '', string $fileName = '')
    {
        $res = [];
        if (self::$appid && self::$secret) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            if (!$fileId) {
                $msg = 'fileId不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_ADD_DOCUMNET;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $requestParameterArr = [
                'docs' => [
                    [
                        'fileId' => $fileId,
                        'fileName' => $fileName,
                    ]
                ],
            ];
            $res = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken);
        }
        return $res;
    }

    /**
     * @name 流程文本域添加
     * @param $signPos 签署区位置信息
     * @param $accountId 待签署的账户id
     * $signPos = [ 'posPage' => 1 , 'posX' => 111 , 'posY'=> 222 ];
     */
    public static function addPlatformSign(string $flowId = '', string $fileId = '', string $accountId = '', array $signPos = [])
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            if (!$fileId) {
                $msg = 'fileId不能为空';
                exit($msg);
            }
            if (!$signPos) {
                $msg = 'signPos不能为空';
                exit($msg);
            }
            if (!(isset($signPos['posPage']) && isset($signPos['posX']) && isset($signPos['posY']))) {
                $msg = 'posPage\posX\posY值不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_ADD_PLATFORM_SIGN;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $requestParameterArr = [
                'signfields' =>
                    [
                        [
                            'order' => 1,
                            'fileId' => $fileId,
                            'sealId' => $accountId,
                            'signType' => 1,
                            'posBean' => $signPos,
                        ]
                    ]
            ];
            $res = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken);
        }
        return $res;
    }

    /**
     * @name 添加手动盖章区域
     * @param $accountId 待签署的账户id
     * @param $signPos 签署区位置信息
     */
    public static function addHandSign(string $flowId = '', string $fileId = '', string $accountId = '', array $signPos = [], $additionData = [], $debug = false)
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            if (!$fileId) {
                $msg = 'fileId不能为空';
                exit($msg);
            }
            if (!$accountId) {
                $msg = 'accountId不能为空';
                exit($msg);
            }
            if (!$signPos) {
                $msg = 'signPos不能为空';
                exit($msg);
            }
            if (!(isset($signPos['posPage']) && isset($signPos['posX']) && isset($signPos['posY']))) {
                $msg = 'posPage\posX\posY值不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_ADD_HAND_SIGN;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $authorizedAccountId = isset($additionData['authorized_account_id']) ? $additionData['authorized_account_id'] : '';
            $actorIndentityType = isset($additionData['actor_indentity_type']) ? $additionData['actor_indentity_type'] : '';
            $signfields = [
                [
                    'order' => '1',
                    'fileId' => $fileId,
                    'signerAccountId' => $accountId,
                    // 'signerAccountId' => $authorizedAccountId,
                    'authorizedAccountId' => $authorizedAccountId,
                    'actorIndentityType' => $actorIndentityType,
                    'signType' => '1',
                    'posBean' => $signPos,
                    'assignedPosbean' => true,
                ]
            ];
            $requestParameterArr = [
                'signfields' => $signfields,
                'signDateBeanType' => 1,
            ];
            $res = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken);
        }
        return $res;
    }

    /**
     * @name 添加签署方自动盖章签署区
     * @param $signPos 签署区位置信息
     * @param $accountId 待签署的账户id
     * $signPos = [ 'posPage' => 1 , 'posX' => 111 , 'posY'=> 222 ];
     */
    public static function addAutoSign(string $flowId = '', string $fileId = '', string $accountId = '', array $signPos = [])
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            if (!$fileId) {
                $msg = 'fileId不能为空';
                exit($msg);
            }
            if (!$signPos) {
                $msg = 'signPos不能为空';
                exit($msg);
            }
            if (!(isset($signPos['posPage']) && isset($signPos['posX']) && isset($signPos['posY']))) {
                $msg = 'posPage\posX\posY值不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_AUTOSIGN;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $requestParameterArr = [
                'signfields' =>
                    [
                        [
                            'order' => 1,
                            'fileId' => $fileId,
                            'authorizedAccountId' => $accountId,
                            'signType' => 1,
                            'posBean' => $signPos,
                        ]
                    ]
            ];
            $res = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken , true);
        }
        return $res;
    }

    /**
     * @name 签署流程开启
     */
    public static function startSign(string $flowId = '' , $debug=false)
    {
        $success = false;
        $errCode = -1;
        $errMsg = 'error';
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $errMsg = 'flowId不能为空';
            }else{
                $apiUrl = self::$apiHost . Uri::EURI_START_SIGN;
                $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
                Utility::sendHttpPUT($apiUrl, [], self::$appid, self::$stoken);
                $success = true;
                $errCode = 0;
                $errMsg = 'ok';
            }
        }
        $res = [
            'success' => $success,
            'err_code' => $errCode,
            'err_msg' => $errMsg,
        ];
        return $res;
    }

    /**
     * @name 签署流程撤销
     * $res = [ 'code'=>0 ,'message'=>'成功' ,'data' => array() ];
     */
    public static function revokeSign(string $flowId = '')
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_REVOKE_SIGN;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $requestParameterArr = [
                'revokeReason' => '撤销',
            ];
            $res = Utility::sendHttpPUT($apiUrl, $requestParameterArr, self::$appid, self::$stoken);
        }
        return $res;
    }

    /**
     * @name 签署流程查询
     */
    public static function getSign(string $flowId = ''): array
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_GET_SIGN;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $apiResult = Utility::doGetWithToken($apiUrl, [], self::$appid, self::$stoken);
            $res = isset($apiResult['code']) && ($apiResult['code'] == 0) ? $apiResult['data'] : [];
        }
        return $res;
    }

    /**
     * @name 签署区列表查询
     */
    public static function getSignFlows(string $flowId = ''): array
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_SIGNFLOWS;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $apiResult = Utility::doGetWithToken($apiUrl, [], self::$appid, self::$stoken);
            $tmpData = isset($apiResult['code']) && ($apiResult['code'] == 0) ? $apiResult['data'] : [];
            $res = isset($tmpData['signfields']) ? $tmpData['signfields'] : [];
        }
        return $res;
    }

    /**
     * @name 流程归档
     */
    public static function archiveSign(string $flowId = '')
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_ARCHIVE_SIGN;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $res = Utility::sendHttpPUT($apiUrl, [], self::$appid, self::$stoken);
        }
        return $res;
    }

    /**
     * @name 流程文档下载
     */
    public static function downloadDocument(string $flowId = '')
    {
        $res = false;
        if (self::$stoken) {
            ## 校验
            if (!$flowId) {
                $msg = 'flowId不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_DOWNLOAD_DOCUMENT;
            $apiUrl = str_replace('{flowId}', $flowId, $apiUrl);
            $data = [];
            $res = Utility::doGetWithToken($apiUrl, $data, self::$appid, self::$stoken);
            // $res = '???';
            // 这里待继续开发 2021-04-15 21:31:20
        }
        return $res;
    }

    /**
     * @name 文件直传创建待签署文件
     */
    public static function getUploadUrl(string $filePath = ''): array
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$filePath) {
                $msg = 'filePath不能为空';
                exit($msg);
            }
            if ( file_exists($filePath) && is_file($filePath) ) {
                $apiUrl = self::$apiHost . Uri::EURI_UPLOAD_URL;
                $fileName = basename($filePath);
                $fileSize = filesize($filePath);
                $contentType = 'application/pdf';
                $contentMd5 = Utility::getContentBase64Md5($filePath);
                $requestParameterArr = [
                    'fileName' => $fileName,
                    'fileSize' => $fileSize,
                    'contentType' => $contentType,
                    'contentMd5' => $contentMd5
                ];
                $apiResult = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken);
                if ($apiResult) {
                    $res = isset($apiResult['data']) ? $apiResult['data'] : [];
                }
            }
        }
        return $res;
    }

    /**
     * @name 上传文件
     */
    public static function uploadFile(string $uploadUri = '', string $filePath = ''): bool
    {
        $res = false;
        if (self::$stoken) {
            ## 校验
            if (!$uploadUri) {
                $msg = 'uploadUri不能为空';
            }else if ( !$filePath || !file_exists($filePath) && is_file($filePath) ) {
                $msg = 'filePath 路径不存在:' . $filePath;
            }else{
                ## 文件内容
                $fileContent = file_get_contents($filePath);
                $contentMd5 = Utility::getContentBase64Md5($filePath);
                $status = Utility::sendHttpPUTPdfFile($uploadUri, $contentMd5, $fileContent);
                $status = intval($status);
                $res = isset($status) && ($status == 200) ? true : false;
            }
        }
        return $res;
    }

    /**
     * @name 处理回调数据
     * @param $tmpNoticeFile 临时日志文件路径
     */
    public static function signNotice(string $tmpNoticeFile = '')
    {
        $res = [];
        if (!file_exists($tmpNoticeFile)) {
            file_put_contents($tmpNoticeFile, '');
        }
        if (!is_writeable($tmpNoticeFile)) {
            exit('文件不可写 ' . $tmpNoticeFile);
        }
        ## 日志文件超出大小则清空原日志内容
        $maxFileSize = 2097152;
        if (filesize($tmpNoticeFile) >= $maxFileSize) {
            file_put_contents($tmpNoticeFile, '');
        }
        $file = fopen($tmpNoticeFile, "a");
        fwrite($file, PHP_EOL . " →→→→ startTime  " . date('Y-m-d H:i:s') . PHP_EOL);
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            fwrite($file, '非法回调' . PHP_EOL);
            exit();
        }
        if (!isset($_SERVER['HTTP_X_TSIGN_OPEN_SIGNATURE'])) {
            exit("签名不能为空");
        }
        $sign = $_SERVER['HTTP_X_TSIGN_OPEN_SIGNATURE'];
        ## 项目对应密钥
        $secret = self::$secret;
        ## 1.获取时间戳的字节流
        if (!isset($_SERVER['HTTP_X_TSIGN_OPEN_TIMESTAMP'])) {
            exit("时间戳不能为空");
        }
        $timeStamp = $_SERVER['HTTP_X_TSIGN_OPEN_TIMESTAMP'];
        ## 2.获取query请求的字节流，对 Query 参数按照字典对 Key 进行排序后,按照value1+value2方法拼接
        $params = $_GET;
        if (!empty($params)) {
            ksort($params);
        }
        $requestQuery = '';
        foreach ($params as $val) {
            $requestQuery .= $val;
        }
        fwrite($file, '获取query的数据:' . $requestQuery . PHP_EOL . PHP_EOL);
        ## 3. 获取body的数据
        $reponseJson = file_get_contents("php://input");
        fwrite($file, '获取body的数据:' . $reponseJson . PHP_EOL . PHP_EOL);
        ## 4.组装数据并计算签名
        $data = $timeStamp . $requestQuery . $reponseJson;
        ## 校验签名
        $mySign = hash_hmac("sha256", $data, $secret);
        fwrite($file, 'sign:' . $sign . PHP_EOL);
        fwrite($file, 'mySign:' . $mySign . PHP_EOL . PHP_EOL);
        if ($mySign != $sign) {
            fwrite($file, "签名校验失败" . PHP_EOL . PHP_EOL);
        } else {
            fwrite($file, "签名校验成功 " . PHP_EOL . PHP_EOL);
            $res = (array)json_decode($reponseJson, true);
        }
        ## 通知的结果
        return $res;
    }

    /**
     * @name 创建印章
     * @date 2020-11-25
     */
    private static function _addUserSeal(string $apiUrl, string $userId, array $sealStyle = []): array
    {
        $res = [];
        if (self::$appid) {
            if (self::$stoken) {
                $apiUrl = str_replace('{accountId}', $userId, $apiUrl);
                $requestParameterArr = $sealStyle;
                $apiResult = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken, false);
                if ($apiResult) {
                    $res = isset($apiResult['data']) ? $apiResult['data'] : [];
                }
            }
        }
        return $res;
    }

    /**
     * $name 创建个人印章
     * @date 2020-11-25
     */
    public static function addPersonSeal(string $userId = ''): array
    {
        $apiUrl = self::$apiHost . Uri::EURI_ADD_PERSON_SEAL;
        ## 颜色 形状
        $sealStyle = [
            'color' => 'RED',
            'type' => 'RECTANGLE',
        ];
        return self::_addUserSeal($apiUrl, $userId, $sealStyle);
    }

    /**
     * $name 创建企业印章
     * @date 2020-11-25
     */
    public static function addCompanySeal(string $userId = ''): array
    {
        $apiUrl = self::$apiHost . Uri::EURI_ADD_COMPANY_SEAL;
        ## 颜色 形状 中心图案为五角星
        $sealStyle = [
            'color' => 'RED',
            'type' => 'TEMPLATE_ROUND',
            'central' => 'STAR',
        ];
        return self::_addUserSeal($apiUrl, $userId, $sealStyle);
    }

    /**
     * @name 查询印章
     */
    private static function _getUserSeals(string $apiUrl, string $userId = ''): array
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$userId) {
                $msg = 'userId不能为空';
                exit($msg);
            }
            $apiUrl = str_replace('{accountId}', $userId, $apiUrl);
            $apiResult = Utility::doGetWithToken($apiUrl, [], self::$appid, self::$stoken, false);
            $res = isset($apiResult['code']) && ($apiResult['code'] == 0) ? $apiResult['data'] : [];
        }
        return (array)$res;
    }

    /**
     * $name 查询个人所有印章
     */
    public static function getPersonSeals(string $userId = ''): array
    {
        $apiUrl = self::$apiHost . Uri::EURI_PERSON_SEALS;
        return self::_getUserSeals($apiUrl, $userId);
    }

    /**
     * $name 查询企业所有印章
     */
    public static function getCompanySeals(string $userId = '')
    {
        $apiUrl = self::$apiHost . Uri::EURI_COMPANY_SEALS;
        return self::_getUserSeals($apiUrl, $userId);
    }

    /**
     * $name 获取企业授权印章列表
     */
    public static function getCompanyGrantedSeals(string $userId = '')
    {
        $apiUrl = self::$apiHost . Uri::EURI_COMPANY_GRANTED_SEALS;
        return self::_getUserSeals($apiUrl, $userId);
    }

    /**
     * @name 删除印章
     */
    private static function _deleteUserSeal(string $apiUrl, string $userId = '', string $sealId = ''): array
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$userId) {
                $msg = 'userId不能为空';
                exit($msg);
            }
            if (!$sealId) {
                $msg = 'sealId不能为空';
                exit($msg);
            }
            $apiUrl = str_replace('{accountId}', $userId, $apiUrl);
            $apiUrl = str_replace('{sealId}', $sealId, $apiUrl);
            $res = Utility::sendHttpDELETE($apiUrl, [], self::$appid, self::$stoken, false);
        }
        return (array)$res;
    }

    /**
     * $name 删除个人印章
     */
    public static function deletePersonSeal(string $userId = '', string $sealId = ''): array
    {
        $apiUrl = self::$apiHost . Uri::EURI_DELETE_PERSON_SEAL;
        return self::_deleteUserSeal($apiUrl, $userId, $sealId);
    }

    /**
     * $name 删除企业印章
     */
    public static function deleteCompanySeal(string $userId = '', string $sealId = ''): array
    {
        $apiUrl = self::$apiHost . Uri::EURI_DELETE_COMPANY_SEAL;
        return self::_deleteUserSeal($apiUrl, $userId, $sealId);
    }

    /**
     * @name 自动签署
     * @date 2021-03-22 18:11:25
     */
    public static function signAuth(string $accountId = '', string $deadline = ''): array
    {
        $res = [];
        if (self::$stoken) {
            ## 校验
            if (!$accountId) {
                $msg = 'accountId不能为空';
                exit($msg);
            }
            $apiUrl = self::$apiHost . Uri::EURI_SIGNAUTH;
            $apiUrl = str_replace('{accountId}', $accountId, $apiUrl);
            $requestParameterArr = [
                'accountId' => $accountId,
                'deadline' => $deadline,
            ];
            $res = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken, false);
        }
        return $res;
    }

    /**
     * @name 发起企业实名认证4要素校验
     */
    public static function getIdentityOrgUri(string $legalOrgId = '', string $clientAccountId = ''): array
    {
        $res = [];
        if (self::$stoken && $legalOrgId && $clientAccountId) {
            $apiUrl = self::$apiHost . Uri::EURI_IDENTITY_ORG_WEB;
            $apiUrl = str_replace('{accountId}', $legalOrgId, $apiUrl);
            $notifyUrl = '';
            $contextId = '';
            $name = '广州善盟信息科技有限公司';
            $certNo = '91440101MA5CWR5P18';
            $legalRepName = '刘松森';
            $agentName = '林雪';
            $orgEntity = [
                'name' => $name,
                'certNo' => $certNo,
                'legalRepName' => $legalRepName,
                'agentName' => $agentName,
            ];
            $configParams = [
                'orgUneditableInfo' => [
                    'name' => $name,
                    'certNo' => $certNo,
                    'legalRepName' => $legalRepName,
                    'agentName' => $agentName,
                ]
            ];
            $requestParameterArr = [
                'agentAccountId' => $clientAccountId,
                'orgEntity' => $orgEntity,
                // 'configParams' => $configParams,

                // 'contextId' => $contextId,
                // 'notifyUrl' => $notifyUrl,
                // 'repetition' => true,
                'repeatIdentity' => false,
                'authType' => 'ORG_BANK_TRANSFER',
            ];
            // var_dump( $apiUrl );
            // var_dump($requestParameterArr);
            $res = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken, false);
            // var_dump($res);
        }
        return $res;
    }

    /**
     * @name 发起个人实名认证4要素校验
     */
    public static function getIdentityPersonUri(string $clientAccountId = ''): array
    {
        $res = [];
        if (self::$stoken && $clientAccountId) {
            $apiUrl = self::$apiHost . Uri::EURI_IDENTITY_PERSON_WEB;
            $apiUrl = str_replace('{accountId}', $clientAccountId, $apiUrl);
            $notifyUrl = '';
            $callbackUrl = url('yizhi.webview.identity.callback');
            $mobile = 15363200220;
            $requestParameterArr = [
                'faceauthMode' => 'ZHIMACREDIT',
                'repetition' => false,
                'callbackUrl' => $callbackUrl,
                'notifyUrl' => $notifyUrl,
                'contextId' => '',
            ];
            ## flowId  1810962967160728982
            // var_dump( $requestParameterArr );
            // exit;
            $res = Utility::doPost($apiUrl, $requestParameterArr, self::$appid, self::$stoken, false);
        }
        return $res;
    }


}

