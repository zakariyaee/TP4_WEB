<?php
require_once '../../config/database.php';
require_once '../../check_auth.php';

checkJoueur();

$playerName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitations - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 py-10 max-w-7xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-12">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-sm font-medium mb-3">
                    <i class="fas fa-envelope-open-text"></i>
                    Invitations
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Gérez vos invitations</h1>
                <p class="text-gray-600 mt-3 max-w-2xl">
                    Retrouvez ici les invitations aux tournois reçues par vos équipes. Acceptez-les pour valider votre participation ou déclinez-les lorsque vous n'êtes pas disponible.
                </p>
            </div>
        </div>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-12">
            <div class="bg-white border border-blue-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-blue-600">En attente</span>
                    <span class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-hourglass-half"></i>
                    </span>
                </div>
                <div id="statPendingInvites" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Invitations en attente de réponse</p>
            </div>
            <div class="bg-white border border-emerald-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-emerald-600">Confirmées</span>
                    <span class="w-9 h-9 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <i class="fas fa-check-circle"></i>
                    </span>
                </div>
                <div id="statAcceptedInvites" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Participations validées</p>
            </div>
            <div class="bg-white border border-purple-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-purple-600">Tournois</span>
                    <span class="w-9 h-9 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                        <i class="fas fa-trophy"></i>
                    </span>
                </div>
                <div id="statTotalInvites" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Total des invitations reçues</p>
            </div>
            <div class="bg-white border border-amber-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-amber-600">À coordonner</span>
                    <span class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center text-amber-600">
                        <i class="fas fa-users-cog"></i>
                    </span>
                </div>
                <div id="statCaptainPending" class="text-3xl font-bold text-gray-900">0</div>
                <p class="text-sm text-gray-500 mt-2">Invitations où vous êtes capitaine</p>
            </div>
        </section>

        <section class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Invitations en attente</h2>
                <span id="pendingInvitesCount" class="text-sm text-gray-500">0 invitation</span>
            </div>
            <div id="pendingInvitesContainer" class="space-y-6"></div>
            <div id="emptyPendingInvites" class="hidden border border-dashed border-blue-200 rounded-2xl bg-blue-50/40 p-10 text-center text-blue-700">
                <i class="fas fa-envelope-open text-3xl mb-4"></i>
                <p class="font-semibold mb-2">Aucune invitation en attente</p>
                <p class="text-sm">Vos équipes n'ont actuellement aucune invitation en cours. Continuez à explorer les tournois pour lancer de nouveaux défis !</p>
            </div>
        </section>

        <section class="mb-16">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Historique des invitations</h2>
                <span id="historyInvitesCount" class="text-sm text-gray-500">0 élément</span>
            </div>
            <div id="historyInvitesContainer" class="space-y-4"></div>
            <div id="emptyHistoryInvites" class="hidden border border-dashed border-gray-200 rounded-2xl bg-white p-10 text-center text-gray-500">
                <i class="fas fa-clipboard-check text-3xl mb-4"></i>
                <p class="font-semibold mb-2">Pas encore d'historique</p>
                <p class="text-sm">Les invitations acceptées ou refusées apparaîtront ici pour garder une trace de vos décisions.</p>
            </div>
        </section>
    </main>

    <!-- Toast -->
    <div id="invitationsToast" class="hidden fixed top-6 right-6 px-6 py-4 rounded-2xl shadow-lg text-white z-50"></div>

    <script>
        window.PLAYER_INVITATIONS_DATA = {
            endpoints: {
                fetch: '../../actions/player/tournament/get_invitations.php',
                respond: '../../actions/player/tournament/respond_invitation.php'
            },
            playerName: <?php echo json_encode($playerName, JSON_UNESCAPED_UNICODE); ?>
        };
    </script>
    <script src="../../assets/js/player/invitations.js"></script>
</body>

</html>

