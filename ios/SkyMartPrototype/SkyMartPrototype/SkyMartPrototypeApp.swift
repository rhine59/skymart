import SwiftUI

@main
struct SkyMartPrototypeApp: App {
    @StateObject private var store = MarketplaceStore()
    @StateObject private var account: AccountSession

    init() {
        // Replace with the production HTTPS SkyMart origin at deployment time.
        let api = SkyMartAPI(baseURL: URL(string: "https://example.invalid")!)
        _account = StateObject(wrappedValue: AccountSession(api: api))
    }

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(store)
                .environmentObject(account)
                .task { await account.restore() }
        }
    }
}
