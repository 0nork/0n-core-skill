# Universal Onboarding Flow

This script is platform-agnostic. Every 0n integration follows this exact sequence.

## Detection
- Claude Code: ~/.0n/session.json
- Chrome Extension: chrome.storage.local.get('0n_profile')
- Slack: workspace config in Supabase
- WordPress: get_option('0n_core_profile')
- Dispatch: session state

If no session → onboarding. If expired → re-auth.

## Step 1: Welcome + Token
Paste 0n_ token. Validate: starts with 0n_, 51 chars. Verify against Supabase profiles. On fail: "Check 0ncore.com/settings."

## Step 2: Discovery
Show connected services: CRM, Stripe, AI provider, brand, plan, role. Show "1,171 tools across 91 services."

## Step 3: HSM
User executes first command. Contextual suggestions based on connected services. Target: < 60 seconds from paste to wow.

## Session Schema
```json
{"access_token":"0n_xxx","user_id":"uuid","email":"","full_name":"","plan":"free","role":"member","core_ai_provider":"groq","brand_color":"#6EE05A","platform":"claude-code","onboarding_complete":true}
```
