<?php
require_once 'config.php';
verifierAdmin();

$message = '';
$erreur = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'ajouter') {
        $allee = trim($_POST['allee']);
        $capacite = (int)$_POST['capacite'];
        $orientation = trim($_POST['orientation']);
        $type = trim($_POST['type']);
        $exposition_vent = trim($_POST['exposition_vent']);
        $proximete_entre = (int)$_POST['proximete_entre'];
        $accesibilite = trim($_POST['accesibilite']);
        
        if (empty($allee) || empty($orientation) || empty($type) || empty($exposition_vent) || empty($accesibilite)) {
            $erreur = 'Tous les champs obligatoires doivent être remplis.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO emplacement (Allee, capacite, orientation, type, exposition_vent, proximete_entre, accesibilite) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$allee, $capacite, $orientation, $type, $exposition_vent, $proximete_entre, $accesibilite]);
                $message = 'Emplacement créé avec succès.';
            } catch (PDOException $e) {
                $erreur = 'Erreur lors de la création de l\'emplacement.';
            }
        }
    }
    
    elseif ($action === 'modifier') {
        $id_emplacement = (int)$_POST['id_emplacement'];
        $allee = trim($_POST['allee']);
        $capacite = (int)$_POST['capacite'];
        $orientation = trim($_POST['orientation']);
        $type = trim($_POST['type']);
        $exposition_vent = trim($_POST['exposition_vent']);
        $proximete_entre = (int)$_POST['proximete_entre'];
        $accesibilite = trim($_POST['accesibilite']);
        
        try {
            $stmt = $pdo->prepare("UPDATE emplacement SET Allee = ?, capacite = ?, orientation = ?, type = ?, exposition_vent = ?, proximete_entre = ?, accesibilite = ? WHERE id_emplacement = ?");
            $stmt->execute([$allee, $capacite, $orientation, $type, $exposition_vent, $proximete_entre, $accesibilite, $id_emplacement]);
            $message = 'Emplacement modifié avec succès.';
        } catch (PDOException $e) {
            $erreur = 'Erreur lors de la modification de l\'emplacement.';
        }
    }
}

