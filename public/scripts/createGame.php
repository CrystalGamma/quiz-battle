<?php
    if($_SERVER['REQUEST_METHOD']=='POST'){
        $inputJSON = file_get_contents('php://input');
        $input= json_decode( $inputJSON, TRUE ); //convert JSON into array
        $players=$input["players_"];
        $rounds=$input["rounds"];
        $turns=$input["turns"];
        $timelimit=$input["timelimit"];
        $roundlimit=$input["roundlimit"];
        $dealingrule=explode("/",$input["dealingrule"])[2];
        
        $stmt = $conn->prepare("Insert Into spiel (einsatz, dealer, runden, fragen_pro_runde, fragenzeit, rundenzeit, status) Values (100, :dealer, :runden, :fragen_pro_runde, :fragenzeit, :rundenzeit, 'Offen')");
        $stmt->bindValue(':dealer', $dealingrule);
        $stmt->bindValue(':runden', (int) turns, PDO::PARAM_INT);
        $stmt->bindValue(':fragen_pro_runde', (int) $turns, PDO::PARAM_INT);
        $stmt->bindValue(':fragenzeit', (int) $timelimit, PDO::PARAM_INT);
        $stmt->bindValue(':rundenzeit', (int) $roundlimit, PDO::PARAM_INT);
        
        if($stmt->execute()){
            $id=$conn->lastInsertId();
        }else{    
            var_dump($stmt->errorInfo());
            die();
        }
        
        $stmt = $conn->prepare("Insert Into teilnahme (spiel, spieler, akzeptiert) VALUES ($id, :spieler, 0)");
        foreach($players as $player){
            $stmt->bindValue(':spieler', explode("/",$input["dealingrule"])[2]);
            if(!$stmt->execute()){   
                var_dump($stmt->errorInfo());
                die();
            }
        }
        //TODO: Kontroller dealer angemeldet, Dealer automatische Annahme, Transaktion drumherum
    }else{
        http_response_code(405);
        die();
    }
?>