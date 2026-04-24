# 0n Core — Google Gemini Gem

Turn Google Gemini into a fully connected 0n Core command center with one token.

## What This Is

A Gem (custom Gemini) configuration that connects Gemini to the 0n Core platform via function calling. Users paste their `0n_` token and immediately have access to 1,554 tools across 96 services — from within Gemini.

## Files

| File | Purpose |
|------|---------|
| `system-prompt.md` | Full Gem system instructions — paste into Gem Builder |
| `gem-config.json` | Gem configuration spec (model, temperature, tools) |
| `function-declarations.json` | Gemini function calling definitions for all 5 core actions |

## Setup Instructions

### Option A: Google AI Studio (Recommended for Testing)

1. Go to [aistudio.google.com](https://aistudio.google.com)
2. Click **Create new** → **Create new Gem** (or use the System Instructions panel)
3. Paste the contents of `system-prompt.md` into the **System instructions** field
4. Under **Tools**, enable **Function calling**
5. Add function declarations from `function-declarations.json`
6. Set model to `gemini-2.0-flash` (or latest available)
7. Save and test

### Option B: Gemini App (gemini.google.com)

1. Go to [gemini.google.com](https://gemini.google.com)
2. Click **Gems** in the left sidebar → **New Gem**
3. **Name**: `0n Core`
4. **Description**: `Universal AI Command Center — 1,554 tools across 96 services. Paste your 0n token, describe any outcome.`
5. Copy the contents of `system-prompt.md` into the instructions field
6. Save and test

> Note: Gemini App Gems don't yet support custom function declarations via the UI. For full function calling support, use Google AI Studio or the Gemini API directly.

### Option C: Gemini API (for developers)

```python
import google.generativeai as genai
import json

genai.configure(api_key="YOUR_GEMINI_API_KEY")

# Load function declarations
with open("function-declarations.json") as f:
    declarations = json.load(f)

# Load system prompt
with open("system-prompt.md") as f:
    system_prompt = f.read()

model = genai.GenerativeModel(
    model_name="gemini-2.0-flash",
    system_instruction=system_prompt,
    tools=declarations["function_declarations"]
)

chat = model.start_chat(enable_automatic_function_calling=False)
```

---

## Function Declarations

Five core functions are defined in `function-declarations.json`:

| Function | Description |
|----------|-------------|
| `verify_token` | Verify 0n_ token → get user profile, plan, services |
| `compose` | Generate content (blog, email, social, etc.) via Groq backend |
| `execute` | Natural language execution against connected services |
| `generate_blog` | Generate full SEO blog post |
| `hipaa_scan` | HIPAA compliance scan on any URL |

---

## How Function Calling Works

When Gemini calls a function, you (or your integration layer) need to:

1. Receive the `functionCall` response from Gemini
2. Make the actual HTTP request to `https://www.0ncore.com`
3. Return the result as a `functionResponse` back to Gemini
4. Gemini uses the response to form its reply

### HTTP Request Pattern

```javascript
// Example: verify_token function call handler
async function handleFunctionCall(name, args, token) {
  const headers = {
    "Content-Type": "application/json",
    "Authorization": `Bearer ${token}`
  };

  const endpoints = {
    verify_token: { method: "POST", path: "/api/auth/verify-token" },
    compose:      { method: "POST", path: "/api/ai/compose" },
    execute:      { method: "POST", path: "/api/ai/execute" },
    generate_blog: { method: "GET", path: "/api/cron/blog" },
    hipaa_scan:   { method: "POST", path: "/api/hipaa/scan" }
  };

  const { method, path } = endpoints[name];
  const url = `https://www.0ncore.com${path}`;

  const response = await fetch(url, {
    method,
    headers,
    body: method !== "GET" ? JSON.stringify(args) : undefined
  });

  return response.json();
}
```

---

## How It Works

```
User pastes 0n_ token
      ↓
Gemini calls verify_token function
      ↓
Your code → POST https://www.0ncore.com/api/auth/verify-token
      ↓
Profile loads (name, plan, services, brand)
      ↓
User describes outcome in plain English
      ↓
Gemini calls appropriate function
      ↓
Your code → 0ncore.com API
      ↓
Result returned to Gemini → user
```

---

## API Reference

All endpoints at `https://www.0ncore.com`:

| Function | Method | Endpoint |
|----------|--------|----------|
| verify_token | POST | `/api/auth/verify-token` |
| compose | POST | `/api/ai/compose` |
| execute | POST | `/api/ai/execute` |
| generate_blog | GET | `/api/cron/blog` |
| hipaa_scan | POST | `/api/hipaa/scan` |

---

## Notes

- All AI generation runs through **Groq** on the 0ncore.com backend
- Never say "GHL", "Go High Level", or "HighLevel" — always "CRM"
- Token format: `0n_` + 48 hex characters
- For production, handle function calls server-side to keep the `0n_` token secure

---

Built by [RocketOpp LLC](https://rocketopp.com) — [0ncore.com](https://0ncore.com)
