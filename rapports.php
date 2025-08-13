<?php
require_once 'config.php';
require_once 'auth.php';

if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();


// Vérifier les permissions (professeur, admin, super admin)
if (!in_array($user['role_id'], [1, 2, 3])) {
    echo "Accès refusé.";
    exit;
}

$errors = [];
$success = '';

$reportsDir = __DIR__ . '/reports/';

// Suppression d'un rapport PDF
if (isset($_GET['action'], $_GET['file']) && $_GET['action'] === 'supprimer') {
    $file = basename($_GET['file']); // Sécuriser le nom de fichier
    $filePath = realpath($reportsDir . $file);

    if ($filePath && strpos($filePath, realpath($reportsDir)) === 0 && is_file($filePath)) {
        if (unlink($filePath)) {
            $success = "Le rapport PDF '$file' a été supprimé avec succès.";
        } else {
            $errors[] = "Impossible de supprimer le fichier '$file'.";
        }
    } else {
        $errors[] = "Fichier invalide ou non trouvé.";
    }
}

// Lecture des fichiers PDF dans le dossier reports
$pdfFiles = [];
if (is_dir($reportsDir)) {
    $files = scandir($reportsDir);
    foreach ($files as $f) {
        if (is_file($reportsDir . $f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf') {
            $pdfFiles[] = $f;
        }
    }
} else {
    $errors[] = "Le dossier des rapports PDF n'existe pas.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rapports PDF de plagiat - PlagiaTrack</title>
    <link href="../templatemo_455_visual_admin/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../templatemo_455_visual_admin/css/font-awesome.min.css" rel="stylesheet" />
    <link href="../templatemo_455_visual_admin/css/templatemo-style.css" rel="stylesheet" />
    <style>
        /* Additional styles for table */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1em;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .btn-delete {
            color: red;
            cursor: pointer;
        }
        .message-success {
            color: green;
            margin-bottom: 1em;
        }
        .message-error {
            color: red;
            margin-bottom: 1em;
        }
    </style>
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
          <li><a href="../backend/dashboard.php"><i class="fa fa-home fa-fw"></i>TABLEAU DE BORD</a></li>
          <?php if ($user['role_id'] != 3 && $user['role_id'] != 4): ?>
          <li><a href="../backend/gestion_utilisateurs.php"><i class="fa fa-users fa-fw"></i>GESTION DES UTILISATEURS</a></li>
            <?php endif; ?>
          <li><a href="../backend/gestion_documents.php"><i class="fa fa-file fa-fw"></i>GESTION DES DOCUMENTS</a></li>
          <li>
            <a href="../backend/analyse_plagiat.php">
              <img src="../data_analyse.svg" alt="Analyse Icon" style="width:16px; height:16px; vertical-align:middle; margin-right:5px;" />
              ANALYSE DE PLAGIAT
            </a>
          </li>
          <li><a href="../backend/rapports.php" class="active"><i class="fa fa-file-pdf-o fa-fw"></i>RAPPORTS DE PLAGIAT</a></li>
          <!-- Suppression du lien vers la page Administration -->
          <!-- <li><a href="../backend/administration.php"><i class="fa fa-cog fa-fw"></i>ADMINISTRATION</a></li> -->
          <li><a href="../backend/logout.php"><i class="fa fa-sign-out fa-fw"></i>DÉCONNEXION</a></li>
        </ul>
      </nav>
    </div>
    <div class="templatemo-content col-1 light-gray-bg">
      <div class="templatemo-content-container">
        <h1>Rapports PDF de plagiat</h1>

        <?php if ($success): ?>
            <div class="message-success"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="message-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?=htmlspecialchars($error)?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($pdfFiles)): ?>
            <p>Aucun rapport PDF disponible.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom du fichier</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pdfFiles as $pdfFile): ?>
                        <tr>
                            <td><?=htmlspecialchars($pdfFile)?></td>
                            <td>
                                <a href="reports/<?=rawurlencode($pdfFile)?>" target="_blank" rel="noopener noreferrer">Voir</a> |
                                <a href="reports/<?=rawurlencode($pdfFile)?>" download>Télécharger</a> |
                                <a href="?action=supprimer&amp;file=<?=rawurlencode($pdfFile)?>" class="btn-delete" onclick="return confirm('Confirmez-vous la suppression du rapport PDF ?')">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

      </div>
    </div>
  </div>
  <script src="../templatemo_455_visual_admin/js/jquery-1.11.2.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/jquery-migrate-1.2.1.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/bootstrap-filestyle.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/templatemo-script.js"></script>
</body>
</html>
</create_file>
