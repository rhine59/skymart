# SkyMart iPhone prototype

Native SwiftUI iPhone app with sample adverts and an API client for the SkyMart server.

## Build and run on a Mac

Requirements: Xcode 26, XcodeGen, iOS 17+ simulator runtime.

```sh
cd ios/SkyMartPrototype
xcodegen generate
open SkyMartPrototype.xcodeproj
```

Select the **SkyMartPrototype** scheme and an iPhone simulator, then press Run.

Command-line build:

```sh
xcodebuild -project SkyMartPrototype.xcodeproj -scheme SkyMartPrototype \
  -sdk iphonesimulator -destination 'generic/platform=iOS Simulator' \
  CODE_SIGNING_ALLOWED=NO build
```

The Xcode project is generated from the tracked `project.yml`; do not hand-maintain a divergent project. The app uses the bundle ID `uk.co.lollipopdesign.SkyMartPrototype` for simulator development. Device builds require an Apple development team and signing configuration.

## Prototype scope

- Browse/search adverts and filter by category.
- View advert details, photo gallery, seller and location.
- Favourite adverts.
- Account/login UI with Keychain token storage.
- Sell form with up to ten photos and API upload path.
- Mock/sample listings for UI development.

The API client is implemented, but end-to-end public server login/listing/photo tests and TestFlight distribution are **not yet validated**. Do not use real payment or sensitive production accounts for prototype testing. The contact-seller control is intentionally disabled.

## Current limitations

The app currently mixes sample listing data with optional live API flows. Favourites are prototype-local, and a listing can become active before all images finish uploading. A later milestone should introduce draft → photo upload → publish and reliable upload retry.

## Project status

An Xcode Simulator Debug build succeeded on Xcode 26.6 on 9 October 2026. Simulator unit-test and runtime results should be recorded separately; a successful compile alone is not proof of API integration.
