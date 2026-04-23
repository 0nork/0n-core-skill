# 0nCore API Reference

Base: https://www.0ncore.com

| Endpoint | Method | Purpose |
|----------|--------|---------|
| /api/cron/blog | GET | Generate blog |
| /api/cron/use-cases | GET | Generate use case |
| /api/hipaa/scan | POST | HIPAA scan |
| /api/provision/cro9 | POST | CRO9 provisioning |
| /api/forms/submit | POST | Form submission |
| /api/remote/agents/{id}/run | POST | Run agent |
| /api/crm/brand-board | GET | Brand sync |
| /api/ai/compose | POST | AI generation |
| /api/ai/execute | POST | NL execution |

## Token Verification
```
GET https://pwujhhmlrtxjmjzyttwn.supabase.co/rest/v1/profiles?access_token=eq.{TOKEN}&select=*
```
