<?php

    header("Access-Control-Allow-Methods: GET");
    include 'structs.php';

    if (isset($_SERVER['REQUEST_METHOD'])){
        switch ($_SERVER['REQUEST_METHOD']){
            case 'GET': {
                if (isset($_COOKIE['PHPSESSID'])){
                    session_start();
                    if (isset($_SESSION['EXAM_SESSION_ID'])){

                        $dt = timex::seconds($_SESSION['EXAM_EXPIRE'][0], $_SESSION['EXAM_EXPIRE'][1]) - time();
                        if ($dt < 0){
                            // L'esame è scaduto da |$d| secondi
                            sqlx::connect_lh();
                            $esid = $_SESSION['EXAM_SESSION_ID'];
                            $date_end = $_SESSION['EXAM_EXPIRE'][1]+":00";
                            sqlx::qry_a("UPDATE exam_sessions SET end='$date_end', voto=0 WHERE id=$esid");
                            unset($_SESSION['EXAM_SESSION_ID']);
                            unset($_SESSION['EXAM_EXPIRE']);
                            response::client_error(401); 
                        }
                        else echo "<p class='txt0' style='font-size:1.5rem;margin-top:50px;'>hai ancora $dt secondi<p>";

                    }else response::client_error(401);
                }else response::client_error(401);
                break;
            }
            default: response::client_error(405);
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="ID_TITLE">Exam</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="std.css">
</head>
<body onload="main()">
    <div id="ID_EXAM_FORM" class="mainDiv">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px">Form esame</h1>
        <div class="payload">
            <p style="word-break:break-all;" id="ID_CONTX"></p>
        </div>
        <button id="ID_T" class="btnDef" style="width:100%">concludi esame</button>
    </div>
</body>
</html>

<script>
    
    const main = () => {

        const payload = localStorage.getItem('bpayload');
        document.getElementById('ID_CONTX').innerHTML = "PAYLOAD<br><br>"+payload;
        console.log(JSON.parse(payload));
    }

    $('#ID_T').on('click', () => {

        localStorage.removeItem('bpayload');

        const options = {
            url: 'server.php',
            data: {ACTION:'END_EXAM'},
            type: 'POST',
            success: (response) => {
                console.log(response);
                window.location.href = response.link;
            },
            error: (xhr) => {
                alert(xhr.responseText);
                console.log(xhr.responseText);
            }
        };

        $.ajax(options);

    });

</script>


