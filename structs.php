<?php

    header("Access-Control-Allow-Methods: GET");

    class timex {
        private static $init = false;
        
        public static function init($location='Europe/Rome'){
            date_default_timezone_set($location);
            self::$init = true;
        }
        
        public static function today($format='d/m/Y'){
            if (self::$init === false) self::init();
            return date($format);
        }

        public static function seconds($dmy, $hms){
            if (self::$init === false) self::init();
            $dmy = str_replace('/', '-', $dmy);   
            $ymd = date('Y-m-d', strtotime($dmy));
            $seconds = strtotime($ymd." $hms");
            return $seconds;
        }

        public static function get_date($format, $seconds){
            return Date($format, $seconds);
        }

        public static function get_now($format){
            return Date($format);
        }
    }

    class sqlx {

        private static $conn;

        public static function connect_lh(){
            self::$conn = new mysqli("127.0.0.1", "root", "", "db_exam_enviroment");
        }

        public static function connect_x(){
            self::$conn = new mysqli();
        }

        public static function qry_r(string $qry){
            $res = self::$conn->query($qry)->fetch_all();
            return empty($res) ? -1 : $res; 
        }

        public static function qry_a(string $qry){
            return self::$conn->query($qry);
        }
    }

    class regex {

        //index [0] can be => [a-z or _], index [1-18] can be => [a-z or 0-9 or _] 
        public static function username($username){
            return (preg_match('/^[a-z_]{1}[a-z0-9_]{2,19}$/', $username));
        }
    }

    class response {

        private const CT_JSON = "Content-Type: application/json; charset=utf-8";
        private const CT_TEXT = "Content-Type: text/plain; charset=utf-8";
        private const CT_HTML = "Content-Type: text/html; charset=UTF-8";

        private const ID_SUCCESSFUL = 200;
        private const ID_SERVER_ERROR = 500;
        private const ID_CLIENT_ERROR = 400;

        private const HTTP_RESPONSE_STATUS_CODES = array(

            self::ID_SUCCESSFUL => array(
                
                200 => "OK",
                201 => "Created",
                204 => "No Content"
            ),

            self::ID_CLIENT_ERROR => array(

                400 => "Bad Request",
                401 => "Unauthorized",
                403 => "Forbidden",
                404 => "Not Found",
                405 => "Method Not Allowed",
                429 => "Too Many Requests"
            ),

            self::ID_SERVER_ERROR => array(

                500 => "Internal Server Error",
                501 => "Not Implemented"
            )
        );

        public static function client_error(int $status_code, $status_msg = false, array $array = array(), $file = false){

            if (!self::status_code_valid($status_code, self::ID_CLIENT_ERROR)) response::server_error(500);

            http_response_code($status_code);
            $status_msg = self::get_status_msg(self::ID_CLIENT_ERROR, $status_code, $status_msg);

            $json = json_encode(array_merge(array('success' => false,'status_code' => $status_code,'status_message' => $status_msg), $array), JSON_PRETTY_PRINT);

            if ($file !== false) $file = file_get_contents($file);
            
            self::send($json, true, $file);
        }

        public static function server_error(int $status_code, $status_msg = false, array $array = array(), $file = false){

            if (!self::status_code_valid($status_code, self::ID_SERVER_ERROR)) response::server_error(500);

            http_response_code($status_code);
            $status_msg = self::get_status_msg(self::ID_SERVER_ERROR, $status_code, $status_msg);

            $json = json_encode(array_merge(array('success' => false,'status_code' => $status_code,'status_message' => $status_msg), $array), JSON_PRETTY_PRINT);
            
            self::send($json, true, $file);
        }

        public static function successful(int $status_code, $status_msg = false, array $array = array(), $file = false){

            if (!self::status_code_valid($status_code, self::ID_SUCCESSFUL)) response::server_error(500);

            http_response_code($status_code);
            $status_msg = self::get_status_msg(self::ID_SUCCESSFUL, $status_code, $status_msg);

            $json = json_encode(array_merge(array('success' => true,'status_code' => $status_code,'status_message' => $status_msg), $array), JSON_PRETTY_PRINT);

            self::send($json, false, $file);
        }

        public static function ctype($option){

            switch (strtoupper($option)){
                case 'TEXT': default: {   
                    header(self::CT_TEXT);
                    break;
                }
                case 'JSON': {
                    header(self::CT_JSON);
                    break;
                }
                case 'HTML': {
                    header(self::CT_HTML);
                    break;
                }
            }
        }

        private static function status_code_valid(int $status_code, int $id){

            return ($status_code >= $id && $status_code <= $id + 99);
        }

        private static function get_status_msg(int $index, int $status_code, $status_msg){

            return ( 
                $status_msg !== false ? 
                    $status_msg : (@self::HTTP_RESPONSE_STATUS_CODES[$index][$status_code] === null ? 
                        "Status Message Not Available" : self::HTTP_RESPONSE_STATUS_CODES[$index][$status_code])
            );
        }

        public static function send($response, $exit, $file = false){

            if ($file !== false){

                self::ctype('HTML');
                echo $file;
                echo "<h3>('$response')</h3>";
                
            } else {         

                self::ctype('JSON');
                echo $response;
            }
            if ($exit) exit;
        }

        public static function format_type($code, $type){
            self::ctype($type);
            http_response_code($code);
        }
    }

    function get_ID($element){
        $len = 7;
        $value = null;
        switch (strtolower($element)) {
            case 'jwt': {
                if (!file_exists("data/jwtc")) return 'DEFAULT_TOKEN_ID';
                $value = file_get_contents("data/jwtc");
                break;
            }
            default: return -1;
        }
        $id = "";
        $value = intval($value)+1;
        file_put_contents('data/jwtc', $value);
        
        for ($i = 0; $i < ($len - strlen(strval($value))); $i++)
            $id .= "0";
        
        $id = 'T' . $id . strval($value);
        
        return $id;
    }

?>
