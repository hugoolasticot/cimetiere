<?php
require_once 'config.php';
verifierAdmin();

$message = '';
$erreur = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'ajouter') {
        $nom_utilisateur = trim($_POST['nom_utilisateur']);
        $mot_de_passe = trim($_POST['mot_de_passe']);
        $type_compte = trim($_POST['type_compte']);
        $id_heritier = !empty($_POST['id_heritier']) ? (int)$_POST['id_heritier'] : null;
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        if (empty($nom_utilisateur) || empty($mot_de_passe) || empty($type_compte)) {
            $erreur = 'Tous les champs obligatoires doivent être remplis.';
        } else {
            try {
                // Vérifier si le nom d'utilisateur existe déjà
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ?");
                $stmt->execute([$nom_utilisateur]);
                if ($stmt->fetchColumn() > 0) {
                    $erreur = 'Ce nom d\'utilisateur existe déjà.';
                } else {
                    $mot_de_passe_hash = '{' . $mot_de_passe . '}'; // Simple hash comme dans votre DB
                    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, type_compte, id_heritier, actif) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nom_utilisateur, $mot_de_passe_hash, $type_compte, $id_heritier, $actif]);
                    $message = 'Utilisateur créé avec succès.';
                }
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la création de l\'utilisateur.';
            }
        }
    }
    
    elseif ($action === 'modifier') {
        $id_utilisateur = (int)$_POST['id_utilisateur'];
        $nom_utilisateur = trim($_POST['nom_utilisateur']);
        $type_compte = trim($_POST['type_compte']);
        $id_heritier = !empty($_POST['id_heritier']) ? (int)$_POST['id_heritier'] : null;
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, type_compte = ?, id_heritier = ?, actif = ? WHERE id_utilisateur = ?");
            $stmt->execute([$nom_utilisateur, $type_compte, $id_heritier, $actif, $id_utilisateur]);
            $message = 'Utilisateur modifié avec succès.';
        } catch (PDOException $e) {
            $erreur = 'Erreur lors de la modification de l\'utilisateur.';
        }
    }
    
    elseif ($action === 'supprimer') {
        $id_utilisateur = (int)$_POST['id_utilisateur'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
            $stmt->execute([$id_utilisateur]);
            $message = 'Utilisateur supprimé avec succès.';
        } catch (PDOException $e) {
            $erreur = 'Erreur lors de la suppression de l\'utilisateur.';
        }
    }
}

