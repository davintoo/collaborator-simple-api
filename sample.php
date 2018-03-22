<?php

require './src/Cbr/Api.php';

$api = new \Cbr\Api('http://yourdomain');
$res = $api->auth('api-user', 'api-password');
if ($res['code'] != 200) {
    echo 'Error: ' . print_r($res['body'], true) . "\n";
    exit;
}

$api->setAuthToken($res['body']['access_token']);

echo "Create user:\n";
$res = $api->import([
    'uid' => 'u123',
    'firstname' => 'test firstname',
    'secondname' => 'test secondname',
    'login' => 'test123',
    'email' => 'test123@test.me',
    'password' => '123456789',
    'position' => 'developer',
    'is_active' => 1
]);
print_r($res);


echo "Get users:\n";
$res = $api->getUsers([
    'filter[login]' => 'test123'
]);
print_r($res);


$id = $res['body']['data'][0]['id'];

echo "Block user:\n";
$res = $api->blockUser($id);
print_r($res);

echo "Unblock user:\n";
$res = $api->unBlockUser($id);
print_r($res);

echo "Set user rating:\n";
$res = $api->setUserRating('u123', 10, date('Y-m-d'));
print_r($res);

echo "Get user tasks:\n";
$res = $api->getUserTasks('u123', [
    'filter[data_filter]' => 'new'
]);
print_r($res);

echo "Get user notices:\n";
$res = $api->getUserNotices('u123');
print_r($res);

echo "Get news:\n";
$res = $api->getNews();
print_r($res);