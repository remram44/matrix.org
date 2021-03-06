<?php

function _get_jenkins_stream_context() {
    $username = 'dbkr';
    $password = '11d7ad76ed7471de59bd23a2d8795c6c';

    return stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
    ));
}

function fetch_ios_build($stream) {
    $streams = array(
        'master' => 'MatrixConsoleiOS',
        'develop' => 'MatrixConsoleiOSDevelop',
        'endpoint' => 'MatrixConsoleiOSEndpoint'
    );

    $jenkinsjob = $streams[$stream];

    $context = _get_jenkins_stream_context();

    $resp = json_decode(file_get_contents("http://matrix.org/jenkins/job/$jenkinsjob/lastSuccessfulBuild/api/json", false, $context), true);

    $build = $resp['number'];

    #$ipaRemoteFileName = null;
    $ipaRemotePath = null;

    foreach ($resp['artifacts'] as $artifact) {
        if (substr($artifact['fileName'], -4) == '.ipa') {
            #$ipaRemoteFileName = $artifact['fileName'];
            $ipaRemotePath = $artifact['relativePath'];
        }
    }

    $ipaFileName = "matrixConsole-ios-$stream-integration$build.ipa";
    $ipaPath = "cache/$ipaFileName";

    $plistFileName = "matrixConsole-ios-$stream-integration$build-Info.plist";
    $plistPath = "cache/$plistFileName";

    $iconFileName = "matrixConsole-ios-$stream-integration$build-icon-57x57.png";
    $iconPath = "cache/$iconFileName";

    //$retinaIconFileName = "matrixConsole-$version-$bundleVersion-$build-icon-57x57.png";
    //$retinaIconPath = "cache/$retinaIconFileName";

    if (!file_exists($ipaPath)) {
        file_put_contents($ipaPath, fopen("http://matrix.org/jenkins/job/$jenkinsjob/$build/artifact/$ipaRemotePath", 'r', false, $context));
    }

    if (!file_exists($plistPath)) {
        `unzip -p $ipaPath Payload/*.app/Info.plist > $plistPath`;
    }

    if (!file_exists($iconPath)) {
        `unzip -p $ipaPath Payload/*.app/AppIcon57x57.png > $iconPath`;
    }

    $bundleVersion = trim(`python plistget.py $plistPath CFBundleVersion`);
    $version = trim(`python plistget.py $plistPath CFBundleShortVersionString`);
    $bundleId = trim(`python plistget.py $plistPath CFBundleIdentifier`);

    return array(
        'build' => $build,
        'ipaPath' => $ipaPath,
        'plistPath' => $plistPath,
        'iconPath' => $iconPath,
        'bundleVersion' => $bundleVersion,
        'version' => $version,
        'bundleId' => $bundleId
    );
}

// XXX: c+p tastic but probably not worth factoring out for this
function fetch_android_build($stream) {
    $streams = array(
        'master' => 'AndroidConsole',
        'develop' => 'AndroidConsoleDevelop'
    );

    $jenkinsjob = $streams[$stream];

    $context = _get_jenkins_stream_context();

    $resp = json_decode(file_get_contents("http://matrix.org/jenkins/job/$jenkinsjob/lastSuccessfulBuild/api/json", false, $context), true);

    $build = $resp['number'];

    #$ipaRemoteFileName = null;
    $apkRemotePath = null;

    foreach ($resp['artifacts'] as $artifact) {
        if ($artifact['fileName'] == 'console-alpha-debug.apk') {
            #$ipaRemoteFileName = $artifact['fileName'];
            $apkRemotePath = $artifact['relativePath'];
        }
    }

    $apkFileName = "matrix-android-console-$stream-integration$build.apk";
    $apkPath = "cache/$apkFileName";

    if (!file_exists($apkPath)) {
        file_put_contents($apkPath, fopen("http://matrix.org/jenkins/job/$jenkinsjob/$build/artifact/$apkRemotePath", 'r', false, $context));
    }
    return array(
        'apkPath' => $apkPath,
    );
}

?>
