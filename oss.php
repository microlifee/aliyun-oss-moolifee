<?php
header('Content-type: text/html; charset=utf-8');
require_once '/oss-sdk/autoload.php';
use OSS\OssClient;
use OSS\Core\OssException;

$accessKeyId = "LTAIqsckErHArERB";
$accessKeySecret = "Ml7EeZo9lspICHqRyACPGTBO6OjSFp";
$endpoint = "http://moonthy.oss-cn-shanghai.aliyuncs.com/";
try {
    $ossClient = new OssClient(
        $accessKeyId, $accessKeySecret, $endpoint, true /* use cname */);
} catch (OssException $e) {
    print $e->getMessage();
}

$result = listAllObjects($ossClient, 'images/', 'moonthy');
// print_r(json_encode(array_filter($result), JSON_UNESCAPED_UNICODE));
print_r($result);

/**
 * 列出Bucket内所有目录和文件， 根据返回的nextMarker循环调用listObjects接口得到所有文件和目录
 *
 * @param OssClient $ossClient OssClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function listAllObjects($ossClient, $prefix='images/广元/', $bucket = 'moonthy')
{
    //构造dir下的文件和虚拟目录
    // for ($i = 0; $i < 100; $i += 1) {
    //     $ossClient->putObject($bucket, "images/广元/主图" . strval($i), "hi");
    //     $ossClient->createObjectDir($bucket, "images/广元/主图" . strval($i));
    // }
    $prefix = $prefix;
    $delimiter = '/';
    $nextMarker = '';
    $maxkeys = 30;
    while (true) {
        $options = array(
            'delimiter' => $delimiter,
            'prefix' => $prefix,
            'max-keys' => $maxkeys,
            'marker' => $nextMarker,
        );
        
        try {
            $listObjectInfo = $ossClient->listObjects($bucket, $options);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
        $nextMarker = $listObjectInfo->getNextMarker();
        $listObject = $listObjectInfo->getObjectList();
        $listPrefix = $listObjectInfo->getPrefixList();
        // var_dump(count($listObject));
        $result = array();
        foreach ($listPrefix as $key => $row) {
            $arr['name'] = $row->getPrefix();
            $arr['children'] = listAllObjects($ossClient, $row->getPrefix());
            if (count($arr['children']) === 0) unset($arr['children']);

            $result[] = $arr;
        }

        return $result;
        if ($nextMarker === '') {
            break;
        }
    }
}