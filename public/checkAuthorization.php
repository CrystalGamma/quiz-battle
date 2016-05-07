<?php
header("Content-Type: text/plain; charset=UTF-8");
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
$tokenDecoded=base64_decode(substr($auth,6));
$pos=strpos($tokenDecoded,":");
$username=substr($tokenDecoded,0,$pos);
$password=substr($tokenDecoded,$pos+1);
if($username!=="admin" or $password!=="admin"){
    http_response_code(401);
    header("WWW-Authenticate: Token");
    die("Nicht authorisiert $username $password");
}

?>
Authorization not failed
