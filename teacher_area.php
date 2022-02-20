<?php

    header("Access-Control-Allow-Methods: GET");
    include 'structs.php';

    switch ($_SERVER['REQUEST_METHOD']){

        case 'GET': {
            
            if (isset($_COOKIE['PHPSESSID'])){
                session_start();
                if ($_SESSION['TASK'] == 0) response::client_error(401);
                else if ($_SESSION['TASK'] > 1) response::server_error(500);
            }else
                response::client_error(401);
            break;
        }
        default: response::server_error(405);
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

    <div id="ID_CREATE_EXAM_FORM" class="mainDiv" style="display:none;">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px">Create Exam</h1>
        <div style="padding:50px;border-radius:35px">
            <center><input id="ID_EXAM_NAME" class="txtBox0" type="text" placeholder="Exam name" style="margin-bottom:1rem"></center>
            <center><input id="ID_DATE" class="txtBox0" type="text" placeholder="Date (dd/mm/yyyy)" style="margin-bottom:1rem" maxlength="10"></center>
            <center><input id="ID_START" class="txtBox0" type="text" placeholder="Start (hh:mm)" style="margin-bottom:1rem" maxlength="5"></center>
            <center><input id="ID_END" class="txtBox0" type="text" placeholder="End (hh:mm)" style="margin-top:0rem" maxlength="5"></center>
            <center><button class="btnDef" id="ID_CREATE_BTN" style="margin-top:5rem">create</button></center>
        </div>
    </div>

    <div id="ID_HANDLE_EXAM_FORM" class="mainDiv" style="display:none">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px">Gestisci iscrizioni esami</h1>
        <div class="sel">
            <center><button id="ID_STUDENTS_DATA" class="btnDef" style="margin-top: 50px; width:100%;margin-bottom:2rem;">Visualizza * studenti</button></center>
            <input id="ID_STUDENT_M" type="text" class="txtBox0" style="width:100%;margin-bottom:2rem;" placeholder="matricola studente" maxlength="6">
            <select id="ID_SELECT_0">
                <option value="" selected disabled hidden>Seleziona esame</option>
            </select>
            <center><button id="ID_AUTORIZATION_EXAM" class="btnDef" style="margin-top: 50px; width:100%;">Autorizza studente</button></center>
        </div>
    </div>

    <div id="ID_MAIN_FORM" class="mainDiv">
        <h1 class="txt0" style="margin-bottom: 5rem; letter-spacing: 5px">Area Privata Teacher</h1>

        <div id="ID_PERS_BOX" class="personal">
            <h3 id="ID_NAME" class="txt0"></h3>
            <h3 id="ID_SURNAME" class="txt0"></h3>
            <h3 id="ID_MATRICOLA" class="txt0"></h3>
        </div>

        <div class="sel">
            <h2 id="ID_CREATE_EXAM" class="txt0">Crea un esame</h2>
            <h2 id="ID_HANDLE_EXAM" class="txt0" style="margin-top: 5rem">Gestisci esami programmati da me</h2>
        </div>
    </div>
</body>
</html>

<script>

    const forms = [
        $('#ID_MAIN_FORM'),
        $('#ID_CREATE_EXAM_FORM'),
        $('#ID_HANDLE_EXAM_FORM')
    ];

    const main = () => {

        $('#ID_NAME').html('<?php echo $_SESSION["NAME"]; ?>');
        $('#ID_SURNAME').html('<?php echo $_SESSION["SURNAME"]; ?>');
        $('#ID_MATRICOLA').html('<?php echo $_SESSION["MATRICOLA"]; ?>');
        
        requireExams();
    }

    const vDate = (str) => /^(0[1-9]|1\d|2\d|3[01])\/(0[1-9]|1[0-2])\/(19|20)\d{2}$/.test(str);
    const vTime = (str) => /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(str);
    const validateComplexDate = (date, t1, t2) => vDate(date) && vTime(t1) && vTime(t2) && (t1 < t2);     

    const today = () => {
        const d = new Date().getDate();
        let m = new Date().getMonth()+1;
        const y = new Date().getUTCFullYear();
        m = m.toString().length === 1 ? ("0"+m) : m.toString();
        return (y+"/"+m+"/"+d);
    }

    const convDate = (str) => {
        var date = new Date(dateString);
        return date.getDate()+"/"+(date.getMonth() + 1)+"/"+date.getFullYear();
    }

    const requireExams = () => {

        const options = {   
            url: 'server.php',
            type: 'GET',
            data: {ACTION:'GET_MY_EXAMS'},
            success: (response) => {
                if (response.exams.length===0)
                    console.log(response);
                else{
                    response.exams.forEach((exam) => {
                        document.getElementById('ID_SELECT_0').innerHTML += 
                            "<option id="+exam[0]+">("+exam[1]+") ("+exam[2]+" "+exam[3]+"-"+exam[4]+")</option>";
                    });
                }
            },

            error: (xhr) => {
                alert(JSON.parse(xhr.responseText));
                console.log(JSON.parse(xhr.responseText));
            }
        };

        $.ajax(options);
    }

    const clearSelect = (idElement) => {

        const e = document.getElementById(idElement);
        const len = e.options.length - 1;
        for(let i = len; i >= 0; i--) e.remove(i);
    }


    $('#ID_P').on('click', () => {
        $('#ID_ERROR').css('display','none');
    })

    $('#ID_LOGOUT_BTN').on('click', () => {
        $.ajax({url:'server.php', type:'POST', data:{ACTION:'LOGOUT'}, success: () => window.location.href = "login.php" });
    });

    $('#ID_CREATE_EXAM').on('click', () => {
        $('#ID_CREATE_EXAM_FORM').css('display','block');
        $('#ID_MAIN_FORM').css('display','none');
    });

    $('#ID_HANDLE_EXAM').on('click', () => {
        $('#ID_HANDLE_EXAM_FORM').css('display','block');
        $('#ID_MAIN_FORM').css('display','none');
    });

    $('#ID_HOME_BTN').on('click', () => {
        hideForms();
        $('#ID_MAIN_FORM').css('display', 'block');
    });

    $('#ID_CREATE_BTN').on('click', () => {
        
        if ($('#ID_EXAM_NAME').val()===""|| $('#ID_DATE').val()===""||$('#ID_START').val()===""||$('#ID_END').val()===""){
            alert("Insert all fields");
            return;
        }

        if (!validateComplexDate($('#ID_DATE').val(), $('#ID_START').val(), $('#ID_END').val())){
            alert("Formato data/orari non corretto");
            return;
        }

        const options = {
            url:'server.php', 
            type:'POST', 
            data: {
                EXAM: $('#ID_EXAM_NAME').val(),
                DATE: $('#ID_DATE').val(),
                START: $('#ID_START').val(),
                END: $('#ID_END').val()
            }, 
            success: (resp) => {
                console.log(resp); 
                alert((resp.status_message));
                $('#ID_EXAM_NAME').val("");
                $('#ID_DATE').val("");
                $('#ID_START').val("");
                $('#ID_END').val("");
                clearSelect("ID_SELECT_0");
                requireExams();
            },
            error: (xhr) => {
                alert(xhr.responseText);
            } 
        }

        $.ajax(options);
    });

    const getSelExamID = () => {
        var x = document.querySelector('#ID_SELECT_0');
        x.selectedIndex;
        return x.options[x.selectedIndex].id;
    }

    $('#ID_AUTORIZATION_EXAM').on('click', () => {

        if ($('#ID_SELECT_0').val()===null||$('#ID_STUDENT_M').val()===""){
            alert("Insert all fields");
            return;
        }

        const options = {
            url:'server.php', 
            type:'POST', 
            data: {
                ID_EXAM: getSelExamID(),
                STUDENT_M: $('#ID_STUDENT_M').val(),
            }, 
            success: (resp) => { 
                alert("Da adesso lo/a studente e' autorizzato/a ad iscriversi all'esame");
                console.log(resp); 
                $('#ID_STUDENT_M').val("");
            },
            error: (xhr) => {
                console.log(JSON.parse(xhr.responseText));
                alert(xhr.responseText);
            } 
        }

        $.ajax(options);
    });


    $('#ID_STUDENTS_DATA').on('click', () => {

        const options = {
            url:'server.php', 
            type:'GET', 
            data: {
                ACTION: 'GET_STUDENTS_DATA'
            }, 
            success: (resp) => {
                console.table(resp.students); 
                alert('Dati (tabella) studenti nella console'); 
            },
            error: (xhr) => {
                console.log(JSON.parse(xhr.responseText));
            }
        }

        $.ajax(options);
    });

    const hideForms = () => forms.forEach((form) => form.css('display','none'));

</script>


