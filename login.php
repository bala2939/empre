<?php
  require 'assets/bd/config.php';

  if(isset($_POST['login'])) {
    $errMsg = '';

    // Get data from FORM
    $usuario = $_POST['usuario'];
    
    $clave = MD5($_POST['clave']);

    if($usuario == '')
      $errMsg = 'Digite su usuario';
    if($clave == '')
      $errMsg = 'Digite su contraseña';

    if($errMsg == '') {
      try {
$stmt = $connect->prepare('SELECT id, nombre, usuario, correo,clave, cargo FROM usuarios WHERE usuario = :usuario');


        $stmt->execute(array(
          ':usuario' => $usuario
          
          
          ));
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if($data == false){
          $errMsg = "User $usuario no encontrado.";
        }
        else {
          if($clave == $data['clave']) {

            $_SESSION['id'] = $data['id'];
            $_SESSION['nombre'] = $data['nombre'];
            $_SESSION['usuario'] = $data['usuario'];
            $_SESSION['correo'] = $data['correo'];
            $_SESSION['clave'] = $data['clave'];
            $_SESSION['cargo'] = $data['cargo'];
            
            
    if($_SESSION['cargo'] == 1){
          header('Location: view/dashboard/index.php');
        }
            exit;
          }
          else
            $errMsg = 'Contraseña incorrecta.';
        }
      }
      catch(PDOException $e) {
        $errMsg = $e->getMessage();
      }
    }
  }
?>

<!DOCTYPE html>
<!-- Coding By CodingNepal - youtube.com/codingnepal -->
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Acceder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/plugins/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/fuentes/iconic/css/material-design-iconic-font.min.css">
  </head>
  <body>
    <div class="center">
      <h1>I LOVE YOU</h1>
      <?php
    if(isset($errMsg)){
    echo '<div style="color:#FF0000;text-align:center;font-size:20px;">'.$errMsg.'</div>';  
         }
?>
      <form class="login-form validate-form"  action="" method="post">
        
        <div class="txt_field" data-validate = "Usuario incorrecto">
          <input type="text" class="input100"  name="usuario" value="<?php if(isset($_POST['usuario'])) echo $_POST['usuario'] ?>" autocomplete="off">
          <span></span>
          <label>Usuario</label>
        </div>

        <div class="txt_field" data-validate="Password incorrecto">
          <input type="password" class="input100"  required="true" name="clave" value="<?php if(isset($_POST['clave'])) echo MD5($_POST['clave']) ?>">
          <span></span>
          <label>Contraseña</label>
        </div>

         <div class="login-form-bgbtn"></div>
         <button type="submit" name='login' class="login-form-btn">CONECTAR</button>
        <div class="signup_link">
        </div>
      </form>
    </div>
    <script src="assets/jquery/jquery-3.3.1.min.js"></script>
    <script src="assets/popper/popper.min.js"></script>          
    <script src="assets/plugins/sweetalert2/sweetalert2.all.min.js"></script>    
       
  </body>
</html>
