import SwiftUI

struct ListingDetailView: View {
    @EnvironmentObject var store: MarketplaceStore
    let listingID: UUID

    var listing: Listing? { store.listings.first(where: {$0.id == listingID}) }

    var body: some View {
        if let listing {
            ScrollView {
                VStack(alignment: .leading, spacing: 18) {
                    ZStack {
                        RoundedRectangle(cornerRadius: 18).fill(.secondary.opacity(0.1)).frame(height: 230)
                        Image(systemName: listing.symbol).font(.system(size: 80)).foregroundStyle(.secondary)
                    }
                    Text(listing.title).font(.largeTitle.bold())
                    Text(listing.price, format: .currency(code: "GBP").precision(.fractionLength(0))).font(.title2.bold())
                    Label(listing.location, systemImage: "mappin.and.ellipse")
                    Text(listing.details)
                    Divider()
                    Text("Seller").font(.headline)
                    Text(listing.seller)
                    Button { store.toggleFavourite(listing) } label: {
                        Label(listing.isFavourite ? "Remove from favourites" : "Save to favourites",
                              systemImage: listing.isFavourite ? "heart.fill" : "heart")
                    }.buttonStyle(.borderedProminent)
                    Button("Contact seller") { }.buttonStyle(.bordered).disabled(true)
                    Text("Contact messaging will connect to the Phase 3 backend.").font(.caption).foregroundStyle(.secondary)
                }.padding()
            }.navigationTitle("Advert").navigationBarTitleDisplayMode(.inline)
        } else {
            ContentUnavailableView("Advert unavailable", systemImage: "exclamationmark.triangle")
        }
    }
}
