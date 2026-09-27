# Implementation status

## Delivered automated vertical slice

The repository now implements a guarded, side-effect-free daily automation path. One API call creates ten ranked candidates, selects the strongest, generates a versioned script, reserves budget, runs the claim/policy gate, builds a deterministic master manifest and captions, and creates independent private approval packets for YouTube, TikTok, Instagram, and Facebook. A second explicit approval call schedules all four bundles. The scheduler and idempotent publisher handle due work independently.

Metric snapshots can be ingested for the 30m, 2h, 24h, and 7d windows. VIRA normalises available fields, calculates QAG1000, records uncertainty, and produces a controlled next-experiment recommendation. The deterministic reasoner, manual media route, and null publisher make this complete lifecycle reproducible without paid credentials or external side effects.

## TikTok review vertical slice

VIRA now includes a protected browser control room, TikTok Login Kit OAuth,
encrypted access and refresh tokens, connected creator identity, live
creator-info settings, 50 MB review-video upload, in-browser preview, inbox
draft upload, separately consented Direct Post, AI-content disclosure, privacy
and interaction controls, publish IDs, and manual/automatic status polling.

Automated tests fake TikTok's HTTP API. Live use remains gated by production
credentials, granted `video.upload` and `video.publish` scopes, a verified media
URL prefix, an authorised reviewer account, and TikTok's audit restrictions.

## Automatic media production

The control room can queue automatic production for any project with a current
script. OpenAI generates one cinematic vertical scene image per beat,
ElevenLabs synthesises narration, VIRA creates timed SRT captions, and FFmpeg
renders a 1080×1920 H.264/AAC master. Generation requests and final asset
provenance are persisted. Live operation requires provider credentials and an
FFmpeg-enabled queue worker; tests use HTTP fakes and spend no provider credits.

## Safety invariants

- Daily automation is idempotent per channel.
- Every platform has its own publication and immutable approval hash.
- High-risk unsupported claims and hostile generalisations fail closed.
- Paid operations reserve against daily and monthly caps before starting.
- Every generated bundle defaults to private visibility and synthetic-media disclosure.
- Real public publishing and full autopilot remain disabled until credentials, audits, and the Section 23 promotion gates are satisfied.

## Next engineering milestone

Implement a YouTube adapter with OAuth, resumable `videos.insert`, private/unlisted staging uploads, remote status reconciliation, and contract tests. Keep the null publisher as the CI default. External adapters are the only deliberately unactivated capability.

## External blockers

- Social developer application review and production scopes.
- Selection of a server-to-server video API.
- Voice and music licensing decisions.
- Production object storage and secret management.

## Definition of the first live milestone

One evidence-backed topic becomes one approved 45-second master, uploads privately to YouTube exactly once, and receives a traceable 24-hour performance report.
