const newsletterState = {
  campaigns: [],
  promotions: [],
  stats: {
    subscribers: 0,
    campaignsSent: 0,
    averageOpenRate: 0,
    activePromotions: 0,
  },
  promotionsPagination: {
    page: 1,
    perPage: 3,
    total: 0,
    totalPages: 1,
    hasPrev: false,
    hasNext: false,
  },
  _promotionsHandlersAttached: false,
};

const selectors = {
  tabs: () => document.querySelectorAll('.newsletter-tab'),
  tabContents: () => document.querySelectorAll('.tab-content'),
  statSubscribers: () => document.getElementById('stat-subscribers'),
  statSent: () => document.getElementById('stat-sent'),
  statOpenRate: () => document.getElementById('stat-open-rate'),
  statPromotions: () => document.getElementById('stat-promotions'),
  historyTable: () => document.getElementById('historyTable'),
  promotionsList: () => document.getElementById('promotionsList'),
  toast: () => document.getElementById('toast'),
  historySearch: () => document.getElementById('historySearch'),
  refreshBtn: () => document.getElementById('refreshDashboardBtn'),
  sendTabBtn: () => document.getElementById('openSendTabBtn'),
  sendNowBtn: () => document.getElementById('sendNowBtn'),
  saveDraftBtn: () => document.getElementById('saveDraftBtn'),
  newsletterForm: () => document.getElementById('newsletterForm'),
  promotionForm: () => document.getElementById('promotionForm'),
  promoSubmitBtn: () => document.getElementById('promoSubmitBtn'),
  promotionsPagination: () => document.getElementById('promotionsPagination'),
  promotionsPrevBtn: () => document.getElementById('promotionsPrevBtn'),
  promotionsNextBtn: () => document.getElementById('promotionsNextBtn'),
  promotionsPageInfo: () => document.getElementById('promotionsPageInfo'),
};

const PROMOTIONS_PER_PAGE = 3;

const endpoints = {
  dashboard: '../../actions/admin-manager/newsletter/get_dashboard.php',
  sendCampaign: '../../actions/admin-manager/newsletter/send_newsletter.php',
  saveCampaign: '../../actions/admin-manager/newsletter/save_campaign.php',
  createPromotion: '../../actions/admin-manager/newsletter/create_promotion.php',
};

document.addEventListener('DOMContentLoaded', () => {
  attachTabHandlers();
  attachFormHandlers();
  newsletterState.promotionsPagination.perPage = PROMOTIONS_PER_PAGE;
  attachPromotionsPaginationHandlers();
  fetchDashboard();
});

function attachTabHandlers() {
  selectors.tabs().forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      selectors.tabs().forEach((btn) => btn.classList.remove('tab-active'));
      tab.classList.add('tab-active');

      selectors.tabContents().forEach((section) => {
        section.classList.toggle('hidden', section.id !== `tab-${target}`);
      });
    });
  });

  const refreshBtn = selectors.refreshBtn();
  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => fetchDashboard(true));
  }

  const sendTabBtn = selectors.sendTabBtn();
  if (sendTabBtn) {
    sendTabBtn.addEventListener('click', () => {
      const firstTab = document.querySelector('.newsletter-tab[data-tab="send"]');
      if (firstTab) {
        firstTab.click();
      }
      document.getElementById('newsletterTitre')?.focus();
    });
  }

  const historySearch = selectors.historySearch();
  if (historySearch) {
    historySearch.addEventListener('input', () => renderHistory());
  }
}

function attachFormHandlers() {
  const sendNowBtn = selectors.sendNowBtn();
  const saveDraftBtn = selectors.saveDraftBtn();
  const newsletterForm = selectors.newsletterForm();

  if (sendNowBtn) {
    sendNowBtn.addEventListener('click', () => handleNewsletterSubmit(true));
  }

  if (saveDraftBtn) {
    saveDraftBtn.addEventListener('click', () => handleNewsletterSubmit(false));
  }

  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (event) => {
      event.preventDefault();
    });
  }

  const promotionForm = selectors.promotionForm();
  if (promotionForm) {
    promotionForm.addEventListener('submit', (event) => {
      event.preventDefault();
      handlePromotionSubmit();
    });
  }
}

