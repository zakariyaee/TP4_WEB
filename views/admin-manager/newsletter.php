<?php
/**
 * Newsletter & Promotions View
 *
 * Provides UI for managing newsletter campaigns and promotions.
 *
 * @package views/admin-manager
 */

require_once '../../config/database.php';
require_once '../../check_auth.php';

checkAdminOrRespo();

$pageTitle = "Newsletter & Promotions";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .animate-fade {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tab-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-emerald-50 via-blue-50/20 to-white min-h-screen text-slate-700">
    <div class="flex">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="flex-1 ml-64 p-8">
            <header class="mb-10 animate-fade">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <p class="uppercase tracking-wider text-emerald-500 font-semibold text-xs mb-2">Marketing</p>
                        <h1 class="text-3xl font-bold text-slate-800"><?php echo $pageTitle; ?></h1>
                        <p class="text-slate-500 mt-2 text-sm">Envoyez vos campagnes d'emails, consultez les performances et
                            créez des promotions ciblées pour vos abonnés.</p>
                    </div>
                    <div class="flex gap-3">
                        <button id="refreshDashboardBtn" class="px-4 py-2 rounded-lg border border-emerald-500 text-emerald-600 text-sm font-semibold hover:bg-emerald-50 transition">
                            <i class="fa-solid fa-rotate me-2"></i>Actualiser
                        </button>
                        <button id="openSendTabBtn" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition">
                            <i class="fa-solid fa-paper-plane me-2"></i>Nouvelle campagne
                        </button>
                    </div>
                </div>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
                <article class="bg-white rounded-3xl shadow-md p-6 border border-emerald-100 animate-fade">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs uppercase tracking-widest font-semibold text-emerald-500">Abonnés newsletter</span>
                        <span class="h-10 w-10 rounded-2xl bg-emerald-100 text-emerald-500 flex items-center justify-center">
                            <i class="fa-solid fa-user-group"></i>
                        </span>
                    </div>
                    <p id="stat-subscribers" class="text-3xl font-bold text-slate-800">0</p>
                    <p class="text-xs text-slate-400 mt-2">Abonnés actifs recevant vos emails.</p>
                </article>

                <article class="bg-white rounded-3xl shadow-md p-6 border border-emerald-100 animate-fade">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs uppercase tracking-widest font-semibold text-emerald-500">Campagnes envoyées</span>
                        <span class="h-10 w-10 rounded-2xl bg-emerald-100 text-emerald-500 flex items-center justify-center">
                            <i class="fa-solid fa-envelope-circle-check"></i>
                        </span>
                    </div>
                    <p id="stat-sent" class="text-3xl font-bold text-slate-800">0</p>
                    <p class="text-xs text-slate-400 mt-2">Nombre total de campagnes envoyées.</p>
                </article>

                <article class="bg-white rounded-3xl shadow-md p-6 border border-emerald-100 animate-fade">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs uppercase tracking-widest font-semibold text-emerald-500">Taux d'ouverture moyen</span>
                        <span class="h-10 w-10 rounded-2xl bg-emerald-100 text-emerald-500 flex items-center justify-center">
                            <i class="fa-solid fa-chart-simple"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-slate-800"><span id="stat-open-rate">0</span><span class="text-lg font-semibold">%</span></p>
                    <p class="text-xs text-slate-400 mt-2">Basé sur les dernières campagnes envoyées.</p>
                </article>

                <article class="bg-white rounded-3xl shadow-md p-6 border border-emerald-100 animate-fade">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs uppercase tracking-widest font-semibold text-emerald-500">Promotions actives</span>
                        <span class="h-10 w-10 rounded-2xl bg-emerald-100 text-emerald-500 flex items-center justify-center">
                            <i class="fa-solid fa-tags"></i>
                        </span>
                    </div>
                    <p id="stat-promotions" class="text-3xl font-bold text-slate-800">0</p>
                    <p class="text-xs text-slate-400 mt-2">Promotions actuellement disponibles.</p>
                </article>
            </section>

            <section class="bg-white rounded-3xl shadow-md border border-slate-100 overflow-hidden animate-fade">
                <nav class="flex flex-wrap gap-3 p-4 border-b border-slate-100 bg-slate-50/60">
                    <button data-tab="send" class="newsletter-tab px-5 py-2.5 rounded-2xl text-sm font-semibold text-slate-600 hover:text-emerald-600 transition tab-active">
                        <i class="fa-solid fa-pen-nib me-2"></i>Envoyer newsletter
                    </button>
                    <button data-tab="history" class="newsletter-tab px-5 py-2.5 rounded-2xl text-sm font-semibold text-slate-600 hover:text-emerald-600 transition">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>Historique
                    </button>
                    <button data-tab="promotions" class="newsletter-tab px-5 py-2.5 rounded-2xl text-sm font-semibold text-slate-600 hover:text-emerald-600 transition">
                        <i class="fa-solid fa-gift me-2"></i>Promotions
                    </button>
                </nav>

                <div class="p-8 space-y-10">
                    <!-- Send Newsletter -->
                    <section id="tab-send" class="tab-content">
                        <div class="grid gap-6 md:grid-cols-[2fr,1fr]">
                            <form id="newsletterForm" class="bg-slate-50 rounded-3xl p-6 border border-slate-100 space-y-5">
                                <div>
                                    <label class="text-xs font-semibold text-slate-500 tracking-wide uppercase mb-1 block">Titre de la campagne</label>
                                    <input type="text" id="newsletterTitre" name="titre" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50" placeholder="Promotion Automne 2025" required>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-500 tracking-wide uppercase mb-1 block">Objet de l'email</label>
                                    <input type="text" id="newsletterObjet" name="objet" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50" placeholder="Profitez de 20% de réduction..." required>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-500 tracking-wide uppercase mb-1 block">Destinataires</label>
                                    <div class="relative">
                                        <select id="newsletterAudience" name="audience_label" class="w-full appearance-none px-4 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50">
                                            <option value="Tous les abonnés">Tous les abonnés (liste synchronisée)</option>
                                            <option value="Joueurs actifs">Joueurs actifs</option>
                                            <option value="Responsables">Responsables</option>
                                        </select>
                                        <i class="fa-solid fa-angle-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-500 tracking-wide uppercase mb-1 block">Contenu de l'email</label>
                                    <textarea id="newsletterContenu" name="contenu" rows="8" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50" placeholder="Rédigez votre message..." required></textarea>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 pt-3">
                                    <button type="button" id="sendNowBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-3 rounded-2xl shadow transition">
                                        <i class="fa-solid fa-paper-plane me-2"></i>Envoyer maintenant
                                    </button>
                                    <button type="button" id="saveDraftBtn" class="flex-1 border border-slate-300 text-slate-600 hover:border-emerald-500 hover:text-emerald-600 font-semibold px-5 py-3 rounded-2xl transition">
                                        <i class="fa-solid fa-floppy-disk me-2"></i>Sauvegarder comme brouillon
                                    </button>
                                </div>
                            </form>

                            <aside class="space-y-5">
                                <div class="bg-emerald-600 text-white rounded-3xl p-6 shadow-lg relative overflow-hidden">
                                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/30 rounded-full"></div>
                                    <h3 class="text-lg font-bold mb-3">Conseil pour de meilleures campagnes</h3>
                                    <p class="text-sm text-emerald-50 leading-relaxed">Personnalisez vos objets d'email et utilisez un call-to-action clair pour maximiser le taux d'ouverture.</p>
                                </div>
                                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm">
                                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Statistiques rapides</h4>
                                    <ul class="space-y-3 text-sm text-slate-500">
                                        <li class="flex items-center gap-3">
                                            <span class="h-8 w-8 rounded-2xl bg-emerald-100 text-emerald-500 flex items-center justify-center">
                                                <i class="fa-solid fa-circle-check"></i>
                                            </span>
                                            <span>70% taux moyen d'ouverture (objectif 75%).</span>
                                        </li>
                                        <li class="flex items-center gap-3">
                                            <span class="h-8 w-8 rounded-2xl bg-emerald-100 text-emerald-500 flex items-center justify-center">
                                                <i class="fa-solid fa-users"></i>
                                            </span>
                                            <span>Audience synchronisée automatiquement depuis les comptes actifs.</span>
                                        </li>
                                    </ul>
                                </div>
                            </aside>
                        </div>
                    </section>

                    <!-- History -->
                    <section id="tab-history" class="tab-content hidden">
                        <div class="bg-slate-50 rounded-3xl border border-slate-100 p-6">
                            <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                                <div>
                                    <h2 class="text-xl font-bold text-slate-800">Historique des campagnes</h2>
                                    <p class="text-sm text-slate-500">Suivez les performances de vos envois précédents.</p>
                                </div>
                                <div class="flex gap-3">
                                    <div class="relative">
                                        <input type="text" id="historySearch" placeholder="Rechercher une campagne..." class="pl-11 pr-4 py-2.5 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50">
                                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    </div>
                                </div>
                            </header>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs uppercase tracking-wider text-slate-400">
                                            <th class="py-3 px-4">Titre</th>
                                            <th class="py-3 px-4">Objet</th>
                                            <th class="py-3 px-4">Date d'envoi</th>
                                            <th class="py-3 px-4">Destinataires</th>
                                            <th class="py-3 px-4">Taux d'ouverture</th>
                                            <th class="py-3 px-4">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTable" class="divide-y divide-slate-100">
                                        <tr>
                                            <td colspan="6" class="py-12 text-center text-slate-400">Chargement en cours...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- Promotions -->
                    <section id="tab-promotions" class="tab-content hidden">
                        <div class="grid gap-6 lg:grid-cols-[1fr,1fr]">
                            <form id="promotionForm" class="bg-slate-50 border border-slate-100 rounded-3xl p-6 space-y-5">
                                <header>
                                    <h2 class="text-xl font-bold text-slate-800">Créer une promotion</h2>
                                    <p class="text-sm text-slate-500 mt-1">Boostez vos réservations avec des codes promo ciblés.</p>
                                </header>

                                <div>
                                    <label class="text-xs font-semibold text-slate-500 uppercase block mb-1">Code promo</label>
                                    <input type="text" id="promoCode" name="code" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50 uppercase" placeholder="AUTOMNE20" maxlength="40" required>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-500 uppercase block mb-1">Description</label>
                                    <input type="text" id="promoDescription" name="description" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50" placeholder="20% de réduction sur vos réservations" required>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-500 uppercase block mb-1">Réduction (%)</label>
                                        <input type="number" step="0.1" min="0" max="100" id="promoReduction" name="reduction" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50" value="10" required>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-500 uppercase block mb-1">Utilisation max</label>
                                        <input type="number" min="0" id="promoUsageMax" name="utilisation_max" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50" value="100">
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-500 uppercase block mb-1">Valide jusqu'au</label>
                                    <input type="date" id="promoExpiration" name="date_expiration" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring focus:ring-emerald-200/50">
                                </div>

                                <button type="submit" id="promoSubmitBtn" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-3 rounded-2xl shadow transition">
                                    <i class="fa-solid fa-plus me-2"></i>Créer la promotion
                                </button>
                            </form>

                            <div class="space-y-4" id="promotionsList">
                                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm">
                                    <p class="text-sm text-slate-500 text-center">Chargement des promotions...</p>
                                </div>
                            </div>
                            <div id="promotionsPagination" class="hidden mt-4 flex items-center justify-between gap-4">
                                <button id="promotionsPrevBtn" class="px-4 py-2 rounded-2xl border border-slate-200 text-sm font-semibold text-slate-600 hover:border-emerald-500 hover:text-emerald-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    ← Précédent
                                </button>
                                <span id="promotionsPageInfo" class="text-sm text-slate-500">Page 1 / 1</span>
                                <button id="promotionsNextBtn" class="px-4 py-2 rounded-2xl border border-slate-200 text-sm font-semibold text-slate-600 hover:border-emerald-500 hover:text-emerald-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    Suivant →
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <div id="toast" class="hidden fixed bottom-8 right-8 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-lg z-50 text-sm font-medium"></div>

    <script src="../../assets/js/newsletter.js?v=3"></script>
</body>

</html>

