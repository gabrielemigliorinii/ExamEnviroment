
<?php

    header("Access-Control-Allow-Methods: GET");
    include 'structs.php';	

    if (isset($_SERVER['REQUEST_METHOD'])){
        if ($_SERVER['REQUEST_METHOD'] === 'GET'){
            if (isset($_COOKIE['PHPSESSID'])){
                if ($_SESSION['TASK'] == 0) header("location: student_area.php");
                else if ($_SESSION['TASK'] == 1) header("location: teacher_area.php");
            }
        }
        else response::client_error(405);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Area</title>
    <link rel="stylesheet" href="std.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>

    <div id="ID_MAIN_FORM" class="mainDiv">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px;">LOGIN</h1>
        <div style="padding:40px;border-radius:25px;width:50%;margin-left:auto;margin-right:auto;">
            <center>
                <select id="ID_TASK" style="margin-bottom:1rem">
                    <option value="" selected disabled hidden>role</option>
                    <option>student</option>
                    <option>teacher</option>
                </select>
            </center>
            <center><input id="ID_ID" class="txtBox0" type="text" placeholder="MATRICOLA" style="margin-bottom:1rem"></center>
            <center><input id="ID_PASS" class="txtBox0" type="password" placeholder="PASSWORD" style="margin-top:0rem"></center>
            <center><button class="btnDef" id="ID_LOGIN_BTN" style="margin-top:5rem">Login</button></center>
        </div>
    </div>
</body>
</html>

<script>

    class request {

        static send(url, data, method, callback = false){

            const options = {

                url: url,
                type: method,
                data: data,
                success: (response) => {
                    if (typeof callback === 'function') callback(response);
                    else console.log(response);
                },
                error: (xhr) =>  {
                    alert(xhr.responseText); 
                    console.log(JSON.parse(xhr.responseText));
                }
            }

            $.ajax(options);
        }
    }

    class href { static get = () => window.location.href; static set = (location) => window.location.href = location; }

    $('#ID_LOGIN_BTN').on('click', () => {
        const taskID = getTaskID($('#ID_TASK').val());
        if (taskID === -1) {
            alert("Role not selected");
            return;
        }
        if ($('#ID_ID').val()==="" || $('#ID_PASS').val()===""){
            alert("Insert all fields");
            return;
        } 
        request.send('server.php', {ACTION:'LOGIN', MATRICOLA:$('#ID_ID').val(), PASS:$('#ID_PASS').val(), TASK:taskID}, 'POST', 
            (response) => href.set(response.href));
    });

    const getTaskID = (task) => task === null ? -1 : task.toUpperCase() === "STUDENT" ? 0 : 1;

    /*
        db studenti: id_studente, nome, cognome, classe, password
        db professori: id_professore, nome, cognome, password
        db esame: id_esame, nome, data, durata, creatore (*id_professore) 
        db partecipazione: id_esame, id_studente
    */

</script>
