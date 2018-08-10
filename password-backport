    include "/data/common/password.php"; //backport PHP password_* functions to this PHP 5.3
    // https://github.com/ircmaxell/password_compat
    
    $authenticated = false;
    // find out the student ID
    if (isset($_POST["username"]) && isset($_POST["password"])){
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        /I'd kill for some PDO love
        $sql_string = "SELECT student_account.sid, student_account.password
                    FROM student_account
                    WHERE username = '$username'";
        $result = mysql_query($sql_string);
        $data = mysql_fetch_assoc($result);

        if(($password == $data['password']) //plaintext match
            ||(md5($password) == $data['password']) //MD5 String
            ||(password_verify($password,$data['password'])) // current hash!
        ){
            //test and replace current pw, if needed
            if (password_needs_rehash($data['password'], PASSWORD_DEFAULT)) {
                // If so, create a new hash, and replace the old one
                $newHash = password_hash($password,PASSWORD_DEFAULT);
                //$newHash has $ in it. Don't refactor out the quotes
                mysql_query("UPDATE student_account SET password='".$newHash."' WHERE student_account.sid = $SESSION_VAR[sid];");
            }
            $authenticated = true;
        }else{
            fatalError("Incorrect username or password. Please <a href=\"/\">try again</a>.
            <br><br>If you need assistance, please <a href='/contact-us/'>contact us</a>.", "11", ".");
            exit;
        }
