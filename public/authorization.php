<?php
require_once "../connection.php";
header("Content-Type: text/plain; charset=UTF-8");
header("Vary: Authorization");
$headers=getallheaders();
if ($headers{"Content-Type"} !== "application/json" && $headers{"Content-Type"} !== "application/json; charset=UTF-8" ) {
    http_response_code(400);
    die("nicht erwarteter Content-Type; erwartete wurde application/json oder application/json; charset=UTF-8");
}
$requestBody=json_decode(file_get_contents("php://input"),true);
if(!is_string($requestBody["user"]) or !is_string($requestBody["password"])){
    http_response_code(400);
    die("Username und Passwort müssen ein String sein");
}
$stmt= $conn->prepare("Select passwort from spieler where name= ?");
$stmt->execute([$requestBody["user"]]);
$password=$stmt->fetch();
if(!$password){
    http_response_code(404);
    die("Username wurde nicht gefunden");
}
if(!password_verify($requestBody["password"], $password[0])){
    http_response_code(403);
    die("Passwort ist inkorrekt");
}
echo("Token ".base64_encode($requestBody["user"].":".$requestBody["password"]));
?>
