<?php
require_once '../../../config/database.php';
require_once '../../../check_auth.php';

checkAdminOrRespo();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id_equipe'] ?? 0);
$nom = trim($data['nom_equipe'] ?? '');
$email = trim($data['email_equipe'] ?? '');

if ($id <= 0 || $nom === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Champs requis manquants']);
    exit;
}

try {
    // Vérifier que l'équipe existe
    $stmt = $pdo->prepare('SELECT id_equipe FROM equipe WHERE id_equipe = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => "Équipe introuvable"]);
        exit;
    }

    // S'assurer d'unicité email si vous le souhaitez (facultatif)
    $stmt = $pdo->prepare('SELECT id_equipe FROM equipe WHERE email_equipe = ? AND id_equipe <> ?');
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par une autre équipe']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE equipe SET nom_equipe = ?, email_equipe = ? WHERE id_equipe = ?');
    $stmt->execute([$nom, $email, $id]);

    echo json_encode(['success' => true, 'message' => "Équipe mise à jour"]);
} catch (PDOException $e) {
    error_log('Erreur edit_equipe: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'équipe']);
}
?>


