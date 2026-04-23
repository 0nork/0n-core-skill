---
name: 0n-core
description: Universal 0n onboarding skill. Paste your 0n token, connect to 91 services and 1171 tools instantly. Works across Claude Code, Chrome extension, Slack app, WordPress plugin, Dispatch, and any future integration. Guided HSM onboarding, token verification, service discovery, and AI execution — all from one install.
---

# 0n Core — Universal Skill

> Stop building workflows. Start describing outcomes.

This skill turns any Claude environment into a fully connected **0n Core command center**. One token. 91 services. 1,171 tools. Instant access.

## What This Skill Does

When a user invokes `/0n` or installs this skill, it:

1. **Onboards** — Guided 3-step flow: paste token → discover services → execute first command
2. **Verifies** — Validates the `0n_` token against the 0nCore Supabase profiles table
3. **Loads** — Pulls the user's profile, plan, connected services, brand settings, and AI provider
4. **Enables** — Exposes all available 0nMCP tools based on the user's account
5. **Delivers the HSM** — Within 60 seconds, the user describes an outcome and watches 0n execute it

## Non-Negotiables

- **ALL AI calls → Groq** (llama-3.3-70b-versatile). NEVER Anthropic API. Claude Code is free — external API calls are NOT.
- **NEVER say "GHL", "Go High Level", "HighLevel"** — always "CRM" or "ROCKET"
- **NEVER use emoji as icons in UI** — Lucide React icons only
- **Brand colors**: bg=#0d1117, card=#161b22, border=#30363d, primary=#6EE05A
- **Token format**: always `0n_` prefix, 48-char hex
- **Push to main** — no branches, no PRs. Vercel auto-deploys.

## Universal Onboarding Flow

This flow is identical across ALL platforms. The medium changes, the experience doesn't.

### Step 1: Token Entry
User pastes `0n_` token. Validated against Supabase:
```
GET https://pwujhhmlrtxjmjzyttwn.supabase.co/rest/v1/profiles?access_token=eq.{TOKEN}&select=*
Headers: apikey + Authorization with anon key
```

### Step 2: Service Discovery
Shows user's connected ecosystem: CRM, Stripe, AI provider, brand settings, plan, role.

### Step 3: HSM (Holy Shit Moment)
User executes their first natural language command against a live service. Under 60 seconds from token paste to "holy shit."

## Platform Integration

| Platform | Auth Method | Session Storage |
|----------|------------|-----------------|
| Claude Code | Paste token in `/0n login` | ~/.0n/session.json |
| Chrome Extension | Paste in side panel | chrome.storage.local |
| Slack | `/0n connect 0n_xxx` | Supabase |
| WordPress | Settings page | wp_options |
| Dispatch | Session context | Dispatch state |

## API Reference

Base: https://www.0ncore.com

| Endpoint | Method | Purpose |
|----------|--------|---------|
| /api/cron/blog | GET | Generate blog post |
| /api/cron/use-cases | GET | Generate use case |
| /api/hipaa/scan | POST | HIPAA scan |
| /api/provision/cro9 | POST | CRO9 provisioning |
| /api/forms/submit | POST | Form submission |
| /api/remote/agents/{id}/run | POST | Run agent |
| /api/crm/brand-board | GET | Brand sync |
| /api/ai/compose | POST | AI content generation |
| /api/ai/execute | POST | Natural language execution |

## Supabase

Project: pwujhhmlrtxjmjzyttwn
URL: https://pwujhhmlrtxjmjzyttwn.supabase.co
Anon Key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB3dWpoaG1scnR4am1qenl0dHduIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzA0MjI0NTcsImV4cCI6MjA4NTk5ODQ1N30.VA_AqMDtjfoQUIOsYR6CdZ5O4Akyggg6PgLw1UOnr3g

*Built by 0nORK — RocketOpp LLC — https://0ncore.com*
