import SwiftUI

struct AccountView: View {
    @EnvironmentObject var store: MarketplaceStore
    var mine: [Listing] { store.listings.filter {$0.seller == "You"} }

    var body: some View {
        List {
            Section("Prototype account") {
                Label("Signed in as prototype user", systemImage: "person.crop.circle.badge.checkmark")
                Text("The production app will use the SkyMart server session/API rather than storing credentials on the phone.")
                    .font(.caption).foregroundStyle(.secondary)
            }
            Section("My adverts") {
                if mine.isEmpty { Text("No adverts published yet.").foregroundStyle(.secondary) }
                ForEach(mine) { ListingRow(listing: $0) }
            }
            Section { Button("Reset prototype data", role: .destructive) { store.reset() } }
        }.navigationTitle("Account")
    }
}
