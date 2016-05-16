<?php
require_once "../connection.php";
header("Content-Type: text/plain; charset=UTF-8");
function getAuthToken()
{
    $headers=getallheaders();
    $auth=$headers{"Authorization"};
    if (!$auth)
    {
        http_response_code(401);
        header("WWW-Authenticate: Token");
        die("Nicht authorisiert1");
    }
    if(substr($auth,0,6)!=="Token "){
        http_response_code(400);
        die("Falscher Authorisierungsheader");
    }
    return substr($auth,6); 
}
function checkAuthToken($token){
    global $conn;
    $tokenDecoded=base64_decode($token);
    $pos=strpos($tokenDecoded,":");
    $username=substr($tokenDecoded,0,$pos);
    $password=substr($tokenDecoded,$pos+1);
    $stmt= $conn->prepare("Select passwort from spieler where name= ?");
    $stmt->execute([$username]);
    $row=$stmt->fetch();
    if(!$row or !password_verify($password, $row[0])){
        return false;
    }
    return $username;
}
if(checkAuthToken(getAuthToken())===false){
    http_response_code(401);
    header("WWW-Authenticate: Token");
    die("Nicht authorisiert $username $password");
}

