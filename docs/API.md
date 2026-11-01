# REST API v1

All API responses are JSON. Authenticate with a short-lived JWT in the Authorization header.

## Obtain a token
A signed-in browser session may POST to /api/v1/token.php with the CSRF token from the current session. The response includes a Bearer token valid for one hour. Configure JWT_SECRET in .env with at least 32 random characters.

## Current user
GET /api/v1/me.php

## List transcripts
GET /api/v1/transcripts.php

## Queue a public URL transcript
POST /api/v1/transcripts.php
Content-Type: application/json

Body: {"url":"https://www.youtube.com/watch?v=...","language":"auto"}

Response: {"id":123,"status":"queued"}

## Notes
API access is user-scoped. A token can access only the authenticated user's transcript records. Tokens are stateless and expire after one hour; rotate JWT_SECRET to invalidate all outstanding tokens. Add rate limiting and token revocation before exposing this API publicly.
