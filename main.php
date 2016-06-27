<?php

require_once 'vendor/autoload.php';
require_once 'DatastoreService.php';


/*
 * Enable Datastore API (https://console.cloud.google.com/apis/)
 *
 * In APIs & Credentials / Credentials, create credentials for a new Service Account Key, generate a .json file.
 * Download it and saved it at the root of this project with the name: "CloudDatastoreCredentials.json"
 *
 */

DatastoreService::setInstance(new DatastoreService([
    'auth-conf-path' => 'CloudDatastoreCredentials.json',
    'application-id' => 'Swike-Caching-Issue', // Update this
    'dataset-id' => 'com-swike-caching-issue' // Update this
]));


$datastore = DatastoreService::getInstance();

$path = new \Google_Service_Datastore_PathElement();
$path->setKind('Foo');
$path->setName('Bar');

$key = DatastoreService::getInstance()->createKey();
$key->setPath([$path]);

$lookup_req = new \Google_Service_Datastore_LookupRequest();
$lookup_req->setKeys([$key]);

$response = DatastoreService::getInstance()->lookup($lookup_req);

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"></head><body><pre>';

var_dump($response);