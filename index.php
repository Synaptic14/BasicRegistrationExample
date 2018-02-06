<?php
error_reporting(E_ALL);
include 'db_class.php';
include 'msg_class.php';
define("SALT", "genericSalt");
$test = 5;
function encryptPass($pass) {
    $options = ['cost' => 11];
    $pass += SALT;
    return password_hash($pass, PASSWORD_BCRYPT, $options);
}

function genRandStr() {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ$%*', ceil(15/strlen($x)) )),1,15);
}

function sendConfirmEmail($email, $confirm_code) {
    $headers = "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $msg = "Thank you for registering at the Test Registration Page. Please click the following link to confirm your email: <a href=\"http://alex-drake.com/code_examples/keiran/?cc=".$confirm_code."\">Confirm Email</a>";
    mail($email, "Welcome to the Test Registration Page!", $msg, $headers);
}

function confirmEmail($id) {
    $confirmEmailQuery = new Database();
    $confirmEmailQuery->query('UPDATE users SET email_confirm = 1 WHERE id = :id');
    $confirmEmailQuery->bind(':id', $id);
    $confirmEmailQuery->execute();
    if($confirmEmailQuery->rowCount() == 1) {
        Messages::setMsg('Your Email has been successfully confirmed!', 'success');
    } else {
        Messages::setMsg('There was a problem confirming your email. Please try again.', 'error');
    }
}

if(isset($_POST['reg_submit'])) {
    $confirm_code = genRandStr();
        try {
            $regQuery = new Database();
            $regQuery->query('INSERT INTO users (username, email, password, ip, confirm_code) VALUES (:username, :email, :password, :ip, :confirm_code)');
            $regQuery->bind(':username', $_POST['username']);
            $regQuery->bind(':password', encryptPass($_POST['password']));
            $regQuery->bind(':email', $_POST['email']);
            $regQuery->bind(':ip', $_SERVER['REMOTE_ADDR']);
            $regQuery->bind(':confirm_code', $confirm_code);
            $regQuery->execute();
        if($regQuery->lastInsertId()) {
            Messages::setMsg('Registration was successful! Please check your email for a link to confirm your account.', 'success');
            sendConfirmEmail($_POST['email'], $confirm_code);
        } else {
            Messages::setMsg('There was a problem processing your registration.', 'error');
        }
        } catch(PDOException $e) {
             Messages::setMsg("Registration Query Error: ".$e->getMessage(), 'error');
        }
}

if(isset($_GET['cc'])) {
    $ccQuery = new Database();
    $ccQuery->query('SELECT * FROM users WHERE confirm_code = :confirm_code');
    $ccQuery->bind(':confirm_code', $_GET['cc']);
    if($ccQuery->singleRow()) {
        $userId = $ccQuery->singleRow();
        confirmEmail($userId['id']);
    } else {
        Messages::setMsg('The provided confirmation code is invalid.', 'error');
    }
}

?>
<!doctype html>
<html>
    <head>
        <link href="bootstrap.css" type="text/css" rel="stylesheet">
        <meta charset="UTF-8">
        <title>Keiran Registration Example</title>
    </head>
    <body>
        <?php Messages::display(); ?>
        <h1>Registration Example</h1>
        <form style="text-align:center" method="post" action="#">
            <span style="font-weight: strong;">Username: </span> <input type="text" name="username" /><br />
            <span style="font-weight: strong;">Email: </span> <input type="email" name="email" /><br />
            <span style="font-weight: strong;">Password: </span> <input type="password" name="password" />
            <br /><input type="submit" value="Register" name="reg_submit" />
        </form>
    </body>
</html>
