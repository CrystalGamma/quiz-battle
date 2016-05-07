<?php
header("Content-Type: text/plain; charset=UTF-8");
header("Vary: Authorization");
$headers=getallheaders();
if ($headers{"Content-Type"} !== "application/json" && $headers{"Content-Type"} !== "application/json; charset=UTF-8" ) {
    http_response_code(400);
    die("nicht erwarteter Content-Type; erwartete wurde application/json oder application/json; charset=UTF-8");
}
$reqestBody=json_decode(file_get_contents("php://input"),true);

if($reqestBody["user"]!=="admin"){
    http_response_code(404);
    die("Username wurde nicht gefunden");
}
if($reqestBody["password"]!=="admin"){
    http_response_code(403);
    die("Passwort ist inkorrekt");
}
echo("Token ".base64_encode($reqestBody["user"].":".$reqestBody["password"]));

?>
