<?php
    require('tokenValidation.php');
    $headers = apache_request_headers();
    if(isset($headers['Authorization'])){
        $authorizationHeader = $headers['Authorization'];
    }else{
        $authorizationHeader = '';
    }

    $reponse = array();

    if(($userOwnId = validateToken($authorizationHeader)) > 0){
        if(strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') == 0){
            $_SERVER['REQUEST_URI_PATH'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $queries = array();
            parse_str($_SERVER['QUERY_STRING'], $queries);

            $beacons = explode(',',$queries["beacons"]);
            $currentTime = $queries["time"];
            $currentDay = $queries["day"];

            $beaconsString = "";

            for ($i = 0; $i < sizeof($beacons); $i++)
            {
                $beaconsString .= "'".$beacons[$i]."'";
                if(($i + 1) < sizeof($beacons))
                {
                    $beaconsString .= ",";
                }
            }

            require('../config.php');

            $conn = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);

            $sql_get_class_info = "SELECT c.RoomNo 'roomNo', s.code 'streamCode', s.day 'day', s.time_start 'start', s.time_end 'end', p.Code 'paperCode', p.Name 'paperName' FROM beacon b, class_beacon cb, class c, paper p, stream s, users_stream us WHERE b.id = cb.beaconId AND cb.classId = c.id AND c.id = s.classId AND s.id = us.stream_id AND s.paperId = p.id AND us.user_id = '$userOwnId' AND s.time_start <= '$currentTime' AND s.time_end >= '$currentTime' AND s.day = '$currentDay' AND b.UUID IN ($beaconsString)";
            $result = mysqli_query($conn,$sql_get_class_info);
            $reponseCollectionOfClasses = array();

            while ($row= mysqli_fetch_assoc($result)) {
                $responseClass = array();
                $responseClass["roomNo"]  = $row["roomNo"];
                $responseClass["streamCode"] = $row["streamCode"];
                $responseClass["start"] = $row["start"];
                $responseClass["end"] = $row["end"];
                $responseClass["paperCode"] = $row["paperCode"];
                $responseClass["paperName"] = $row["paperName"];
                array_push($reponseCollectionOfClasses, $responseClass);
            }
            http_response_code(200);
            $response["statusCode"] = 200;
            $response["classes"] = $reponseCollectionOfClasses;
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