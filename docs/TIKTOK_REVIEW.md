# TikTok integration and review demo

## TikTok developer configuration

Configure the TikTok developer app with:

- Login Kit for Web.
- Content Posting API.
- Redirect URI: `https://vira.synteric.co.uk/integrations/tiktok/callback`.
- Scopes: `user.info.basic`, `video.upload`, and `video.publish`.
- Verified URL prefix or domain covering `https://vira.synteric.co.uk/media/`.

Store the client key and secret only in the production `.env`. Never place the
secret in a recording, source control, browser code, or support message.

The operator signs in at `/operator/login` using `VIRA_OPERATOR_TOKEN`. For an
app review, use a temporary review token and rotate it afterward.

## Recording sequence

1. Open `/` and click **Open VIRA control room**.
2. Sign in and show the dashboard.
3. Click **Connect TikTok**, complete TikTok OAuth, and show the returned
   profile and granted scopes.
4. Select an existing VIRA project and upload its final MP4 or MOV. Confirm the
   media rights checkbox. The upload is limited to 50 MB.
5. Select the uploaded media and play it in the dashboard preview. Show the
   connected TikTok destination, caption, hashtags, and AI-content disclosure.
6. Click **Send to TikTok as Draft**. Show VIRA's publish ID and status, then
   open TikTok's inbox notification and continue through TikTok's editing and
   publishing flow.
7. Return to VIRA for the separate Direct Post demonstration. Show the privacy
   choices and interaction settings returned by TikTok creator-info.
8. Tick the explicit publication consent and click **Publish to TikTok**. Show
   status polling and the resulting private or public post permitted by the
   app's current audit state.

Do not imply that a draft upload is a Direct Post. Do not hide TikTok's consent,
privacy, disclosure, or inbox-completion steps.

## Implemented endpoints

```text
GET    /integrations/tiktok/connect
GET    /integrations/tiktok/callback
POST   /integrations/tiktok/{account}/refresh
DELETE /integrations/tiktok/{account}
POST   /tiktok/drafts
POST   /tiktok/publish
POST   /tiktok/posts/{post}/refresh
```

VIRA uses TikTok's current OAuth token endpoint, user-info endpoint,
creator-info query, inbox video initialisation, Direct Post initialisation, and
publish-status fetch endpoint. Provider responses and publish IDs are persisted
without logging access or refresh tokens.

Official references:

- https://developers.tiktok.com/doc/login-kit-web
- https://developers.tiktok.com/doc/oauth-user-access-token-management
- https://developers.tiktok.com/doc/content-posting-api-reference-upload-video
- https://developers.tiktok.com/doc/content-posting-api-reference-direct-post
- https://developers.tiktok.com/doc/content-posting-api-reference-query-creator-info
- https://developers.tiktok.com/doc/content-posting-api-reference-get-video-status
