# openCal Mobile — Implementation Plan

> Status: **Phase 1 ✅, Phase 2 ✅, Phase 3 ✅ (native code written, unverified).**
> Building/running the APK still needs local Android tooling (see below).

## Goal

Ship an **Android app** for openCal. openCal is a self-hosted, multi-user calorie
tracker: users run their own Laravel instance and their data lives on **their**
server. The mobile app is a **thin client** — one generic APK where the user
enters their self-hosted URL on first launch.

## Key decisions

| Decision | Choice |
| --- | --- |
| Data model | Thin client → self-hosted server (data stays on the server) |
| Wrapper | PHP-native: **NativePHP for Mobile** (`nativephp/mobile`, free/MIT since v3) |
| Platforms | Android only (for now) |
| Distribution | Prebuilt APK/AAB + "enter your server URL" first-run screen; PWA as fallback |

> **Note on "Boson":** Boson (`bosonphp.com`) is desktop-only (Windows/macOS/
> Linux) and cannot produce an Android APK. The PHP-native mobile tool is
> **NativePHP for Mobile**. If desktop is wanted later, that is a separate track.

## Architecture

One generic APK. On first launch the user enters their self-hosted URL
(e.g. `https://cal.example.com`). The WebView loads the existing Inertia/React
SPA from that URL. All PHP and data remain on the user's server.

Two ways to do this with NativePHP Mobile:

1. **`aureuserp/nativephp-remote`** (community, May 2026) — patches the shell so
   the WebView routes to a remote server instead of on-device PHP. This matches
   our model exactly.
2. **v4 `<native:webview src="https://…">`** — but v4 is still RC; pin **v3**
   (stable, free) for now.

> **Risk:** NativePHP's "hosted remote" mode is young and not first-party. If it
> proves immature, fall back to **Capacitor** — identical thin-client result,
> non-PHP but far more battle-tested. The rest of this plan is wrapper-agnostic.

## Phases

### 1. Backend readiness ✅ done
- Added `GET /api/instance` (public) returning `{ name, version }`
  (`app/Http/Controllers/Api/InstanceController.php`, route `api.instance`).
- Added `opencal.version` config (`OPENCAL_VERSION` env, default `1.0.0`).
- Documented `SESSION_SECURE_COOKIE` / `SESSION_SAME_SITE` in `.env.example`.
- Tests: `tests/Feature/InstanceTest.php`.

No DB/schema changes were required — the database stays on the server.

### 2. Shell spike ✅ done (scaffold + thin-client patch)
- Installed `nativephp/mobile` `^3.3` (v3.3.9 — supports guzzle 8) and
  `aureuserp/nativephp-remote` (v0.1.0).
- `php artisan native:install android` scaffolded `nativephp/android` (Kotlin +
  Gradle) and generated `config/nativephp.php`.
- `php artisan nativephp:patch-remote` patched the shell for hosted-remote mode:
  remote `start_url` loading, HTTPS enforcement, camera permission handling,
  and CAMERA/VIBRATE permissions in `AndroidManifest.xml`.
- Added `RECORD_AUDIO` (voice input) to the manifest + `config/nativephp-remote.php`.
- Set `NATIVEPHP_APP_ID=com.opencal.app`; set app label to `openCal`.
- Added `/nativephp` to `.gitignore` (the dir is ephemeral — rebuilt with
  `native:install --force`, then re-run `nativephp:patch-remote`).

**Not yet runnable on this machine** — building/running requires local tooling
that isn't installed (see "Environment prerequisites" below).

### 3. Server-URL configuration ✅ done (native code, unverified)
Implemented runtime URL config in the native shell:

- `LaravelEnvironment.kt` — added `getConfiguredServerUrl` / `setConfiguredServerUrl`
  / `clearConfiguredServerUrl` (SharedPreferences `opencal_prefs`), and
  `getStartURL`/`getRawStartURL` now check the stored URL before falling back to
  the build-time `.env` `NATIVEPHP_START_URL`.
- `MainActivity.kt` — on first launch (no configured URL, no deep link) a native
  `AlertDialog` prompts for the server URL, persists it, and loads the WebView.

**Re-apply after any `native:install --force`:** these edits live in the ephemeral
`nativephp/` dir (gitignored). After reinstall + `nativephp:patch-remote`, re-apply
the two edits above (or make them a tracked patch).

**Deferred:** a "reconfigure" affordance (re-open the dialog from Settings / a JS
bridge) — clearing app data re-triggers first-run for now.

### 4. Permissions & camera
- `CAMERA`, `RECORD_AUDIO`, `VIBRATE` in `AndroidManifest.xml` ✅ (manifest edited;
  `RECORD_AUDIO` added for voice input).
- Verify photo analysis, barcode scan, and voice input work through the WebView
  (they are already web `getUserMedia`). ⏳ needs a real device build.

### 5. Packaging & distribution
- Signed APK + AAB; app icon, splash, versioning.
- Play Store needs a privacy policy; sideload is the simplest first release.
- Keep the PWA as a zero-install fallback.

### 6. Docs
- Extend this file with build/install instructions for self-hosters.

## Environment prerequisites for building

Not installed on this machine (verified):

1. **Android Studio / Android SDK** — `NATIVEPHP_ANDROID_SDK_LOCATION` is unset;
   no `adb`, no `gradle`. Needed to compile the APK (`native:run` / `native:build`).
2. **7-Zip** — PHP binary extraction failed: `7-Zip not found at
   C:\Program Files\7-Zip\7z.exe`. Install 7-Zip or set `NATIVEPHP_7ZIP_LOCATION`,
   then re-run `native:install android` (or `--force`) to extract the binaries.

> Note: the project's `composer.json` requires PHP `^8.5`; the `opencal` Herd site
> was isolated to PHP 8.5 (`herd isolate 8.5`) to match.

## Server-side requirements for self-hosters

- `APP_URL` set to the public HTTPS URL (drives email links + Google Health OAuth).
- `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN=null`, `same_site=lax`.
- `URL::forceScheme('https')` is already set in `AppServiceProvider`.

## Known deferred item

- **Google Health OAuth** returns to the app via a deep link (`calapp://`
  scheme). Needs to be wired up or accepted as "opens in system browser".
