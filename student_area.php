<?php

    header("Access-Control-Allow-Methods: GET");
    include 'structs.php';

    switch ($_SERVER['REQUEST_METHOD']){

        case 'GET': {
            
            if (isset($_COOKIE['PHPSESSID'])){
                session_start();
                if ($_SESSION['TASK'] == 1) response::client_error(401);
                else if ($_SESSION['TASK'] > 1) response::server_error(500);
            }else
                response::client_error(401);
            break;
        }
        default: response::client_error(405);
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Area</title>
    <link rel="stylesheet" href="std.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body onload="main()">

    <center><button id="ID_HOME_BTN" class="btnDef" style="margin-top: 80px;">HOME</button></center>
    <center><button id="ID_LOGOUT_BTN" class="btnDef" style="margin-top: 20px;">LOGOUT</button></center>

    <div id="ID_SUBSCRIBE_FORM" class="mainDiv" style="display:none">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px">Esami in programma</h1>
        <div class="sel">
            <select id="ID_SELECT_0">
                <option value="" selected disabled hidden>Seleziona esame</option>
            </select>
            <center><button id="ID_SUBSCRIBE_EXAM" class="btnDef" style="margin-top: 50px">Iscriviti</button></center>
        </div>
    </div>

    <div id="ID_DO_EXAM_FORM" class="mainDiv" style="display:none">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px">Unisciti ad una sessione d'esame</h1>
        <div class="sel">
            <select id="ID_SELECT_1">
                <option value="" selected disabled hidden>Seleziona esame</option>

            </select>
            <center><button id="ID_NOW_EXAM" class="btnDef" style="margin-top: 50px;width:100%;">Partecipa adesso</button></center>
        </div>
    </div>

    <div id="ID_MAIN_FORM" class="mainDiv">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px">Area Privata Studente</h1>

        <div id="ID_PERS_BOX" class="personal">
            <h3 id="ID_NAME" class="txt0"></h3>
            <h3 id="ID_SURNAME" class="txt0"></h3>
            <h3 id="ID_CLASS" class="txt0"></h3>
            <h3 id="ID_MATRICOLA" class="txt0"></h3>
        </div>

        <div class="sel">
            <h2 id="ID_SUBSCRIBE" class="txt0">Iscriviti ad un esame</h2>
            <h2 id="ID_DO_EXAM" class="txt0" style="margin-top: 5rem">Unisciti ad una sessione d'esame</h2>
        </div>
    </div>
</body>
</html>

<script>

    const forms = [
        $('#ID_MAIN_FORM'),
        $('#ID_SUBSCRIBE_FORM'),
        $('#ID_DO_EXAM_FORM'),
        $('#ID_EXAM_FORM')
    ]

    const main = () => {

        $('#ID_NAME').html('<?php echo $_SESSION["NAME"]; ?>');
        $('#ID_SURNAME').html('<?php echo $_SESSION["SURNAME"]; ?>');
        $('#ID_CLASS').html('<?php echo $_SESSION["CLASS"]; ?>');
        $('#ID_MATRICOLA').html('<?php echo $_SESSION["MATRICOLA"]; ?>');

        requireExams('ID_SELECT_0');
        requireExams('ID_SELECT_1');
    }

    const requireExams = (id) => {

        const options = {   
            url: 'server.php',
            type: 'GET',
            data: {ACTION:'GET_EXAMS'},
            success: (response) => {
                response.exams.forEach((exam) => {
                    document.getElementById(id).innerHTML += 
                        "<option id="+exam[0]+">("+exam[1]+") ("+exam[2]+" "+exam[3]+"-"+exam[4]+")</option>";
                });
            },

            error: (xhr) => {
                alert(JSON.parse(xhr.responseText));
                console.log(JSON.parse(xhr.responseText));
            }
        };

        $.ajax(options);
    }

    $('#ID_HOME_BTN').on('click', () => {
        hideForms();
        $('#ID_MAIN_FORM').css('display', 'block');
    });

    const hideForms = () => forms.forEach((form) => form.css('display', 'none'));

    $('#ID_P').on('click', () => {
        $('#ID_ERROR').css('display','none');
    })

    $('#ID_SUBSCRIBE').on('click', () => {
        $('#ID_MAIN_FORM').css('display','none');
        $('#ID_SUBSCRIBE_FORM').css('display','block');
    }); 

    $('#ID_DO_EXAM').on('click', () => {
        $('#ID_MAIN_FORM').css('display','none');
        $('#ID_DO_EXAM_FORM').css('display','block');
    });
    
    $('#ID_LOGOUT_BTN').on('click', () => {
        $.ajax({url:'server.php', type:'POST', data:{ACTION:'LOGOUT'}, success: () => window.location.href = "login.php" });
    });

    const getSelExamID = (idjq) => {
        var x = document.querySelector(idjq);
        x.selectedIndex;
        return x.options[x.selectedIndex].id;
    }

    $('#ID_SUBSCRIBE_EXAM').on('click', () => {
        
        if ($('#ID_SELECT_0').val() === null) {alert("Selezionare un esame per eseguire la richiesta"); return;}
        
        if (localStorage.getItem('JWT_'+"<?php echo $_SESSION['MATRICOLA']; ?>"+'_'+getSelExamID('#ID_SELECT_0')) !== null){
            alert("Sei gia' iscritto/a a questo esame");
            return;
        }

        $.ajax(
            {
                url:'server.php', 
                type:'POST', 
                data: {ACTION:'SUBSCRIBE_EXAM', ID_EXAM:getSelExamID('#ID_SELECT_0')}, 
                success: (response) => {
                    alert("Ti sei iscritto correttamente all'esame");
                    console.log(response);
                    delete response.success;
                    delete response.status_code;
                    delete response.status_message;
                    localStorage.setItem(Object.keys(response)[0], Object.values(response)[0]);
                    console.table(localStorage);
                },
                error: (xhr) => {
                    alert(xhr.responseText);
                } 
            }
        );
    });
    
    const getJWT = (idA, idB) => localStorage.getItem("JWT_"+idA+"_"+idB) === null ? "empty" : localStorage.getItem("JWT_"+idA+"_"+idB);
    const delJWT = (idA, idB) => localStorage.removeItem("JWT_"+idA+"_"+idB);

    $('#ID_NOW_EXAM').on('click', () => {

        if ($('#ID_SELECT_1').val() === null){
            alert("Seleziona un esame");
            return;
        }

        const jwt_name = ["<?php echo $_SESSION['MATRICOLA']; ?>", getSelExamID('#ID_SELECT_1')];

        const options = {
            url:'server.php', 
            type:'GET', 
            data: {ACTION:'REQUIRE_EXAM', ID_EXAM:getSelExamID('#ID_SELECT_1'), JWT:getJWT(jwt_name[0],jwt_name[1])}, 
            success: (response) => {
                delJWT(jwt_name[0],jwt_name[1]);
                console.log(response);
                localStorage.setItem('bpayload', response.payload);
                window.location.href = response.link;
            },
            error: (xhr) => {
                console.log(JSON.parse(xhr.responseText));
                alert(xhr.responseText);
            } 
        };

        $.ajax(options);
    });

</script>
