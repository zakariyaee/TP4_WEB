<?php
// actions/admin-respo/get_responsables.php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

try {
    // Seul l'admin peut voir tous les responsables
    $sql = "SELECT email, nom, prenom FROM utilisateur WHERE role = 'responsable' AND statut_compte = 'actif'";
    $params = [];
    
    if ($_SESSION['user_role'] === 'responsable') {
        // Un responsable ne peut voir que lui-même
        $sql .= " WHERE email = :email";
        $params[':email'] = $_SESSION['user_email'];
    }
    
    $sql .= " ORDER BY nom, prenom";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $responsables = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'responsables' => $responsables]);
    
} catch (PDOException $e) {
    error_log("Erreur get_managers: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des responsables']);
}
?>