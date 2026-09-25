import SwiftUI

@main
struct SkyMartPrototypeApp: App {
    @StateObject private var store = MarketplaceStore()
    var body: some Scene {
        WindowGroup { RootView().environmentObject(store) }
    }
}
