/* 0n Core v3 — Background Service Worker */
const SUPABASE_URL = 'https://pwujhhmlrtxjmjzyttwn.supabase.co';
const SUPABASE_ANON = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB3dWpoaG1scnR4am1qenl0dHduIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzA0MjI0NTcsImV4cCI6MjA4NTk5ODQ1N30.VA_AqMDtjfoQUIOsYR6CdZ5O4Akyggg6PgLw1UOnr3g';
const CORE_URL = 'https://www.0ncore.com';

chrome.action.onClicked.addListener(tab => chrome.sidePanel.open({ tabId: tab.id }));

chrome.runtime.onInstalled.addListener(() => {
  chrome.contextMenus.create({ id: '0n-compose', title: '0n: Compose reply', contexts: ['selection'] });
  chrome.contextMenus.create({ id: '0n-scrape', title: '0n: Scrape this profile', contexts: ['page'], documentUrlPatterns: ['https://www.linkedin.com/in/*'] });
  chrome.contextMenus.create({ id: '0n-execute', title: '0n: Execute with AI', contexts: ['selection'] });
});

chrome.contextMenus.onClicked.addListener((info, tab) => {
  if (info.menuItemId === '0n-compose') chrome.runtime.sendMessage({ type: 'PANEL_ACTION', action: 'compose', text: info.selectionText });
  else if (info.menuItemId === '0n-scrape') chrome.tabs.sendMessage(tab.id, { type: 'DO_SCRAPE' }, data => chrome.runtime.sendMessage({ type: 'PANEL_ACTION', action: 'scraped', data }));
  else if (info.menuItemId === '0n-execute') chrome.runtime.sendMessage({ type: 'PANEL_ACTION', action: 'execute', text: info.selectionText });
});

chrome.runtime.onMessage.addListener((msg, sender, reply) => {
  if (msg.type === 'VERIFY_TOKEN') { verifyToken(msg.token).then(reply); return true; }
  if (msg.type === 'SUPABASE') { supabaseQuery(msg.table, msg.select, msg.filter, msg.limit).then(reply); return true; }
  if (msg.type === 'CORE_API') { coreApi(msg.path, msg.method, msg.body).then(reply); return true; }
  if (msg.type === 'TO_CONTENT') { chrome.tabs.query({ active: true, currentWindow: true }, tabs => { if (tabs[0]) chrome.tabs.sendMessage(tabs[0].id, msg.payload, reply); }); return true; }
  if (msg.type === 'OPEN_TAB') chrome.tabs.create({ url: msg.url });
});

async function verifyToken(token) {
  try {
    const res = await fetch(`${SUPABASE_URL}/rest/v1/profiles?access_token=eq.${encodeURIComponent(token)}&select=*`, { headers: { 'apikey': SUPABASE_ANON, 'Authorization': `Bearer ${SUPABASE_ANON}` } });
    const data = await res.json();
    if (!data || data.length === 0) return { error: 'Invalid token' };
    return { profile: data[0] };
  } catch (e) { return { error: e.message }; }
}

async function supabaseQuery(table, select = '*', filter = {}, limit = 50) {
  let url = `${SUPABASE_URL}/rest/v1/${table}?select=${encodeURIComponent(select)}&limit=${limit}`;
  for (const [k, v] of Object.entries(filter)) url += `&${k}=eq.${encodeURIComponent(v)}`;
  try { const res = await fetch(url, { headers: { 'apikey': SUPABASE_ANON, 'Authorization': `Bearer ${SUPABASE_ANON}` } }); return await res.json(); } catch (e) { return { error: e.message }; }
}

async function coreApi(path, method = 'GET', body) {
  try { const opts = { method, headers: { 'Content-Type': 'application/json' } }; if (body) opts.body = JSON.stringify(body); const res = await fetch(`${CORE_URL}${path}`, opts); return await res.json(); } catch (e) { return { error: e.message }; }
}
