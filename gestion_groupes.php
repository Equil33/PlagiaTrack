<?php
require_once 'config.php';
require_once 'auth.php';

if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();
$role_id = $user['role_id'];

// Vérifier les permissions (super_admin, admin, prof, etudiant peuvent voir leurs groupes)
if (!in_array($role_id, [1, 2, 3, 4])) {
    echo "Accès refusé.";
    exit;
}

// Récupérer tous les groupes créés par l'utilisateur (ou tous si admin/super_admin)
if (in_array($role_id, [1, 2])) {
    $stmt = $pdo->prepare("SELECT * FROM groupes_documents ORDER BY nom ASC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM groupes_documents WHERE createur_id = ? ORDER BY nom ASC");
    $stmt->execute([$user['id']]);
}
$groupes = $stmt->fetchAll();

// Gestion recherche
$search = $_GET['search'] ?? '';
$params = [];
if ($search !== '') {
    if (in_array($role_id, [1, 2])) {
        $stmt = $pdo->prepare("SELECT * FROM groupes_documents WHERE nom LIKE ? ORDER BY nom ASC");
        $stmt->execute(["%$search%"]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM groupes_documents WHERE createur_id = ? AND nom LIKE ? ORDER BY nom ASC");
        $stmt->execute([$user['id'], "%$search%"]);
    }
    $groupes = $stmt->fetchAll();
}

$selected_groupe_id = $_GET['groupe_id'] ?? null;
$documents = [];
if ($selected_groupe_id) {
    $stmt = $pdo->prepare("SELECT d.*, u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur 
        FROM documents d 
        JOIN utilisateurs u ON d.utilisateur_id = u.id 
        WHERE d.groupe_id = ? ORDER BY d.date_soumission DESC");
    $stmt->execute([$selected_groupe_id]);
    $documents = $stmt->fetchAll();
}

// Gestion suppression groupe
if (isset($_POST['supprimer_groupe'])) {
    $groupe_id_suppr = intval($_POST['groupe_id_suppr'] ?? 0);
    if ($groupe_id_suppr > 0) {
        // Vérifier droits
        $stmt = $pdo->prepare("SELECT createur_id FROM groupes_documents WHERE id = ?");
        $stmt->execute([$groupe_id_suppr]);
        $groupe = $stmt->fetch();
        if ($groupe && ($role_id == 1 || $role_id == 2 || $groupe['createur_id'] == $user['id'])) {
            // Supprimer les documents du groupe (optionnel: ou dissocier)
            $stmt = $pdo->prepare("UPDATE documents SET groupe_id = NULL WHERE groupe_id = ?");
            $stmt->execute([$groupe_id_suppr]);
            // Supprimer le groupe
            $stmt = $pdo->prepare("DELETE FROM groupes_documents WHERE id = ?");
            $stmt->execute([$groupe_id_suppr]);
            header("Location: gestion_groupes.php?msg=Groupe supprimé avec succès");
            exit;
        } else {
            $errors[] = "Accès refusé pour supprimer ce groupe.";
        }
    }
}

// Gestion suppression document du groupe
if (isset($_POST['supprimer_document'])) {
    $doc_id_suppr = intval($_POST['doc_id_suppr'] ?? 0);
    if ($doc_id_suppr > 0) {
        // Vérifier droits
        $stmt = $pdo->prepare("SELECT d.utilisateur_id, g.createur_id FROM documents d LEFT JOIN groupes_documents g ON d.groupe_id = g.id WHERE d.id = ?");
        $stmt->execute([$doc_id_suppr]);
        $info = $stmt->fetch();
        if ($info && ($role_id == 1 || $role_id == 2 || $info['createur_id'] == $user['id'])) {
            // Dissocier document du groupe
            $stmt = $pdo->prepare("UPDATE documents SET groupe_id = NULL WHERE id = ?");
            $stmt->execute([$doc_id_suppr]);
            header("Location: gestion_groupes.php?groupe_id=$selected_groupe_id&msg=Document dissocié avec succès");
            exit;
        } else {
            $errors[] = "Accès refusé pour dissocier ce document.";
        }
    }
}

// Gestion ajout documents au groupe existant
if (isset($_POST['ajouter_documents'])) {
    $groupe_id_ajout = intval($_POST['groupe_id_ajout'] ?? 0);
    if ($groupe_id_ajout > 0 && isset($_FILES['documents_ajout'])) {
        // Vérifier droits
        $stmt = $pdo->prepare("SELECT createur_id FROM groupes_documents WHERE id = ?");
        $stmt->execute([$groupe_id_ajout]);
        $groupe = $stmt->fetch();
        if ($groupe && ($role_id == 1 || $role_id == 2 || $groupe['createur_id'] == $user['id'])) {
            $allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
            $uploadDir = __DIR__ . '/../documents/' . $user['id'] . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach ($_FILES['documents_ajout']['tmp_name'] as $key => $tmpName) {
                $fileName = basename($_FILES['documents_ajout']['name'][$key]);
                $fileType = $_FILES['documents_ajout']['type'][$key];
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
                    $stmt = $pdo->prepare("INSERT INTO documents (utilisateur_id, nom_fichier, chemin, type_fichier, statut, est_enregistre, groupe_id) VALUES (?, ?, ?, ?, 'en_attente', 0, ?)");
                    $stmt->execute([$user['id'], $fileName, $targetFile, $typeFichier, $groupe_id_ajout]);
                } else {
                    $errors[] = "Erreur lors du téléchargement du fichier $fileName.";
                }
            }
            header("Location: gestion_groupes.php?groupe_id=$groupe_id_ajout&msg=Documents ajoutés avec succès");
            exit;
        } else {
            $errors[] = "Accès refusé pour ajouter des documents à ce groupe.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des groupes - PlagiaTrack</title>
    <link href="../templatemo_455_visual_admin/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../templatemo_455_visual_admin/css/font-awesome.min.css" rel="stylesheet" />
    <link href="../templatemo_455_visual_admin/css/templatemo-style.css" rel="stylesheet" />
    <style>
        #documentsTable {
            transition: opacity 0.5s ease-in-out;
        }
        #documentsTable.fade-out {
            opacity: 0;
        }
        #documentsTable.fade-in {
            opacity: 1;
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
                    <li><a href="../backend/dashboard.php"><i class="fa fa-home fa-fw"></i>Tableau de bord</a></li>
                    <?php if ($user['role_id'] != 3 && $user['role_id'] != 4): ?>
                    <li><a href="../backend/gestion_utilisateurs.php"><i class="fa fa-users fa-fw"></i>Gestion des utilisateurs</a></li>
                    <?php endif; ?>
                    <li><a href="../backend/gestion_documents.php"><i class="fa fa-file fa-fw"></i>Gestion des documents</a></li>
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
                    <li><a href="../backend/logout.php"><i class="fa fa-sign-out fa-fw"></i>Déconnexion</a></li>
                </ul>
            </nav>
        </div>
        <div class="templatemo-content col-1 light-gray-bg">
            <div class="templatemo-content-container">
                <h1>Gestion des groupes de documents</h1>

                <?php if (isset($errors) && !empty($errors)): ?>
                    <ul style="color:red;">
                        <?php foreach ($errors as $error): ?>
                            <li><?=htmlspecialchars($error)?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (isset($_GET['msg'])): ?>
                    <p style="color:green;"><?=htmlspecialchars($_GET['msg'])?></p>
                <?php endif; ?>

                <form method="get" action="" class="form-inline">
                    <div class="form-group">
                        <label>Rechercher un groupe :</label>
                        <input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Nom du groupe" class="form-control">
                        <button type="submit" class="btn btn-primary">Rechercher</button>
                    </div>
                </form>

                <h2>Résultats de la recherche</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="searchResultsTable">
                        <thead>
                            <tr>
                                <th>Nom du groupe</th>
                                <th>Créateur</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupes)): ?>
                                <tr><td colspan="3">Aucun groupe trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupes as $groupe): ?>
                                    <?php
                                    // Vérifier droits d'accès
                                    if (!in_array($role_id, [1, 2]) && $groupe['createur_id'] != $user['id']) {
                                        continue;
                                    }
                                    // Récupérer nom créateur
                                    $stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = ?");
                                    $stmt->execute([$groupe['createur_id']]);
                                    $createur = $stmt->fetch();
                                    ?>
                                    <tr>
                                        <td><?=htmlspecialchars($groupe['nom'])?></td>
                                        <td><?=htmlspecialchars($createur['nom'] . ' ' . $createur['prenom'])?></td>
                                        <td>
                                            <form method="get" action="" style="margin:0;">
                                                <input type="hidden" name="groupe_id" value="<?=htmlspecialchars($groupe['id'])?>">
                                                <button type="submit" class="btn btn-info">Afficher</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($selected_groupe_id): ?>
                    <h2>Documents du groupe sélectionné</h2>
                    <form method="post" action="">
                        <input type="hidden" name="groupe_id_suppr" value="<?=htmlspecialchars($selected_groupe_id)?>">
                        <?php if ($selected_groupe_id && (in_array($role_id, [1, 2]) || (isset($groupes) && array_filter($groupes, fn($g) => $g['id'] == $selected_groupe_id && $g['createur_id'] == $user['id'])))): ?>
                            <button type="submit" name="supprimer_groupe" class="btn btn-danger" onclick="return confirm('Supprimer ce groupe et dissocier tous ses documents ?')">Supprimer ce groupe</button>
                        <?php endif; ?>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="documentsTable" class="fade-in">
                            <thead>
                                <tr>
                                    <th>Nom du fichier</th>
                                    <th>Type</th>
                                    <th>Date de soumission</th>
                                    <th>Statut</th>
                                    <th>Utilisateur</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($documents) === 0): ?>
                                    <tr><td colspan="6">Aucun document dans ce groupe.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?=htmlspecialchars($doc['nom_fichier'])?></td>
                                            <td><?=htmlspecialchars($doc['type_fichier'])?></td>
                                            <td><?=htmlspecialchars($doc['date_soumission'])?></td>
                                            <td><?=htmlspecialchars($doc['statut'])?></td>
                                            <td><?=htmlspecialchars($doc['nom_utilisateur'] . ' ' . $doc['prenom_utilisateur'])?></td>
                                            <td>
                                                <?php if (in_array($role_id, [1, 2]) || (isset($groupes) && array_filter($groupes, fn($g) => $g['id'] == $selected_groupe_id && $g['createur_id'] == $user['id']))): ?>
                                                    <form method="post" action="" style="display:inline;">
                                                        <input type="hidden" name="doc_id_suppr" value="<?=htmlspecialchars($doc['id'])?>">
                                                        <button type="submit" name="supprimer_document" class="btn btn-sm btn-warning" onclick="return confirm('Dissocier ce document du groupe ?')">Dissocier</button>
                                                    </form>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($selected_groupe_id && (in_array($role_id, [1, 2]) || (isset($groupes) && array_filter($groupes, fn($g) => $g['id'] == $selected_groupe_id && $g['createur_id'] == $user['id'])))): ?>
                        <h3>Ajouter des documents à ce groupe</h3>
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="groupe_id_ajout" value="<?=htmlspecialchars($selected_groupe_id)?>">
                            <div class="form-group">
                                <input type="file" name="documents_ajout[]" multiple required class="form-control">
                            </div>
                            <button type="submit" name="ajouter_documents" class="btn btn-primary">Ajouter</button>
                        </form>
                    <?php endif; ?>
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
