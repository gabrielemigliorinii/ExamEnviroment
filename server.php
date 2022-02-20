<?php

    header("Access-Control-Allow-Methods: GET, POST");

    include 'structs.php'; // strutture dati
    require_once 'php-jwt-main/src/BeforeValidException.php';
    require_once 'php-jwt-main/src/ExpiredException.php';
    require_once 'php-jwt-main/src/SignatureInvalidException.php';
    require_once 'php-jwt-main/src/JWT.php';
    require_once 'php-jwt-main/src/Key.php';

    use Firebase\JWT\JWT;  
    use Firebase\JWT\Key; 
    use Firebase\JWT\ExpiredException;
    use Firebase\JWT\SignatureInvalidException;
    use Firebase\JWT\BeforeValidException;

    // nbf: not before [timestamp]
        // il token sarà valido solo dopo la data indicata 
        // in questo campo

    // iat: issued at [timestamp]
        // indica il momento in cui il token è stato generato

    // exp: expiration [timestamp]
        // Indica fino a quando il token sarà valido

    if (isset($_SERVER['REQUEST_METHOD'])){

        switch ($_SERVER['REQUEST_METHOD']){

            case 'POST': {

                if (isset($_POST['EXAM']) && isset($_POST['DATE']) && isset($_POST['START']) && isset($_POST['END']) && count($_POST) === 4){

                    if (isset($_COOKIE['PHPSESSID'])) session_start();
                    else response::client_error(401);

                    $exam = $_POST['EXAM'];
                    $date = $_POST['DATE'];
                    $start = $_POST['START'];
                    $end = $_POST['END'];
                    $m_teacher = $_SESSION['MATRICOLA'];

                    sqlx::connect_lh();

                    $res = sqlx::qry_r("SELECT name FROM exams WHERE name='$exam'");
                    
                    if ($res !== -1)
                        response::client_error(400, "Exam name already used");
                    
                    $res = null;

                    $res = sqlx::qry_a("INSERT INTO exams (name, date, start, end, matricola_teacher) VALUES ('$exam', '$date', '$start', '$end', '$m_teacher');");
                    
                    if ($res === true){
                        response::successful(200, "exam inserted successful");
                        exit;
                    }else response::server_error(500);
                }

                else if (isset($_POST['ACTION']) && isset($_POST['TASK']) && isset($_POST['MATRICOLA']) && isset($_POST['PASS']) && count($_POST) === 4){
                    
                    if ($_POST['ACTION'] === 'LOGIN'){
                        
                        $table = null;
                        $_POST['TASK'] = intval($_POST['TASK']);

                        switch ($_POST['TASK']) {
                            case 0: {$table = "students"; break;}
                            case 1: {$table = "teachers"; break;}
                            default: {response::client_error(400);}
                        }
                        
                        $matricola = $_POST['MATRICOLA'];

                        sqlx::connect_lh();
                        $res = sqlx::qry_r("SELECT matricola, password FROM $table WHERE matricola='$matricola';");
                        
                        if ($res === -1)
                            response::client_error(400, "Matricola non corretta o inesistente per il ruolo selezionato");

                        if ($res[0][1] === hash('sha256', $_POST['PASS']) && $res[0][0] === $_POST['MATRICOLA']){

                            session_set_cookie_params(['lifetime' => 3600, 'path' => "/"]);
                            session_start();
                            $res = null;

                            if ($table === "students"){

                                $res = sqlx::qry_r("SELECT name, surname, class FROM $table WHERE matricola='$matricola';");

                                $_SESSION['NAME'] = $res[0][0];
                                $_SESSION['SURNAME'] = $res[0][1];
                                $_SESSION['CLASS'] = $res[0][2];
                                $_SESSION['MATRICOLA'] = $matricola;
                                $_SESSION['TASK'] = $_POST['TASK'];

                                response::successful(200, 'login OK', array('href' => 'student_area.php'));

                            }else{
                                $res = sqlx::qry_r("SELECT name, surname, jwtkey FROM $table WHERE matricola='$matricola';");

                                $_SESSION['NAME'] = $res[0][0];
                                $_SESSION['SURNAME'] = $res[0][1];
                                $_SESSION['JKEY'] = $res[0][2];
                                $_SESSION['MATRICOLA'] = $matricola;
                                $_SESSION['TASK'] = $_POST['TASK'];

                                response::successful(200, 'login OK', array('href' => 'teacher_area.php'));
                            }

                        }else response::client_error(400, "wrong password");
                        exit;
                    }else response::client_error(400, "invalid parameters");
                }

                else if (isset($_POST['ACTION']) && count($_POST) === 1){
                    if ($_POST['ACTION'] === 'LOGOUT'){
                        if (isset($_COOKIE['PHPSESSID'])){
                            session_start();
                            setcookie("PHPSESSID", false, time()-5000, "/");
                            session_destroy();
                        }
                    }
                    
                    else if ($_POST['ACTION'] === 'END_EXAM'){
                        if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);
                        else session_start();

                        $esid = $_SESSION['EXAM_SESSION_ID'];
                        $date_end = timex::get_now('H:i:s');

                        sqlx::connect_lh();
                        sqlx::qry_a("UPDATE exam_sessions SET end='$date_end', voto=10 WHERE id=$esid");

                        unset($_SESSION['EXAM_SESSION_ID']);

                        response::successful(200, "exam terminated", array("link" => "student_area.php"));
                    }   
                    
                    else response::client_error(400, "invalid parameters");
                }

                else if (isset($_POST['ID_EXAM']) && isset($_POST['STUDENT_M']) && count($_POST) === 2){

                    if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);

                    sqlx::connect_lh();

                    $id_exam = $_POST['ID_EXAM'];
                    $student_m = $_POST['STUDENT_M'];

                    if (sqlx::qry_r("SELECT matricola FROM students WHERE matricola='$student_m'")===-1)
                        response::client_error(400, "Matricola studente inesistente");

                    if (sqlx::qry_r("SELECT * FROM attends_exam WHERE id_exam = $id_exam AND m_student = '$student_m'") !== -1)
                        response::client_error(400, "Studente gia' autorizzato per questo esame");

                    $id_exam = $_POST['ID_EXAM'];
                    $m_student = $_POST['STUDENT_M'];
                    $state = sqlx::qry_a("INSERT INTO attends_exam (id_exam, m_student) VALUES ($id_exam, '$m_student')");
                    if ($state === true){
                        response::successful(200, "student autorized");
                        exit;
                    }else response::server_error(500);
                }

                else if (isset($_POST['ACTION']) && isset($_POST['ID_EXAM']) && count($_POST) === 2){

                    if (isset($_COOKIE['PHPSESSID'])) session_start();
                    else response::client_error(401);

                    sqlx::connect_lh();
                    
                    $id_exam = intval($_POST['ID_EXAM']);
                    $res = sqlx::qry_r("SELECT m_student FROM attends_exam WHERE id_exam=$id_exam");

                    if ($res === -1) {
                        response::client_error(400, "Non puoi iscriverti a questo esame");
                    }

                    if (in_array(array($_SESSION['MATRICOLA']), $res)){

                        $exam_data = sqlx::qry_r("SELECT name, date, start, end, matricola_teacher FROM exams WHERE id=$id_exam");
                        
                        $name_exam = $exam_data[0][0];
                        $date_exam = $exam_data[0][1];
                        $time_start = $exam_data[0][2];
                        $time_end = $exam_data[0][3];
                        $teacher_matr = $exam_data[0][4];

                        $teacher_data = sqlx::qry_r("SELECT name, surname, jwtkey FROM teachers WHERE matricola='$teacher_matr'");

                        $teacher_name = $teacher_data[0][0];
                        $teacher_surname = $teacher_data[0][1];
                        $teacher_jwt_key = $teacher_data[0][2];

                        $payload = array(

                            "iss" => "/",
                            "aud" => "/",
                    
                            "jti" => get_ID('jwt'),
                    
                            "iat" => time(), // nascita token
                            "nbf" => timex::seconds($date_exam, $time_start), // inizio validità token
                            "exp" => timex::seconds($date_exam, $time_end), // fine validità token
                            
                            "data" => array(
                                
                                "student" => array(
                                    "name" => $_SESSION['NAME'],
                                    "surname" => $_SESSION['SURNAME'],
                                    "class" => $_SESSION['CLASS'],
                                    "matricola" => $_SESSION['MATRICOLA']
                                ),
                                
                                "teacher" => array(
                                    "name" => $teacher_name,
                                    "surname" => $teacher_surname,
                                    "matricola" => $teacher_matr                            
                                ),

                                "exam" => array(
                                    "id" => $id_exam,
                                    "name" => $name_exam
                                )
                            )
                        );

                        try
                        { 
                            $jwt = JWT::encode($payload, $teacher_jwt_key, 'HS256'); 
                            response::successful(200, "OK", array("JWT_".$_SESSION['MATRICOLA']."_".$id_exam => $jwt));
                            exit;
                        }
                        catch (UnexpectedValueException $e) 
                        { 
                            response::server_error(500);
                        }
                    
                    }
                    else{
                        response::client_error(400, "Non puoi iscriverti a questo esame");
                    }
                }

                else if (count($_POST)===0){
                    if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);
                    else response::client_error(400, "Invalid Parameters");
                }

                else  response::client_error(400, "Invalid Parameters");

                break;
            }
            
            case 'GET': {

                if (isset($_GET['ACTION']) && count($_GET) === 1){
                    if ($_GET['ACTION'] === 'GET_EXAMS'){
                        if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);
                        sqlx::connect_lh();
                        $res = sqlx::qry_r("SELECT id, name, date, start, end FROM exams");

                        response::successful(200, false, array("exams" => $res));
                        exit;
                    }

                    else if ($_GET['ACTION'] === 'GET_MY_EXAMS'){
                        if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);
                        else session_start();

                        sqlx::connect_lh();
                        $m = $_SESSION['MATRICOLA'];
                        $exams = sqlx::qry_r("SELECT id, name, date, start, end FROM exams WHERE matricola_teacher='$m';");

                        if ($exams === -1){  
                            $msg = "Nessun esame creato da te";
                            $exams = array();
                        }else
                            $msg = false;

                        response::successful(200, $msg, array("exams" => $exams));
                        unset($exams);
                        exit;
                    }

                    else if ($_GET['ACTION'] === 'GET_STUDENTS_DATA'){

                        if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);

                        sqlx::connect_lh();
                        $res = sqlx::qry_r("SELECT matricola, name, surname FROM students");

                        response::successful(200, false, array("students" => $res));
                        exit;
                    }

                    else response::client_error(400, "invalid value for parameters");
                }

                else if (isset($_GET['ACTION']) && isset($_GET['ID_EXAM']) && isset($_GET['JWT']) && count($_GET) === 3){
                    $decoded_jwt = null;
                    if ($_GET['ACTION'] === 'REQUIRE_EXAM'){

                        if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);
                        else session_start();

                        $id_exam = $_GET['ID_EXAM'];
                        $matr_student = $_SESSION['MATRICOLA'];

                        sqlx::connect_lh();
                        if (sqlx::qry_r("SELECT * FROM exam_sessions WHERE matricola_studente='$matr_student' AND exam_id=$id_exam") !== -1){
                            response::client_error(400, "Hai gia' partecipato a questo esame");
                        }

                        $jwt = $_GET['JWT'];
                        $jwtkeys = sqlx::qry_r("SELECT jwtkey FROM teachers");
                        $temp_key = null;
                        if (count($jwtkeys)===0) response::server_error(500);

                        for ($i=0; $i<count($jwtkeys); $i++){

                            try {
                                $decoded_jwt = JWT::decode($jwt, new Key($jwtkeys[$i][0], 'HS256'));
                                $temp_key = $jwtkeys[$i][0];
                                break;
                            } 
                            catch (SignatureInvalidException $e)
                            {
                                if ($i === count($jwtkeys)-1)
                                    response::client_error(400, "JWT firmato con una chiave non riconosciuta");
                                else continue;
                            }
                            catch (ExpiredException $e){
                                response::client_error(400, "L'esame non e' piu' disponibile");
                            }
                            catch (BeforeValidException $e){
                                response::client_error(400, "L'esame non e' ancora iniziato");
                            }
                            catch (UnexpectedValueException $e){
                                response::client_error(400, "Non sei iscritto a questo esame (JWT invalido)");
                            }
                        }

                        $decoded_jwt = json_decode(json_encode($decoded_jwt), true); // StdObject -> array

                        $matr_teacher = $decoded_jwt["data"]["teacher"]["matricola"];
                        $matr_student = $decoded_jwt["data"]["student"]["matricola"];
                        $date_today = timex::get_date('d:m:Y', time());
                        $date_hm = timex::get_now('H:i:s');

                        sqlx::qry_a("INSERT INTO exam_sessions (matricola_studente, matricola_teacher, data, start, end, voto, exam_id) VALUES ('$matr_student', '$matr_teacher', '$date_today', '$date_hm', NULL, NULL, $id_exam)");
                        
                        $exam_session = sqlx::qry_r("SELECT id FROM exam_sessions WHERE matricola_studente='$matr_student' AND exam_id=$id_exam");
                        $exam_data =sqlx::qry_r("SELECT date, end FROM exams WHERE id=$id_exam");

                        $_SESSION['EXAM_SESSION_ID'] = $exam_session[0][0];
                        $_SESSION['EXAM_EXPIRE'] = array($exam_data[0][0], $exam_data[0][1]);

                        response::successful(200, false, array('payload' => json_encode($decoded_jwt), 'link' => 'exam.php'));

                        break;

                    }else response::client_error(400);
                }

                else if (count($_GET)===0){
                    if (!isset($_COOKIE['PHPSESSID'])) response::client_error(401);
                    else response::client_error(400, "Invalid Parameters");
                }
                
                else response::client_error(400, "Invalid Parameters");

                break;
            }

            default: {

                response::client_error(405);  
                // response::server_error(501); metodo non implementato
                break;
            }
        }
    }

?>