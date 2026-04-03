// ============================================================
// assets/js/main.js — ServiçosItanhaém
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // ── Mobile hamburger ───────────────────────────────────────
  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobileNav');
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => {
      mobileNav.classList.toggle('open');
    });
  }

  // ── Auto dismiss alerts ────────────────────────────────────
  document.querySelectorAll('.alert[data-autodismiss]').forEach(el => {
    setTimeout(() => el.remove(), 4000);
  });

  // ── Star rating input ──────────────────────────────────────
  const starLabels = document.querySelectorAll('.star-rating label');
  starLabels.forEach((label, idx) => {
    label.addEventListener('mouseover', () => {
      starLabels.forEach((l, i) => {
        l.style.color = i <= idx ? '#f59e0b' : '';
      });
    });
    label.addEventListener('mouseout', () => {
      starLabels.forEach(l => l.style.color = '');
    });
  });

  // ── Image preview on file input ────────────────────────────
  document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
    input.addEventListener('change', () => {
      const target = document.getElementById(input.dataset.preview);
      if (!target || !input.files[0]) return;
      const reader = new FileReader();
      reader.onload = e => { target.src = e.target.result; };
      reader.readAsDataURL(input.files[0]);
    });
  });

  // ── Confirm delete ─────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Tem certeza?')) e.preventDefault();
    });
  });

  // ── Favorite toggle ────────────────────────────────────────
  document.querySelectorAll('.btn-favoritar').forEach(btn => {
    btn.addEventListener('click', async () => {
      const prestadorId = btn.dataset.id;
      try {
        const res  = await fetch(`${SITE_URL}/cliente/favoritar.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `prestador_id=${prestadorId}`
        });
        const data = await res.json();
        if (data.ok) {
          btn.classList.toggle('favorited', data.favoritado);
          btn.querySelector('i').classList.toggle('fas', data.favoritado);
          btn.querySelector('i').classList.toggle('far', !data.favoritado);
        }
      } catch(e) { console.error(e); }
    });
  });

  // ── Chat send (básico) ──────────────────────────────────────
  const chatForm  = document.getElementById('chatForm');
  const chatInput = document.getElementById('chatInput');
  const chatArea  = document.getElementById('messagesArea');
  if (chatForm && chatInput && chatArea) {
    chatForm.addEventListener('submit', async e => {
      e.preventDefault();
      const msg = chatInput.value.trim();
      if (!msg) return;
      chatInput.value = '';

      const url   = chatForm.action;
      const body  = new URLSearchParams(new FormData(chatForm));
      body.set('mensagem', msg);

      const res  = await fetch(url, { method: 'POST', body });
      const data = await res.json();
      if (data.ok) {
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble message-out';
        bubble.innerHTML = `<div>${escHtml(msg)}</div><div class="message-time">agora</div>`;
        chatArea.appendChild(bubble);
        chatArea.scrollTop = chatArea.scrollHeight;
      }
    });
    // Scroll to bottom
    chatArea.scrollTop = chatArea.scrollHeight;
  }

  // ── Search suggestions (simples) ──────────────────────────
  const searchInput = document.querySelector('.header-search input');
  if (searchInput) {
    searchInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const q = searchInput.value.trim();
        if (q) window.location.href = `${SITE_URL}/buscar.php?q=${encodeURIComponent(q)}`;
      }
    });
  }

  // ── Tooltip from data-tooltip ──────────────────────────────
  document.querySelectorAll('[data-tooltip]').forEach(el => {
    el.title = el.dataset.tooltip;
  });

});

// Escape html helper
function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// SITE_URL is injected by PHP inline script on each page
