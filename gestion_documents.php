<?php
require_once 'config.php';
require_once 'auth.php';

if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();

$role_id = $user['role_id'];
$peutSoumettre = in_array($role_id, [1, 2, 3, 4]); // super_admin, admin, prof, etudiant

if (!$peutSoumettre) {
    echo "Accès refusé.";
    exit;
}

$errors = [];
$success = '';

// Gestion création groupe
if (isset($_POST['creer_groupe'])) {
    $nomGroupe = trim($_POST['nom_groupe'] ?? '');
    if ($nomGroupe === '') {
        $errors[] = "Le nom du groupe est obligatoire.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO groupes_documents (nom, createur_id, date_creation) VALUES (?, ?, NOW())");
        $stmt->execute([$nomGroupe, $user['id']]);
        $success = "Groupe créé avec succès.";
    }
}

// Gestion soumission documents
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
    $allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    $uploadDir = __DIR__ . '/../documents/' . $user['id'] . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $professeurDestinataire = $_POST['professeur_destinataire'] ?? 'tous';

    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['documents']['name'][$key]);
        $fileType = $_FILES['documents']['type'][$key];
        $fileSize = $_FILES['documents']['size'][$key];

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Le fichier $fileName n'est pas dans un format supporté.";
            continue;
        }

        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($tmpName, $targetFile)) {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $typeFichier = strtolower($ext);
            if (!in_array($typeFichier, ['pdf', 'docx', 'txt'])) {
                $typeFichier = 'txt';
            }

            $stmt = $pdo->prepare("INSERT INTO documents (utilisateur_id, nom_fichier, chemin, type_fichier, statut, est_enregistre, destinataire_professeur) VALUES (?, ?, ?, ?, 'en_attente', 0, ?)");
            $stmt->execute([$user['id'], $fileName, $targetFile, $typeFichier, $professeurDestinataire]);
            $success = "Documents soumis avec succès.";
        } else {
            $errors[] = "Erreur lors du téléchargement du fichier $fileName.";
        }
    }
}

// Gestion recherche et affichage documents
$searchNom = $_GET['search_nom'] ?? '';
$searchType = $_GET['search_type'] ?? '';
$searchDateDebut = $_GET['search_date_debut'] ?? '';
$searchDateFin = $_GET['search_date_fin'] ?? '';

// Construction de la requête selon rôle
if (in_array($role_id, [1, 2])) {
    // admin et super_admin voient tous les documents
    $query = "SELECT d.*, u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur FROM documents d JOIN utilisateurs u ON d.utilisateur_id = u.id WHERE 1=1";
    $params = [];
} elseif ($role_id == 3) {
    // prof voit ses documents et ceux destinés à lui ou à tous
    $query = "SELECT d.*, u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur FROM documents d JOIN utilisateurs u ON d.utilisateur_id = u.id WHERE (d.destinataire_professeur = ? OR d.destinataire_professeur = 'tous') AND (u.id = ? OR d.destinataire_professeur = 'tous')";
    $params = [$user['id'], $user['id']];
} else {
    // eleve voit ses documents uniquement
    $query = "SELECT d.*, u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur FROM documents d JOIN utilisateurs u ON d.utilisateur_id = u.id WHERE d.utilisateur_id = ?";
    $params = [$user['id']];
}

// Ajout filtres recherche
if ($searchNom !== '') {
    $query .= " AND d.nom_fichier LIKE ?";
    $params[] = "%$searchNom%";
}
if ($searchType !== '') {
    $query .= " AND d.type_fichier = ?";
    $params[] = $searchType;
}
if ($searchDateDebut !== '') {
    $query .= " AND d.date_soumission >= ?";
    $params[] = $searchDateDebut;
}
if ($searchDateFin !== '') {
    $query .= " AND d.date_soumission <= ?";
    $params[] = $searchDateFin;
}