function attachPromotionsPaginationHandlers() {
  if (newsletterState._promotionsHandlersAttached) {
    return;
  }
  newsletterState._promotionsHandlersAttached = true;

  const prevBtn = selectors.promotionsPrevBtn();
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (!newsletterState.promotionsPagination.hasPrev) return;
      newsletterState.promotionsPagination.page = Math.max(
        1,
        newsletterState.promotionsPagination.page - 1
      );
      fetchDashboard();
    });
  }

  const nextBtn = selectors.promotionsNextBtn();
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (!newsletterState.promotionsPagination.hasNext) return;
      newsletterState.promotionsPagination.page += 1;
      fetchDashboard();
    });
  }
}

async function fetchDashboard(showToastOnSuccess = false) {
  try {
    const params = new URLSearchParams({
      promotions_page: newsletterState.promotionsPagination.page,
      promotions_limit: newsletterState.promotionsPagination.perPage,
    });
    const url = `${endpoints.dashboard}?${params.toString()}`;

    const response = await fetch(url, {
      credentials: 'include',
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const payload = await response.json();
    if (!payload.success) {
      throw new Error(payload.message || 'Réponse invalide');
    }

    newsletterState.stats = payload.stats;
    newsletterState.campaigns = payload.campaigns || [];

    if (Array.isArray(payload.promotions)) {
      newsletterState.promotions = payload.promotions;
      newsletterState.promotionsPagination = {
        ...newsletterState.promotionsPagination,
        page: 1,
        total: payload.promotions.length,
        totalPages: 1,
        hasPrev: false,
        hasNext: false,
      };
    } else if (
      payload.promotions &&
      Array.isArray(payload.promotions.items)
    ) {
      newsletterState.promotions = payload.promotions.items;
      const pagination = payload.promotions.pagination || {};
      const perPage = toPositiveInt(
        pagination.perPage,
        newsletterState.promotionsPagination.perPage
      );
      const total = toNonNegativeInt(
        pagination.total,
        newsletterState.promotionsPagination.total
      );
      const requestedPage = toPositiveInt(
        pagination.page,
        newsletterState.promotionsPagination.page
      );
      const calculatedTotalPages = Math.max(1, Math.ceil(total / perPage || 1));
      const totalPages = toPositiveInt(
        pagination.totalPages,
        calculatedTotalPages
      );
      const normalizedPage = Math.min(requestedPage, totalPages);

      newsletterState.promotionsPagination = {
        ...newsletterState.promotionsPagination,
        page: normalizedPage,
        perPage,
        total,
        totalPages,
        hasPrev: pagination.hasPrev ?? normalizedPage > 1,
        hasNext: pagination.hasNext ?? normalizedPage < totalPages,
      };
    } else {
      newsletterState.promotions = [];
      newsletterState.promotionsPagination = {
        ...newsletterState.promotionsPagination,
        page: 1,
        total: 0,
        totalPages: 1,
        hasPrev: false,
        hasNext: false,
      };
    }

    renderStats();
    renderHistory();
    renderPromotions();

    if (showToastOnSuccess) {
      showToast('Tableau de bord actualisé.');
    }
  } catch (error) {
    console.error('Erreur dashboard newsletter:', error);
    showToast("Impossible de charger les données newsletter.", true);
  }
}

function renderStats() {
  selectors.statSubscribers().textContent = newsletterState.stats.subscribers ?? 0;
  selectors.statSent().textContent = newsletterState.stats.campaignsSent ?? 0;
  selectors.statOpenRate().textContent = formatNumber(newsletterState.stats.averageOpenRate ?? 0);
  selectors.statPromotions().textContent = newsletterState.stats.activePromotions ?? 0;
}

function renderHistory() {
  const tbody = selectors.historyTable();
  if (!tbody) return;

  const query = (selectors.historySearch()?.value || '').toLowerCase();
  const campaigns = newsletterState.campaigns.filter((campaign) => {
    if (!query) return true;
    return (
      (campaign.titre || '').toLowerCase().includes(query) ||
      (campaign.objet || '').toLowerCase().includes(query) ||
      (campaign.statut || '').toLowerCase().includes(query)
    );
  });

  if (campaigns.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="py-12 text-center text-slate-400">Aucune campagne trouvée.</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = campaigns
    .map((campaign) => {
      const statutBadge = renderStatusBadge(campaign.statut);
      const openRate = campaign.open_rate != null ? `${formatNumber(campaign.open_rate)}%` : '-';
      const sendDate = campaign.date_envoi || campaign.created_at || '-';
      const destinataires = campaign.destinataires_count ?? 0;

      return `
        <tr class="hover:bg-white/60 transition">
          <td class="px-4 py-3 font-semibold text-slate-700">${escapeHtml(campaign.titre || '')}</td>
          <td class="px-4 py-3 text-slate-500">${escapeHtml(campaign.objet || '')}</td>
          <td class="px-4 py-3 text-slate-500">${sendDate}</td>
          <td class="px-4 py-3 text-slate-500">${destinataires}</td>
          <td class="px-4 py-3 text-slate-500">${openRate}</td>
          <td class="px-4 py-3">${statutBadge}</td>
        </tr>
      `;
    })
    .join('');
}

function renderPromotions() {
  const container = selectors.promotionsList();
  if (!container) return;

  const items = Array.isArray(newsletterState.promotions)
    ? newsletterState.promotions
    : [];

  if (items.length === 0) {
    container.innerHTML = `
      <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm">
          <p class="text-sm text-slate-500 text-center">Aucune promotion enregistrée pour le moment.</p>
      </div>
    `;
  } else {
    container.innerHTML = items
      .map((promo) => {
        const usageMax = promo.utilisation_max ?? 0;
        const usage = promo.utilisation ?? 0;
        const ratio =
          usageMax > 0 ? Math.min(100, Math.round((usage / usageMax) * 100)) : 0;
        const statusBadge = renderPromotionStatus(
          promo.statut,
          promo.date_expiration
        );
        const description = escapeHtml(promo.description || '');
        const code = escapeHtml(promo.code || '');
        const reduction = formatNumber(promo.reduction ?? 0);
        const expireLabel = promo.date_expiration
          ? `Expire le ${promo.date_expiration}`
          : 'Sans limite';

        return `
        <article class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-wide text-emerald-500 font-semibold mb-1">Code</p>
              <h3 class="text-xl font-bold text-slate-800 tracking-wide">${code}</h3>
            </div>
            ${statusBadge}
          </div>

          <p class="text-sm text-slate-500">${description}</p>

          <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-600 text-sm font-semibold">${reduction}%</span>
            <span class="text-xs text-slate-400">${expireLabel}</span>
          </div>

          <div>
            <div class="flex justify-between text-xs text-slate-400 mb-2">
              <span>${usage} utilisation(s)</span>
              <span>${usageMax > 0 ? `${usageMax} maximum` : 'Illimité'}</span>
            </div>
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: ${ratio}%;"></div>
            </div>
          </div>
        </article>
      `;
      })
      .join('');
  }

  const pagination = newsletterState.promotionsPagination;
  const paginationWrapper = selectors.promotionsPagination();
  if (!paginationWrapper) return;

  if (
    !pagination ||
    pagination.total <= pagination.perPage ||
    items.length === 0
  ) {
    paginationWrapper.classList.add('hidden');
  } else {
    paginationWrapper.classList.remove('hidden');
    const pageInfo = selectors.promotionsPageInfo();
    if (pageInfo) {
      pageInfo.textContent = `Page ${pagination.page} / ${pagination.totalPages}`;
    }

    const prevBtn = selectors.promotionsPrevBtn();
    if (prevBtn) {
      prevBtn.disabled = !pagination.hasPrev;
      prevBtn.classList.toggle('opacity-50', !pagination.hasPrev);
      prevBtn.classList.toggle('cursor-not-allowed', !pagination.hasPrev);
    }

    const nextBtn = selectors.promotionsNextBtn();
    if (nextBtn) {
      nextBtn.disabled = !pagination.hasNext;
      nextBtn.classList.toggle('opacity-50', !pagination.hasNext);
      nextBtn.classList.toggle('cursor-not-allowed', !pagination.hasNext);
    }
  }
}

async function handleNewsletterSubmit(sendNow = false) {
  const form = selectors.newsletterForm();
  if (!form) return;

  const endpoint = sendNow ? endpoints.sendCampaign : endpoints.saveCampaign;
  const triggerBtn = sendNow ? selectors.sendNowBtn() : selectors.saveDraftBtn();

  const formData = new FormData(form);
  // Ensure textarea value trimmed
  const content = (formData.get('contenu') || '').toString().trim();
  formData.set('contenu', content);

  if (!content) {
    showToast('Veuillez renseigner le contenu de la newsletter.', true);
    return;
  }

  toggleButtonLoading(triggerBtn, true);

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      body: formData,
      credentials: 'include',
    });

    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Erreur lors du traitement.');
    }

    if (sendNow) {
      showToast(payload.message || 'Newsletter envoyée.');
      form.reset();
    } else {
      showToast(payload.message || 'Brouillon sauvegardé.');
    }

    await fetchDashboard();
  } catch (error) {
    console.error('Erreur envoi newsletter:', error);
    showToast(error.message || 'Impossible de finaliser l\'action.', true);
  } finally {
    toggleButtonLoading(triggerBtn, false);
  }
}