// Récupération de tous les utilisateurs avec leurs héritiers
try {
    $stmt = $pdo->query("
        SELECT u.*, h.nom_heritier, h.prenom_heritier
        FROM utilisateurs u
        LEFT JOIN heritier h ON u.id_heritier = h.id_heritier
        ORDER BY u.nom_utilisateur
    ");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erreur = 'Erreur lors de la récupération des utilisateurs.';
    $utilisateurs = [];
}

// Récupération de tous les héritiers pour le formulaire
try {
    $stmt = $pdo->query("SELECT * FROM heritier ORDER BY nom_heritier, prenom_heritier");
    $heritiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $heritiers = [];
}

// Utilisateur à modifier si demandé
$utilisateur_modifier = null;
if (isset($_GET['modifier']) && is_numeric($_GET['modifier'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
        $stmt->execute([$_GET['modifier']]);
        $utilisateur_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erreur = 'Utilisateur non trouvé.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Admin</title>
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
            max-width: 1400px;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
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
            font-size: 0.9rem;
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
        
        .status-actif {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-inactif {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .type-admin {
            background: #bee3f8;
            color: #2a4365;
        }
        
        .type-famille {
            background: #e6fffa;
            color: #234e52;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Gestion des Utilisateurs</h1>
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
                <h3><?php echo count($utilisateurs); ?></h3>
                <p>Total Utilisateurs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($utilisateurs, function($u) { return $u['type_compte'] === 'admin'; })); ?></h3>
                <p>Administrateurs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($utilisateurs, function($u) { return $u['type_compte'] === 'famille'; })); ?></h3>
                <p>Comptes Famille</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($utilisateurs, function($u) { return $u['actif'] == 1; })); ?></h3>
                <p>Comptes Actifs</p>
            </div>
        </div>
        
        <!-- Formulaire d'ajout/modification -->
        <div class="section">
            <h2><?php echo $utilisateur_modifier ? 'Modifier l\'Utilisateur' : 'Ajouter un Nouvel Utilisateur'; ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $utilisateur_modifier ? 'modifier' : 'ajouter'; ?>">
                <?php if ($utilisateur_modifier): ?>
                    <input type="hidden" name="id_utilisateur" value="<?php echo $utilisateur_modifier['id_utilisateur']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom_utilisateur">Nom d'utilisateur :</label>
                        <input type="text" id="nom_utilisateur" name="nom_utilisateur" 
                               value="<?php echo $utilisateur_modifier ? htmlspecialchars($utilisateur_modifier['nom_utilisateur']) : ''; ?>" required>
                    </div>
                    
                    <?php if (!$utilisateur_modifier): ?>
                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe :</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="type_compte">Type de compte :</label>
                        <select id="type_compte" name="type_compte" required>
                            <option value="">Sélectionner...</option>
                            <option value="admin" <?php echo ($utilisateur_modifier && $utilisateur_modifier['type_compte'] === 'admin') ? 'selected' : ''; ?>>
                                Administrateur
                            </option>
                            <option value="famille" <?php echo ($utilisateur_modifier && $utilisateur_modifier['type_compte'] === 'famille') ? 'selected' : ''; ?>>
                                Famille
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_heritier">Héritier associé :</label>
                        <select id="id_heritier" name="id_heritier">
                            <option value="">Aucun</option>
                            <?php foreach ($heritiers as $heritier): ?>
                                <option value="<?php echo $heritier['id_heritier']; ?>" 
                                        <?php echo ($utilisateur_modifier && $utilisateur_modifier['id_heritier'] == $heritier['id_heritier']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($heritier['nom_heritier'] . ' ' . $heritier['prenom_heritier']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="actif" name="actif" 
                                   <?php echo (!$utilisateur_modifier || $utilisateur_modifier['actif']) ? 'checked' : ''; ?>>
                            <label for="actif">Compte actif</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo $utilisateur_modifier ? 'Modifier l\'Utilisateur' : 'Créer l\'Utilisateur'; ?>
                </button>
                
                <?php if ($utilisateur_modifier): ?>
                    <a href="gestion_utilisateurs.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Liste des utilisateurs -->
        <div class="section">
            <h2>Liste des Utilisateurs</h2>
            
            <?php if (empty($utilisateurs)): ?>
                <p>Aucun utilisateur trouvé.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Type de compte</th>
                                <th>Héritier associé</th>
                                <th>Date de création</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                                <tr>
                                    <td><?php echo $utilisateur['id_utilisateur']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?></strong></td>
                                    <td>
                                        <span class="type-badge type-<?php echo $utilisateur['type_compte']; ?>">
                                            <?php echo ucfirst($utilisateur['type_compte']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($utilisateur['nom_heritier']) {
                                            echo htmlspecialchars($utilisateur['nom_heritier'] . ' ' . $utilisateur['prenom_heritier']);
                                        } else {
                                            echo 'Aucun';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($utilisateur['date_creation'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $utilisateur['actif'] ? 'actif' : 'inactif'; ?>">
                                            <?php echo $utilisateur['actif'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?modifier=<?php echo $utilisateur['id_utilisateur']; ?>" class="btn btn-warning">
                                            Modifier
                                        </a>
                                        <?php if ($utilisateur['id_utilisateur'] != 1): // Ne pas supprimer l'admin principal ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id_utilisateur" value="<?php echo $utilisateur['id_utilisateur']; ?>">
                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>