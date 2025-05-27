<?php
require_once 'config.php';
verifierAdmin();

$message = '';
$erreur = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'ajouter') {
        $id_emplacement = (int)$_POST['id_emplacement'];
        $id_acheteur = (int)$_POST['id_acheteur'];
        $id_occupant = (int)$_POST['id_occupant'];
        $duree = (int)$_POST['duree'];
        $prix = (float)$_POST['prix'];
        $prolongation = trim($_POST['prolongation']);
        $date_achat = $_POST['date_achat'];
        $date_expiration = $_POST['date_expiration'];
        
        if (empty($id_emplacement) || empty($id_acheteur) || empty($id_occupant) || empty($duree) || empty($prix) || empty($date_achat)) {
            $erreur = 'Tous les champs obligatoires doivent être remplis.';
        } else {
            try {
                // Vérifier si l'emplacement est déjà occupé
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM concession WHERE id_emplacement = ?");
                $stmt->execute([$id_emplacement]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $erreur = 'Cet emplacement est déjà occupé par une concession.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO concession (id_emplacement, id_acheteur, id_occupant, duree, prix, prolongation, date_achat, date_expiration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id_emplacement, $id_acheteur, $id_occupant, $duree, $prix, $prolongation, $date_achat, $date_expiration]);
                    $message = 'Concession créée avec succès.';
                }
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la création de la concession: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'modifier') {
        $id_emplacement_original = (int)$_POST['id_emplacement_original'];
        $id_acheteur_original = (int)$_POST['id_acheteur_original'];
        $id_occupant_original = (int)$_POST['id_occupant_original'];
        
        $id_emplacement = (int)$_POST['id_emplacement'];
        $id_acheteur = (int)$_POST['id_acheteur'];
        $id_occupant = (int)$_POST['id_occupant'];
        $duree = (int)$_POST['duree'];
        $prix = (float)$_POST['prix'];
        $prolongation = trim($_POST['prolongation']);
        $date_achat = $_POST['date_achat'];
        $date_expiration = $_POST['date_expiration'];
        
        try {
            $stmt = $pdo->prepare("UPDATE concession SET id_emplacement = ?, id_acheteur = ?, id_occupant = ?, duree = ?, prix = ?, prolongation = ?, date_achat = ?, date_expiration = ? WHERE id_emplacement = ? AND id_acheteur = ? AND id_occupant = ?");
            $stmt->execute([$id_emplacement, $id_acheteur, $id_occupant, $duree, $prix, $prolongation, $date_achat, $date_expiration, $id_emplacement_original, $id_acheteur_original, $id_occupant_original]);
            $message = 'Concession modifiée avec succès.';
        } catch (PDOException $e) {
            $erreur = 'Erreur lors de la modification de la concession: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'supprimer') {
        $id_emplacement = (int)$_POST['id_emplacement'];
        $id_acheteur = (int)$_POST['id_acheteur'];
        $id_occupant = (int)$_POST['id_occupant'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM concession WHERE id_emplacement = ? AND id_acheteur = ? AND id_occupant = ?");
            $stmt->execute([$id_emplacement, $id_acheteur, $id_occupant]);
            $message = 'Concession supprimée avec succès.';
        } catch (PDOException $e) {
            $erreur = 'Erreur lors de la suppression de la concession: ' . $e->getMessage();
        }
    }
}

// Récupération des concessions avec toutes les informations liées
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               e.Allee, e.type as type_emplacement,
               a.nom as nom_acheteur, a.prenom as prenom_acheteur,
               o.nom as nom_occupant, o.prenom as prenom_occupant, o.date_mort,
               h.nom_heritier, h.prenom_heritier,
               DATEDIFF(c.date_expiration, CURDATE()) as jours_restants,
               CASE 
                   WHEN c.date_expiration < CURDATE() THEN 'Expirée'
                   WHEN DATEDIFF(c.date_expiration, CURDATE()) <= 365 THEN 'Bientôt expirée'
                   ELSE 'Active'
               END as statut_expiration
        FROM concession c
        JOIN emplacement e ON c.id_emplacement = e.id_emplacement
        JOIN acheteur a ON c.id_acheteur = a.id_acheteur
        JOIN occupant o ON c.id_occupant = o.id_occupant
        JOIN heritier h ON a.id_heritier = h.id_heritier
        ORDER BY c.date_expiration ASC
    ");
    $concessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erreur = 'Erreur lors de la récupération des concessions.';
    $concessions = [];
}

// Récupération des données pour les formulaires
try {
    // Emplacements libres
    $stmt = $pdo->query("
        SELECT e.id_emplacement, e.Allee, e.type 
        FROM emplacement e 
        LEFT JOIN concession c ON e.id_emplacement = c.id_emplacement 
        WHERE c.id_emplacement IS NULL
        ORDER BY e.Allee
    ");
    $emplacements_libres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tous les emplacements (pour modification)
    $stmt = $pdo->query("SELECT id_emplacement, Allee, type FROM emplacement ORDER BY Allee");
    $tous_emplacements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Acheteurs
    $stmt = $pdo->query("
        SELECT a.id_acheteur, a.nom, a.prenom, h.nom_heritier, h.prenom_heritier 
        FROM acheteur a 
        JOIN heritier h ON a.id_heritier = h.id_heritier 
        ORDER BY a.nom, a.prenom
    ");
    $acheteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Occupants
    $stmt = $pdo->query("SELECT id_occupant, nom, prenom, date_mort FROM occupant ORDER BY nom, prenom");
    $occupants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erreur = 'Erreur lors de la récupération des données pour les formulaires.';
    $emplacements_libres = [];
    $tous_emplacements = [];
    $acheteurs = [];
    $occupants = [];
}

// Concession à modifier si demandé
$concession_modifier = null;
if (isset($_GET['modifier'])) {
    $params = explode('-', $_GET['modifier']);
    if (count($params) === 3) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       e.Allee, e.type as type_emplacement,
                       a.nom as nom_acheteur, a.prenom as prenom_acheteur,
                       o.nom as nom_occupant, o.prenom as prenom_occupant
                FROM concession c
                JOIN emplacement e ON c.id_emplacement = e.id_emplacement
                JOIN acheteur a ON c.id_acheteur = a.id_acheteur
                JOIN occupant o ON c.id_occupant = o.id_occupant
                WHERE c.id_emplacement = ? AND c.id_acheteur = ? AND c.id_occupant = ?
            ");
            $stmt->execute($params);
            $concession_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $erreur = 'Concession non trouvée.';
        }
    }
}

// Statistiques
$stats = [
    'total' => count($concessions),
    'actives' => count(array_filter($concessions, function($c) { return $c['statut_expiration'] === 'Active'; })),
    'bientot_expirees' => count(array_filter($concessions, function($c) { return $c['statut_expiration'] === 'Bientôt expirée'; })),
    'expirees' => count(array_filter($concessions, function($c) { return $c['statut_expiration'] === 'Expirée'; })),
    'chiffre_affaires' => array_sum(array_column($concessions, 'prix'))
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Concessions - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            margin-right: 0.5rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-bientot-expiree {
            background: #fef5e7;
            color: #c05621;
        }
        
        .status-expiree {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        
        .stat-card p {
            color: #666;
        }
        
        .actions-cell {
            white-space: nowrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: none;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            text-align: center;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .modal h3 {
            color: #dc3545;
            margin-bottom: 1rem;
        }
        
        .modal p {
            margin-bottom: 2rem;
            color: #666;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
        }
        
        .export-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gestion des Concessions</h1>
        <a href="dashboard.php" class="back-btn">← Retour au Dashboard</a>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($erreur): ?>
            <div class="message error"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>
        
        <!-- Statistiques rapides -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Concessions</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['actives']; ?></h3>
                <p>Concessions Actives</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['bientot_expirees']; ?></h3>
                <p>Bientôt Expirées</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['expirees']; ?></h3>
                <p>Expirées</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['chiffre_affaires'], 2); ?>€</h3>
                <p>Chiffre d'Affaires Total</p>
            </div>
        </div>
        
        <!-- Formulaire d'ajout/modification -->
        <div class="section">
            <h2><?php echo $concession_modifier ? 'Modifier la Concession' : 'Ajouter une Nouvelle Concession'; ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $concession_modifier ? 'modifier' : 'ajouter'; ?>">
                <?php if ($concession_modifier): ?>
                    <input type="hidden" name="id_emplacement_original" value="<?php echo $concession_modifier['id_emplacement']; ?>">
                    <input type="hidden" name="id_acheteur_original" value="<?php echo $concession_modifier['id_acheteur']; ?>">
                    <input type="hidden" name="id_occupant_original" value="<?php echo $concession_modifier['id_occupant']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_emplacement">Emplacement :</label>
                        <select id="id_emplacement" name="id_emplacement" required>
                            <option value="">Sélectionner un emplacement...</option>
                            <?php 
                            $emplacements_options = $concession_modifier ? $tous_emplacements : $emplacements_libres;
                            foreach ($emplacements_options as $emp): 
                            ?>
                                <option value="<?php echo $emp['id_emplacement']; ?>"
                                        <?php echo ($concession_modifier && $concession_modifier['id_emplacement'] == $emp['id_emplacement']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['Allee'] . ' - ' . $emp['type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_acheteur">Acheteur :</label>
                        <select id="id_acheteur" name="id_acheteur" required>
                            <option value="">Sélectionner un acheteur...</option>
                            <?php foreach ($acheteurs as $acheteur): ?>
                                <option value="<?php echo $acheteur['id_acheteur']; ?>"
                                        <?php echo ($concession_modifier && $concession_modifier['id_acheteur'] == $acheteur['id_acheteur']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($acheteur['prenom'] . ' ' . $acheteur['nom'] . ' (Héritier: ' . $acheteur['prenom_heritier'] . ' ' . $acheteur['nom_heritier'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_occupant">Occupant :</label>
                        <select id="id_occupant" name="id_occupant" required>
                            <option value="">Sélectionner un occupant...</option>
                            <?php foreach ($occupants as $occupant): ?>
                                <option value="<?php echo $occupant['id_occupant']; ?>"
                                        <?php echo ($concession_modifier && $concession_modifier['id_occupant'] == $occupant['id_occupant']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($occupant['prenom'] . ' ' . $occupant['nom'] . ' (†' . date('d/m/Y', strtotime($occupant['date_mort'])) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="duree">Durée (années) :</label>
                        <input type="number" id="duree" name="duree" min="1" max="100"
                               value="<?php echo $concession_modifier ? $concession_modifier['duree'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prix">Prix (€) :</label>
                        <input type="number" id="prix" name="prix" min="0" step="0.01"
                               value="<?php echo $concession_modifier ? $concession_modifier['prix'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prolongation">Prolongation possible :</label>
                        <select id="prolongation" name="prolongation" required>
                            <option value="">Sélectionner...</option>
                            <option value="Oui" <?php echo ($concession_modifier && $concession_modifier['prolongation'] === 'Oui') ? 'selected' : ''; ?>>Oui</option>
                            <option value="Non" <?php echo ($concession_modifier && $concession_modifier['prolongation'] === 'Non') ? 'selected' : ''; ?>>Non</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_achat">Date d'achat :</label>
                        <input type="date" id="date_achat" name="date_achat"
                               value="<?php echo $concession_modifier ? $concession_modifier['date_achat'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_expiration">Date d'expiration :</label>
                        <input type="date" id="date_expiration" name="date_expiration"
                               value="<?php echo $concession_modifier ? $concession_modifier['date_expiration'] : ''; ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo $concession_modifier ? 'Modifier la Concession' : 'Créer la Concession'; ?>
                </button>
                
                <?php if ($concession_modifier): ?>
                    <a href="gestion_concessions.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Section de filtres -->
        <div class="section">
            <h2>Filtres et Export</h2>
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="filter-statut">Filtrer par statut :</label>
                        <select id="filter-statut" onchange="filtrerConcessions()">
                            <option value="">Tous les statuts</option>
                            <option value="Active">Actives</option>
                            <option value="Bientôt expirée">Bientôt expirées</option>
                            <option value="Expirée">Expirées</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter-allee">Filtrer par allée :</label>
                        <select id="filter-allee" onchange="filtrerConcessions()">
                            <option value="">Toutes les allées</option>
                            <?php
                            $allees = array_unique(array_column($concessions, 'Allee'));
                            sort($allees);
                            foreach ($allees as $allee):
                            ?>
                                <option value="<?php echo htmlspecialchars($allee); ?>"><?php echo htmlspecialchars($allee); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button onclick="exporterCSV()" class="btn export-btn">Exporter CSV</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste des concessions -->
        <div class="section">
            <h2>Liste des Concessions</h2>
            
            <?php if (empty($concessions)): ?>
                <p>Aucune concession trouvée.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table" id="tableau-concessions">
                        <thead>
                            <tr>
                                <th>Emplacement</th>
                                <th>Acheteur</th>
                                <th>Héritier</th>
                                <th>Occupant</th>
                                <th>Durée</th>
                                <th>Prix</th>
                                <th>Prolongation</th>
                                <th>Date Achat</th>
                                <th>Date Expiration</th>
                                <th>Jours Restants</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concessions as $concession): ?>
                                <tr data-statut="<?php echo htmlspecialchars($concession['statut_expiration']); ?>" 
                                    data-allee="<?php echo htmlspecialchars($concession['Allee']); ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($concession['Allee']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($concession['type_emplacement']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($concession['prenom_acheteur'] . ' ' . $concession['nom_acheteur']); ?></td>
                                    <td><?php echo htmlspecialchars($concession['prenom_heritier'] . ' ' . $concession['nom_heritier']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($concession['prenom_occupant'] . ' ' . $concession['nom_occupant']); ?><br>
                                        <small>†<?php echo date('d/m/Y', strtotime($concession['date_mort'])); ?></small>
                                    </td>
                                    <td><?php echo $concession['duree']; ?> ans</td>
                                    <td><?php echo number_format($concession['prix'], 2); ?>€</td>
                                    <td>
                                        <span class="status-badge <?php echo $concession['prolongation'] === 'Oui' ? 'status-active' : 'status-expiree'; ?>">
                                            <?php echo htmlspecialchars($concession['prolongation']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($concession['date_achat'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($concession['date_expiration'])); ?></td>
                                    <td>
                                        <?php 
                                        $jours = $concession['jours_restants'];
                                        if ($jours < 0) {
                                            echo '<span style="color: #dc3545;">Expirée depuis ' . abs($jours) . ' jours</span>';
                                        } else {
                                            echo $jours . ' jours';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($concession['statut_expiration'])); ?>">
                                            <?php echo htmlspecialchars($concession['statut_expiration']); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="?modifier=<?php echo $concession['id_emplacement'] . '-' . $concession['id_acheteur'] . '-' . $concession['id_occupant']; ?>" 
                                           class="btn btn-warning">Modifier</a>
                                        <button onclick="confirmerSuppression(<?php echo $concession['id_emplacement']; ?>, <?php echo $concession['id_acheteur']; ?>, <?php echo $concession['id_occupant']; ?>, '<?php echo htmlspecialchars($concession['Allee']); ?>')" 
                                                class="btn btn-danger">Supprimer</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div id="modal-suppression" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fermerModal()">&times;</span>
            <h3>Confirmer la suppression</h3>
            <p id="message-suppression">Êtes-vous sûr de vouloir supprimer cette concession ?</p>
            <form id="form-suppression" method="POST" action="">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id_emplacement" id="supp_id_emplacement">
                <input type="hidden" name="id_acheteur" id="supp_id_acheteur">
                <input type="hidden" name="id_occupant" id="supp_id_occupant">
                <button type="submit" class="btn btn-danger">Oui, supprimer</button>
                <button type="button" onclick="fermerModal()" class="btn btn-secondary">Annuler</button>
            </form>
        </div>
    </div>

    <script>
        // Fonction pour filtrer les concessions
        function filtrerConcessions() {
            const filterStatut = document.getElementById('filter-statut').value;
            const filterAllee = document.getElementById('filter-allee').value;
            const rows = document.querySelectorAll('#tableau-concessions tbody tr');
            
            rows.forEach(row => {
                const statut = row.getAttribute('data-statut');
                const allee = row.getAttribute('data-allee');
                
                let afficher = true;
                
                if (filterStatut && statut !== filterStatut) {
                    afficher = false;
                }
                
                if (filterAllee && allee !== filterAllee) {
                    afficher = false;
                }
                
                row.style.display = afficher ? '' : 'none';
            });
        }
        
        // Fonction pour exporter en CSV
        function exporterCSV() {
            const table = document.getElementById('tableau-concessions');
            const rows = table.querySelectorAll('tr:not([style*="display: none"])');
            let csv = [];
            
            // En-têtes
            const headers = [];
            rows[0].querySelectorAll('th').forEach(th => {
                if (th.textContent !== 'Actions') {
                    headers.push('"' + th.textContent.replace(/"/g, '""') + '"');
                }
            });
            csv.push(headers.join(','));
            
            // Données
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const data = [];
                const cells = row.querySelectorAll('td');
                
                for (let j = 0; j < cells.length - 1; j++) { // -1 pour exclure la colonne Actions
                    let cellText = cells[j].textContent.trim().replace(/\s+/g, ' ');
                    data.push('"' + cellText.replace(/"/g, '""') + '"');
                }
                csv.push(data.join(','));
            }
            
            // Téléchargement
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'concessions_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Fonctions pour le modal de suppression
        function confirmerSuppression(idEmplacement, idAcheteur, idOccupant, allee) {
            document.getElementById('supp_id_emplacement').value = idEmplacement;
            document.getElementById('supp_id_acheteur').value = idAcheteur;
            document.getElementById('supp_id_occupant').value = idOccupant;
            document.getElementById('message-suppression').innerHTML = 
                'Êtes-vous sûr de vouloir supprimer la concession de l\'emplacement <strong>' + allee + '</strong> ?<br>' +
                '<small style="color: #dc3545;">Cette action est irréversible.</small>';
            document.getElementById('modal-suppression').style.display = 'block';
        }
        
        function fermerModal() {
            document.getElementById('modal-suppression').style.display = 'none';
        }
        
        // Fermer le modal en cliquant à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('modal-suppression');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Calcul automatique de la date d'expiration
        document.getElementById('date_achat').addEventListener('change', calculerDateExpiration);
        document.getElementById('duree').addEventListener('change', calculerDateExpiration);
        
        function calculerDateExpiration() {
            const dateAchat = document.getElementById('date_achat').value;
            const duree = document.getElementById('duree').value;
            
            if (dateAchat && duree) {
                const date = new Date(dateAchat);
                date.setFullYear(date.getFullYear() + parseInt(duree));
                document.getElementById('date_expiration').value = date.toISOString().split('T')[0];
            }
        }
        
        // Validation du formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            const dateAchat = new Date(document.getElementById('date_achat').value);
            const dateExpiration = new Date(document.getElementById('date_expiration').value);
            
            if (dateExpiration <= dateAchat) {
                alert('La date d\'expiration doit être postérieure à la date d\'achat.');
                e.preventDefault();
            }
        });
        
        // Mise à jour automatique des statistiques toutes les 30 secondes
        setInterval(function() {
            // Cette fonction pourrait faire un appel AJAX pour mettre à jour les statistiques
            // sans recharger la page entière
        }, 30000);
    </script>
</body>
</html>