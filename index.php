<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TerrainBook - Authentification</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-emerald-600 to-teal-500 min-h-screen flex items-center justify-center p-4">
    
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <!-- Logo and Title -->
        <div class="flex items-center justify-center mb-6">
            <div class="bg-emerald-500 rounded-lg p-2 mr-3">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C11.5 2 11 2.19 10.59 2.59L10 3.17L9.41 2.59C9 2.19 8.5 2 8 2H5C3.89 2 3 2.89 3 4V8C3 9.1 3.89 10 5 10H6.5C7.08 10 7.65 9.79 8.09 9.42C9.1 11.55 10.9 13.07 13 13.72V16H11C9.89 16 9 16.89 9 18V20H7V22H17V20H15V18C15 16.89 14.11 16 13 16H11V13.72C13.1 13.07 14.9 11.55 15.91 9.42C16.35 9.79 16.92 10 17.5 10H19C20.11 10 21 9.1 21 8V4C21 2.89 20.11 2 19 2H16C15.5 2 15 2.19 14.59 2.59L14 3.17L13.41 2.59C13 2.19 12.5 2 12 2M5 4H8L9 5L8 6H5V4M16 4H19V6H16L15 5L16 4M6.5 8H7V8.5C7 9.88 7.39 11.16 8.03 12.27C7.5 11.94 7 11.5 6.5 11V8M17.5 8V11C17 11.5 16.5 11.94 15.97 12.27C16.61 11.16 17 9.88 17 8.5V8H17.5Z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">TerrainBook</h1>
        </div>

        <h2 class="text-xl text-center text-gray-700 mb-2">Bienvenue</h2>
        <p class="text-center text-gray-500 text-sm mb-6">Connectez-vous ou créez un compte pour continuer</p>

        <!-- Tab Navigation -->
        <div class="flex mb-6 border-b border-gray-200">
            <button id="loginTab" class="tab-button flex-1 py-3 text-center font-medium border-b-2 border-emerald-500 text-emerald-600 transition-all">
                Connexion
            </button>
            <button id="registerTab" class="tab-button flex-1 py-3 text-center font-medium text-gray-500 hover:text-gray-700 transition-all">
                Inscription
            </button>
        </div>

        <!-- Login Form -->
        <form id="loginForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" id="loginEmail" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all" placeholder="votre@email.com" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                <input type="password" id="loginPassword" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all" placeholder="••••••••" required>
            </div>

            <div id="loginMessage" class="hidden p-3 rounded-lg text-sm"></div>

            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-3 rounded-lg transition-colors">
                Se connecter
            </button>

            <div class="text-center">
                <a href="#" class="text-sm text-gray-500 hover:text-emerald-600 transition-colors">← Retour à l'accueil</a>
            </div>
        </form>

        <!-- Registration Form (Hidden by default) -->
        <form id="registerForm" class="space-y-4 hidden">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label>
                <input type="text" id="registerName" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all" placeholder="Jean Dupont" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" id="registerEmail" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all" placeholder="votre@email.com" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                <input type="tel" id="registerPhone" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all" placeholder="06 12 34 56 78" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
                <input type="password" id="registerPassword" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all" placeholder="••••••••" required>
            </div>

            <div id="registerMessage" class="hidden p-3 rounded-lg text-sm"></div>

            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-3 rounded-lg transition-colors">
                Créer un compte
            </button>

            <div class="text-center">
                <a href="#" class="text-sm text-gray-500 hover:text-emerald-600 transition-colors">← Retour à l'accueil</a>
            </div>
        </form>
    </div>

    <script>
        // Tab switching
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        loginTab.addEventListener('click', () => {
            loginTab.classList.add('border-emerald-500', 'text-emerald-600');
            loginTab.classList.remove('text-gray-500');
            registerTab.classList.remove('border-emerald-500', 'text-emerald-600');
            registerTab.classList.add('text-gray-500');
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        });

        registerTab.addEventListener('click', () => {
            registerTab.classList.add('border-emerald-500', 'text-emerald-600');
            registerTab.classList.remove('text-gray-500');
            loginTab.classList.remove('border-emerald-500', 'text-emerald-600');
            loginTab.classList.add('text-gray-500');
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        });

        // Login Form Submission
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const messageDiv = document.getElementById('loginMessage');

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    messageDiv.className = 'p-3 rounded-lg text-sm bg-green-100 text-green-700';
                    messageDiv.textContent = data.message;
                    messageDiv.classList.remove('hidden');
                    
                    // Redirect to dashboard after 1 second
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    messageDiv.className = 'p-3 rounded-lg text-sm bg-red-100 text-red-700';
                    messageDiv.textContent = data.message;
                    messageDiv.classList.remove('hidden');
                }
            } catch (error) {
                messageDiv.className = 'p-3 rounded-lg text-sm bg-red-100 text-red-700';
                messageDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
                messageDiv.classList.remove('hidden');
            }
        });

        // Registration Form Submission
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const name = document.getElementById('registerName').value;
            const email = document.getElementById('registerEmail').value;
            const phone = document.getElementById('registerPhone').value;
            const password = document.getElementById('registerPassword').value;
            const messageDiv = document.getElementById('registerMessage');

            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ name, email, phone, password })
                });

                const data = await response.json();

                if (data.success) {
                    messageDiv.className = 'p-3 rounded-lg text-sm bg-green-100 text-green-700';
                    messageDiv.textContent = data.message;
                    messageDiv.classList.remove('hidden');
                    
                    // Switch to login tab after 1.5 seconds
                    setTimeout(() => {
                        loginTab.click();
                        registerForm.reset();
                        messageDiv.classList.add('hidden');
                    }, 1500);
                } else {
                    messageDiv.className = 'p-3 rounded-lg text-sm bg-red-100 text-red-700';
                    messageDiv.textContent = data.message;
                    messageDiv.classList.remove('hidden');
                }
            } catch (error) {
                messageDiv.className = 'p-3 rounded-lg text-sm bg-red-100 text-red-700';
                messageDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
                messageDiv.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>