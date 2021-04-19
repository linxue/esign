<?php

/**
 * Uri 常量
 * @desc 接口地址 从官网抓取后做了一些小变动(只是变量名有些改动而已)
 * 
 * @authors linxue (mrlindaxue@sina.com)
 * @date    2020-11-19 10:59:11
 */
namespace Esign;


/**
* 请求地址 : 正式生产环境    EURI_HOST
* 请求地址 : 沙箱测试环境    EURI_HOST_DEBUG
* 获取鉴权Token            EURI_TOKEN
* 创建个人账户              EURI_ADD_PERSON
* 创建企业账户              EURI_ADD_COMPANY
* 删除个人账户              EURI_DELETE_PERSON
* 删除企业账户              EURI_DELETE_COMPANY
* 文件直传创建带签署文件      EURI_UPLOAD_URL
* 创建签署流程              EURI_ADD_FLOW
* 流程文档添加              EURI_ADD_DOCUMNET
* 流程签名域添加 : 甲方      EURI_ADD_PLATFORM_SIGN
* 流程签名域添加 : 乙方      EURI_ADD_HAND_SIGN
* 签署流程开启              EURI_START_SIGN
* 签署流程撤销              EURI_REVOKE_SIGN
* 签署流程查询              EURI_GET_SIGN
* 签署流程归档              EURI_ARCHIVE_SIGN
* 签署流程文档下载           EURI_DOWNLOAD_DOCUMENT
* 签署区列表查询             EURI_SIGNFLOWS
* 创建个人印章              EURI_ADD_PERSON_SEAL
* 创建企业印章              EURI_ADD_COMPANY_SEAL
* 查询个人所以印章           EURI_PERSON_SEALS
* 查询企业所以印章           EURI_COMPANY_SEALS
* 删除个人印章              EURI_DELETE_PERSON_SEAL
* 删除企业印章              EURI_DELETE_COMPANY_SEAL
* 设置静默签署              EURI_SIGNAUTH
* 
*/


/**
 * 
 */
class Uri
{
	
	const EURI_HOST  = 'https://openapi.esign.cn' ;
	const EURI_HOST_DEBUG  = 'https://smlopenapi.esign.cn' ;

	const EURI_TOKEN  = '/v1/oauth2/access_token' ;
	const EURI_ADD_PERSON  = '/v1/accounts/createByThirdPartyUserId' ;
	const EURI_ADD_COMPANY  = '/v1/organizations/createByThirdPartyUserId' ;
	const EURI_DELETE_PERSON  = '/v1/accounts/deleteByThirdId' ;
	const EURI_DELETE_COMPANY  = '/v1/organizations/deleteByThirdId' ;
	const EURI_GET_PERSON  = '/v1/accounts/getByThirdId' ;
	const EURI_GET_COMPANY  = '/v1/organizations/getByThirdId' ;
	const EURI_UPLOAD_URL  = '/v1/files/getUploadUrl' ;
	const EURI_ADD_FLOW  = '/v1/signflows' ;
	const EURI_ADD_DOCUMNET  = '/v1/signflows/{flowId}/documents' ;
	const EURI_ADD_PLATFORM_SIGN  = '/v1/signflows/{flowId}/signfields/platformSign' ;
	const EURI_ADD_HAND_SIGN  = '/v1/signflows/{flowId}/signfields/handSign' ;
	const EURI_START_SIGN  = '/v1/signflows/{flowId}/start' ;
	const EURI_REVOKE_SIGN  = '/v1/signflows/{flowId}/revoke' ;
	const EURI_GET_SIGN  = '/v1/signflows/{flowId}' ;
	const EURI_ARCHIVE_SIGN  = '/v1/signflows/{flowId}/archive' ;
	const EURI_DOWNLOAD_DOCUMENT  = '/v1/signflows/{flowId}/documents' ;
	const EURI_SIGNFLOWS  = '/v1/signflows/{flowId}/signfields' ;

	const EURI_ADD_PERSON_SEAL  = '/v1/accounts/{accountId}/seals/personaltemplate' ;
	const EURI_ADD_COMPANY_SEAL  = '/v1/organizations/{accountId}/seals/officialtemplate' ;
	const EURI_PERSON_SEALS  = '/v1/accounts/{accountId}/seals' ;
	const EURI_COMPANY_SEALS  = '/v1/organizations/{accountId}/seals' ;
	const EURI_DELETE_PERSON_SEAL  = '/v1/accounts/{accountId}/seals/{sealId}' ;
	const EURI_DELETE_COMPANY_SEAL  = '/v1/organizations/{accountId}/seals/{sealId}' ;
	const EURI_COMPANY_GRANTED_SEALS  = '/v1/organizations/{accountId}/granted/seals' ;

	const EURI_SIGNAUTH  = '/v1/signAuth/{accountId}' ;
	const EURI_AUTOSIGN  = '/v1/signflows/{flowId}/signfields/autoSign' ;

}


