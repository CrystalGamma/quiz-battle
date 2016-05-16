<?php
require_once "../connection.php";
header("Vary: Authorization");
$headers=getallheaders();
if ($headers{"Content-Type"} !== "application/json" && $headers{"Content-Type"} !== "application/json; charset=UTF-8" ) {
    http_response_code(400);
    header("Content-Type: text/plain; charset=UTF-8");
    die("nicht erwarteter Content-Type; erwartete wurde application/json oder application/json; charset=UTF-8");
}
$requestBody=json_decode(file_get_contents("php://input"),true);
if(!is_string($requestBody["user"]) or !is_string($requestBody["password"])){
    http_response_code(400);
    header("Content-Type: text/plain; charset=UTF-8");
    die("Username und Passwort müssen ein String sein");
}
$stmt= $conn->prepare("Select passwort, id from spieler where name= ?");
$stmt->execute([$requestBody["user"]]);
$row=$stmt->fetch();
if(!$row){
    http_response_code(404);
    header("Content-Type: text/plain; charset=UTF-8");
    die("Username wurde nicht gefunden");
}
if(!password_verify($requestBody["password"], $row[0])){
    http_response_code(403);
    header("Content-Type: text/plain; charset=UTF-8");
    die("Passwort ist inkorrekt");
}
header("Content-Type: application/json");
echo("{\"player_\":\"/players/".$row["id"]."\",\"token\":\"$Token ".base64_encode($requestBody["user"].":".$requestBody["password"])."\"}");
?>
