const SUPABASE_URL = 'https://pwujhhmlrtxjmjzyttwn.supabase.co';
const SUPABASE_ANON = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB3dWpoaG1scnR4am1qenl0dHduIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzA0MjI0NTcsImV4cCI6MjA4NTk5ODQ1N30.VA_AqMDtjfoQUIOsYR6CdZ5O4Akyggg6PgLw1UOnr3g';
const CORE_URL = 'https://www.0ncore.com';

async function verifyToken(token) {
  const res = await fetch(`${SUPABASE_URL}/rest/v1/profiles?access_token=eq.${encodeURIComponent(token)}&select=*`, { headers: { 'apikey': SUPABASE_ANON, 'Authorization': `Bearer ${SUPABASE_ANON}` } });
  const data = await res.json();
  return data?.length ? data[0] : null;
}

async function handleSlashCommand(payload) {
  const { text, team_id, user_id, user_name } = payload;
  const args = text.trim();

  if (args.startsWith('connect ')) {
    const token = args.replace('connect ', '').trim();
    if (!token.startsWith('0n_') || token.length !== 51) return { text: 'Invalid token. Find yours at 0ncore.com/settings' };
    const profile = await verifyToken(token);
    if (!profile) return { text: 'Token not found. Check 0ncore.com/settings' };
    return { response_type: 'ephemeral', blocks: [
      { type: 'header', text: { type: 'plain_text', text: '0n Core Connected' } },
      { type: 'section', text: { type: 'mrkdwn', text: `*${profile.full_name || profile.email}*\n${profile.plan} plan · ${profile.role}\n1,171 tools across 91 services ready.` } }
    ]};
  }

  if (!args || args === 'status') return { text: 'Run `/0n connect 0n_yourtoken` to get started. Get token at 0ncore.com/settings' };

  if (args.startsWith('compose ')) {
    const res = await fetch(`${CORE_URL}/api/ai/compose`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ prompt: args.replace('compose ', ''), tone: 'professional', type: 'general' }) });
    const d = await res.json();
    return { response_type: 'in_channel', blocks: [{ type: 'section', text: { type: 'mrkdwn', text: d.output || d.content || JSON.stringify(d) } }, { type: 'context', elements: [{ type: 'mrkdwn', text: `0n Core · @${user_name}` }] }] };
  }

  if (args === 'blog') { const d = await fetch(`${CORE_URL}/api/cron/blog`).then(r => r.json()); return { text: `Blog generated: ${d.title || JSON.stringify(d)}` }; }

  const d = await fetch(`${CORE_URL}/api/ai/execute`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ command: args }) }).then(r => r.json());
  return { blocks: [{ type: 'section', text: { type: 'mrkdwn', text: d.output || d.message || JSON.stringify(d) } }] };
}

module.exports = { handleSlashCommand, verifyToken };
