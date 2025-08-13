<?php
require_once 'auth.php';

if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tableau de bord - PlagiaTrack</title>
  <link href="../templatemo_455_visual_admin/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../templatemo_455_visual_admin/css/font-awesome.min.css" rel="stylesheet" />
  <link href="../templatemo_455_visual_admin/css/templatemo-style.css" rel="stylesheet" />
</head>
<body>
  <div class="templatemo-flex-row">
      <div class="templatemo-sidebar">
      <header class="templatemo-site-header">
        <div class="square"></div>
        <h1>PlagiaTrack</h1>
      </header>
      <div class="profile-photo-container">
        <img src="../templatemo_455_visual_admin/images/profile-photo.jpg" alt="Profile Photo" class="img-responsive" />
        <div class="profile-photo-overlay"></div>
      </div>
      <nav class="templatemo-left-nav">
        <ul>
          <li><a href="../backend/dashboard.php" class="active"><i class="fa fa-home fa-fw"></i>TABLEAU DE BORD</a></li>
          <?php if ($user['role_id'] != 3 && $user['role_id'] != 4): ?>
          <li><a href="../backend/gestion_utilisateurs.php"><i class="fa fa-users fa-fw"></i>GéRER UTILISATEURS</a></li>
          <?php endif; ?>
          <li><a href="../backend/gestion_documents.php"><i class="fa fa-file fa-fw"></i>GESTION DES DOCUMENTS</a></li>
          <?php if ($user['role_id'] != 4): ?>
          <li>
            <a href="../backend/analyse_plagiat.php">
              <i class="fa fa-line-chart fa-fw"></i>
              ANALYSE DE PLAGIAT
            </a>
          </li>
          <?php endif; ?>
          <?php if ($user['role_id'] != 4): ?>
          <li><a href="../backend/rapports.php"><i class="fa fa-file-pdf-o fa-fw"></i>RAPPORTS DE PLAGIAT</a></li>
          <?php endif; ?>
          <li><a href="../backend/notifications.php"><i class="fa fa-bell fa-fw"></i>Notifications</a></li>
          <li><a href="../backend/profil.php"><i class="fa fa-cog fa-fw"></i>Gérer profil</a></li>
          <!-- Suppression du lien vers la page Administration -->
          <!-- <li><a href="../backend/administration.php"><i class="fa fa-cog fa-fw"></i>ADMINISTRATION</a></li> -->
          <li><a href="../backend/logout.php"><i class="fa fa-sign-out fa-fw"></i>DÉCONNEXION</a></li>
        </ul>
      </nav>
    </div>
    <div class="templatemo-content col-1 light-gray-bg">
      <div class="templatemo-content-container">
        <div class="templatemo-flex-row flex-content-row">
          <div class="templatemo-content-widget white-bg col-1 text-center">
            <i class="fa fa-users fa-4x"></i>
            <h2>Utilisateurs</h2>
            <p>Gérez les utilisateurs de la plateforme.</p>
            <?php if ($user['role_id'] != 4 && $user['role_id'] != 3): ?>
            <a href="gestion_utilisateurs.php" class="btn btn-primary">Gérer</a>
            <?php endif; ?>
          </div>
          <div class="templatemo-content-widget white-bg col-1 text-center">
            <i class="fa fa-file fa-4x"></i>
            <h2>Documents</h2>
            <p>Gérez les documents soumis pour analyse.</p>
            <a href="gestion_documents.php" class="btn btn-primary">Gérer</a>
          </div>
          <div class="templatemo-content-widget white-bg col-1 text-center">
            <!-- Custom SVG icon for Analyse de plagiat -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 94 94" width="64" height="64" style="margin-bottom: 15px;">
              <path d="M83.51,81.74h-73A9.24,9.24,0,0,1,1.25,72.5V27.34a9.25,9.25,0,0,1,9.24-9.24h73a9.25,9.25,0,0,1,9.24,9.24V72.5A9.24,9.24,0,0,1,83.51,81.74Zm-73-62.14a7.75,7.75,0,0,0-7.74,7.74V72.5a7.75,7.75,0,0,0,7.74,7.74h73a7.75,7.75,0,0,0,7.74-7.74V27.34a7.75,7.75,0,0,0-7.74-7.74Z"/>
              <path d="M42.5,74.75H8.15a.75.75,0,0,1,0-1.5H42.5a.75.75,0,0,1,0,1.5Z"/>
              <path d="M17.58,74.75H10.45A.75.75,0,0,1,9.7,74V59.07a.76.76,0,0,1,.75-.75h7.13a.76.76,0,0,1,.75.75V74A.75.75,0,0,1,17.58,74.75Zm-6.38-1.5h5.63V59.82H11.2Z"/>
              <path d="M24.7,74.75H17.58a.74.74,0,0,1-.75-.75V54.21a.75.75,0,0,1,.75-.75H24.7a.75.75,0,0,1,.75.75V74A.74.74,0,0,1,24.7,74.75Zm-6.37-1.5H24V55H18.33Z"/>
              <path d="M31.83,74.75H24.7A.75.75,0,0,1,24,74V56.83a.76.76,0,0,1,.75-.75h7.13a.75.75,0,0,1,.75.75V74A.74.74,0,0,1,31.83,74.75Zm-6.38-1.5h5.63V57.58H25.45Z"/>
              <path d="M39,74.75H31.83a.75.75,0,0,1-.75-.75V49.92a.76.76,0,0,1,.75-.75H39a.76.76,0,0,1,.75.75V74A.75.75,0,0,1,39,74.75Zm-6.38-1.5h5.63V50.67H32.58Z"/>
              <path d="M41.37,34.87a3.48,3.48,0,1,1,3.47-3.47A3.48,3.48,0,0,1,41.37,34.87Zm0-5.45a2,2,0,1,0,2,2A2,2,0,0,0,41.37,29.42Z"/>
              <path d="M28,49.09a3.47,3.47,0,1,1,3.47-3.47A3.47,3.47,0,0,1,28,49.09Zm0-5.44a2,2,0,1,0,2,2A2,2,0,0,0,28,43.65Z"/>
              <path d="M18.58,38.72a3.47,3.47,0,1,1,3.48-3.47A3.48,3.48,0,0,1,18.58,38.72Zm0-5.44a2,2,0,1,0,2,2A2,2,0,0,0,18.58,33.28Z"/>
              <path d="M8.74,49.82a3.48,3.48,0,1,1,3.47-3.48A3.48,3.48,0,0,1,8.74,49.82Zm0-5.45a2,2,0,1,0,2,2A2,2,0,0,0,8.74,44.37Z"/>
              <path d="M29.85,44.39a.73.73,0,0,1-.51-.21.74.74,0,0,1,0-1L39,32.87a.73.73,0,0,1,1,0,.75.75,0,0,1,0,1.06L30.4,44.15A.75.75,0,0,1,29.85,44.39Z"/>
              <path d="M26.16,44.36a.78.78,0,0,1-.56-.25l-5.74-6.34a.75.75,0,0,1,1.11-1l5.75,6.34a.76.76,0,0,1-.06,1.06A.74.74,0,0,1,26.16,44.36Z"/>
              <path d="M10.55,45.06a.73.73,0,0,1-.5-.19A.75.75,0,0,1,10,43.81l6.23-7a.76.76,0,0,1,1.06-.07.77.77,0,0,1,.06,1.06l-6.23,7A.78.78,0,0,1,10.55,45.06Z"/>
              <path d="M66.46,63A16.09,16.09,0,1,1,82.55,46.89,16.1,16.1,0,0,1,66.46,63Zm0-30.67A14.59,14.59,0,1,0,81.05,46.89,14.6,14.6,0,0,0,66.46,32.31Z"/>
              <path d="M66.46,47.64a.75.75,0,0,1-.43-.13L53.51,38.65a.74.74,0,0,1-.18-1,.75.75,0,0,1,1.05-.18L66.43,46,78,36.75a.75.75,0,0,1,.93,1.18l-12,9.55A.74.74,0,0,1,66.46,47.64Z"/>
              <path d="M66.46,63a.76.76,0,0,1-.75-.75V46.89a.74.74,0,0,1,1.09-.66l13.66,7a.75.75,0,0,1-.69,1.33L67.21,48.12V62.23A.76.76,0,0,1,66.46,63Z"/>
              <path d="M81.92,74.75H52.63a.75.75,0,1,1,0-1.5H81.92a.75.75,0,1,1,0,1.5Z"/>
              <path d="M66.2,70.21H52.63a.75.75,0,0,1,0-1.5H66.2a.75.75,0,0,1,0,1.5Z"/>
              <path d="M48.69,19.6H45.31a.75.75,0,0,1-.75-.75V14.7a2.44,2.44,0,1,1,4.88,0v4.15A.75.75,0,0,1,48.69,19.6Zm-2.63-1.5h1.88V14.7a.94.94,0,1,0-1.88,0Z"/>
            </svg>
            <h2>Analyse de plagiat</h2>
            <p>Lancez des analyses de plagiat sur les documents.</p>
            <?php if ($user['role_id'] != 4): ?>
            <a href="analyse_plagiat.php" class="btn btn-primary">Analyser</a>
            <?php endif; ?>
          </div>
          <div class="templatemo-content-widget white-bg col-1 text-center">
            <i class="fa fa-file-pdf-o fa-4x"></i>
            <h2>Rapports</h2>
            <p>Consultez les rapports de plagiat.</p>
            <?php if ($user['role_id'] != 4): ?>
            <a href="rapports.php" class="btn btn-primary">Voir</a>
            <?php endif; ?>
          </div>
        </div>
    </div>
  </div>
  <script src="../templatemo_455_visual_admin/js/jquery-1.11.2.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/jquery-migrate-1.2.1.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/bootstrap-filestyle.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/templatemo-script.js"></script>
</body>
</html>
