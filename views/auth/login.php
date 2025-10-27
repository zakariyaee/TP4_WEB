<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TerrainBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .message-animate {
            animation: slideDown 0.3s ease-out;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-green-300 via-white to-green-300/100 min-h-screen flex items-center justify-center p-4">

    <!-- Message de succès/erreur en haut -->
    <div id="topMessage" class="fixed top-0 left-0 right-0 z-50 hidden">
        <div class="max-w-md mx-auto mt-4 px-4">
            <div id="messageContent" class="rounded-lg shadow-lg p-4 message-animate"></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
        <!-- Logo et titre -->
        <div class="text-center mb-6">
            <div class="flex items-center justify-center gap-2 mb-3">
                <div class="w-8 h-8 bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" class="w-5 h-5">
                        <rect x="3" y="4" width="18" height="16" rx="2" ry="2" stroke="white" stroke-width="2" />
                        <line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="2" />
                        <circle cx="12" cy="12" r="1.5" fill="white" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-900">TerrainBook</span>
            </div>

            <h1 class="text-xl font-bold text-gray-900 mb-1">Bienvenue</h1>
            <p class="text-xs text-gray-600">Connectez-vous ou créez un compte pour continuer</p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1.5 mb-5 bg-gray-100 p-1 rounded-lg">
            <button id="loginTab" class="flex-1 py-2 text-xs font-semibold text-white bg-gradient-to-r from-emerald-600 to-green-700 rounded-md shadow-sm">
                Connexion
            </button>
            <button id="registerTab" class="flex-1 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 rounded-md">
                Inscription
            </button>
        </div>

        <!-- Formulaire de connexion -->
        <form id="loginForm">
            <div class="mb-3">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Email</label>
                <input
                    type="email"
                    id="loginEmail"
                    placeholder="votre@email.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none text-xs"
                    required>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Mot de passe</label>
                <div class="relative">
                    <input
                        type="password"
                        id="loginPassword"
                        placeholder="••••••••"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none text-xs pr-9"
                        required>
                    <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button
                type="submit"
                class="w-full bg-gradient-to-r from-emerald-600 to-green-700 text-white font-semibold py-2 rounded-lg hover:shadow-lg transition-all text-sm mb-3">
                Se connecter
            </button>

            <div class="text-center">
                <a href="../../index.php" class="inline-flex items-center gap-1.5 text-xs text-emerald-600 hover:text-emerald-700 font-semibold group">
                    <svg class="w-3.5 h-3.5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Retour à l'accueil
                </a>
            </div>
        </form>
    </div>

    <script>
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const topMessage = document.getElementById('topMessage');
        const messageContent = document.getElementById('messageContent');
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('loginPassword');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
        });

        registerTab.addEventListener('click', () => {
            loginTab.classList.remove('text-white', 'bg-gradient-to-r', 'from-emerald-600', 'to-green-700', 'shadow-sm');
            loginTab.classList.add('text-gray-600', 'hover:text-gray-900');
            registerTab.classList.remove('text-gray-600', 'hover:text-gray-900');
            registerTab.classList.add('text-white', 'bg-gradient-to-r', 'from-emerald-600', 'to-green-700', 'shadow-sm');

            setTimeout(() => {
                window.location.href = 'register.php';
            }, 200);
        });

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            submitBtn.disabled = true;

            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;

            try {
                const response = await fetch('../../actions/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email,
                        password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showTopMessage(data.message, 'success');
                    setTimeout(() => {
                        // Rediriger selon le rôle de l'utilisateur
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showTopMessage(data.message, 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                showTopMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        function showTopMessage(message, type) {
            messageContent.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            ${type === 'success' 
                                ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
                                : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
                            }
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">${message}</p>
                    </div>
                    <button onclick="hideTopMessage()" class="flex-shrink-0 text-current opacity-70 hover:opacity-100">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            `;

            messageContent.className = `rounded-lg shadow-lg p-4 message-animate ${
                type === 'success' 
                    ? 'bg-green-50 text-green-800 border-l-4 border-green-500' 
                    : 'bg-red-50 text-red-800 border-l-4 border-red-500'
            }`;

            topMessage.classList.remove('hidden');

            setTimeout(() => {
                hideTopMessage();
            }, 900);
        }

        function hideTopMessage() {
            topMessage.classList.add('hidden');
        }
    </script>
</body>

</html>