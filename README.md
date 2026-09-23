
# VIRA Agent

VIRA is a guarded Laravel 12 foundation for researching, scoring, scripting, producing, approving, publishing, and learning from short-form social video.

This package implements the first vertical slice from `VIRA_Implementation_JEM.md`. It deliberately uses safe local adapters by default: it will not spend generation credits or post to a real social account until you add provider-specific implementations and credentials.

## Implemented

- Laravel 12 modular source layout.
- PostgreSQL schema for channels, topics, content projects, scripts, media, approvals, publications, attempts, provider usage, and audit decisions.
- Weighted topic scoring with validation and qualification threshold.
- Transactional topic selection.
- Versioned structured script generation.
- Deterministic reasoner for local/test operation.
- Video-provider contract and manual handoff provider.
- Timed SRT caption generation.
- FFmpeg composition service for 1080 × 1920 H.264/AAC output.
- Immutable approval payload hashing.
- Approval, rejection, expiry, and invalidation logic.
- Platform publication records and deterministic idempotency keys.
- Locked, retryable publishing job.
- Null publisher that completes the workflow without external side effects.
- Idempotent daily guarded automation: ten candidates through four platform approval bundles.
- Daily/monthly budget reservation with fail-closed caps.
- Automated evidence/claim and hostile-language quality gate.
- Private-by-default, synthetic-media-labelled metadata for every platform.
- Due-publication scheduler with platform-isolated jobs.
- 30m/2h/24h/7d metric snapshots, normalisation, QAG1000, and performance diagnosis.
- Operator bearer-token middleware.
- Docker Compose stack with app, Horizon worker, scheduler, PostgreSQL, Redis, and FFmpeg.
- Unit and feature tests for the core guarded workflow.
- HTTP request examples.

## Deliberately not activated

- Real OpenAI calls.
- VideoGen or another paid video-generation API.
- Voice provider API.
- YouTube OAuth/upload.
- TikTok Direct Post.
- Instagram/Facebook Reels publishing.
- Live platform analytics collectors (manual/API snapshot ingestion is implemented).
- Full autopilot.

Those pieces require vendor accounts, OAuth application review, current scopes, live credentials, and explicit approval before external side effects. The contracts and state model are ready for their adapters.

## Quick start with Docker

Requirements: Docker Engine with Docker Compose v2.

```bash
cp .env.example .env
```

Set `VIRA_OPERATOR_TOKEN` in `.env` to a long random value. Then run:

```bash
docker compose build
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
docker compose up -d
```

Open:

```text
GET http://localhost:8080/api/v1/health
```

Protected endpoints require:

```text
Authorization: Bearer <VIRA_OPERATOR_TOKEN>
```

The seeded channel ID can be obtained with:

```bash
docker compose exec app php artisan tinker --execute="echo App\\Models\\Channel::first()->id;"
```

Use `docs/api-examples.http` to exercise the full workflow.

## Run tests

```bash
make test
```

Equivalent command:

```bash
docker compose run --rm \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e QUEUE_CONNECTION=sync \
  app php artisan test
```

## Automated workflow

```text
Create scored topic
  → select qualified topic
  → create content project
  → generate versioned script
  → prepare platform publication
  → calculate immutable approval hash
  → approve exact review payload
  → dispatch idempotent publishing job
  → null publisher records a side-effect-free result
```

Any change to script, metadata, media checksum, destination, or schedule should create a new review payload and approval. Never mutate an approved packet in place.

To execute the complete safe pipeline, call `POST /api/v1/channels/{channel}/automation/run`. Review the returned packets, then call `POST /api/v1/automation/{run}/approve`. The first call is idempotent per channel and calendar day. See `docs/api-examples.http` for metric ingestion.

## Important defaults

```dotenv
VIRA_REQUIRE_APPROVAL=true
VIRA_AUTOPILOT_ENABLED=false
VIRA_REASONER_DRIVER=deterministic
VIRA_VIDEO_DRIVER=manual
VIRA_PUBLISHER_DRIVER=null
```

Keep these values until real provider adapters have passed contract tests in staging.

## Code map

```text
app/Modules/Research       topic scoring and selection
app/Modules/Editorial      structured reasoning and script versioning
app/Modules/Production     video provider, captions and FFmpeg
app/Modules/Approval       immutable review and decisions
app/Modules/Publishing     publication contract and idempotency
app/Jobs                   queued side effects
app/Http/Controllers       API endpoints
database/migrations        PostgreSQL schema
tests                      unit and guarded end-to-end workflow
docs/api-examples.http     executable request examples
```

## Adding a real reasoner

Implement `StructuredReasoner`, validate the model response against a fixed JSON schema, and bind the adapter in `ViraServiceProvider`. Persist prompt version, model, token usage, estimated cost, and output decision. Never accept free-form model output directly into a publishing payload.

## Adding VideoGen or another generator

Implement `VideoProvider` using documented server-to-server authentication. Do not reuse a browser session or assume a ChatGPT MCP connection is a backend credential. The adapter must:

1. expose capabilities;
2. submit one scene;
3. poll without holding a worker;
4. retrieve and checksum the asset;
5. record cost and provenance;
6. support cancellation when the provider allows it.

The manual provider keeps this workflow usable until a supported backend API is confirmed.

## Adding a social publisher

Implement `SocialPublisher` per platform rather than one multi-platform class. A production adapter must:

- validate scopes, account, privacy, media and metadata;
- reconcile ambiguous timeouts before retrying;
- persist the remote request/post ID immediately;
- honour AI-generated/synthetic-media disclosures;
- return retryable versus terminal failures;
- pass contract tests against recorded, sanitised fixtures;
- never bypass the approval hash gate.

Recommended adapter order:

1. YouTube private/unlisted pilot.
2. TikTok private Direct Post during audit.
3. Instagram Reels container/poll/publish.
4. Facebook Reels or explicit manual handoff.

## Production hardening still required

- Replace the single operator token with user authentication, MFA, and roles.
- Move secrets to a managed secret store.
- Use managed PostgreSQL/Redis and private object storage.
- Add HTTPS, rate limiting, request IDs, Sentry, and structured logs.
- Implement cost reservation before any paid job.
- Add rights/provenance review screens.
- Add OAuth token encryption and reauthorisation workflows.
- Add social app audits and provider contract tests.
- Build the React/Inertia control room.
- Add metric collection, baselines, experiments, and learning proposals.

## Verification status

The source was assembled in an environment without PHP, Composer, or Docker. It therefore received structural review and archive verification here, but not an executed Composer install or PHPUnit run. Run `make test` as the first action after extraction. Treat any dependency/API version issue as a normal integration task before production use.

## Safety statement

VIRA optimises measurable content performance; it does not guarantee virality. Keep guarded approval enabled for sensitive relationship content. Describe behaviours, avoid universal gender claims, do not diagnose individuals, and do not publish identifiable allegations.
