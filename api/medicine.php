<?php
require('tokenValidation.php');
$headers = apache_request_headers();
if(isset($headers['Authorization'])){
    $authorizationHeader = $headers['Authorization'];
}else{
    $authorizationHeader = '';
}

if(($userOwnId = validateToken($authorizationHeader)) > 0) {
    if(strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') == 0){
        require('../config.php');
        $conn = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
        $sql_check_credential = "SELECT DISTINCT * FROM medicine WHERE userId = $userOwnId";
        $result = mysqli_query($conn,$sql_check_credential);

        $reponseCollectionOfMedicine = array();

        while ($row= mysqli_fetch_assoc($result)) {
            $responseMedicine = array();
            $responseMedicine["ID"]  = $row["ID"];
            $responseMedicine["name"] = $row["name"];
            $responseMedicine["note"] = $row["note"];
            $responseMedicine["amount"] = $row["amount"];
            $responseMedicine["repeatTime"] = $row["repeattime"];
            array_push($reponseCollectionOfMedicine, $responseMedicine);
        }
        http_response_code(200);
        $response["statusCode"] = 200;
        $response["medicines"] = $reponseCollectionOfMedicine;
    }else if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0){
        $jsonReceived = trim(file_get_contents("php://input"));
        
        $content = json_decode($jsonReceived, true);
    
        if(!is_array($content)){
            $response["statusCode"] = 405;
            $response["message"] = "Request must be in valid json";
        }else{
            $name = $content["name"];
            $note = $content["note"];
            $repeatTime = $content["repeatTime"];
            $amount = $content["amount"];
            require('../config.php');
            $conn = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
            $stmt = $conn->prepare("insert into medicine" . "(name, note, repeatTime, amount, userId)" . " values (?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $note, $repeatTime, $amount, $userOwnId);
            $result = $stmt->execute();
            if (!$result) {
                http_response_code(400);
                $response["statusCode"] = 400;
                $response["message"] = "Cannot add medicine.";
            } else {
                http_response_code(200);
                $response["statusCode"] = 200;
                $response["message"] = "Add successful."; 
            }
        }
    }else if(strcasecmp($_SERVER['REQUEST_METHOD'], 'DELETE') == 0){
        $jsonReceived = trim(file_get_contents("php://input"));
        
        $content = json_decode($jsonReceived, true);
    
        if(!is_array($content)){
            $response["statusCode"] = 405;
            $response["message"] = "Request must be in valid json";
        }else{
            $medicineId = $content["medicineId"];
            require('../config.php');
            $conn = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
            $sql_check_credential = "DELETE FROM medicine WHERE userId='$userOwnId' and id = '$medicineId'";
            $result = mysqli_query($conn,$sql_check_credential);
            if (!$result) {
                http_response_code(400);
                $response["statusCode"] = 400;
                $response["message"] = "Cannot delete medicine."; 
            } else {
                http_response_code(200);
                $response["statusCode"] = 200;
                $response["message"] = "Delete medicine successful."; 
            }
        }
    }else{
        http_response_code(405);
        $response["statusCode"] = 405;
        $response["message"] = "Request not supported";
    }
}else{
    http_response_code(401);
    $response["statusCode"] = 401;
    $response["message"] = "Unauthorized";
}
echo json_encode($response);
?>