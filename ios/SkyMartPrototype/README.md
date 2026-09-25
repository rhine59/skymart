# SkyMart iPhone Prototype

A native SwiftUI prototype of the SkyMart aviation marketplace. It deliberately uses in-memory mock data so the iPhone UI can be developed before the Phase 3 PHP API exists.

## Xcode
Create a new iOS App named **SkyMartPrototype** (SwiftUI, Swift), then replace the generated Swift files with the files in `SkyMartPrototype/`.

Target iOS 17 or later. No third-party packages are required.

## Prototype flows
- Browse/search aviation adverts
- Browse by category
- View advert details and seller/location information
- Favourite adverts
- Create and preview an advert
- Account screen with user's adverts
- Reset mock data

The service boundary is isolated in `MarketplaceStore`; Phase 3 can replace the mock implementation with the SkyMart HTTP API without redesigning the screens.
