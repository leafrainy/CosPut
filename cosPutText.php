<?php
/*
腾讯云COS文件上传类  
@author：leafrainy
@time：2022年06月14日

@食用方式：

@单一文本文件
$config  = array(
    'SecretId' => "xxxxx", 
    'SecretKey' => "xxxxx", 
    'bucketName' => "leafrainy-124544343", 
    'region' => "ap-beijing-1"
    );
$go = new CosPut($config);

$go->putFile("123.txt","6789678912345");


*/


class CosPut {


    //腾讯云授权ak
    private $SecretId = "";
    //腾讯云授权sk
    private $SecretKey = "";
    //Bucket名称
    private $bucketName = "";
    //Bucket区域
    private $region = "";


    public function __construct($config =array()){
        $this->SecretId = $config['SecretId'];
        $this->SecretKey = $config['SecretKey'];
        $this->bucketName = $config["bucketName"];
        $this->region = $config['region'];
    }

    //获取文件大小
    private function getContentLength($data){

        if(is_file($data)){
            $data=  file_get_contents($data);
        }

        return strlen($data);

    }

    //获取签名
    private function getSign($fileName,$host,$length,$expires = '+30 minutes'){

        if ( is_null( $expires ) || !strtotime( $expires )) {
            $expires = '+30 minutes';
        }

        $signTime = ( string )( time() - 60 ) . ';' . ( string )( strtotime( $expires ) );
        
        $signKey = hash_hmac( 'sha1', $signTime, trim($this->SecretKey) );

        $headerList = "content-length;host";
        $httpHeaders = "content-length=".$length."&host=".$host;

    
        $httpString ="put\n" . urldecode("/".$fileName ) . "\n" ."\n".$httpHeaders."\n";

        $sha1edHttpString = sha1( $httpString );
        $stringToSign = "sha1\n$signTime\n$sha1edHttpString\n";

        $signature = hash_hmac( 'sha1', $stringToSign, $signKey );

        $authorization = 'q-sign-algorithm=sha1&q-ak='. trim($this->SecretId) .
        "&q-sign-time=$signTime&q-key-time=$signTime&q-header-list=$headerList&q-url-param-list=$urlParamList&" .
        "q-signature=$signature";
        
        return $authorization;
    }

    //上传请求
    private function request($fileName,$fileData){

        $host = $this->bucketName.".cos.".$this->region.".myqcloud.com";
        $url = "https://".$host."/".$fileName;

        $length = $this->getContentLength($fileData);

        $data['header'] = array(
            "Host:".$host,
            "Content-Length: ".$length
        );
        $sign = $this->getSign($fileName,$host,$length);

        array_push($data['header'], "Authorization:".$sign);

        $data['body'] = $fileData;
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $data['header']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data['body']);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //上传文件
    public function putFile($fileName,$fileData){

        $res = $this->request($fileName,$fileData);

        if(!$res){
            echo "{code:1,msg:success}";
        }else{
            var_dump($res);//error自己写日志吧
        }
    }

}

