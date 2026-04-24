/* 0n Core v3 — LinkedIn Content Script */

// --- Profile Scraper ---
function scrapeProfile() {
  const get = sel => document.querySelector(sel)?.innerText?.trim() || '';
  const getAll = sel => [...document.querySelectorAll(sel)].map(el => el.innerText.trim()).filter(Boolean);

  const name = get('h1.text-heading-xlarge, h1[class*="name"]') || get('h1');
  const headline = get('.text-body-medium.break-words, [class*="headline"]');
  const location = get('.text-body-small.inline.t-black--light.break-words, [class*="location"]');
  const about = get('#about ~ div .full-width, section[id="about"] .display-flex span[aria-hidden]');

  const experience = [...document.querySelectorAll('#experience ~ div .pvs-list__paged-list-item, section[id="experience"] li')].map(el => ({
    title: el.querySelector('[class*="title"]')?.innerText?.trim() || '',
    company: el.querySelector('[class*="subtitle"]')?.innerText?.trim() || '',
    duration: el.querySelector('[class*="caption"]')?.innerText?.trim() || ''
  })).filter(e => e.title);

  const education = [...document.querySelectorAll('#education ~ div .pvs-list__paged-list-item, section[id="education"] li')].map(el => ({
    school: el.querySelector('[class*="title"]')?.innerText?.trim() || '',
    degree: el.querySelector('[class*="subtitle"]')?.innerText?.trim() || ''
  })).filter(e => e.school);

  const skills = getAll('#skills ~ div .pvs-list__paged-list-item [class*="title"], section[id="skills"] [class*="name"]').slice(0, 20);

  const url = window.location.href;
  const avatar = document.querySelector('img.profile-photo-edit__preview, img[class*="profile-photo"], .pv-top-card__photo img')?.src || '';

  return { name, headline, location, about, experience, education, skills, url, avatar, scrapedAt: new Date().toISOString() };
}

// --- Feed Scanner ---
function scanFeed() {
  return [...document.querySelectorAll('[data-urn*="activity"], .feed-shared-update-v2')].slice(0, 10).map(post => ({
    author: post.querySelector('[class*="actor-name"], .update-components-actor__name')?.innerText?.trim() || '',
    content: post.querySelector('[class*="commentary"], .feed-shared-text')?.innerText?.trim()?.slice(0, 300) || '',
    reactions: post.querySelector('[class*="social-counts"], .social-details-social-counts')?.innerText?.trim() || '',
    urn: post.dataset.urn || ''
  })).filter(p => p.content);
}

// --- Search Results Scraper ---
function scrapeSearchResults() {
  return [...document.querySelectorAll('.reusable-search__result-container, [class*="search-result"]')].map(el => ({
    name: el.querySelector('[class*="actor-name"], .entity-result__title-text a span[aria-hidden]')?.innerText?.trim() || '',
    headline: el.querySelector('[class*="entity-result__primary-subtitle"], .entity-result__primary-subtitle')?.innerText?.trim() || '',
    location: el.querySelector('[class*="entity-result__secondary-subtitle"]')?.innerText?.trim() || '',
    profileUrl: el.querySelector('a[href*="/in/"]')?.href || ''
  })).filter(r => r.name);
}

// --- Text Injectors ---
function injectText(text) {
  const targets = [
    document.querySelector('.ql-editor[contenteditable="true"]'),
    document.querySelector('[contenteditable="true"][role="textbox"]'),
    document.querySelector('textarea[name="message"]'),
    document.querySelector('.msg-form__contenteditable'),
    document.querySelector('[data-placeholder*="message"]'),
    document.querySelector('[data-placeholder*="comment"]'),
    document.querySelector('[data-placeholder*="post"]')
  ];
  const el = targets.find(Boolean);
  if (!el) return false;
  el.focus();
  if (el.tagName === 'TEXTAREA') {
    el.value = text;
    el.dispatchEvent(new Event('input', { bubbles: true }));
  } else {
    document.execCommand('selectAll', false, null);
    document.execCommand('insertText', false, text);
  }
  return true;
}