async function handlePromotionSubmit() {
  const form = selectors.promotionForm();
  const submitBtn = selectors.promoSubmitBtn();
  if (!form || !submitBtn) return;

  const formData = new FormData(form);
  toggleButtonLoading(submitBtn, true);

  try {
    const response = await fetch(endpoints.createPromotion, {
      method: 'POST',
      body: formData,
      credentials: 'include',
    });

    const payload = await response.json();

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Erreur lors de la création de la promotion.');
    }

    showToast(payload.message || 'Promotion créée.');
    form.reset();
    newsletterState.promotionsPagination.page = 1;
    await fetchDashboard();
  } catch (error) {
    console.error('Erreur promotion newsletter:', error);
    showToast(error.message || 'Impossible de créer la promotion.', true);
  } finally {
    toggleButtonLoading(submitBtn, false);
  }
}

function toggleButtonLoading(button, loading) {
  if (!button) return;
  if (loading) {
    button.dataset.originalHtml = button.innerHTML;
    button.disabled = true;
    button.classList.add('opacity-80', 'cursor-not-allowed');
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Traitement...';
  } else {
    button.disabled = false;
    button.classList.remove('opacity-80', 'cursor-not-allowed');
    if (button.dataset.originalHtml) {
      button.innerHTML = button.dataset.originalHtml;
    }
  }
}