$query .= " ORDER BY d.date_soumission DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Récupérer la liste des professeurs pour sélection destinataire
$stmt = $pdo->query("SELECT id, nom, prenom FROM utilisateurs WHERE role_id = 3 ORDER BY nom ASC");
$professeurs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestion des documents - PlagiaTrack</title>
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
          <li><a href="../backend/dashboard.php"><i class="fa fa-home fa-fw"></i>Tableau de bord</a></li>
          <?php if ($user['role_id'] != 3 && $user['role_id'] != 4): ?>
          <li><a href="../backend/gestion_utilisateurs.php"><i class="fa fa-users fa-fw"></i>Gestion des utilisateurs</a></li>
          <?php endif; ?>
          <li><a href="../backend/gestion_documents.php" class="active"><i class="fa fa-file fa-fw"></i>Gestion des documents</a></li>
          <?php if ($user['role_id'] != 4): ?>
          <li>
            <a href="../backend/analyse_plagiat.php">
              <img src="../data_analyse.svg" alt="Analyse Icon" style="width:16px; height:16px; vertical-align:middle; margin-right:5px;" />
              Analyse de plagiat
            </a>
          </li>
          <?php endif; ?>
          <?php if ($user['role_id'] != 4): ?>
          <li><a href="../backend/rapports.php"><i class="fa fa-file-pdf-o fa-fw"></i>Rapports de plagiat</a></li>
          <?php endif; ?>
          <!-- Suppression du lien vers la page Administration -->
          <!-- <li><a href="../backend/administration.php"><i class="fa fa-cog fa-fw"></i>Administration</a></li> -->
          <li><a href="../backend/logout.php"><i class="fa fa-sign-out fa-fw"></i>Déconnexion</a></li>
        </ul>
      </nav>
    </div>
    <div class="templatemo-content col-1 light-gray-bg">
      <!-- Suppression du menu en haut dans la gestion des documents -->
      <!--
      <div class="templatemo-top-nav-container">
        <div class="row">
          <nav class="templatemo-top-nav col-lg-12 col-md-12">
            <ul class="text-uppercase">
              <li><a href="#" class="active">Gestion des documents</a></li>
              <li><a href="../backend/dashboard.php">Tableau de bord</a></li>
            </ul>
          </nav>
        </div>
      </div>
      -->
      <div class="templatemo-content-container">
        <div class="row">
            <div class="col-md-12">

            </div>
        </div>
        <h1>Gestion des documents</h1>
        <?php if ($errors): ?>
          <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
              <li><?=htmlspecialchars($error)?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if ($success): ?>
          <p style="color:green;"><?=htmlspecialchars($success)?></p>
        <?php endif; ?>

        <h2>Créer un groupe de documents</h2>
        <form method="post" action="">
          <label>Nom du groupe : <input type="text" name="nom_groupe" required></label>
          <button type="submit" name="creer_groupe">Créer</button>
        </form>

        <h2>Soumettre des documents</h2>
        <form method="post" action="" enctype="multipart/form-data">
          <label>Choisir le professeur destinataire :</label>
          <select name="professeur_destinataire" required>
            <option value="tous">Tous</option>
            <?php foreach ($professeurs as $prof): ?>
              <option value="<?=htmlspecialchars($prof['id'])?>"><?=htmlspecialchars($prof['nom'] . ' ' . $prof['prenom'])?></option>
            <?php endforeach; ?>
          </select><br /><br />

          <label>Choisir un ou plusieurs documents (formats .pdf, .docx, .txt) :</label><br />
          <input type="file" name="documents[]" multiple required /><br />
          <button type="submit">Soumettre</button>
        </form>

        <h2>Rechercher des documents</h2>
        <form method="get" action="">
          <label>Nom du fichier : <input type="text" name="search_nom" value="<?=htmlspecialchars($searchNom)?>" /></label>
          <label>Type de fichier :
            <select name="search_type">
              <option value="">Tous</option>
              <option value="pdf" <?= $searchType == 'pdf' ? 'selected' : '' ?>>PDF</option>
              <option value="docx" <?= $searchType == 'docx' ? 'selected' : '' ?>>DOCX</option>
              <option value="txt" <?= $searchType == 'txt' ? 'selected' : '' ?>>TXT</option>
            </select>
          </label>
          <label>Date début : <input type="date" name="search_date_debut" value="<?=htmlspecialchars($searchDateDebut)?>" /></label>
          <label>Date fin : <input type="date" name="search_date_fin" value="<?=htmlspecialchars($searchDateFin)?>" /></label>
          <button type="submit">Rechercher</button>
        </form>

        <h2>Documents soumis</h2>
                <div class="templatemo-content-container">
            <div class="row">
                <div class="col-md-12">
                    <a href="../backend/gestion_groupes.php" class="btn btn-primary">
                        <i class="fa fa-folder-open"></i> Gestion des groupes créés
                    </a>
                </div>
            </div>

        <form method="post" action="affecter_groupe.php">
          <button type="submit" name="affecter_groupe">Affecter au groupe sélectionné</button>
          <select name="groupe_id" required>
            <option value="">-- Sélectionner un groupe --</option>
            <?php
            // Récupérer les groupes pour l'utilisateur
            $stmt = $pdo->prepare("SELECT id, nom FROM groupes_documents WHERE createur_id = ? ORDER BY nom ASC");
            $stmt->execute([$user['id']]);
            $groupes = $stmt->fetchAll();
            foreach ($groupes as $groupe):
            ?>
              <option value="<?=htmlspecialchars($groupe['id'])?>"><?=htmlspecialchars($groupe['nom'])?></option>
            <?php endforeach; ?>
          </select>
          <br /><br />
          <table border="1" cellpadding="5" cellspacing="0" id="documentsTable" class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>
                  <input type="checkbox" id="selectAll" />
                  <label for="selectAll"><span></span></label>
                </th>
                <th>Nom du fichier</th>
                <th>Type</th>
                <th>Date de soumission</th>
                <th>Statut</th>
                <th>Utilisateur</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($documents as $doc): ?>
                <?php $checkboxId = 'doc_checkbox_' . htmlspecialchars($doc['id']); ?>
                <tr>
                  <td>
                    <input type="checkbox" id="<?= $checkboxId ?>" name="document_ids[]" value="<?=htmlspecialchars($doc['id'])?>" />
                    <label for="<?= $checkboxId ?>"><span></span></label>
                  </td>
                  <td><?=htmlspecialchars($doc['nom_fichier'])?></td>
                  <td><?=htmlspecialchars($doc['type_fichier'])?></td>
                  <td><?=htmlspecialchars($doc['date_soumission'])?></td>
                  <td><?=htmlspecialchars($doc['statut'])?></td>
                  <td><?=htmlspecialchars($doc['nom_utilisateur'] . ' ' . $doc['prenom_utilisateur'])?></td>
                  <td>
                    <?php
                    $peutSupprimer = false;
                    $peutTelecharger = false;
                    if (in_array($role_id, [1, 2])) {
                      $peutSupprimer = true;
                      $peutTelecharger = true;
                    } elseif (($role_id == 3 || $role_id == 4) && $doc['utilisateur_id'] == $user['id']) {
                      $peutSupprimer = true;
                      $peutTelecharger = true;
                    }
                    
                    // Vérifier si le fichier existe
                    $fileExists = file_exists($doc['chemin']);
                    
                    if ($peutTelecharger && $fileExists):
                    ?>
                      <a href="download_document.php?id=<?=htmlspecialchars($doc['id'])?>" class="btn btn-primary btn-sm">Télécharger</a><br />
                    <?php elseif ($peutTelecharger && !$fileExists): ?>
                      <span style="color: red;">Fichier introuvable</span><br />
                    <?php endif; ?>
                    
                    <?php if ($peutSupprimer): ?>
                      <a href="supprimer_document.php?id=<?=htmlspecialchars($doc['id'])?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce document ?')">Supprimer</a>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </form>
        <script>
          document.getElementById('selectAll').addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('#documentsTable tbody input[type="checkbox"]');
            for (var checkbox of checkboxes) {
              checkbox.checked = this.checked;
            }
          });
        </script>

        <div class="templatemo-content-container">
            <div class="row">
                <div class="col-md-12">

                </div>
            </div>
            <hr>
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