// Récupération de tous les emplacements avec informations sur l'occupation
try {
    $stmt = $pdo->query("
        SELECT e.*, 
               COUNT(c.id_emplacement) as nb_concessions,
               GROUP_CONCAT(CONCAT(o.prenom, ' ', o.nom) SEPARATOR ', ') as occupants
        FROM emplacement e
        LEFT JOIN concession c ON e.id_emplacement = c.id_emplacement
        LEFT JOIN occupant o ON c.id_occupant = o.id_occupant
        GROUP BY e.id_emplacement
        ORDER BY e.Allee
    ");
    $emplacements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erreur = 'Erreur lors de la récupération des emplacements.';
    $emplacements = [];
}

// Emplacement à modifier si demandé
$emplacement_modifier = null;
if (isset($_GET['modifier']) && is_numeric($_GET['modifier'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM emplacement WHERE id_emplacement = ?");
        $stmt->execute([$_GET['modifier']]);
        $emplacement_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erreur = 'Emplacement non trouvé.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Emplacements - Admin</title>
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
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            margin-right: 0.5rem;
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
            text-decoration: none;
        }
        
        .btn-warning:hover {
            background: #e0a800;
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
        
        .status-libre {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-occupe {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .occupants-list {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
        <h1>Gestion des Emplacements</h1>
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
                <h3><?php echo count($emplacements); ?></h3>
                <p>Total Emplacements</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($emplacements, function($e) { return $e['nb_concessions'] > 0; })); ?></h3>
                <p>Emplacements Occupés</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($emplacements, function($e) { return $e['nb_concessions'] == 0; })); ?></h3>
                <p>Emplacements Libres</p>
            </div>
            <div class="stat-card">
                <h3><?php echo array_sum(array_column($emplacements, 'capacite')); ?></h3>
                <p>Capacité Totale</p>
            </div>
        </div>
        
        <!-- Formulaire d'ajout/modification -->
        <div class="section">
            <h2><?php echo $emplacement_modifier ? 'Modifier l\'Emplacement' : 'Ajouter un Nouvel Emplacement'; ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $emplacement_modifier ? 'modifier' : 'ajouter'; ?>">
                <?php if ($emplacement_modifier): ?>
                    <input type="hidden" name="id_emplacement" value="<?php echo $emplacement_modifier['id_emplacement']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="allee">Allée :</label>
                        <input type="text" id="allee" name="allee" 
                               value="<?php echo $emplacement_modifier ? htmlspecialchars($emplacement_modifier['Allee']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="capacite">Capacité :</label>
                        <input type="number" id="capacite" name="capacite" min="1" 
                               value="<?php echo $emplacement_modifier ? $emplacement_modifier['capacite'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="orientation">Orientation :</label>
                        <select id="orientation" name="orientation" required>
                            <option value="">Sélectionner...</option>
                            <?php 
                            $orientations = ['Nord', 'Sud', 'Est', 'Ouest', 'Nord-Est', 'Nord-Ouest', 'Sud-Est', 'Sud-Ouest'];
                            foreach ($orientations as $orient): 
                            ?>
                                <option value="<?php echo $orient; ?>" 
                                        <?php echo ($emplacement_modifier && $emplacement_modifier['orientation'] === $orient) ? 'selected' : ''; ?>>
                                    <?php echo $orient; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type :</label>
                        <select id="type" name="type" required>
                            <option value="">Sélectionner...</option>
                            <?php 
                            $types = ['Jardin', 'Marbre', 'Bois', 'Pierre'];
                            foreach ($types as $type_emp): 
                            ?>
                                <option value="<?php echo $type_emp; ?>" 
                                        <?php echo ($emplacement_modifier && $emplacement_modifier['type'] === $type_emp) ? 'selected' : ''; ?>>
                                    <?php echo $type_emp; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exposition_vent">Exposition au vent :</label>
                        <select id="exposition_vent" name="exposition_vent" required>
                            <option value="">Sélectionner...</option>
                            <?php 
                            $expositions = ['Faible', 'Moyenne', 'Forte'];
                            foreach ($expositions as $expo): 
                            ?>
                                <option value="<?php echo $expo; ?>" 
                                        <?php echo ($emplacement_modifier && $emplacement_modifier['exposition_vent'] === $expo) ? 'selected' : ''; ?>>
                                    <?php echo $expo; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="proximete_entre">Proximité entrée (mètres) :</label>
                        <input type="number" id="proximete_entre" name="proximete_entre" min="0" 
                               value="<?php echo $emplacement_modifier ? $emplacement_modifier['proximete_entre'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="accesibilite">Accessibilité :</label>
                        <select id="accesibilite" name="accesibilite" required>
                            <option value="">Sélectionner...</option>
                            <?php 
                            $accessibilites = ['Facile', 'Moyenne', 'Difficile'];
                            foreach ($accessibilites as $access): 
                            ?>
                                <option value="<?php echo $access; ?>" 
                                        <?php echo ($emplacement_modifier && $emplacement_modifier['accesibilite'] === $access) ? 'selected' : ''; ?>>
                                    <?php echo $access; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo $emplacement_modifier ? 'Modifier l\'Emplacement' : 'Créer l\'Emplacement'; ?>
                </button>
                
                <?php if ($emplacement_modifier): ?>
                    <a href="gestion_emplacements.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Liste des emplacements -->
        <div class="section">
            <h2>Liste des Emplacements</h2>
            
            <?php if (empty($emplacements)): ?>
                <p>Aucun emplacement trouvé.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Allée</th>
                                <th>Capacité</th>
                                <th>Type</th>
                                <th>Orientation</th>
                                <th>Exposition Vent</th>
                                <th>Proximité Entrée</th>
                                <th>Accessibilité</th>
                                <th>Statut</th>
                                <th>Occupants</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emplacements as $emplacement): ?>
                                <tr>
                                    <td><?php echo $emplacement['id_emplacement']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($emplacement['Allee']); ?></strong></td>
                                    <td><?php echo $emplacement['capacite']; ?></td>
                                    <td><?php echo htmlspecialchars($emplacement['type']); ?></td>
                                    <td><?php echo htmlspecialchars($emplacement['orientation']); ?></td>
                                    <td><?php echo htmlspecialchars($emplacement['exposition_vent']); ?></td>
                                    <td><?php echo $emplacement['proximete_entre']; ?>m</td>
                                    <td><?php echo htmlspecialchars($emplacement['accesibilite']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $emplacement['nb_concessions'] > 0 ? 'occupe' : 'libre'; ?>">
                                            <?php echo $emplacement['nb_concessions'] > 0 ? 'Occupé' : 'Libre'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="occupants-list" title="<?php echo htmlspecialchars($emplacement['occupants'] ?: 'Aucun'); ?>">
                                            <?php echo htmlspecialchars($emplacement['occupants'] ?: 'Aucun'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="?modifier=<?php echo $emplacement['id_emplacement']; ?>" class="btn btn-warning">
                                            Modifier
                                        </a>
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