// --- Floating Toolbar ---
function injectFAB() {
  if (document.getElementById('on-fab')) return;

  const fab = document.createElement('div');
  fab.id = 'on-fab';
  fab.innerHTML = `
    <div class="on-f-toggle" id="on-f-toggle" title="0n Core">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6EE05A" stroke-width="2.5">
        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
      </svg>
    </div>
    <div class="on-f-menu" id="on-f-menu">
      <div class="on-f-btn" id="on-btn-scrape" title="Scrape Profile">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6EE05A" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div class="on-f-btn" id="on-btn-compose" title="AI Compose">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6EE05A" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      </div>
      <div class="on-f-btn" id="on-btn-scan" title="Scan Feed">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6EE05A" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
      </div>
      <div class="on-f-btn" id="on-btn-panel" title="Open Panel">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6EE05A" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
      </div>
      <div class="on-f-btn" id="on-btn-dash" title="0nCore Dashboard">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6EE05A" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      </div>
    </div>
  `;
  document.body.appendChild(fab);

  const menu = document.getElementById('on-f-menu');
  let open = false;
  document.getElementById('on-f-toggle').addEventListener('click', () => {
    open = !open;
    menu.style.display = open ? 'flex' : 'none';
  });

  document.getElementById('on-btn-scrape').addEventListener('click', () => {
    const data = scrapeProfile();
    chrome.runtime.sendMessage({ type: 'PANEL_ACTION', action: 'scraped', data });
    showToast('Profile scraped — check 0n panel');
  });

  document.getElementById('on-btn-compose').addEventListener('click', () => {
    const data = window.location.href.includes('/in/') ? scrapeProfile() : {};
    chrome.runtime.sendMessage({ type: 'PANEL_ACTION', action: 'compose', context: data });
    showToast('Composing in 0n panel...');
  });

  document.getElementById('on-btn-scan').addEventListener('click', () => {
    const data = scanFeed();
    chrome.runtime.sendMessage({ type: 'PANEL_ACTION', action: 'feed', data });
    showToast(`Scanned ${data.length} posts`);
  });

  document.getElementById('on-btn-panel').addEventListener('click', () => {
    chrome.runtime.sendMessage({ type: 'OPEN_PANEL' });
  });

  document.getElementById('on-btn-dash').addEventListener('click', () => {
    chrome.runtime.sendMessage({ type: 'OPEN_TAB', url: 'https://www.0ncore.com/console' });
  });
}

function showToast(msg) {
  const t = document.createElement('div');
  t.style.cssText = 'position:fixed;bottom:88px;right:20px;z-index:99999;background:rgba(13,17,23,.96);border:1px solid rgba(110,224,90,.4);color:#6EE05A;padding:8px 14px;border-radius:8px;font-size:13px;font-family:system-ui,sans-serif;box-shadow:0 4px 16px rgba(0,0,0,.5);pointer-events:none;';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2800);
}

// --- Message listener from background ---
chrome.runtime.onMessage.addListener((msg, sender, reply) => {
  if (msg.type === 'DO_SCRAPE') { reply(scrapeProfile()); return true; }
  if (msg.type === 'DO_INJECT') { reply({ ok: injectText(msg.text) }); return true; }
  if (msg.type === 'DO_SCAN_FEED') { reply(scanFeed()); return true; }
  if (msg.type === 'DO_SCRAPE_SEARCH') { reply(scrapeSearchResults()); return true; }
});

// --- SPA Navigation Observer ---
let lastUrl = location.href;
new MutationObserver(() => {
  if (location.href !== lastUrl) {
    lastUrl = location.href;
    setTimeout(injectFAB, 1200);
  }
}).observe(document.body, { childList: true, subtree: true });

// Init
setTimeout(injectFAB, 1000);
