<?php

/**
 * Utility 工具类
 * @authors linxue (mrlindaxue@sina.com)
 * @date    2020-11-19 11:55:10
 */


namespace Esign;


class Utility
{
  ## 默认请求超时时间
  const TIMEOUT = 2;
  /**
   * @name 发起POST请求
   */
  public static function doPost( string $url, array $data=[], string $appid='', string $stoken=''): array
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    $dataStr = json_encode( $data );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataStr); ## header已设置json
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_TIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ "X-Tsign-Open-App-Id:" . $appid, "X-Tsign-Open-Token:" . $stoken, "Content-Type:application/json" ] );
    $returnContent = curl_exec( $ch );
    $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return (array)json_decode($returnContent, true);
  }

  /**
   * @name 发起GET请求
   */
  public static function doGet( string $url ) : array
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, static::TIMEOUT);
    curl_setopt($ch, CURLOPT_TIMEOUT, static::TIMEOUT);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $returnContent = curl_exec($ch);
    $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return (array)json_decode($returnContent, true);
  }

  /**
   * @name 发起PUT请求 : 上传pdf文件
   * @desc 为了能正常上传文件 timeout 要设置大一些
   */
  public static function sendHttpPUTPdfFile(string $url, string $contentMd5='', string $fileContent='')
  {
    $header = [
      'Content-Type:application/pdf',
      'Content-Md5:' . $contentMd5
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILETIME, true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($result === false) {
      $status = curl_errno($ch);
      // $result = 'put file to oss - curl error :' . curl_error($ch);
    }
    curl_close($ch);
    return $status;
  }

  /**
   * @name 发起PUT请求
   */
  public static function sendHttpPUT( string $url, array $data=[], string $appid='', string $stoken='') : array
  {
    $ch = curl_init( $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if( $data ){
      $dataStr = json_encode( $data );
      curl_setopt($ch, CURLOPT_POSTFIELDS, $dataStr);
    }
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_TIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt( $ch, CURLOPT_HTTPHEADER, [ "X-Tsign-Open-App-Id:" . $appid, "X-Tsign-Open-Token:" . $stoken, "Content-Type:application/json" ] );
    $returnContent = curl_exec($ch);
    $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return (array)json_decode($returnContent, true);
  }

  /**
   * @name 发起DELETE请求
   */
  public static function sendHttpDELETE( string $url, array $data=[], string $appid='', string $stoken='') : array
  {
    $ch = curl_init( $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if( $data ){
      $dataStr = json_encode( $data );
      curl_setopt($ch, CURLOPT_POSTFIELDS, $dataStr);
    }
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_TIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt( $ch, CURLOPT_HTTPHEADER, [ "X-Tsign-Open-App-Id:" . $appid, "X-Tsign-Open-Token:" . $stoken, "Content-Type:application/json" ] );
    $returnContent = curl_exec($ch);
    $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return (array)json_decode($returnContent, true);
  }

  /**
   * @name 带stoken发起GET请求
   */
  public static function doGetWithToken(string $url, array $data=[], string $appid='', string $stoken='') : array
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_TIMEOUT, static::TIMEOUT );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Tsign-Open-App-Id:" . $appid, "X-Tsign-Open-Token:" . $stoken, "Content-Type:application/json"] );
    $returnContent = curl_exec($ch);
    $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return (array)json_decode($returnContent, true);
  }

  /**
   * @name 获取文件的Content-MD5
   * 原理：1.先计算MD5加密的二进制数组（128位）
   * 2.再对这个二进制进行base64编码（而不是对32位字符串编码）
   */
  public static function getContentBase64Md5( string $filePath )
  {
    ## 获取文件MD5的128位二进制数组
    $md5file = md5_file($filePath, true);
    ## 计算文件的Content-MD5
    $contentBase64Md5 = base64_encode($md5file);
    return $contentBase64Md5;
  }


}