function toPositiveInt(value, fallback) {
  const parsed = Number.parseInt(value, 10);
  if (Number.isNaN(parsed) || parsed <= 0) {
    return fallback;
  }
  return parsed;
}

function toNonNegativeInt(value, fallback = 0) {
  const parsed = Number.parseInt(value, 10);
  if (Number.isNaN(parsed) || parsed < 0) {
    return fallback;
  }
  return parsed;
}

function formatNumber(value) {
  return Number.parseFloat(value || 0)
    .toFixed(1)
    .replace('.0', '');
}

function renderStatusBadge(statut) {
  const normalized = (statut || '').toLowerCase();
  const baseClass =
    'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold';
  switch (normalized) {
    case 'envoyee':
      return `<span class="${baseClass} bg-emerald-100 text-emerald-600"><i class="fa-solid fa-circle-check"></i>Envoyée</span>`;
    case 'planifiee':
      return `<span class="${baseClass} bg-blue-100 text-blue-600"><i class="fa-solid fa-clock"></i>Planifiée</span>`;
    default:
      return `<span class="${baseClass} bg-slate-100 text-slate-500"><i class="fa-solid fa-floppy-disk"></i>Brouillon</span>`;
  }
}

function renderPromotionStatus(statut, expiration) {
  const normalized = (statut || '').toLowerCase();
  const baseClass =
    'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold';
  if (normalized === 'active') {
    return `<span class="${baseClass} bg-emerald-100 text-emerald-600"><i class="fa-solid fa-bolt"></i>Active</span>`;
  }
  if (normalized === 'expiree') {
    return `<span class="${baseClass} bg-slate-200 text-slate-600"><i class="fa-solid fa-hourglass-end"></i>Expirée</span>`;
  }
  return `<span class="${baseClass} bg-slate-100 text-slate-500"><i class="fa-solid fa-circle-exclamation"></i>Désactivée</span>`;
}

function escapeHtml(value) {
  const safe = (value ?? '').toString();
  return safe
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

let toastTimeout;
function showToast(message, isError = false) {
  const toast = selectors.toast();
  if (!toast) return;

  toast.textContent = message;
  toast.classList.remove('hidden');
  toast.classList.toggle('bg-rose-600', !!isError);
  toast.classList.toggle('bg-slate-900', !isError);

  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toast.classList.add('hidden');
  }, 3800);
}

