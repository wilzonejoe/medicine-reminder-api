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
            $sql_check_credential = "SELECT DISTINCT u.id ID, u.firstname firstname, u.lastname lastname FROM users u join friendlink fl on fl.userId1 = $userOwnId WHERE not(u.id = $userOwnId)";
            $result = mysqli_query($conn,$sql_check_credential);

            $reponseCollectionOfUsers = array();

            while ($row= mysqli_fetch_assoc($result)) {
                $responseUser = array();
                $responseUser["userId"]  = $row["ID"];
                $responseUser["firstname"] = $row["firstname"];
                $responseUser["lastname"] = $row["lastname"];;
                array_push($reponseCollectionOfUsers, $responseUser);
            }
            http_response_code(200);
            $response["statusCode"] = 200;
            $response["user"] = $reponseCollectionOfUsers;
        }else if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0){
            $jsonReceived = trim(file_get_contents("php://input"));
            
            $content = json_decode($jsonReceived, true);
        
            if(!is_array($content)){
                http_response_code(405);
                $response["statusCode"] = 405;
                $response["message"] = "Request must be in valid json";
            }else{
                $userId = $content["userId"];
                require('../config.php');
                $conn = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
                $sql_check_credential = "SELECT DISTINCT * FROM friendlink WHERE (userId1='$userId' and  userId2 = '$userOwnId') or (userId1='$userOwnId' and  userId2 = '$userId')";
                $result = mysqli_query($conn,$sql_check_credential);
                if (mysqli_num_rows($result)>1) {
                    http_response_code(400);
                    $response["statusCode"] = 400;
                    $response["message"] = "Cannot add friend."; 
                } else {
                    $stmt = $conn->prepare("insert into friendlink" . "(userId1, userId2)" . " values (?,?),(?,?)");
                    $stmt->bind_param("ssss", $userOwnId, $userId,$userId, $userOwnId);
                    $result = $stmt->execute();
                    if (!$result) {
                        http_response_code(400);
                        $response["statusCode"] = 400;
                        $response["message"] = "Cannot add friend.";
                    } else {
                        http_response_code(200);
                        $response["statusCode"] = 200;
                        $response["message"] = "Add successful."; 
                    }
                }
            }
        }else if(strcasecmp($_SERVER['REQUEST_METHOD'], 'DELETE') == 0){
            $jsonReceived = trim(file_get_contents("php://input"));
            
            $content = json_decode($jsonReceived, true);
        
            if(!is_array($content)){
                http_response_code(405);
                $response["statusCode"] = 405;
                $response["message"] = "Request must be in valid json";
            }else{
                $userId = $content["userId"];
                require('../config.php');
                $conn = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
                $sql_check_credential = "DELETE FROM friendlink WHERE (userId1='$userId' and  userId2 = '$userOwnId') or (userId1='$userOwnId' and  userId2 = '$userId')";
                $result = mysqli_query($conn,$sql_check_credential);
                if (!$result) {
                    http_response_code(400);
                    $response["statusCode"] = 400;
                    $response["message"] = "Cannot delete friend."; 
                } else {
                    http_response_code(200);
                    $response["statusCode"] = 200;
                    $response["message"] = "Delete friend successful."; 
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