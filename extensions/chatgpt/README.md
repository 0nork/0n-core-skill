# 0n Core — ChatGPT Custom GPT

Turn ChatGPT into a fully connected 0n Core command center with one token.

## What This Is

A Custom GPT configuration that connects ChatGPT to the 0n Core platform. Users paste their `0n_` token and immediately have access to 1,554 tools across 96 services — from within ChatGPT.

## Files

| File | Purpose |
|------|---------|
| `system-prompt.md` | Full GPT system instructions — paste into GPT Builder |
| `gpt-config.json` | GPT configuration spec (name, description, actions) |
| `openapi.yaml` | OpenAPI 3.0 schema for GPT Actions — import into GPT Builder |

## Setup Instructions

### Step 1: Open GPT Builder

1. Go to [chatgpt.com](https://chatgpt.com)
2. Click your profile → **My GPTs** → **Create a GPT**
3. Switch to the **Configure** tab

### Step 2: Configure Basic Info

- **Name**: `0n Core`
- **Description**: `Universal AI Command Center — 1,554 tools across 96 services. Paste your 0n token, describe any outcome.`
- **Profile picture**: Download from [0ncore.com/logo.png](https://0ncore.com/logo.png)

### Step 3: Add System Instructions

Copy the full contents of `system-prompt.md` and paste into the **Instructions** field.

### Step 4: Add Conversation Starters

Add these starters (from `gpt-config.json`):
- Connect my 0n_ token
- Write a blog post about AI automation
- Scan my site for HIPAA compliance issues
- What can I do with my current plan?

### Step 5: Add GPT Actions

1. Click **Add actions**
2. Click **Import from URL** or paste the contents of `openapi.yaml`
3. The API schema defines all 7 endpoints pointing to `https://www.0ncore.com`
4. Auth type: **None** (tokens are passed in request body for verify, then Bearer for all others)

> **Auth setup**: Under Authentication, select "API Key", type "Bearer", and set the header name to `Authorization`. Users provide their `0n_` token when chatting — the GPT passes it as the Bearer token.

### Step 6: Capabilities

Disable all optional capabilities:
- Web browsing: **Off** (0n Core handles all external calls)
- DALL-E: **Off**
- Code Interpreter: **Off**

### Step 7: Save and Test

1. Click **Save** → **Only me** for testing
2. Open the GPT
3. Test the onboarding flow with a real `0n_` token
4. Verify the token call works and profile loads
5. Set to **Anyone with the link** or **Public** when ready

## How It Works

```
User pastes 0n_ token
      ↓
GPT calls POST /api/auth/verify-token
      ↓
Profile loads (name, plan, services, brand)
      ↓
User describes outcome in plain English
      ↓
GPT routes to correct API action
      ↓
0ncore.com executes via Groq + connected services
      ↓
Result returned to user
```

## API Actions Reference

All actions point to `https://www.0ncore.com`:

| Action | Method | Endpoint |
|--------|--------|----------|
| Verify token | POST | `/api/auth/verify-token` |
| Generate content | POST | `/api/ai/compose` |
| Execute command | POST | `/api/ai/execute` |
| Generate blog | GET | `/api/cron/blog` |
| Generate use case | GET | `/api/cron/use-cases` |
| HIPAA scan | POST | `/api/hipaa/scan` |
| Run add-on | POST | `/api/addons/{slug}/execute` |

## Notes

- All AI generation runs through **Groq** on the 0ncore.com backend — not ChatGPT's own inference
- Never say "GHL", "Go High Level", or "HighLevel" — always "CRM"
- Token format: `0n_` + 48 hex characters

---

Built by [RocketOpp LLC](https://rocketopp.com) — [0ncore.com](https://0ncore.com)
